// ===== DRAG & DROP =====

// ---- Função auxiliar para bloquear seleção durante arraste (manual e nativo) ----
function enableDragSelectionBlock(card) {
  card.addEventListener("dragstart", function (e) {
    e.preventDefault();
    document.body.style.userSelect = "none";
    document.body.style.webkitUserSelect = "none";
    document.body.style.MozUserSelect = "none";
    document.body.style.msUserSelect = "none";
    this.classList.add("dragging");
    document.body.style.cursor = "grabbing";
  });

  card.addEventListener("dragend", function (e) {
    document.body.style.userSelect = "";
    document.body.style.webkitUserSelect = "";
    document.body.style.MozUserSelect = "";
    document.body.style.msUserSelect = "";
    this.classList.remove("dragging");
    document.body.style.cursor = "";
  });
}

// ---- Listeners para reordenação (APENAS UMA VEZ) ----
container.addEventListener("dragover", function (e) {
  e.preventDefault();
  e.dataTransfer.dropEffect = "move";
  const targetItem = e.target.closest(".editable-item");
  if (!targetItem) return;
  document
    .querySelectorAll(".drag-over")
    .forEach((el) => el.classList.remove("drag-over"));
  targetItem.classList.add("drag-over");
});

container.addEventListener("drop", function (e) {
  e.preventDefault();
  const targetItem = e.target.closest(".editable-item");
  if (!targetItem || !dragData) return;
  document
    .querySelectorAll(".drag-over")
    .forEach((el) => el.classList.remove("drag-over"));
  document
    .querySelectorAll(".dragging")
    .forEach((el) => el.classList.remove("dragging"));

  const draggedItem = dragData.element;
  if (draggedItem === targetItem) {
    dragData = null;
    return;
  }
  const parent = container;
  const children = Array.from(parent.children);
  const draggedIndex = children.indexOf(draggedItem);
  const targetIndex = children.indexOf(targetItem);
  if (draggedIndex < targetIndex) {
    parent.insertBefore(draggedItem, targetItem.nextSibling);
  } else {
    parent.insertBefore(draggedItem, targetItem);
  }
  reindexItems();
  dragData = null;

  document.body.style.userSelect = "";
  document.body.style.webkitUserSelect = "";
  document.body.style.MozUserSelect = "";
  document.body.style.msUserSelect = "";
});

container.addEventListener("dragleave", function (e) {
  document
    .querySelectorAll(".drag-over")
    .forEach((el) => el.classList.remove("drag-over"));
});

document.addEventListener("selectstart", function (e) {
  if (document.body.style.userSelect === "none") {
    e.preventDefault();
  }
});

// ============================================================
//  FUNÇÕES DE UI (BOTÕES DE DELETAR, DRAG, DUPLICAR, ETC.)
// ============================================================

function addDeleteButton(container) {}

function addDuplicateButton(container) {}

function deleteItem(item) {
  // ✅ Salva o estado atual (com o card) ANTES de remover
  if (typeof pushState === "function") pushState();

  const toRemove = connections.filter(
    (c) => c.fromCard === item || c.toCard === item,
  );
  toRemove.forEach((c) => {
    c.lineElement.remove();
    if (c.arrowElement) c.arrowElement.remove();
    if (c.deleteBtn) c.deleteBtn.remove();
    const idx = connections.indexOf(c);
    if (idx !== -1) connections.splice(idx, 1);
  });

  if (
    activeToolbar &&
    window.__toolbarElement &&
    item.contains(window.__toolbarElement)
  ) {
    if (closeHandler) {
      document.removeEventListener("mousedown", closeHandler);
      closeHandler = null;
    }
    activeToolbar.remove();
    activeToolbar = null;
    currentToolbar = null;
    targetElements.forEach((el) => el.classList.remove("target-selected"));
    targetElements = [];
    filterWord = "";
  }
  item.remove();
  updateContainerHeight();
  updateAllConnections();
  // ⚠️ NÃO chama pushState() aqui para não empilhar estado vazio
}

