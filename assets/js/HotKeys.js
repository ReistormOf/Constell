// ============================================================
//  ATALHOS DE TECLADO (com suporte a múltiplos cards selecionados)
// ============================================================

let copyTarget = null; // usado para cópia única (legado)
let clipboardCards = []; // usado para múltiplos cards selecionados

// Listener principal para Ctrl+B/I/U/S (formatação) e Ctrl+C/V (copy/paste)
document.addEventListener("keydown", function (e) {
  const active = document.activeElement;
  // USANDO isContentEditable para detectar corretamente edição de texto
  const isEditable = active && active.isContentEditable;

  // ---- SE ESTIVER EDITANDO TEXTO, DEIXA O NAVEGADOR LIDAR COM Ctrl+C/V ----
  if (isEditable) {
    // Ctrl+C e Ctrl+V não são interceptados, navegador faz copy/paste de texto
    if (e.ctrlKey && (e.key === "c" || e.key === "v")) {
      return;
    }
    // Ctrl+B/I/U/S ainda funcionam para formatação
    switch (e.key.toLowerCase()) {
      case "b":
        if (e.ctrlKey) {
          e.preventDefault();
          if (window.getSelection().toString().length > 0) {
            document.execCommand("bold");
          } else {
            toggleInlineStyle(active, "strong");
          }
        }
        break;
      case "i":
        if (e.ctrlKey) {
          e.preventDefault();
          if (window.getSelection().toString().length > 0) {
            document.execCommand("italic");
          } else {
            toggleInlineStyle(active, "em");
          }
        }
        break;
      case "u":
        if (e.ctrlKey) {
          e.preventDefault();
          if (window.getSelection().toString().length > 0) {
            document.execCommand("underline");
          } else {
            toggleInlineStyle(active, "u");
          }
        }
        break;
      case "s":
        if (e.ctrlKey && e.altKey) {
          e.preventDefault();
          if (window.getSelection().toString().length > 0) {
            document.execCommand("strikeThrough");
          } else {
            toggleInlineStyle(active, "s");
          }
        }
        break;
    }
    return; // sai do listener após tratar os atalhos de formatação
  }

  // ---- Ctrl+C (copiar cards selecionados) ----
  if (e.ctrlKey && e.key === "c") {
    if (selectedItems.length > 0) {
      e.preventDefault();

      // 1. Copiar o texto dos cards para o clipboard do sistema
      const textContent = selectedItems
        .map((card) => card.innerText)
        .join("\n");
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
          .writeText(textContent)
          .catch((err) => console.warn("Erro ao copiar texto:", err));
      } else {
        // Fallback para navegadores antigos
        const textarea = document.createElement("textarea");
        textarea.value = textContent;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
      }

      // 2. Salva os dados completos dos cards para o clipboard interno (Ctrl+V cola os cards)
      clipboardCards = selectedItems.map((card) => ({
        html: card.innerHTML,
        left: card.style.left,
        top: card.style.top,
        width: card.style.width,
        height: card.style.height,
        className: card.className,
        textAlign: card.style.textAlign || "",
      }));

      // Feedback visual (borda amarela)
      selectedItems.forEach((c) => (c.style.outline = "2px solid #ffaa00"));
      setTimeout(() => {
        selectedItems.forEach((c) => (c.style.outline = ""));
      }, 300);

      console.log(
        `📋 Texto de ${selectedItems.length} card(s) copiado para a área de transferência.`,
      );
      return;
    }

    // Fallback: copiar o card que contém o elemento ativo (comportamento antigo)
    if (active) {
      const item = active.closest?.(".editable-item");
      if (item) {
        copyTarget = item;
        item.style.outline = "2px solid #ffaa00";
        setTimeout(() => (item.style.outline = ""), 300);
        e.preventDefault();
      }
    }
  }

  // ---- Ctrl+V (colar cards) ----
  if (e.ctrlKey && e.key === "v") {
    // Se houver dados de múltiplos cards no clipboard
    if (clipboardCards.length > 0) {
      e.preventDefault();
      const container = document.getElementById("cards-container");
      if (!container) return;

      const offset = 30;
      clipboardCards.forEach((cardData, index) => {
        const newCard = document.createElement("div");
        newCard.className = cardData.className || "editable-item";
        newCard.style.position = "absolute";
        const left = parseFloat(cardData.left) || 20;
        const top = parseFloat(cardData.top) || 20;
        newCard.style.left = left + (index + 1) * offset + "px";
        newCard.style.top = top + (index + 1) * offset + "px";
        newCard.style.width = cardData.width || "auto";
        newCard.style.height = cardData.height || "auto";
        if (cardData.textAlign) {
          newCard.style.textAlign = cardData.textAlign;
        }
        newCard.innerHTML = cardData.html;

        // Reaplica funcionalidades
        if (typeof addDeleteButton === "function") addDeleteButton(newCard);
        if (typeof addDragHandle === "function") addDragHandle(newCard);
        if (typeof addPorts === "function") addPorts(newCard);
        if (typeof addResizeHandle === "function") addResizeHandle(newCard);

        // Reaplica edição
        const editableElements = newCard.querySelectorAll(
          "p, h1, h2, h3, h4, h5, h6, li, th, td, .file-name, .code-block, .terminal-content",
        );
        editableElements.forEach((el) => {
          if (typeof enableEditOnDoubleClick === "function") {
            enableEditOnDoubleClick(el, plainTextOnBlur);
          }
        });

        container.appendChild(newCard);
      });

      // Limpa o clipboard após colar
      clipboardCards = [];
      // Atualiza layout
      if (typeof updateContainerHeight === "function") updateContainerHeight();
      if (typeof updateSvgSize === "function") updateSvgSize();
      if (typeof updateAllConnections === "function") updateAllConnections();
      // Salva estado (undo)
      if (typeof pushState === "function") pushState();
      return;
    }

    // Fallback: duplicar o card copiado anteriormente (comportamento antigo)
    if (copyTarget && copyTarget.parentNode) {
      e.preventDefault();
      if (typeof duplicateItem === "function") {
        duplicateItem(copyTarget);
        copyTarget = null;
      } else {
        // Se duplicateItem não existir, tenta clonar manualmente
        const parent = copyTarget.parentNode;
        const clone = copyTarget.cloneNode(true);
        const left = parseFloat(copyTarget.style.left) || 20;
        const top = parseFloat(copyTarget.style.top) || 20;
        clone.style.left = left + 30 + "px";
        clone.style.top = top + 30 + "px";
        // Reaplica funcionalidades no clone
        if (typeof addDeleteButton === "function") addDeleteButton(clone);
        if (typeof addDragHandle === "function") addDragHandle(clone);
        if (typeof addPorts === "function") addPorts(clone);
        if (typeof addResizeHandle === "function") addResizeHandle(clone);
        parent.appendChild(clone);
        copyTarget = null;
        if (typeof updateContainerHeight === "function")
          updateContainerHeight();
        if (typeof updateSvgSize === "function") updateSvgSize();
        if (typeof pushState === "function") pushState();
      }
    }
  }

  // ---- Delete (remover conexão selecionada) ----
  if ((e.key === "Delete" || e.key === "Del") && window._selectedLine) {
    const idx = connections.findIndex(
      (c) => c.lineElement === window._selectedLine,
    );
    if (idx !== -1) {
      window._selectedLine.remove();
      if (connections[idx].deleteBtn) connections[idx].deleteBtn.remove();
      connections.splice(idx, 1);
      window._selectedLine = null;
      e.preventDefault();
    }
  }

  // ---- Delete (remover cards selecionados) ----
  if ((e.key === "Delete" || e.key === "Del") && selectedItems.length > 0) {
    e.preventDefault();

    console.log(`🗑️ Deletando ${selectedItems.length} card(s)`);

    // ✅ SALVA O ESTADO ANTES DE REMOVER (para que o undo tenha os cards)
    if (typeof pushState === "function") {
      pushState();
    }

    const cardsToRemove = [...selectedItems];
    cardsToRemove.forEach((card) => {
      // Remove todas as conexões associadas a este card
      const toRemove = connections.filter(
        (conn) => conn.fromCard === card || conn.toCard === card,
      );
      toRemove.forEach((conn) => {
        if (conn.lineElement) conn.lineElement.remove();
        if (conn.arrowElement) conn.arrowElement.remove();
        if (conn.deleteBtn) conn.deleteBtn.remove();
        const idx = connections.indexOf(conn);
        if (idx !== -1) connections.splice(idx, 1);
      });
      // Remove o card do DOM
      card.remove();
    });

    // Limpa a seleção
    selectedItems = [];

    // Atualiza layout (NÃO chama pushState novamente)
    if (typeof updateContainerHeight === "function") updateContainerHeight();
    if (typeof updateSvgSize === "function") updateSvgSize();
    if (typeof updateAllConnections === "function") updateAllConnections();

    console.log("✅ Cards removidos, estado salvo antes da deleção.");
  }
});