function addDragHandle(cardElement) {
  const grip = document.createElement("div");
  grip.className = "drag-handle";
  grip.draggable = false;
  grip.title = "Arrastar livremente (Shift para desativar snap)";
  grip.style.cursor = "grab";
  cardElement.appendChild(grip);

  cardElement.addEventListener("mousedown", function (e) {
    if (
      e.target.closest(
        ".delete-btn, .duplicate-btn, .flow-port, .resize-handle, .edit-toolbar, .table-toolbar",
      ) ||
      e.target.closest("input") ||
      e.target.closest("textarea") ||
      e.target.closest("select") ||
      e.target.closest("button")
    )
      return;
    if (e.target.closest('[contenteditable="true"]')) return;
    if (e.target.closest(".vscode-card")) return;
    startDrag(e, cardElement);
  });

  function startDrag(e, cardElement) {
    e.preventDefault();
    e.stopPropagation();

    // 🔥 FORÇA O FIM DA EDIÇÃO ANTES DE COMEÇAR O ARRASTE
    if (document.activeElement && document.activeElement.isContentEditable) {
      document.activeElement.blur();
    }

    const isMulti =
      selectedItems.length > 0 && selectedItems.includes(cardElement);
    const itemsToMove = isMulti ? selectedItems : [cardElement];

    document.body.style.userSelect = "none";
    document.body.style.webkitUserSelect = "none";
    document.body.style.MozUserSelect = "none";
    document.body.style.msUserSelect = "none";

    itemsToMove.forEach((card) => {
      card.classList.add("dragging");
      card.style.userSelect = "none";
      card.style.webkitUserSelect = "none";
      card.style.MozUserSelect = "none";
      card.style.msUserSelect = "none";
      card.querySelectorAll("*").forEach((el) => {
        el.style.userSelect = "none";
        el.style.webkitUserSelect = "none";
        el.style.MozUserSelect = "none";
        el.style.msUserSelect = "none";
      });
    });

    const initialPositions = itemsToMove.map((card) => ({
      card: card,
      left: parseFloat(card.style.left) || 0,
      top: parseFloat(card.style.top) || 0,
    }));

    const startX = e.clientX;
    const startY = e.clientY;

    cardElement.style.cursor = "grabbing";
    if (grip) grip.style.cursor = "grabbing";

    let moving = true;
    let connectionRafId = null;

    function onMouseMove(ev) {
      ev.preventDefault();
      const deltaX = ev.clientX - startX;
      const deltaY = ev.clientY - startY;
      const container = document.getElementById("cards-container");
      const padding = 20;

      initialPositions.forEach(({ card, left, top }) => {
        let newLeft = left + deltaX;
        let newTop = top + deltaY;
        const maxLeft = container.offsetWidth - card.offsetWidth - padding;
        const maxTop = container.offsetHeight - card.offsetHeight - padding;
        newLeft = Math.max(padding, Math.min(newLeft, maxLeft));
        newTop = Math.max(padding, Math.min(newTop, maxTop));
        if (!ev.shiftKey) {
          newLeft = Math.round(newLeft / 20) * 20;
          newTop = Math.round(newTop / 20) * 20;
        }
        card.style.left = newLeft + "px";
        card.style.top = newTop + "px";
      });

      if (!connectionRafId && moving) {
        connectionRafId = requestAnimationFrame(() => {
          if (moving) {
            updateAllConnections();
          }
          connectionRafId = null;
        });
      }
    }

    function onMouseUp() {
      moving = false;
      if (connectionRafId) {
        cancelAnimationFrame(connectionRafId);
        connectionRafId = null;
      }

      document.body.style.userSelect = "";
      document.body.style.webkitUserSelect = "";
      document.body.style.MozUserSelect = "";
      document.body.style.msUserSelect = "";

      itemsToMove.forEach((card) => {
        card.classList.remove("dragging");
        card.style.userSelect = "";
        card.style.webkitUserSelect = "";
        card.style.MozUserSelect = "";
        card.style.msUserSelect = "";
        card.querySelectorAll("*").forEach((el) => {
          el.style.userSelect = "";
          el.style.webkitUserSelect = "";
          el.style.MozUserSelect = "";
          el.style.msUserSelect = "";
        });
      });

      updateAllConnections();
      updateContainerHeight();

      cardElement.style.cursor = "";
      if (grip) grip.style.cursor = "grab";

      document.removeEventListener("mousemove", onMouseMove);
      document.removeEventListener("mouseup", onMouseUp);

      // 🔥 SALVA O ESTADO APÓS O ARRASTE, COM setTimeout
      // PARA GARANTIR QUE O BLUR (E SEU pushState) JÁ FOI PROCESSADO
      setTimeout(() => {
        if (typeof pushState === "function") pushState();
      }, 0);
    }

    document.addEventListener("mousemove", onMouseMove);
    document.addEventListener("mouseup", onMouseUp);
  }
}

function duplicateItem(item) {
  const container = document.getElementById("cards-container");
  const clone = item.cloneNode(true);
  clone.style.outline = "";
  clone.querySelectorAll('[style*="outline"]').forEach((el) => {
    el.style.outline = "";
  });
  if (clone.id) clone.id = "";

  const editableSelectors = [
    "p",
    "h1",
    "h2",
    "h3",
    "h4",
    "h5",
    "h6",
    "li",
    "th",
    "td",
    ".file-name",
    ".code-block",
    ".terminal-content",
  ].join(",");

  clone.querySelectorAll(editableSelectors).forEach((el) => {
    el.removeAttribute("contenteditable");
    enableEditOnDoubleClick(el, plainTextOnBlur);
  });

  const codeCard = clone.querySelector(".vscode-card");
  if (codeCard) {
    const fileName =
      clone.querySelector(".file-name")?.textContent || "main.py";
    const codeContent = clone.querySelector(".code-block")?.textContent || "";
    const terminalContent =
      clone.querySelector(".terminal-content")?.textContent || "";
    clone.remove();
    createCodeCard(fileName, codeContent, terminalContent);
    if (typeof pushState === "function") pushState();
    return;
  }

  clone
    .querySelectorAll(".delete-btn, .duplicate-btn, .drag-handle")
    .forEach((b) => b.remove());

  addDeleteButton(clone);
  addDuplicateButton(clone);
  addDragHandle(clone);

  const tableToolbar = clone.querySelector(".table-toolbar");
  if (tableToolbar) {
    tableToolbar.classList.add("hidden");
    tableToolbar.classList.remove("visible");
  }

  const fileInput = clone.querySelector('input[type="file"]');
  const urlInputClone = clone.querySelector('input[type="text"]');
  if (fileInput && urlInputClone) {
    const newFileInput = fileInput.cloneNode(true);
    fileInput.parentNode.replaceChild(newFileInput, fileInput);
    newFileInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        const img = clone.querySelector("img");
        const placeholder = clone.querySelector("div:first-child");
        reader.onload = function (ev) {
          img.src = ev.target.result;
          img.style.display = "block";
          placeholder.style.display = "none";
          img.onload = function () {
            img.dataset.originalWidth = img.naturalWidth;
            img.dataset.originalHeight = img.naturalHeight;
            const widthSlider = clone.querySelector(".img-width-slider");
            const heightSlider = clone.querySelector(".img-height-slider");
            if (widthSlider) {
              widthSlider.value = img.naturalWidth;
              widthSlider.max = img.naturalWidth * 2;
            }
            if (heightSlider) {
              heightSlider.value = img.naturalHeight;
              heightSlider.max = img.naturalHeight * 2;
            }
          };
        };
        reader.readAsDataURL(file);
      }
    });

    const newUrlInput = urlInputClone.cloneNode(true);
    urlInputClone.parentNode.replaceChild(newUrlInput, urlInputClone);
    newUrlInput.addEventListener("change", function () {
      const url = this.value.trim();
      if (url) {
        const img = clone.querySelector("img");
        const placeholder = clone.querySelector("div:first-child");
        img.src = url;
        img.style.display = "block";
        placeholder.style.display = "none";
        img.onload = function () {
          img.dataset.originalWidth = img.naturalWidth;
          img.dataset.originalHeight = img.naturalHeight;
          const widthSlider = clone.querySelector(".img-width-slider");
          const heightSlider = clone.querySelector(".img-height-slider");
          if (widthSlider) {
            widthSlider.value = img.naturalWidth;
            widthSlider.max = img.naturalWidth * 2;
          }
          if (heightSlider) {
            heightSlider.value = img.naturalHeight;
            heightSlider.max = img.naturalHeight * 2;
          }
        };
      }
    });

    const widthSlider = clone.querySelector(".img-width-slider");
    const heightSlider = clone.querySelector(".img-height-slider");
    const widthDisplay = clone.querySelector(".img-width-slider + span");
    const heightDisplay = clone.querySelector(".img-height-slider + span");
    if (widthSlider && heightSlider) {
      function updateCloneSize() {
        const w = parseInt(widthSlider.value);
        const h = parseInt(heightSlider.value);
        const img = clone.querySelector("img");
        img.style.width = w + "px";
        img.style.height = h + "px";
        if (widthDisplay) widthDisplay.textContent = w + "px";
        if (heightDisplay) heightDisplay.textContent = h + "px";
      }
      widthSlider.addEventListener("input", updateCloneSize);
      heightSlider.addEventListener("input", updateCloneSize);
    }
  }

  container.appendChild(clone);

  clone.classList.add("float-in");
  setTimeout(() => clone.classList.remove("float-in"), 700);

  reindexItems();

  // ✅ SALVA O ESTADO APÓS A DUPLICAÇÃO
  if (typeof pushState === "function") pushState();
}