// ============================================================
//  SISTEMA DE UNDO / REDO
// ============================================================

function pushState() {
  console.log(
    "📌 pushState chamado, isUndoRedo:",
    isUndoRedo,
    "isEditing:",
    isEditing,
  );

  // ⚠️ REMOVA o "|| isEditing" da condição
  if (isUndoRedo) return; // ✅ só bloqueia durante undo/redo

  cancelAnimationFrame(window._pushStateRAF);
  window._pushStateRAF = requestAnimationFrame(() => {
    // ⚠️ TAMBÉM REMOVA esta verificação extra
    // if (isEditing) return; // <-- REMOVA ESTA LINHA

    const state = collectState();
    if (!state) return;
    console.log(`📦 Estado coletado com ${state.cards.length} cards`);
    state._version = ++version;
    undoStack.push(state);
    if (undoStack.length > 50) undoStack.shift();
    redoStack = [];
    saveHistory();
    console.log(`📚 Pilha de undo agora tem ${undoStack.length} estados`);
  });
}

function undo() {
  console.log("⏪ undo() chamado, tamanho da pilha:", undoStack.length);
  if (undoStack.length < 2) {
    console.warn("⚠️ Pilha pequena demais para undo");
    return;
  }
  const current = undoStack.pop();
  redoStack.push(current);
  const previous = undoStack[undoStack.length - 1];
  if (!previous || !previous.cards || !Array.isArray(previous.cards)) {
    console.warn("undo: estado anterior inválido, recolocando");
    undoStack.push(current);
    return;
  }
  console.log(`📦 Estado anterior tem ${previous.cards.length} cards`);
  isUndoRedo = true;
  restoreState(previous);
  isUndoRedo = false;
  saveHistory();
  console.log("✅ Undo concluído");
}