function reindexItems() {
  const items = document.querySelectorAll(".editable-item");
  items.forEach((item, index) => {
    item.dataset.index = index;
  });
}
function safeStringify(obj, indent = 0) {
  const seen = new WeakSet();
  return JSON.stringify(
    obj,
    function (key, value) {
      if (typeof value === "object" && value !== null) {
        if (seen.has(value)) {
          return "[Circular]";
        }
        seen.add(value);
      }
      // Ignora funções e elementos DOM
      if (typeof value === "function") return undefined;
      if (value instanceof Node) return undefined; // evita DOM
      return value;
    },
    indent,
  );
}

// ============================================================
// EXPORTAR PÁGINA ATUAL (com compressão e criptografia com senha)
// ============================================================
function exportarPagina() {
  const materia = window.currentMateria || "";
  const topico = window.currentTopico || "";
  const pagina = window.currentPagina || 0;

  if (!materia || !topico) {
    showSaveNotification("error");
    console.warn("Selecione uma matéria e tópico primeiro.");
    return;
  }

  // Pede uma senha (opcional) – se o usuário cancelar, interrompe
  const userPassword = prompt(
    "Digite uma senha para proteger o arquivo (opcional):",
    "",
  );
  if (userPassword === null) return; // cancelou

  const url = `api.php?action=export&materia=${encodeURIComponent(materia)}&topico=${encodeURIComponent(topico)}&pagina=${pagina}`;

  fetch(url, {
    method: "GET",
    credentials: "same-origin",
  })
    .then((res) => {
      if (!res.ok) throw new Error(`Erro ${res.status}`);
      return res.json();
    })
    .then((data) => {
      if (!data.success) throw new Error(data.error || "Erro ao exportar");

      // 🔥 Extrai apenas cards e connections (e outros campos simples)
      const paginaParaExportar = {
        id: data.pagina.id || 0,
        public_id: data.pagina.public_id || "",
        is_public: data.pagina.is_public || 0,
        titulo: data.pagina.titulo || "Página",
        cards: data.pagina.cards || [],
        connections: data.pagina.connections || [],
      };

      // 🔥 Usa a função corrigida
      const encrypted = compressAndEncrypt(
        paginaParaExportar,
        userPassword || undefined,
      );

      const blob = new Blob([encrypted], { type: "application/octet-stream" });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download = `${materia}_${topico}_pagina${pagina + 1}.constell`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(link.href);
      showSaveNotification("success");
    })
    .catch((err) => {
      console.error(err);
      showSaveNotification("error");
    });
}

// ============================================================
// IMPORTAR PÁGINA (seleciona arquivo .constell com senha)
// ============================================================
function importarPagina() {
  const materia = window.currentMateria || "";
  const topico = window.currentTopico || "";

  if (!materia || !topico) {
    showSaveNotification("error");
    console.warn("Selecione uma matéria e tópico para importar.");
    return;
  }

  const input = document.createElement("input");
  input.type = "file";
  input.accept = ".constell";
  input.onchange = function (e) {
    const file = e.target.files[0];
    if (!file) return;

    // Pede a senha usada na exportação
    const userPassword = prompt("Digite a senha do arquivo (se houver):", "");
    if (userPassword === null) return; // cancelou

    const reader = new FileReader();
    reader.onload = function (ev) {
      try {
        const encrypted = ev.target.result;
        // Descriptografa e descomprime usando a senha fornecida
        const paginaData = decryptAndDecompress(
          encrypted,
          userPassword || undefined,
        );
        if (!paginaData.cards || !Array.isArray(paginaData.cards)) {
          throw new Error("Arquivo inválido: estrutura corrompida");
        }
        enviarImportacao(materia, topico, paginaData);
      } catch (err) {
        console.error(err);
        showSaveNotification("error");
        alert("Erro ao ler arquivo: " + err.message);
      }
    };
    reader.readAsText(file);
    input.value = "";
  };
  input.click();
}

// ============================================================
// FUNÇÕES DE COMPRESSÃO E CRIPTOGRAFIA (com senha)
// ============================================================

/**
 * Converte Uint8Array para string Base64 de forma segura (sem apply)
 */
function uint8ArrayToBase64(uint8Array) {
  let binary = "";
  for (let i = 0; i < uint8Array.length; i++) {
    binary += String.fromCharCode(uint8Array[i]);
  }
  return btoa(binary);
}

/**
 * Converte string Base64 para Uint8Array
 */