function redo() {
  console.log("⏩ redo() chamado, tamanho do redoStack:", redoStack.length);
  if (redoStack.length === 0) return;
  const state = redoStack.pop();
  if (!state || !state.cards || !Array.isArray(state.cards)) {
    console.warn("redo: estado inválido, ignorando");
    return;
  }
  undoStack.push(state);
  console.log(`📦 Estado refeito tem ${state.cards.length} cards`);
  isUndoRedo = true;
  restoreState(state);
  isUndoRedo = false;
  saveHistory();
  console.log("✅ Redo concluído");
}

// ===== ATALHOS DE UNDO/REDO =====
document.addEventListener("keydown", (e) => {
  const active = document.activeElement;

  // ✅ Se estiver editando texto, DEIXA o navegador tratar Ctrl+Z / Ctrl+Y
  if (active && active.isContentEditable) {
    return;
  }

  if ((e.ctrlKey || e.metaKey) && e.key === "z") {
    e.preventDefault();
    undo();
  }
  if (
    (e.ctrlKey || e.metaKey) &&
    (e.key === "y" || (e.shiftKey && e.key === "Z"))
  ) {
    e.preventDefault();
    redo();
  }
});

// ============================================================
//  PERSISTÊNCIA DO HISTÓRICO (localStorage)
// ============================================================

function saveHistory() {
  try {
    localStorage.setItem("undoHistory", JSON.stringify(undoStack));
  } catch (e) {
    console.warn("❌ Erro ao salvar histórico:", e);
  }
}

function loadHistory() {
  // Desabilitado temporariamente para evitar conflitos
  console.log("⏸️ Histórico desabilitado");
  return;
}

// Carrega o histórico ao iniciar
document.addEventListener("DOMContentLoaded", loadHistory);