function base64ToUint8Array(base64) {
  const binary = atob(base64);
  const len = binary.length;
  const bytes = new Uint8Array(len);
  for (let i = 0; i < len; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return bytes;
}

/**
 * Comprime e criptografa um objeto usando uma senha (AES)
 * @param {Object} data - objeto a ser serializado
 * @param {string} password - senha para criptografia (opcional)
 * @returns {string} - string base64 do JSON comprimido e criptografado
 */
function compressAndEncrypt(data, password) {
  const key = password || "ConstellDefaultKey2024!";

  // 🔥 Usa Flatted se disponível, senão JSON.stringify com replacer seguro
  let jsonStr;
  if (typeof Flatted !== "undefined") {
    jsonStr = Flatted.stringify(data);
  } else {
    // Fallback: remove funções e marca circulares como "[Circular]"
    const seen = new WeakSet();
    jsonStr = JSON.stringify(data, function (key, value) {
      if (typeof value === "object" && value !== null) {
        if (seen.has(value)) return "[Circular]";
        seen.add(value);
      }
      if (typeof value === "function") return undefined;
      if (value instanceof Node) return undefined;
      return value;
    });
  }

  // Comprime com pako
  const compressed = pako.deflate(jsonStr);

  // Converte para Base64 com loop (seguro)
  const compressedBase64 = uint8ArrayToBase64(compressed);

  // Criptografa com AES
  const encrypted = CryptoJS.AES.encrypt(compressedBase64, key).toString();
  return encrypted;
}

/**
 * Descriptografa e descomprime uma string usando uma senha
 * @param {string} encryptedBase64 - string criptografada em base64
 * @param {string} password - senha usada na criptografia
 * @returns {Object} - objeto original
 * @throws {Error} se falhar
 */
function decryptAndDecompress(encryptedBase64, password) {
  const key = password || "ConstellDefaultKey2024!";
  const decrypted = CryptoJS.AES.decrypt(encryptedBase64, key);
  const compressedBase64 = decrypted.toString(CryptoJS.enc.Utf8);
  if (!compressedBase64) {
    throw new Error("Senha incorreta ou arquivo inválido");
  }
  // Converte Base64 para Uint8Array
  const compressed = base64ToUint8Array(compressedBase64);
  // Descomprime
  const jsonStr = pako.inflate(compressed, { to: "string" });
  // Parse JSON (suporta Flatted se foi usado na compressão)
  if (typeof Flatted !== "undefined") {
    return Flatted.parse(jsonStr);
  } else {
    return JSON.parse(jsonStr);
  }
}

// ============================================================
// EXPORTAR PÁGINA ATUAL (com compressão e criptografia com senha)
// ============================================================
function exportarPagina() {
  const materia = window.currentMateria || "";
  const topico = window.currentTopico || "";
  const pagina = window.currentPagina || 0;

  if (!materia || !topico) {
    showSaveNotification("error");
    console.warn("Selecione uma matéria e tópico primeiro.");
    return;
  }

  // Pede uma senha (opcional)
  const userPassword = prompt(
    "Digite uma senha para proteger o arquivo (opcional):",
    "",
  );
  if (userPassword === null) return;

  const url = `api.php?action=export&materia=${encodeURIComponent(materia)}&topico=${encodeURIComponent(topico)}&pagina=${pagina}`;

  fetch(url, {
    method: "GET",
    credentials: "same-origin",
  })
    .then((res) => {
      if (!res.ok) throw new Error(`Erro ${res.status}`);
      return res.json();
    })
    .then((data) => {
      if (!data.success) throw new Error(data.error || "Erro ao exportar");

      // 🔥 Extrai apenas os dados necessários (evita referências DOM)
      const paginaParaExportar = {
        id: data.pagina.id || 0,
        public_id: data.pagina.public_id || "",
        is_public: data.pagina.is_public || 0,
        titulo: data.pagina.titulo || "Página",
        cards: data.pagina.cards || [],
        connections: data.pagina.connections || [],
      };

      // 🔥 Comprime e criptografa
      const encrypted = compressAndEncrypt(
        paginaParaExportar,
        userPassword || undefined,
      );

      const blob = new Blob([encrypted], { type: "application/octet-stream" });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download = `${materia}_${topico}_pagina${pagina + 1}.constell`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(link.href);
      showSaveNotification("success");
    })
    .catch((err) => {
      console.error(err);
      showSaveNotification("error");
    });
}

// ============================================================
// IMPORTAR PÁGINA (seleciona arquivo .constell com senha)
// ============================================================
function importarPagina() {
  const materia = window.currentMateria || "";
  const topico = window.currentTopico || "";

  if (!materia || !topico) {
    showSaveNotification("error");
    console.warn("Selecione uma matéria e tópico para importar.");
    return;
  }

  const input = document.createElement("input");
  input.type = "file";
  input.accept = ".constell";
  input.onchange = function (e) {
    const file = e.target.files[0];
    if (!file) return;

    const userPassword = prompt("Digite a senha do arquivo (se houver):", "");
    if (userPassword === null) return;

    const reader = new FileReader();
    reader.onload = function (ev) {
      try {
        const encrypted = ev.target.result;
        const paginaData = decryptAndDecompress(
          encrypted,
          userPassword || undefined,
        );
        if (!paginaData.cards || !Array.isArray(paginaData.cards)) {
          throw new Error("Arquivo inválido: estrutura corrompida");
        }
        enviarImportacao(materia, topico, paginaData);
      } catch (err) {
        console.error(err);
        showSaveNotification("error");
        alert("Erro ao ler arquivo: " + err.message);
      }
    };
    reader.readAsText(file);
    input.value = "";
  };
  input.click();
}

// ============================================================
// ENVIAR IMPORTAÇÃO PARA O BACKEND
// ============================================================
function enviarImportacao(materia, topico, paginaData) {
  if (!paginaData.titulo) {
    paginaData.titulo = "Página importada";
  }

  showSaveNotification("saving");

  fetch("api.php?action=import", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": window.CSRF_TOKEN || "",
    },
    credentials: "same-origin",
    body: JSON.stringify({
      materia: materia,
      topico: topico,
      pagina: paginaData,
    }),
  })
    .then((res) => {
      if (!res.ok) throw new Error(`Erro ${res.status}`);
      return res.json();
    })
    .then((data) => {
      if (!data.success) throw new Error(data.error || "Erro ao importar");
      showSaveNotification("success");
      recarregarAposImportacao();
    })
    .catch((err) => {
      console.error(err);
      showSaveNotification("error");
    });
}

// ============================================================
// RECARREGAR DADOS APÓS IMPORTAÇÃO
// ============================================================
async function recarregarAposImportacao() {
  try {
    await loadFullState();
    renderNavTree();
    restorePage();
    updateMenuUI();
    showSaveNotification("success");
  } catch (e) {
    console.warn(
      "Falha ao recarregar após importação, recarregando a página...",
      e,
    );
    location.reload();
  }
}

function togglePublicPage() {
  console.log("🔵 togglePublicPage chamada!");
  const publicId = window.currentPublicId;
  console.log("publicId:", publicId);
  console.log("currentIsPublic ANTES:", window.currentIsPublic);

  if (!publicId) {
    alert(
      "Identificador público não encontrado. Recarregue a página e tente novamente.",
    );
    return;
  }

  const isCurrentlyPublic = window.currentIsPublic || 0;
  const newState = isCurrentlyPublic ? 0 : 1;
  const msg = newState
    ? "Tornar esta página pública? Qualquer pessoa poderá vê-la e copiá-la."
    : "Remover o modo público? A página ficará acessível apenas para você.";

  if (!confirm(msg)) return;

  fetch("api.php?action=toggle_public", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": window.CSRF_TOKEN || "",
    },
    credentials: "same-origin",
    body: JSON.stringify({
      public_id: publicId,
      is_public: newState,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      console.log("Resposta da API:", data);
      if (data.success) {
        alert(data.message);
        // 🔥 Atualiza o estado local imediatamente
        window.currentIsPublic = newState;
        // 🔥 Atualiza o dadosCompletos para evitar que o save sobrescreva
        if (dadosCompletos && dadosCompletos.page) {
          dadosCompletos.page.is_public = newState;
        }
        // Atualiza também dentro da estrutura de matérias/tópicos
        try {
          const paginas =
            dadosCompletos.materias[currentMateria]?.topicos[currentTopico]
              ?.paginas;
          if (paginas && paginas[currentPagina]) {
            paginas[currentPagina].is_public = newState;
          }
        } catch (e) {}

        if (newState === 1) {
          const url = `${window.location.origin}/knowsnow1/view.php?public_id=${publicId}`;
          if (confirm(`Página pública! Link:\n${url}\nCopiar?`)) {
            navigator.clipboard.writeText(url);
          }
        }
      } else {
        alert(data.error || "Erro ao alterar visibilidade");
      }
    })
    .catch((err) => {
      console.error(err);
      alert("Erro de conexão");
    });
}
