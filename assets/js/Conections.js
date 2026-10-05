let connections = [];
let connectionStart = null;
let tempLine = null;
let isRestoring = false;

function selectCard(card) {
  if (!selectedItems.includes(card)) {
    selectedItems.push(card);
    card.classList.add("multi-selected");
  }
}

function deselectCard(card) {
  const idx = selectedItems.indexOf(card);
  if (idx !== -1) {
    selectedItems.splice(idx, 1);
    card.classList.remove("multi-selected");
  }
}

function clearSelection() {
  selectedItems.forEach((c) => c.classList.remove("multi-selected"));
  selectedItems = [];
}

// ===== FUNÇÕES AUXILIARES =====
function getRandomPosition() {
  const container = document.getElementById("cards-container");
  const padding = 20;
  const cardWidth = 300;
  const cardHeight = 200;
  const maxLeft = Math.max(0, container.clientWidth - cardWidth - padding * 2);
  const maxTop = Math.max(0, container.clientHeight - cardHeight - padding * 2);
  let left = padding + Math.random() * maxLeft;
  let top = padding + Math.random() * maxTop;
  left = Math.round(left / 20) * 20;
  top = Math.round(top / 20) * 20;
  return { left, top };
}

// ============================================================
//  FUNÇÃO PARA CRIAR O SVG E O MARCADOR (SEGURA)
// ============================================================
// Declare as variáveis de controle fora da função (escopo global ou de módulo)
let maxContainerWidth = 0;
let maxContainerHeight = 0;
let resizeTimer;

function updateContainerHeight() {
  if (isEditing) return; // <-- ADICIONE AQUI PRIMEIRO
  const container = document.getElementById("cards-container"); // <-- USE O ID
  if (!container) return;

  const extraMargin = 20;
  let maxBottom = 0;
  let maxRight = 0;

  container.querySelectorAll(".editable-item").forEach((card) => {
    const bottom = card.offsetTop + card.offsetHeight + extraMargin;
    if (bottom > maxBottom) maxBottom = bottom;

    const right = card.offsetLeft + card.offsetWidth + extraMargin;
    if (right > maxRight) maxRight = right;
  });

  const style = window.getComputedStyle(container);
  const paddingLeft = parseFloat(style.paddingLeft) || 0;
  const paddingRight = parseFloat(style.paddingRight) || 0;
  const paddingTop = parseFloat(style.paddingTop) || 0;
  const paddingBottom = parseFloat(style.paddingBottom) || 0;

  const navbar = document.querySelector(".navbar");
  const alturaNavbar = navbar ? navbar.offsetHeight : 0;
  const minHeight = Math.max(600, window.innerHeight - alturaNavbar);

  // Calcula as dimensões atuais baseadas no conteúdo
  const currentContentHeight = Math.max(
    minHeight,
    maxBottom + paddingTop + paddingBottom,
  );
  const currentTotalWidth = paddingLeft + maxRight + paddingRight;
  const currentMinWidth = Math.max(window.innerWidth, currentTotalWidth);

  // Atualiza os máximos históricos
  if (currentMinWidth > maxContainerWidth) maxContainerWidth = currentMinWidth;
  if (currentContentHeight > maxContainerHeight)
    maxContainerHeight = currentContentHeight;

  // Aplica os máximos (nunca diminui)
  container.style.minHeight = maxContainerHeight + "px";
  container.style.minWidth = maxContainerWidth + "px";
}

// Adiciona o listener para redimensionamento da janela
window.addEventListener("resize", updateContainerHeight);

// Opcional: chama a função uma vez ao carregar a página para definir os valores iniciais
window.addEventListener("load", updateContainerHeight);

function debouncedUpdate() {
  clearTimeout(resizeTimer);
  resizeTimer = setTimeout(updateContainerHeight, 0);
}

// 1. Para redimensionamento da janela (incluindo F12)
window.addEventListener("resize", debouncedUpdate);

// 2. Para mudanças de zoom (Ctrl + + / Ctrl + -)
window.visualViewport?.addEventListener("resize", debouncedUpdate);

// 3. Chama uma vez ao carregar para definir os valores iniciais
window.addEventListener("load", updateContainerHeight);
// ============================================================
//  MECÂNICA DE CONEXÕES (SETAS)
// ============================================================

const container = document.getElementById("cards-container");
const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
svg.setAttribute("id", "flowchart-svg");
svg.style.cssText = `
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 1000;
      overflow: visible !important;
  `;
container.style.position = "relative";
container.appendChild(svg);

// Força atualização em caso de zoom
let zoomTimeout;
window.addEventListener(
  "wheel",
  function (e) {
    if (e.ctrlKey) {
      clearTimeout(zoomTimeout);
      zoomTimeout = setTimeout(() => {
        updateSvgSize();
        updateAllConnections();
      }, 100);
    }
  },
  { passive: true },
);

// Também escuta resize da janela (já existe, mas reforço)
window.addEventListener("resize", function () {
  updateSvgSize();
});

// Observa mudanças no tamanho do container (já existe, mas garanta que chama updateAllConnections)
const ro = new ResizeObserver(() => {
  if (isEditing) return; // <-- ADICIONE
  updateSvgSize();
  updateAllConnections();
});
ro.observe(container);

// ===== SALVAR E RESTAURAR SELEÇÃO =====
function saveSelection() {
  const sel = window.getSelection();
  if (sel.rangeCount > 0) {
    savedSelection = sel.getRangeAt(0).cloneRange();
  } else {
    savedSelection = null;
  }
}

function restoreSelection() {
  if (savedSelection) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedSelection);
    savedSelection = null;
  }
}
function restoreCardState(card) {
  const raw = card.dataset.state;
  if (!raw) return;
  let state;
  try {
    state = JSON.parse(raw);
  } catch (e) {
    console.warn("Erro ao parsear state do card", e);
    return;
  }

  switch (state.type) {
    case "checklist":
      restoreChecklist(card, state);
      break;
    case "kanban":
      restoreKanban(card, state);
      break;
    case "sticky":
      restoreSticky(card, state);
      break;
    case "metrics":
      restoreMetrics(card, state);
      break;
    case "timeline":
      restoreTimeline(card, state);
      break;
    case "progress":
      restoreProgress(card, state);
      break;
    case "embed":
      restoreEmbed(card, state);
      break;
    default:
      console.warn("Tipo de card desconhecido:", state.type);
  }
}

function restoreEmbed(card, state) {
  // Verifica se o card tem um container para o conteúdo
  const container =
    card.querySelector(".embed-container") ||
    card.querySelector(".card-body") ||
    card;

  // Se o estado tiver HTML completo, insere diretamente
  if (state.html) {
    container.innerHTML = state.html;
  }
  // Se tiver uma URL (para iframe/vídeo)
  else if (state.src) {
    const iframe = document.createElement("iframe");
    iframe.src = state.src;
    iframe.width = state.width || "100%";
    iframe.height = state.height || "400px";
    iframe.frameBorder = "0";
    iframe.allowFullscreen = true;
    container.appendChild(iframe);
  }
  // Se for uma imagem (base64 ou URL)
  else if (state.image) {
    const img = document.createElement("img");
    img.src = state.image;
    img.style.maxWidth = "100%";
    container.appendChild(img);
  }

  // Reativa a edição nos elementos de texto dentro do container (se houver)
  container.querySelectorAll('[contenteditable="true"]').forEach((el) => {
    if (typeof enableEditOnDoubleClick === "function") {
      enableEditOnDoubleClick(el, plainTextOnBlur);
    }
  });
}

function restoreChecklist(card, state) {
  const container = card.querySelector(".checklist-container");
  const progressFill = card.querySelector(".progress-fill");
  const progressText = card.querySelector(".progress-text");
  const counter = card.querySelector(".checklist-counter");
  const searchInput = card.querySelector(".search-tasks");
  const addBtn = card.querySelector(".add-task-btn");

  let tasks = state.tasks;
  let filterText = "";

  function saveState() {
    console.log("🟢 saveState chamado", new Date().toISOString());
    card.dataset.state = JSON.stringify({ type: "checklist", tasks });
    // pushState();
  }

  function render() {
    const filtered = filterText
      ? tasks.filter((t) =>
          t.text.toLowerCase().includes(filterText.toLowerCase()),
        )
      : tasks;
    container.innerHTML = "";
    if (filtered.length === 0) {
      container.innerHTML = `<div style="text-align:center; color:var(--text-muted); padding:20px 0; font-size:13px;">
        ${filterText ? "🔍 Nenhuma tarefa encontrada" : "🎯 Nenhuma tarefa cadastrada"}
      </div>`;
    } else {
      filtered.forEach((task, index) => {
        const realIndex = tasks.indexOf(task);
        const item = document.createElement("div");
        item.className = "task-item";
        item.style.cssText = `
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 8px 10px;
          margin-bottom: 4px;
          border-radius: 8px;
          background: ${task.done ? "rgba(74, 124, 247, 0.05)" : "transparent"};
          border: 1px solid ${task.done ? "rgba(74, 124, 247, 0.1)" : "transparent"};
          transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
          opacity: ${task.done ? 0.7 : 1};
          cursor: default;
        `;

        // Checkbox
        const checkboxWrapper = document.createElement("div");
        checkboxWrapper.style.cssText = `
          position: relative;
          width: 22px;
          height: 22px;
          flex-shrink: 0;
          cursor: pointer;
        `;
        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.checked = task.done;
        checkbox.style.cssText = `
          position: absolute;
          opacity: 0;
          width: 100%;
          height: 100%;
          cursor: pointer;
          z-index: 2;
        `;
        checkbox.addEventListener("change", function () {
          task.done = this.checked;
          render();
          saveState();
        });
        const customCheckbox = document.createElement("div");
        customCheckbox.style.cssText = `
          width: 22px;
          height: 22px;
          border-radius: 6px;
          border: 2px solid ${task.done ? "#4a7cf7" : "var(--border-subtle)"};
          background: ${task.done ? "#4a7cf7" : "transparent"};
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.3s ease;
          pointer-events: none;
        `;
        customCheckbox.innerHTML = task.done
          ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
               <polyline points="20 6 9 17 4 12"></polyline>
             </svg>`
          : "";
        checkboxWrapper.appendChild(checkbox);
        checkboxWrapper.appendChild(customCheckbox);

        // Conteúdo
        const contentWrapper = document.createElement("div");
        contentWrapper.style.cssText = `flex:1; min-width:0;`;
        const textSpan = document.createElement("div");
        textSpan.contentEditable = true;
        textSpan.textContent = task.text;
        textSpan.style.cssText = `
          font-size: 14px;
          font-weight: ${task.done ? "400" : "500"};
          color: ${task.done ? "var(--text-muted)" : "var(--text-primary)"};
          text-decoration: ${task.done ? "line-through" : "none"};
          background: transparent;
          border: none;
          padding: 2px 0;
          outline: none;
          cursor: text;
          transition: all 0.3s;
        `;
        enableEditOnDoubleClick(textSpan, plainTextOnBlur);
        let saveTimer; // declare uma variável para o timer (pode ser dentro do escopo da função restoreChecklist)

        textSpan.addEventListener("input", function () {
          clearTimeout(saveTimer);
          task.text = this.textContent;
          saveTimer = setTimeout(() => {
            saveState();
          }, 300);
        });
        const categoryTag = document.createElement("span");
        categoryTag.textContent = task.category || "📌 Geral";
        categoryTag.style.cssText = `
          font-size: 10px;
          color: var(--text-muted);
          background: var(--bg-elevated);
          padding: 2px 10px;
          border-radius: 12px;
          border: 1px solid var(--border-subtle);
          margin-top: 2px;
          display: inline-block;
          cursor: pointer;
        `;
        categoryTag.title = "Clique para mudar a categoria";
        categoryTag.addEventListener("click", function (e) {
          e.stopPropagation();
          const newCat = prompt(
            "Digite a nova categoria:",
            task.category || "",
          );
          if (newCat !== null && newCat.trim() !== "") {
            task.category = newCat.trim();
            render();
            saveState();
          }
        });
        contentWrapper.appendChild(textSpan);
        contentWrapper.appendChild(categoryTag);

        // Ações
        const actionsDiv = document.createElement("div");
        actionsDiv.style.cssText = `
          display: flex;
          gap: 2px;
          align-items: center;
          opacity: 0;
          transition: opacity 0.2s;
        `;
        const moveUpBtn = document.createElement("button");
        moveUpBtn.textContent = "↑";
        moveUpBtn.style.cssText = `background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; padding:2px 4px; border-radius:4px; transition:all 0.2s;`;
        moveUpBtn.title = "Mover para cima";
        moveUpBtn.addEventListener("click", function (e) {
          e.stopPropagation();
          const idx = tasks.indexOf(task);
          if (idx > 0) {
            [tasks[idx], tasks[idx - 1]] = [tasks[idx - 1], tasks[idx]];
            render();
            saveState();
          }
        });
        const moveDownBtn = document.createElement("button");
        moveDownBtn.textContent = "↓";
        moveDownBtn.style.cssText = `background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; padding:2px 4px; border-radius:4px; transition:all 0.2s;`;
        moveDownBtn.title = "Mover para baixo";
        moveDownBtn.addEventListener("click", function (e) {
          e.stopPropagation();
          const idx = tasks.indexOf(task);
          if (idx < tasks.length - 1) {
            [tasks[idx], tasks[idx + 1]] = [tasks[idx + 1], tasks[idx]];
            render();
            saveState();
          }
        });
        const deleteBtn = document.createElement("button");
        deleteBtn.textContent = "✕";
        deleteBtn.style.cssText = `background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:13px; padding:2px 6px; border-radius:4px; transition:all 0.2s;`;
        deleteBtn.title = "Remover tarefa";
        deleteBtn.addEventListener("click", function (e) {
          e.stopPropagation();
          if (tasks.length <= 1) {
            alert("Não é possível remover a última tarefa.");
            return;
          }
          const idx = tasks.indexOf(task);
          if (idx !== -1) {
            tasks.splice(idx, 1);
            render();
            saveState();
          }
        });
        actionsDiv.appendChild(moveUpBtn);
        actionsDiv.appendChild(moveDownBtn);
        actionsDiv.appendChild(deleteBtn);
        item.addEventListener(
          "mouseenter",
          () => (actionsDiv.style.opacity = "1"),
        );
        item.addEventListener(
          "mouseleave",
          () => (actionsDiv.style.opacity = "0"),
        );

        item.appendChild(checkboxWrapper);
        item.appendChild(contentWrapper);
        item.appendChild(actionsDiv);
        container.appendChild(item);
      });
    }
    updateProgress();
  }

  function updateProgress() {
    const total = tasks.length;
    const done = tasks.filter((t) => t.done).length;
    const pct = total > 0 ? Math.round((done / total) * 100) : 0;
    if (progressFill) {
      progressFill.style.width = pct + "%";
      const color = pct === 100 ? "#4cd9a0" : pct > 50 ? "#4a7cf7" : "#ffc107";
      progressFill.style.background = `linear-gradient(90deg, ${color}, ${color}dd)`;
    }
    if (progressText) progressText.textContent = pct + "%";
    if (counter) counter.textContent = `${done}/${total} concluídos`;
  }

  // Eventos
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      filterText = this.value;
      render();
    });
  }
  if (addBtn) {
    addBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      tasks.push({
        id: Date.now(),
        text: `Nova tarefa ${tasks.length + 1}`,
        done: false,
        category: "📌 Geral",
      });
      render();
      saveState();
    });
  }

  render();
  saveState();
}
function restoreKanban(card, state) {
  const container = card.querySelector("#kanban-container");
  const addColumnBtn = card.querySelector("#add-column-btn");

  let columns = state.columns;

  function saveState() {
    card.dataset.state = JSON.stringify({ type: "kanban", columns });
    pushState();
  }

  function createColumn(title, tasks) {
    const column = document.createElement("div");
    column.className = "kanban-column";
    column.style.cssText =
      "flex:1; min-width:140px; background:var(--bg-elevated); border-radius:8px; padding:10px; position:relative;";
    column.innerHTML = `
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <div contenteditable="true" style="font-weight:600; color:var(--text-primary); background:transparent; border:none; padding:0; outline:none; flex:1;">${title}</div>
        <button class="delete-column-btn" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; padding:0 4px;">✕</button>
      </div>
      <div class="kanban-tasks" style="display:flex; flex-direction:column; gap:6px;"></div>
      <button class="add-task-btn" style="background:transparent; border:1px dashed var(--border-subtle); color:var(--text-muted); border-radius:6px; padding:4px; width:100%; cursor:pointer; font-size:12px; margin-top:6px;">+ Adicionar</button>
    `;

    const titleDiv = column.querySelector('[contenteditable="true"]');
    enableEditOnDoubleClick(titleDiv, plainTextOnBlur);
    let kanbanSaveTimer; // declare no escopo da função restoreKanban

    titleDiv.addEventListener("input", function () {
      clearTimeout(kanbanSaveTimer);
      const colIdx = columns.findIndex((c) => c.title === title);
      if (colIdx !== -1) columns[colIdx].title = this.textContent;
      kanbanSaveTimer = setTimeout(() => {
        saveState();
      }, 300);
    });

    const tasksContainer = column.querySelector(".kanban-tasks");
    tasks.forEach((taskText) => {
      const task = document.createElement("div");
      task.style.cssText =
        "background:var(--bg-surface); border-radius:6px; padding:8px; border:1px solid var(--border-subtle); cursor:pointer;";
      task.innerHTML = `<div contenteditable="true" style="background:transparent; border:none; color:var(--text-primary); width:100%; padding:0; outline:none;">${taskText}</div>`;
      const taskEdit = task.querySelector('[contenteditable="true"]'); // <-- DEFINE A VARIÁVEL
      enableEditOnDoubleClick(taskEdit, plainTextOnBlur);
      let taskEditTimer; // timer local
      taskEdit.addEventListener("input", function () {
        clearTimeout(taskEditTimer);
        const colIdx = columns.findIndex((c) => c.title === title);
        if (colIdx !== -1) {
          const taskIdx = columns[colIdx].tasks.indexOf(taskText);
          if (taskIdx !== -1) columns[colIdx].tasks[taskIdx] = this.textContent;
          taskEditTimer = setTimeout(saveState, 300);
        }
      });
      tasksContainer.appendChild(task);
    });

    // Botão adicionar tarefa
    column
      .querySelector(".add-task-btn")
      .addEventListener("click", function (e) {
        e.stopPropagation();
        const colIdx = columns.findIndex((c) => c.title === title);
        if (colIdx !== -1) {
          columns[colIdx].tasks.push("Nova tarefa");
          saveState();
          // Recria a coluna para refletir
          const newCol = createColumn(
            columns[colIdx].title,
            columns[colIdx].tasks,
          );
          column.replaceWith(newCol);
        }
      });

    // Deletar coluna
    column
      .querySelector(".delete-column-btn")
      .addEventListener("click", function (e) {
        e.stopPropagation();
        if (columns.length <= 1) {
          alert("Não é possível remover a última coluna.");
          return;
        }
        const colIdx = columns.findIndex((c) => c.title === title);
        if (colIdx !== -1) {
          columns.splice(colIdx, 1);
          saveState();
          renderColumns();
        }
      });

    return column;
  }

  function renderColumns() {
    container.innerHTML = "";
    columns.forEach((col) => {
      container.appendChild(createColumn(col.title, col.tasks));
    });
  }

  // Adicionar coluna
  if (addColumnBtn) {
    addColumnBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      const name = prompt("Nome da nova coluna:", "Nova Coluna");
      if (name && name.trim() !== "") {
        columns.push({ title: name.trim(), tasks: [] });
        saveState();
        renderColumns();
      }
    });
  }

  renderColumns();
  saveState();
}
function restoreSticky(card, state) {
  const note = card.querySelector(".sticky-note");
  const toolbar = card.querySelector(".sticky-toolbar");
  const pinBtn = card.querySelector(".pin-btn");
  const resetBtn = card.querySelector(".reset-btn");

  if (!note) return;

  // Aplicar estado
  note.textContent = state.text;
  note.style.background = state.color;
  note.style.fontSize = state.fontSize + "px";

  // Atualizar cores
  const colorBtns = card.querySelectorAll(".color-btn");
  colorBtns.forEach((btn) => {
    btn.style.border = "2px solid transparent";
    btn.style.boxShadow = "none";
    if (btn.dataset.color === state.color) {
      btn.style.border = "2px solid #4a7cf7";
      btn.style.boxShadow = "0 0 0 2px #4a7cf7";
    }
  });

  // Tamanho da fonte
  const sizeDisplay = card.querySelector(".font-size-btn + span");
  if (sizeDisplay) sizeDisplay.textContent = state.fontSize + "px";

  // Pin
  if (pinBtn) {
    pinBtn.textContent = state.pinned ? "📌" : "📌";
    pinBtn.style.color = state.pinned ? "#4a7cf7" : "var(--text-muted)";
    if (state.pinned) {
      card.style.boxShadow = "0 8px 40px rgba(74, 124, 247, 0.25)";
      card.style.border = "1px solid rgba(74, 124, 247, 0.3)";
    } else {
      card.style.boxShadow = "";
      card.style.border = "";
    }
  }

  // Função para salvar
  function saveState() {
    const currentState = {
      type: "sticky",
      text: note.textContent,
      color: note.style.background,
      fontSize: parseInt(note.style.fontSize) || 14,
      pinned: state.pinned || false,
    };
    card.dataset.state = JSON.stringify(currentState);
    pushState();
  }

  // Reconectar eventos
  let stickyTimer; // declare no escopo da função restoreSticky

  note.addEventListener("input", function () {
    clearTimeout(stickyTimer);
    stickyTimer = setTimeout(saveState, 300);
  });

  note.addEventListener("blur", function () {
    if (this.textContent.trim() === "")
      this.textContent = "Escreva sua nota aqui...";
    saveState();
  });
  enableEditOnDoubleClick(note, plainTextOnBlur);

  // Cores
  colorBtns.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      const color = this.dataset.color;
      note.style.background = color;
      colorBtns.forEach((b) => {
        b.style.border = "2px solid transparent";
        b.style.boxShadow = "none";
      });
      this.style.border = "2px solid #4a7cf7";
      this.style.boxShadow = "0 0 0 2px #4a7cf7";
      saveState();
    });
  });

  // Tamanho
  card.querySelectorAll(".font-size-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      const delta = parseInt(this.dataset.delta);
      const currentSize = parseInt(note.style.fontSize) || 14;
      let newSize = Math.min(24, Math.max(10, currentSize + delta));
      note.style.fontSize = newSize + "px";
      const sizeDisplay = card.querySelector(".font-size-btn + span");
      if (sizeDisplay) sizeDisplay.textContent = newSize + "px";
      saveState();
    });
  });

  // Pin
  if (pinBtn) {
    pinBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      state.pinned = !state.pinned;
      this.style.color = state.pinned ? "#4a7cf7" : "var(--text-muted)";
      if (state.pinned) {
        card.style.boxShadow = "0 8px 40px rgba(74, 124, 247, 0.25)";
        card.style.border = "1px solid rgba(74, 124, 247, 0.3)";
      } else {
        card.style.boxShadow = "";
        card.style.border = "";
      }
      saveState();
    });
  }

  // Reset
  if (resetBtn) {
    resetBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      if (confirm("Redefinir nota para o estado inicial?")) {
        note.textContent = "Escreva sua nota aqui...";
        note.style.background = "#f9e076";
        note.style.fontSize = "14px";
        state.pinned = false;
        if (pinBtn) {
          pinBtn.style.color = "var(--text-muted)";
          card.style.boxShadow = "";
          card.style.border = "";
        }
        colorBtns.forEach((b) => {
          b.style.border = "2px solid transparent";
          b.style.boxShadow = "none";
        });
        const defaultBtn = card.querySelector(
          '.color-btn[data-color="#f9e076"]',
        );
        if (defaultBtn) {
          defaultBtn.style.border = "2px solid #4a7cf7";
          defaultBtn.style.boxShadow = "0 0 0 2px #4a7cf7";
        }
        const sizeDisplay = card.querySelector(".font-size-btn + span");
        if (sizeDisplay) sizeDisplay.textContent = "14px";
        saveState();
      }
    });
  }

  saveState();
}
// ===== FUNÇÕES DE SERIALIZAÇÃO (VERSÃO COM ID) =====
function collectState() {
  if (isEditing) return;
  const container = document.getElementById("cards-container");
  const items = container.querySelectorAll(".editable-item");
  const cardsData = [];

  items.forEach((card, index) => {
    // 🔥 CONVERTE \n em <br> ANTES de clonar
    card
      .querySelectorAll(
        '[contenteditable="plaintext-only"], [contenteditable="true"]',
      )
      .forEach((el) => {
        const walker = document.createTreeWalker(
          el,
          NodeFilter.SHOW_TEXT,
          null,
          false,
        );
        const nodes = [];
        let node;
        while ((node = walker.nextNode())) nodes.push(node);
        nodes.forEach((textNode) => {
          const text = textNode.textContent;
          if (!text.includes("\n")) return;
          const fragment = document.createDocumentFragment();
          const parts = text.split("\n");
          parts.forEach((part, i) => {
            if (part) fragment.appendChild(document.createTextNode(part));
            if (i < parts.length - 1)
              fragment.appendChild(document.createElement("br"));
          });
          textNode.parentNode.replaceChild(fragment, textNode);
        });
      });

    if (!card.dataset.cardId) {
      card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
    }

    const clone = card.cloneNode(true);
    clone
      .querySelectorAll(
        ".delete-btn, .duplicate-btn, .drag-handle, .resize-handle, .flow-port, .table-toolbar, .edit-toolbar",
      )
      .forEach((el) => el.remove());
    clone
      .querySelectorAll('[contenteditable="true"]')
      .forEach((el) => el.removeAttribute("contenteditable"));

    cardsData.push({
      cardId: card.dataset.cardId || null,
      html: clone.outerHTML,
      left: card.style.left,
      top: card.style.top,
      width: card.style.width,
      height: card.style.height,
      className: card.className,
      sort_order: index,
      state: card.dataset.state || null,
    });
  });

  // 🔥 CONEXÕES
  const connectionsData = connections
    .filter(
      (conn) => conn.fromCard && conn.toCard && conn.fromCard !== conn.toCard,
    )
    .map((conn) => {
      const allCards = Array.from(document.querySelectorAll(".editable-item"));
      const fromIndex = allCards.indexOf(conn.fromCard);
      const toIndex = allCards.indexOf(conn.toCard);
      return {
        fromId: conn.fromCard.dataset.cardId,
        fromIndex,
        fromPos: conn.fromPos,
        toId: conn.toCard.dataset.cardId,
        toIndex,
        toPos: conn.toPos,
      };
    });

  return {
    cards: cardsData,
    connections: connectionsData,
    page: { cards: cardsData, connections: connectionsData },
    global: {
      dadosCompletos: JSON.parse(JSON.stringify(dadosCompletos)),
      currentMateria,
      currentTopico,
      currentPagina,
    },
    _version: ++version,
  };
}

function restoreState(pageData) {
  if (isRestoring) return;
  isRestoring = true;

  try {
    console.log("🔥 restoreState (do arquivo) FOI CHAMADA");

    if (!pageData || !pageData.cards || !Array.isArray(pageData.cards)) {
      console.warn("restoreState: dados inválidos ou vazios", pageData);
      return;
    }

    const container = document.getElementById("cards-container");
    if (!container) return;

    // 1. Remove cards antigos
    container.querySelectorAll(".editable-item").forEach((el) => el.remove());

    // 2. Remove conexões antigas
    connections.forEach((c) => {
      if (c.lineElement) c.lineElement.remove();
      if (c.arrowElement) c.arrowElement.remove();
      if (c.deleteBtn) c.deleteBtn.remove();
    });
    connections = [];
    window._selectedLine = null;

    // 3. Insere novos cards
    const htmlString = pageData.cards.map((card) => card.html).join("");
    const tempDiv = document.createElement("div");
    tempDiv.innerHTML = htmlString;
    while (tempDiv.firstChild) {
      container.appendChild(tempDiv.firstChild);
    }

    // 4. Itera cards e restaura IDs e controles
    const createdCards = [];
    container
      .querySelectorAll(":scope > .editable-item")
      .forEach((card, index) => {
        const savedData = pageData.cards[index] || {};
        if (savedData.cardId) {
          card.dataset.cardId = savedData.cardId;
        } else {
          card.dataset.cardId = `card_${Date.now()}_${index}_${Math.random().toString(36).substr(2, 4)}`;
        }
        if (savedData.left) card.style.left = savedData.left;
        if (savedData.top) card.style.top = savedData.top;
        if (savedData.width) card.style.width = savedData.width;
        if (savedData.height) card.style.height = savedData.height;

        if (card.dataset.state) {
          restoreCardState(card);
        }

        // Remove duplicatas
        card
          .querySelectorAll(
            ".resize-handle, .flow-port, .drag-handle, .delete-btn, .duplicate-btn",
          )
          .forEach((el) => el.remove());

        if (typeof addDeleteButton === "function") addDeleteButton(card);
        if (typeof addDragHandle === "function") addDragHandle(card);
        if (typeof addPorts === "function") addPorts(card);
        if (typeof addResizeHandle === "function") addResizeHandle(card);

        // Se for card de imagem, reconecta os eventos de troca/remoção
        if (card.querySelector(".image-card-container")) {
          if (typeof initializeImageCard === "function") {
            initializeImageCard(card);
          }
        }

        // Recria toolbar de tabela
        const oldToolbar = card.querySelector(".table-toolbar");
        if (oldToolbar) oldToolbar.remove();
        if (card.querySelector(".table-responsive")) {
          const toolbarDiv = document.createElement("div");
          toolbarDiv.className = "d-flex gap-2 mt-2 flex-wrap table-toolbar";
          toolbarDiv.innerHTML = `
            <button class="btn btn-sm btn-outline-primary" onclick="addRowToTable(this)">➕ Linha</button>
            <button class="btn btn-sm btn-outline-primary" onclick="addColumnToTable(this)">➕ Coluna</button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteRowFromTable(this)">➖ Linha</button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteColumnFromTable(this)">➖ Coluna</button>
            <button class="btn btn-sm btn-success" onclick="finalizeThisTable(this)">✅ Finalizar</button>
          `;
          card.appendChild(toolbarDiv);
        }

        // Reativa edição
        const editableElements = card.querySelectorAll(
          "p, h1, h2, h3, h4, h5, h6, .sticky-note, li, th, td, " +
            ".file-name, .code-block, .terminal-content, blockquote, " +
            ".vscode-explanation > *", // 🔥 pega TODOS os filhos diretos
        );
        editableElements.forEach((el) => {
          // Não força a remoção — só garante que o listener está ativo
          if (typeof enableEditOnDoubleClick === "function") {
            enableEditOnDoubleClick(el, plainTextOnBlur);
          }
        });

        createdCards.push(card);
      });

    // 5. Mapa ID -> card
    const cardMap = {};
    createdCards.forEach((card, index) => {
      const savedData = pageData.cards[index] || {};
      if (savedData.cardId) {
        cardMap[savedData.cardId] = card;
      }
    });

    // 6. Restaura conexões (PRIORIZA IDs, FALLBACK índices)
    if (pageData.connections && Array.isArray(pageData.connections)) {
      pageData.connections.forEach((connData) => {
        let fromCard = null;
        let toCard = null;
        // PRIORIZA IDs
        if (connData.fromId && connData.toId) {
          fromCard = cardMap[connData.fromId];
          toCard = cardMap[connData.toId];
        }
        // Fallback para índices
        else if (
          connData.fromIndex !== undefined &&
          connData.toIndex !== undefined
        ) {
          fromCard = createdCards[connData.fromIndex];
          toCard = createdCards[connData.toIndex];
        }
        if (fromCard && toCard && fromCard !== toCard) {
          const exists = connections.some(
            (c) => c.fromCard === fromCard && c.toCard === toCard,
          );
          if (!exists) {
            createConnection(
              fromCard,
              connData.fromPos || "right",
              toCard,
              connData.toPos || "left",
            );
          }
        } else {
          console.warn("⚠️ Conexão ignorada:", connData);
        }
      });
    }

    // 7. Atualiza UI
    setTimeout(() => {
      if (typeof updateContainerHeight === "function") updateContainerHeight();
      if (typeof updateSvgSize === "function") updateSvgSize();
      if (typeof updateAllConnections === "function") updateAllConnections();
      if (typeof updateMenuUI === "function") updateMenuUI();
      // 🔥 Roda spell check depois que tudo renderizou
      if (typeof spellCheckAllCards === "function") {
        setTimeout(spellCheckAllCards, 1500);
      }
    }, 50);

    console.log(
      `✅ Estado restaurado: ${createdCards.length} cards, ${connections.length} conexões`,
    );
  } catch (error) {
    console.error("❌ Erro em restoreState:", error);
  } finally {
    isRestoring = false;
  }
}

const resizeObserver = new ResizeObserver(() => {
  if (isEditing) return;
  updateSvgSize();
});
resizeObserver.observe(container);

// Flag global para evitar execuções concorrentes

function restoreMetrics(card, state) {
  // Aplica os valores do estado
  const valueDisplay = card.querySelector(".metric-value");
  const labelDisplay = card.querySelector(".metric-label");
  const changeDisplay = card.querySelector(".metric-change");
  const changeLabelDisplay = card.querySelector(".metric-change-label");
  const goalDisplay = card.querySelector(".metric-goal");
  const goalLabelDisplay = card.querySelector(".metric-goal-label");
  const progressDisplay = card.querySelector(".metric-progress");
  const progressFill = card.querySelector(".metric-progress-fill");
  const iconDisplay = card.querySelector(".metric-icon");
  const adjustInput = card.querySelector(".metric-adjust-input");

  // Atualiza a UI
  function formatCurrency(value) {
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(value);
  }

  function renderMetrics() {
    if (valueDisplay) valueDisplay.textContent = formatCurrency(state.value);
    if (labelDisplay) labelDisplay.textContent = state.label;
    if (changeDisplay) {
      const diff = ((state.value - state.goal) / state.goal) * 100;
      const absDiff = Math.abs(diff);
      if (Math.abs(diff) < 0.1) {
        changeDisplay.textContent = "✓ Meta atingida!";
        changeDisplay.style.color = "#4cd9a0";
      } else {
        changeDisplay.textContent = `${diff > 0 ? "▲" : "▼"} ${absDiff.toFixed(1)}%`;
        changeDisplay.style.color = diff >= 0 ? "#4cd9a0" : "#ff6b6b";
      }
    }
    if (changeLabelDisplay)
      changeLabelDisplay.textContent = state.changeLabel || "vs meta";
    if (goalDisplay) goalDisplay.textContent = formatCurrency(state.goal);
    if (goalLabelDisplay)
      goalLabelDisplay.textContent = state.goalLabel || "Meta";
    if (progressDisplay)
      progressDisplay.textContent = `${state.progress.toFixed(1)}%`;
    if (progressFill) {
      progressFill.style.width = `${Math.min(100, Math.max(0, state.progress))}%`;
      const color =
        state.progress > 70
          ? "#4cd9a0"
          : state.progress > 40
            ? "#ffc107"
            : "#ff6b6b";
      progressFill.style.background = `linear-gradient(90deg, ${color}, ${color}dd)`;
    }
    if (iconDisplay) iconDisplay.textContent = state.icon || "📊";
    if (adjustInput)
      adjustInput.placeholder = `+/- (ex: ${Math.round(state.value * 0.05)})`;
  }

  function saveState() {
    card.dataset.state = JSON.stringify({ type: "metrics", ...state });
    pushState();
  }

  // Reconecta eventos de edição (clique para editar)
  function makeEditable(element, callback, placeholder = "Digite o valor") {
    element.style.cursor = "pointer";
    element.title = "Clique para editar";
    element.addEventListener("click", function (e) {
      e.stopPropagation();
      const current = this.textContent.trim();
      const newVal = prompt(placeholder, current);
      if (newVal !== null) {
        const cleaned = newVal.replace(/[^0-9,.]/g, "").replace(",", ".");
        const num = parseFloat(cleaned);
        if (!isNaN(num)) {
          callback(num);
          renderMetrics();
          saveState();
        } else {
          alert("Valor inválido. Use números apenas.");
        }
      }
    });
  }

  // Editar valor, meta, label
  if (valueDisplay) {
    makeEditable(
      valueDisplay,
      (val) => {
        state.value = val;
        state.progress = Math.min(
          100,
          Math.max(0, (state.value / state.goal) * 100),
        );
      },
      "Digite o novo valor (ex: 15000)",
    );
  }
  if (goalDisplay) {
    makeEditable(
      goalDisplay,
      (val) => {
        state.goal = val;
        state.progress = Math.min(
          100,
          Math.max(0, (state.value / state.goal) * 100),
        );
      },
      "Digite a nova meta (ex: 20000)",
    );
  }
  if (labelDisplay) {
    makeEditable(
      labelDisplay,
      (val) => {
        state.label = val;
      },
      "Digite o novo rótulo",
    );
  }

  // Ícone clicável
  if (iconDisplay) {
    iconDisplay.addEventListener("click", function (e) {
      e.stopPropagation();
      const newIcon = prompt(
        "Digite o emoji ou ícone (ex: 💰, 📈, 🚀):",
        state.icon || "📊",
      );
      if (newIcon && newIcon.trim()) {
        state.icon = newIcon.trim();
        renderMetrics();
        saveState();
      }
    });
  }

  // Botões de ajuste
  const applyBtn = card.querySelector(".metric-apply-btn");
  if (applyBtn) {
    applyBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      const input = card.querySelector(".metric-adjust-input");
      const val = parseFloat(input?.value);
      if (!isNaN(val) && val !== 0) {
        state.value = Math.max(0, state.value + val);
        state.progress = Math.min(
          100,
          Math.max(0, (state.value / state.goal) * 100),
        );
        renderMetrics();
        saveState();
        if (input) input.value = "";
      } else {
        alert("Digite um valor válido (ex: 500 ou -300)");
      }
    });
  }

  card.querySelectorAll(".metric-percent-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      const pct = parseFloat(this.dataset.pct) / 100;
      const delta = Math.round(state.value * pct);
      state.value = Math.max(0, state.value + delta);
      state.progress = Math.min(
        100,
        Math.max(0, (state.value / state.goal) * 100),
      );
      renderMetrics();
      saveState();
    });
  });

  renderMetrics();
  saveState();
}

function restoreTimeline(card, state) {
  let events = state.events;

  function saveState() {
    card.dataset.state = JSON.stringify({ type: "timeline", events });
    pushState();
  }

  function render() {
    // ← MOVE pra dentro
    const container = card.querySelector(".timeline-container");
    if (!container) return;
    container.innerHTML = "";

    events.forEach((ev, idx) => {
      const item = document.createElement("div");
      item.className = "timeline-item";
      item.style.cssText = `
      display: flex;
      gap: 12px;
      align-items: stretch;
      padding: 6px 0;
      position: relative;
      opacity: ${ev.done ? 0.6 : 1};
      transition: all 0.3s ease;
    `;

      // ----- LINHA (DATA + MARCADOR) -----
      const lineWrapper = document.createElement("div");
      lineWrapper.style.cssText = `
      display: flex;
      flex-direction: column;
      align-items: center;
      min-width: 70px;
      position: relative;
    `;

      // Data (editável)
      const dateDiv = document.createElement("div");
      dateDiv.contentEditable = true;
      dateDiv.style.cssText = `
      font-size: 12px;
      color: var(--text-muted);
      background: transparent;
      border: none;
      padding: 0;
      outline: none;
      text-align: center;
      font-weight: 500;
      width: 100%;
      cursor: text;
    `;
      dateDiv.textContent = ev.date;
      enableEditOnDoubleClick(dateDiv, plainTextOnBlur);
      let dateTimer;
      dateDiv.addEventListener("input", () => {
        clearTimeout(dateTimer);
        ev.date = dateDiv.textContent;
        dateTimer = setTimeout(saveState, 300);
      });
      lineWrapper.appendChild(dateDiv);

      // Marcador + linha vertical
      const lineContainer = document.createElement("div");
      lineContainer.style.cssText = `
      display: flex;
      flex-direction: column;
      align-items: center;
      flex: 1;
      margin-top: 4px;
      position: relative;
    `;

      const marker = document.createElement("div");
      marker.style.cssText = `
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: ${ev.done ? "#4cd9a0" : "#4a7cf7"};
      border: 2px solid var(--bg-surface);
      box-shadow: 0 0 0 3px ${ev.done ? "#4cd9a0" : "#4a7cf7"};
      flex-shrink: 0;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      z-index: 2;
    `;
      marker.title = ev.done ? "Marcar como pendente" : "Marcar como concluído";
      marker.addEventListener("click", function (e) {
        e.stopPropagation();
        ev.done = !ev.done;
        render();
        saveState();
      });
      lineContainer.appendChild(marker);

      if (idx < events.length - 1) {
        const line = document.createElement("div");
        line.style.cssText = `
        width: 2px;
        flex: 1;
        background: linear-gradient(to bottom, ${ev.done ? "#4cd9a0" : "#4a7cf7"}, var(--border-subtle));
        min-height: 20px;
        margin-top: 2px;
      `;
        lineContainer.appendChild(line);
      }
      lineWrapper.appendChild(lineContainer);

      // ----- CONTEÚDO (TÍTULO + HORA) -----
      const contentDiv = document.createElement("div");
      contentDiv.style.cssText = `
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 2px;
      padding-left: 4px;
      padding-bottom: ${idx < events.length - 1 ? "4px" : "0"};
      border-left: 2px solid ${ev.done ? "#4cd9a0" : "#4a7cf7"};
      padding-left: 12px;
      position: relative;
    `;

      // Botões de ação (aparecem ao passar o mouse)
      const actions = document.createElement("div");
      actions.style.cssText = `
      display: flex;
      gap: 4px;
      position: absolute;
      right: 0;
      top: 0;
      opacity: 0;
      transition: opacity 0.2s;
    `;
      actions.innerHTML = `
      <button class="move-up" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:12px; padding:0 2px;" title="Mover para cima">↑</button>
      <button class="move-down" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:12px; padding:0 2px;" title="Mover para baixo">↓</button>
      <button class="delete-event" style="background:transparent; border:none; color:#ff6b6b; cursor:pointer; font-size:12px; padding:0 2px;" title="Remover evento">✕</button>
    `;
      contentDiv.appendChild(actions);

      // Título (editável)
      const titleDiv = document.createElement("div");
      titleDiv.contentEditable = true;
      titleDiv.style.cssText = `
      font-weight: 500;
      color: var(--text-primary);
      background: transparent;
      border: none;
      padding: 0;
      outline: none;
      font-size: 14px;
      cursor: text;
    `;
      titleDiv.textContent = ev.title;
      enableEditOnDoubleClick(titleDiv, plainTextOnBlur);
      let titleTimer;
      titleDiv.addEventListener("input", () => {
        clearTimeout(titleTimer);
        ev.title = titleDiv.textContent;
        titleTimer = setTimeout(saveState, 300);
      });
      contentDiv.appendChild(titleDiv);

      // Hora (editável)
      const timeDiv = document.createElement("div");
      timeDiv.contentEditable = true;
      timeDiv.style.cssText = `
      font-size: 12px;
      color: var(--text-muted);
      background: transparent;
      border: none;
      padding: 0;
      outline: none;
      cursor: text;
    `;
      timeDiv.textContent = ev.time;
      enableEditOnDoubleClick(timeDiv, plainTextOnBlur);
      let timeTimer;
      timeDiv.addEventListener("input", () => {
        clearTimeout(timeTimer);
        ev.time = timeDiv.textContent;
        timeTimer = setTimeout(saveState, 300);
      });
      contentDiv.appendChild(timeDiv);

      // Monta o item
      item.appendChild(lineWrapper);
      item.appendChild(contentDiv);

      // Eventos dos botões de ação
      item
        .querySelector(".delete-event")
        .addEventListener("click", function (e) {
          e.stopPropagation();
          if (events.length <= 1) {
            alert("Não é possível remover o último evento.");
            return;
          }
          events.splice(idx, 1);
          render();
          saveState();
        });
      item.querySelector(".move-up").addEventListener("click", function (e) {
        e.stopPropagation();
        if (idx > 0) {
          [events[idx], events[idx - 1]] = [events[idx - 1], events[idx]];
          render();
          saveState();
        }
      });
      item.querySelector(".move-down").addEventListener("click", function (e) {
        e.stopPropagation();
        if (idx < events.length - 1) {
          [events[idx], events[idx + 1]] = [events[idx + 1], events[idx]];
          render();
          saveState();
        }
      });

      // Mostrar/ocultar ações ao passar o mouse
      item.addEventListener("mouseenter", () => (actions.style.opacity = "1"));
      item.addEventListener("mouseleave", () => (actions.style.opacity = "0"));

      container.appendChild(item);
    });

    // Atualiza contador
    const counter = card.querySelector(".event-counter");
    if (counter) {
      const done = events.filter((e) => e.done).length;
      counter.textContent = `${done}/${events.length} concluídos`;
    }
    render(); // ← CHAMA no final
    saveState();
  }
}

function restoreProgress(card, state) {
  let tasks = state.tasks;
  let taskIdCounter = tasks.reduce((max, t) => Math.max(max, t.id || 0), 0) + 1;

  function saveState() {
    card.dataset.state = JSON.stringify({ type: "progress", tasks });
    pushState();
  }

  function renderTasks() {
    const taskContainer = card.querySelector(".tasks-container");
    if (!taskContainer) return;
    taskContainer.innerHTML = "";

    tasks.forEach((task, index) => {
      const taskDiv = document.createElement("div");
      taskDiv.style.cssText = `
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 8px;
        border-radius: 6px;
        transition: background 0.2s;
        cursor: pointer;
        background: ${task.done ? "rgba(46, 160, 67, 0.08)" : "transparent"};
        border: 1px solid ${task.done ? "rgba(46, 160, 67, 0.2)" : "var(--border-subtle)"};
      `;

      const checkbox = document.createElement("div");
      checkbox.style.cssText = `
        width: 20px;
        height: 20px;
        border-radius: 4px;
        border: 2px solid ${task.done ? "#4cd9a0" : "var(--border-subtle)"};
        background: ${task.done ? "#4cd9a0" : "transparent"};
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.2s;
        color: #fff;
        font-size: 12px;
        cursor: pointer;
      `;
      checkbox.textContent = task.done ? "✓" : "";

      const textSpan = document.createElement("span");
      textSpan.style.cssText = `
        flex: 1;
        color: ${task.done ? "var(--text-muted)" : "var(--text-primary)"};
        text-decoration: ${task.done ? "line-through" : "none"};
        font-size: 14px;
        outline: none;
        padding: 2px 4px;
        border-radius: 4px;
        transition: all 0.2s;
        cursor: text;
      `;
      textSpan.textContent = task.text;

      textSpan.addEventListener("dblclick", function (e) {
        e.stopPropagation();
        this.contentEditable = true;
        this.focus();
        const range = document.createRange();
        range.selectNodeContents(this);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
      });
      textSpan.addEventListener("blur", function () {
        this.contentEditable = false;
        const newText = this.textContent.trim();
        if (newText !== task.text) {
          task.text = newText || "Tarefa";
          saveState();
        }
      });
      textSpan.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          this.blur();
        }
        if (e.key === "Escape") {
          this.textContent = task.text;
          this.blur();
        }
      });

      const deleteBtn = document.createElement("span");
      deleteBtn.style.cssText = `
        color: var(--text-muted);
        font-size: 14px;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s;
        padding: 2px 4px;
        border-radius: 4px;
      `;
      deleteBtn.textContent = "✕";
      deleteBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        tasks.splice(index, 1);
        renderTasks();
        updateProgress();
        saveState();
      });

      taskDiv.addEventListener(
        "mouseenter",
        () => (deleteBtn.style.opacity = "0.6"),
      );
      taskDiv.addEventListener(
        "mouseleave",
        () => (deleteBtn.style.opacity = "0"),
      );

      checkbox.addEventListener("click", function (e) {
        e.stopPropagation();
        task.done = !task.done;
        renderTasks();
        updateProgress();
        saveState();
      });

      taskDiv.addEventListener("click", function (e) {
        if (e.target === taskDiv) {
          task.done = !task.done;
          renderTasks();
          updateProgress();
          saveState();
        }
      });

      taskDiv.appendChild(checkbox);
      taskDiv.appendChild(textSpan);
      taskDiv.appendChild(deleteBtn);
      taskContainer.appendChild(taskDiv);
    });
    updateProgress();
  }

  function updateProgress() {
    const total = tasks.length;
    const completed = tasks.filter((t) => t.done).length;
    const percent = total === 0 ? 0 : Math.round((completed / total) * 100);
    const percentDisplay = card.querySelector(".progress-percent");
    const barFill = card.querySelector(".progress-bar-fill");
    const summary = card.querySelector(".progress-summary");
    if (percentDisplay) percentDisplay.textContent = `${percent}%`;
    if (barFill) barFill.style.width = `${percent}%`;
    if (summary)
      summary.textContent = `${completed} de ${total} tarefas concluídas`;
  }

  // Reconecta botão adicionar
  const addBtn = card.querySelector(".add-task-btn");
  if (addBtn) {
    addBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      const newTaskText = prompt(
        "Digite o nome da nova tarefa:",
        "Nova tarefa",
      );
      if (newTaskText && newTaskText.trim() !== "") {
        tasks.push({
          id: taskIdCounter++,
          text: newTaskText.trim(),
          done: false,
        });
        renderTasks();
        saveState();
      }
    });
  }

  renderTasks();
  saveState();
}

function restoreCardState(card) {
  const raw = card.dataset.state;
  if (!raw) return;
  let state;
  try {
    state = JSON.parse(raw);
  } catch (e) {
    console.warn("Erro ao parsear state do card", e);
    return;
  }

  switch (state.type) {
    case "checklist":
      restoreChecklist(card, state);
      break;
    case "kanban":
      restoreKanban(card, state);
      break;
    case "sticky":
      restoreSticky(card, state);
      break;
    case "metrics":
      restoreMetrics(card, state);
      break;
    case "timeline":
      restoreTimeline(card, state);
      break;
    case "progress":
      restoreProgress(card, state);
      break;
    default:
      console.warn("Tipo de card desconhecido:", state.type);
  }
}
//===============================================
//  MECÂNICA DE CONEXÕES – VERSÃO PROFISSIONAL
// ============================================================

// ---- Função única para garantir o SVG ----
function inicializarSvg() {
  const container = document.getElementById("cards-container");
  if (!container) return null;
  let svg = document.getElementById("flowchart-svg");
  if (svg) return svg;

  svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
  svg.setAttribute("id", "flowchart-svg");
  svg.style.cssText = `
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          pointer-events: none;
          overflow: visible !important;
          z-index: 1000;
      `;
  container.style.position = "relative";
  container.appendChild(svg);

  const defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
  // Gradiente para linha temporária
  const grad = document.createElementNS(
    "http://www.w3.org/2000/svg",
    "linearGradient",
  );
  grad.setAttribute("id", "gradient-stroke");
  grad.setAttribute("x1", "0%");
  grad.setAttribute("y1", "0%");
  grad.setAttribute("x2", "100%");
  grad.setAttribute("y2", "100%");
  const stop1 = document.createElementNS("http://www.w3.org/2000/svg", "stop");
  stop1.setAttribute("offset", "0%");
  stop1.setAttribute("style", "stop-color:#0d6efd;stop-opacity:1");
  const stop2 = document.createElementNS("http://www.w3.org/2000/svg", "stop");
  stop2.setAttribute("offset", "100%");
  stop2.setAttribute("style", "stop-color:#6f42c1;stop-opacity:1");
  grad.appendChild(stop1);
  grad.appendChild(stop2);
  defs.appendChild(grad);

  // Marcador de seta (com refX ajustado para recuo)
  const marker = document.createElementNS(
    "http://www.w3.org/2000/svg",
    "marker",
  );
  marker.setAttribute("id", "arrowhead");
  marker.setAttribute("markerWidth", "4");
  marker.setAttribute("markerHeight", "4");
  marker.setAttribute("refX", "3"); // recuo para não cortar a ponta
  marker.setAttribute("refY", "2");
  marker.setAttribute("orient", "auto");
  const poly = document.createElementNS(
    "http://www.w3.org/2000/svg",
    "polygon",
  );
  poly.setAttribute("points", "0 0, 4 2, 0 4"); // triângulo bem pequeno
  poly.setAttribute("fill", "#ff6b6b");
  marker.appendChild(poly);
  defs.appendChild(marker);

  svg.prepend(defs);
  return svg;
}
// ---- Garante que o DEFS (gradiente + marcador) existe no SVG ----
function ensureDefs(svg) {
  if (!svg) return;
  if (svg.querySelector("defs")) return; // já existe

  const defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");

  // Gradiente para linha temporária
  const grad = document.createElementNS(
    "http://www.w3.org/2000/svg",
    "linearGradient",
  );
  grad.setAttribute("id", "gradient-stroke");
  grad.setAttribute("x1", "0%");
  grad.setAttribute("y1", "0%");
  grad.setAttribute("x2", "100%");
  grad.setAttribute("y2", "100%");
  const stop1 = document.createElementNS("http://www.w3.org/2000/svg", "stop");
  stop1.setAttribute("offset", "0%");
  stop1.setAttribute("style", "stop-color:#0d6efd;stop-opacity:1");
  const stop2 = document.createElementNS("http://www.w3.org/2000/svg", "stop");
  stop2.setAttribute("offset", "100%");
  stop2.setAttribute("style", "stop-color:#6f42c1;stop-opacity:1");
  grad.appendChild(stop1);
  grad.appendChild(stop2);
  defs.appendChild(grad);

  // Marcador de seta (tamanho pequeno)
  const marker = document.createElementNS(
    "http://www.w3.org/2000/svg",
    "marker",
  );
  marker.setAttribute("id", "arrowhead");
  marker.setAttribute("markerWidth", "4");
  marker.setAttribute("markerHeight", "4");
  marker.setAttribute("refX", "3");
  marker.setAttribute("refY", "2");
  marker.setAttribute("orient", "auto");
  const poly = document.createElementNS(
    "http://www.w3.org/2000/svg",
    "polygon",
  );
  poly.setAttribute("points", "0 0, 4 2, 0 4");
  poly.setAttribute("fill", "#ff6b6b");
  marker.appendChild(poly);
  defs.appendChild(marker);

  svg.prepend(defs);
}
function getSvg() {
  return document.getElementById("flowchart-svg");
}

// ---- Posição da porta ----
function getPortPosition(card, pos) {
  const x = card.offsetLeft;
  const y = card.offsetTop;
  const w = card.offsetWidth;
  const h = card.offsetHeight;

  let px = x + w / 2;
  let py = y + h / 2;

  switch (pos) {
    case "top":
      py = y;
      break;
    case "bottom":
      py = y + h;
      break;
    case "left":
      px = x;
      break;
    case "right":
      px = x + w;
      break;
  }
  return { x: px, y: py };
}

// ---- Vetor direção para cada porta ----
function getDirection(pos) {
  const map = {
    right: { x: 1, y: 0 },
    left: { x: -1, y: 0 },
    bottom: { x: 0, y: 1 },
    top: { x: 0, y: -1 },
  };
  return map[pos] || { x: 0, y: -0.5 };
}

// ---- Encontra o card mais próximo do ponto (x, y) ----
function findClosestCard(clientX, clientY, excludeCard) {
  const cards = document.querySelectorAll(".editable-item");
  let closest = null;
  let minDist = Infinity;

  cards.forEach((card) => {
    if (card === excludeCard) return;
    const rect = card.getBoundingClientRect();
    // Centro do card
    const cx = rect.left + rect.width / 2;
    const cy = rect.top + rect.height / 2;
    const dx = clientX - cx;
    const dy = clientY - cy;
    const dist = dx * dx + dy * dy;
    if (dist < minDist) {
      minDist = dist;
      closest = card;
    }
  });

  // Se a distância for muito grande (ex: > 500px), ignorar
  if (minDist > 250000) return null; // 500px ao quadrado
  return closest;
}
// ---- Encontra a porta mais próxima de um ponto (x, y) em um card ----
function findClosestPort(card, clientX, clientY) {
  const positions = ["top", "bottom", "left", "right"];
  let closestPos = null;
  let minDist = Infinity;

  // Obtém as coordenadas da porta em relação à viewport
  const rect = card.getBoundingClientRect();
  const offsetX = rect.left;
  const offsetY = rect.top;

  positions.forEach((pos) => {
    // Posição da porta em coordenadas absolutas (viewport)
    let px, py;
    switch (pos) {
      case "top":
        px = offsetX + rect.width / 2;
        py = offsetY;
        break;
      case "bottom":
        px = offsetX + rect.width / 2;
        py = offsetY + rect.height;
        break;
      case "left":
        px = offsetX;
        py = offsetY + rect.height / 2;
        break;
      case "right":
        px = offsetX + rect.width;
        py = offsetY + rect.height / 2;
        break;
    }
    const dx = clientX - px;
    const dy = clientY - py;
    const dist = dx * dx + dy * dy;
    if (dist < minDist) {
      minDist = dist;
      closestPos = pos;
    }
  });

  return closestPos;
}
// ---- Cria conexão (linha + marker-end) ----
function createConnection(fromCard, fromPos, toCard, toPos) {
  const exists = connections.some(
    (c) =>
      c.fromCard === fromCard &&
      c.fromPos === fromPos &&
      c.toCard === toCard &&
      c.toPos === toPos,
  );
  if (exists) return null;

  const svg = getSvg();
  if (!svg) return null;

  const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
  path.setAttribute("stroke", "#ff6b6b");
  path.setAttribute("stroke-width", "3");
  path.setAttribute("fill", "none");
  path.setAttribute("stroke-linecap", "round");
  path.setAttribute("marker-end", "url(#arrowhead)");
  path.style.pointerEvents = "stroke";
  path.style.cursor = "pointer";

  // 🔥 GUARDA OS IDs DOS CARDS NO ELEMENTO SVG
  path.dataset.fromId = fromCard.dataset.cardId || fromCard.id || "";
  path.dataset.toId = toCard.dataset.cardId || toCard.id || "";
  path.dataset.fromPos = fromPos;
  path.dataset.toPos = toPos;

  path.addEventListener("dblclick", function (e) {
    e.stopPropagation();
    const idx = connections.findIndex((c) => c.lineElement === this);
    if (idx !== -1) {
      const conn = connections[idx];
      if (conn.arrowElement) conn.arrowElement.remove();
      this.remove();
      connections.splice(idx, 1);
      if (conn.deleteBtn) conn.deleteBtn.remove();
    }
  });

  svg.appendChild(path);

  const conn = {
    fromCard,
    fromPos,
    toCard,
    toPos,
    lineElement: path,
    arrowElement: null,
    deleteBtn: null,
  };
  connections.push(conn);
  updateConnection(conn);
  return conn;
}

// ---- Função principal de atualização da curva (robusta) ----
function updateConnection(conn) {
  const from = getPortPosition(conn.fromCard, conn.fromPos);
  const to = getPortPosition(conn.toCard, conn.toPos);
  const x1 = from.x,
    y1 = from.y;
  const x2 = to.x,
    y2 = to.y;

  const dFrom = getDirection(conn.fromPos);
  const dTo = getDirection(conn.toPos);

  const dx = x2 - x1,
    dy = y2 - y1;
  const dist = Math.sqrt(dx * dx + dy * dy);
  if (dist < 20) {
    conn.lineElement.setAttribute("d", `M ${x1} ${y1} L ${x2} ${y2}`);
    conn.lineElement.classList.add("connection-line");
    if (conn.arrowElement) {
      conn.arrowElement.remove();
      conn.arrowElement = null;
    }
    return;
  }

  const curve = Math.min(dist * 0.45, 180);
  let c1x = x1 + dFrom.x * curve;
  let c1y = y1 + dFrom.y * curve;
  let c2x = x2 + dTo.x * curve;
  let c2y = y2 + dTo.y * curve;

  // --- DETECÇÃO DE COLISÃO usando coordenadas lógicas (offset) ---
  const cards = document.querySelectorAll(".editable-item");
  const obstacles = [];
  cards.forEach((card) => {
    if (card === conn.fromCard || card === conn.toCard) return;
    // Usa offsetLeft/Top que são relativos ao container (sem transformação)
    const left = card.offsetLeft;
    const top = card.offsetTop;
    const right = left + card.offsetWidth;
    const bottom = top + card.offsetHeight;
    // Adiciona uma margem de segurança para evitar que a linha toque o card
    const margin = 10; // pixels de folga
    obstacles.push({
      left: left - margin,
      top: top - margin,
      right: right + margin,
      bottom: bottom + margin,
    });
  });

  function curveCollides(c1x, c1y, c2x, c2y) {
    const steps = 20;
    for (let i = 0; i <= steps; i++) {
      const t = i / steps;
      const mt = 1 - t;
      const px =
        mt * mt * mt * x1 +
        3 * mt * mt * t * c1x +
        3 * mt * t * t * c2x +
        t * t * t * x2;
      const py =
        mt * mt * mt * y1 +
        3 * mt * mt * t * c1y +
        3 * mt * t * t * c2y +
        t * t * t * y2;
      for (let obs of obstacles) {
        if (
          px >= obs.left &&
          px <= obs.right &&
          py >= obs.top &&
          py <= obs.bottom
        )
          return true;
      }
    }
    return false;
  }

  if (curveCollides(c1x, c1y, c2x, c2y)) {
    const mx = (x1 + x2) / 2,
      my = (y1 + y2) / 2;
    const len = Math.sqrt(dx * dx + dy * dy);
    const nx = -dy / len,
      ny = dx / len;
    const offset = Math.min(dist * 0.55, 150);
    let bestScore = Infinity,
      bestX = mx,
      bestY = my;
    for (let sign of [-1, 1]) {
      const px = mx + nx * offset * sign;
      const py = my + ny * offset * sign;
      let score = 0;
      for (let obs of obstacles) {
        const cx = Math.max(obs.left, Math.min(px, obs.right));
        const cy = Math.max(obs.top, Math.min(py, obs.bottom));
        const dx2 = px - cx,
          dy2 = py - cy;
        const dist2 = dx2 * dx2 + dy2 * dy2;
        if (dist2 < 10000) score += 1000 / (dist2 + 1);
      }
      if (score < bestScore) {
        bestScore = score;
        bestX = px;
        bestY = py;
      }
    }
    const c1x2 = (x1 + bestX) / 2;
    const c1y2 = (y1 + bestY) / 2;
    const c2x2 = (x2 + bestX) / 2;
    const c2y2 = (y2 + bestY) / 2;
    if (curveCollides(c1x2, c1y2, c2x2, c2y2)) {
      const offset2 = Math.min(dist * 0.8, 220);
      for (let sign of [-1, 1]) {
        const px2 = mx + nx * offset2 * sign;
        const py2 = my + ny * offset2 * sign;
        const c1x3 = (x1 + px2) / 2;
        const c1y3 = (y1 + py2) / 2;
        const c2x3 = (x2 + px2) / 2;
        const c2y3 = (y2 + py2) / 2;
        if (!curveCollides(c1x3, c1y3, c2x3, c2y3)) {
          c1x = c1x3;
          c1y = c1y3;
          c2x = c2x3;
          c2y = c2y3;
          break;
        }
      }
      if (curveCollides(c1x, c1y, c2x, c2y)) {
        c1x = c1x2;
        c1y = c1y2;
        c2x = c2x2;
        c2y = c2y2;
      }
    } else {
      c1x = c1x2;
      c1y = c1y2;
      c2x = c2x2;
      c2y = c2y2;
    }
  }

  const pathD = `M ${x1} ${y1} C ${c1x} ${c1y}, ${c2x} ${c2y}, ${x2} ${y2}`;
  conn.lineElement.setAttribute("d", pathD);
  conn.lineElement.classList.add("connection-line");

  // --- desenha a seta (mesmo código) ---
  if (conn.arrowElement) {
    conn.arrowElement.remove();
    conn.arrowElement = null;
  }
  const t = 0.99;
  const mt = 1 - t;
  const px_ant =
    mt * mt * mt * x1 +
    3 * mt * mt * t * c1x +
    3 * mt * t * t * c2x +
    t * t * t * x2;
  const py_ant =
    mt * mt * mt * y1 +
    3 * mt * mt * t * c1y +
    3 * mt * t * t * c2y +
    t * t * t * y2;
  const dx_t = x2 - px_ant;
  const dy_t = y2 - py_ant;
  let angle = Math.atan2(dy_t, dx_t);
  const dot = dx_t * dx + dy_t * dy;
  if (dot < 0) angle += Math.PI;

  const arrowSize = 3;
  const a1 = angle - Math.PI / 6;
  const a2 = angle + Math.PI / 6;

  const tipX = x2,
    tipY = y2;
  const leftX = tipX - arrowSize * Math.cos(a1);
  const leftY = tipY - arrowSize * Math.sin(a1);
  const rightX = tipX - arrowSize * Math.cos(a2);
  const rightY = tipY - arrowSize * Math.sin(a2);

  const arrow = document.createElementNS("http://www.w3.org/2000/svg", "path");
  arrow.setAttribute(
    "d",
    `M ${tipX},${tipY} L ${leftX},${leftY} L ${rightX},${rightY} Z`,
  );
  arrow.setAttribute("fill", "#ff6b6b");
  arrow.style.pointerEvents = "none";
  arrow.style.filter = "drop-shadow(0 0 3px rgba(255, 107, 107, 0.5))";

  const svg = getSvg();
  if (svg) {
    svg.appendChild(arrow);
    conn.arrowElement = arrow;
  }
}

function updateAllConnections() {
  if (isEditing) return; // ← ADICIONE
  connections.forEach((conn) => updateConnection(conn));
}

function clearAllConnections() {
  connections.forEach((c) => {
    c.lineElement.remove();
    if (c.arrowElement) c.arrowElement.remove();
    if (c.deleteBtn) c.deleteBtn.remove();
  });
  connections = [];
  window._selectedLine = null;
}

// ---- Adiciona portas aos cards (chamado ao criar) ----
function addPorts(card) {
  const positions = ["top", "bottom", "left", "right"];
  positions.forEach((pos) => {
    const port = document.createElement("div");
    port.className = "flow-port";
    port.dataset.position = pos;
    port.style.cssText = `
              position: absolute;
              width: 18px;
              height: 18px;
              background: #888;
              border-radius: 50%;
              border: 2px solid #555;
              cursor: crosshair;
              z-index: 20;
              pointer-events: all;
              transition: background 0.2s, transform 0.2s;
              box-shadow: 0 0 8px rgba(0,0,0,0.6);
              user-select: none;
          `;
    switch (pos) {
      case "top":
        port.style.top = "-9px";
        port.style.left = "calc(50% - 9px)";
        break;
      case "bottom":
        port.style.bottom = "-9px";
        port.style.left = "calc(50% - 9px)";
        break;
      case "left":
        port.style.left = "-9px";
        port.style.top = "calc(50% - 9px)";
        break;
      case "right":
        port.style.right = "-9px";
        port.style.top = "calc(50% - 9px)";
        break;
    }
    port.addEventListener("mouseenter", () => {
      port.style.transform = "scale(1.4)";
      port.style.background = "#8b5cf6";
    });
    port.addEventListener("mouseleave", () => {
      port.style.transform = "scale(1)";
      port.style.background = "#888";
    });
    port.addEventListener("mousedown", startConnection);
    card.appendChild(port);
  });
}

// ---- Início da conexão (arraste) ----
function startConnection(e) {
  e.preventDefault();
  e.stopPropagation();
  const port = e.currentTarget;
  const card = port.closest(".editable-item");
  const pos = port.dataset.position;
  connectionStart = { card, position: pos, portElement: port };

  const svg = getSvg();
  if (!svg) {
    // Se o SVG não existir, cria
    inicializarSvg();
    svg = getSvg();
    if (!svg) return;
  }

  // 🔥 GARANTE QUE O DEFS EXISTE (gradiente e marcador)
  if (!svg.querySelector("defs")) {
    const defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
    // Gradiente
    const grad = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "linearGradient",
    );
    grad.setAttribute("id", "gradient-stroke");
    grad.setAttribute("x1", "0%");
    grad.setAttribute("y1", "0%");
    grad.setAttribute("x2", "100%");
    grad.setAttribute("y2", "100%");
    const stop1 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "stop",
    );
    stop1.setAttribute("offset", "0%");
    stop1.setAttribute("style", "stop-color:#0d6efd;stop-opacity:1");
    const stop2 = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "stop",
    );
    stop2.setAttribute("offset", "100%");
    stop2.setAttribute("style", "stop-color:#6f42c1;stop-opacity:1");
    grad.appendChild(stop1);
    grad.appendChild(stop2);
    defs.appendChild(grad);
    // Marcador
    // Marcador de seta (tamanho pequeno)
    const marker = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "marker",
    );
    marker.setAttribute("id", "arrowhead");
    marker.setAttribute("markerWidth", "4");
    marker.setAttribute("markerHeight", "4");
    marker.setAttribute("refX", "3");
    marker.setAttribute("refY", "2");
    marker.setAttribute("orient", "auto");
    const poly = document.createElementNS(
      "http://www.w3.org/2000/svg",
      "polygon",
    );
    poly.setAttribute("points", "0 0, 4 2, 0 4");
    poly.setAttribute("fill", "#ff6b6b");
    marker.appendChild(poly);
    defs.appendChild(marker);
    svg.prepend(defs);
  }

  const startRect = port.getBoundingClientRect();
  const containerRect = document
    .getElementById("cards-container")
    .getBoundingClientRect();

  // Cria a linha temporária com TODOS os estilos inline
  tempLine = document.createElementNS("http://www.w3.org/2000/svg", "path");
  tempLine.setAttribute("id", "temp-line");
  tempLine.setAttribute("stroke", "url(#gradient-stroke)");
  tempLine.setAttribute("stroke-width", "4");
  tempLine.setAttribute("fill", "none");
  tempLine.setAttribute("stroke-dasharray", "12 8");

  // 🔥 ANIMAÇÃO FORÇADA VIA JavaScript (não confia no CSS)
  tempLine.style.animation = "dash-move 0.8s linear infinite !important";
  tempLine.style.pointerEvents = "none";
  tempLine.style.filter = "drop-shadow(0 0 6px rgba(13, 110, 253, 0.6))";

  const x1 = startRect.left - containerRect.left + 9;
  const y1 = startRect.top - containerRect.top + 9;
  tempLine.setAttribute("d", `M ${x1} ${y1} Q ${x1} ${y1} ${x1} ${y1}`);
  svg.appendChild(tempLine);

  port.style.transform = "scale(1.6)";
  port.style.background = "#8b5cf6";
  port.style.boxShadow = "0 0 20px rgba(139, 92, 246, 0.8)";

  document
    .querySelectorAll(".flow-port.drag-target")
    .forEach((p) => p.classList.remove("drag-target"));
  document.addEventListener("mousemove", moveConnection);
  document.addEventListener("mouseup", endConnection);
}

function moveConnection(e) {
  if (!tempLine || !connectionStart) return;
  const svg = getSvg();
  if (!svg) return;

  const container = document.getElementById("cards-container");
  const containerRect = container.getBoundingClientRect();
  const scale = containerRect.width / container.offsetWidth; // fator de zoom

  // Posição da porta de origem em pixels CSS (offsetLeft/offsetTop)
  const port = connectionStart.portElement;
  const card = port.closest(".editable-item");
  const x1 = card.offsetLeft + port.offsetLeft + port.offsetWidth / 2;
  const y1 = card.offsetTop + port.offsetTop + port.offsetHeight / 2;

  // Posição do mouse convertida para pixels CSS
  const x2 = (e.clientX - containerRect.left) / scale;
  const y2 = (e.clientY - containerRect.top) / scale;

  // Ponto de controle da curva
  const cx = (x1 + x2) / 2;
  const cy = (y1 + y2) / 2 - 30;

  tempLine.setAttribute("d", `M ${x1} ${y1} Q ${cx} ${cy} ${x2} ${y2}`);

  // Destaca a porta alvo (sem alterações)
  const elements = document.elementsFromPoint(e.clientX, e.clientY);
  let targetPort = null;
  for (let el of elements) {
    if (el.classList && el.classList.contains("flow-port")) {
      targetPort = el;
      break;
    }
  }
  document
    .querySelectorAll(".flow-port.drag-target")
    .forEach((p) => p.classList.remove("drag-target"));
  if (targetPort && targetPort !== connectionStart.portElement) {
    targetPort.classList.add("drag-target");
  }
  // Destaca o card alvo também
  document
    .querySelectorAll(".drag-target-card")
    .forEach((el) => el.classList.remove("drag-target-card"));
  if (targetPort) {
    const targetCard = targetPort.closest(".editable-item");
    if (targetCard) targetCard.classList.add("drag-target-card");
  }
}

function endConnection(e) {
  document.removeEventListener("mousemove", moveConnection);
  document.removeEventListener("mouseup", endConnection);

  if (tempLine) {
    tempLine.remove();
    tempLine = null;
  }

  if (connectionStart && connectionStart.portElement) {
    connectionStart.portElement.style.transform = "scale(1)";
    connectionStart.portElement.style.background = "#888";
    connectionStart.portElement.style.boxShadow = "0 0 8px rgba(0,0,0,0.6)";
  }

  if (!connectionStart) {
    connectionStart = null;
    return;
  }

  const originCard = connectionStart.card;
  const originPos = connectionStart.position;

  // Tenta encontrar uma porta sob o mouse
  const elements = document.elementsFromPoint(e.clientX, e.clientY);
  let targetPort = null;
  for (let el of elements) {
    if (el.classList && el.classList.contains("flow-port")) {
      targetPort = el;
      break;
    }
  }

  let targetCard = null;
  let targetPos = null;

  if (targetPort) {
    // Caso 1: mouse sobre uma porta
    targetCard = targetPort.closest(".editable-item");
    targetPos = targetPort.dataset.position;
  } else {
    // Caso 2: mouse não está sobre porta → busca card mais próximo
    const closestCard = findClosestCard(e.clientX, e.clientY, originCard);
    if (closestCard) {
      targetCard = closestCard;
      // Encontra a porta mais próxima do ponto de soltura
      targetPos = findClosestPort(closestCard, e.clientX, e.clientY);
    }
  }

  // Verifica se encontrou um card alvo válido e diferente do card de origem
  if (targetCard && targetCard !== originCard && targetPos) {
    pushState();
    const conn = createConnection(originCard, originPos, targetCard, targetPos);
    if (conn) {
      // Feedback visual na porta alvo
      const portElements = targetCard.querySelectorAll(".flow-port");
      portElements.forEach((port) => {
        if (port.dataset.position === targetPos) {
          port.style.background = "#2ea043";
          port.style.transform = "scale(1.8)";
          port.style.boxShadow = "0 0 30px rgba(46, 160, 67, 0.8)";
          setTimeout(() => {
            port.style.background = "#888";
            port.style.transform = "scale(1)";
            port.style.boxShadow = "0 0 8px rgba(0,0,0,0.6)";
          }, 400);
        }
      });
    }
  }

  // Limpeza
  document
    .querySelectorAll(".flow-port.drag-target")
    .forEach((p) => p.classList.remove("drag-target"));
  document
    .querySelectorAll(".drag-target-card")
    .forEach((el) => el.classList.remove("drag-target-card"));
  connectionStart = null;
}

// ---- Atualiza tamanho do SVG ----
function updateSvgSize() {
  const svg = getSvg();
  const container = document.getElementById("cards-container");
  if (!svg || !container) return;
  const w = container.offsetWidth;
  const h = container.offsetHeight;
  if (w > 0 && h > 0) {
    svg.setAttribute("width", w);
    svg.setAttribute("height", h);
    updateAllConnections();
  }
}
// ---- Inicialização (chamar no DOMContentLoaded) ----
function initSvgSystem() {
  const svg = inicializarSvg();
  if (svg) {
    window.svg = svg;
    setTimeout(updateSvgSize, 50);
    window.addEventListener("resize", updateSvgSize);
    const ro = new ResizeObserver(() => updateSvgSize());
    ro.observe(document.getElementById("cards-container"));
  }
}

// ---- Inicialização (chamar apenas uma vez) ----
document.addEventListener("DOMContentLoaded", function () {
  // Obtém o container (já deve existir no DOM)
  const container = document.getElementById("cards-container");
  if (!container) return;

  // 1. Garante o SVG
  const svg = inicializarSvg();
  if (svg) {
    ensureDefs(svg);
    window.svg = svg;
    setTimeout(updateSvgSize, 50);
    window.addEventListener("resize", updateSvgSize);

    // Observa o container para redimensionamento
    const resizeObserver = new ResizeObserver(() => {
      if (isEditing) return;
      updateSvgSize();
    });
    resizeObserver.observe(container);
  }
});

// ---- Inicialização ÚNICA ----
document.addEventListener("DOMContentLoaded", function () {
  // 🔥 DECLARE O CONTAINER AQUI, ANTES DE QUALQUER USO
  const container = document.getElementById("cards-container");
  if (!container) {
    console.warn("⚠️ Container não encontrado");
    return;
  }

  // 1. Garante o SVG
  const svg = inicializarSvg();
  if (svg) {
    ensureDefs(svg);
    window.svg = svg;
    setTimeout(updateSvgSize, 50);
    window.addEventListener("resize", updateSvgSize);

    // Observa o container para redimensionamento
    const resizeObserver = new ResizeObserver(() => {
      if (isEditing) return;
      updateSvgSize();
    });
    resizeObserver.observe(container); // <-- container já existe aqui
  }

  // 2. Seleção por Lasso (sem redeclarar container)
  container.addEventListener("mousedown", function (e) {
    const target = e.target;
    if (target.closest(".editable-item")) return;
    if (target.closest(".flow-port")) return;
    if (e.shiftKey || e.ctrlKey || e.metaKey) return;

    const rect = container.getBoundingClientRect();
    const scaleX = rect.width / container.offsetWidth;
    const scaleY = rect.height / container.offsetHeight;
    isLassoSelecting = true;
    lassoStartX = (e.clientX - rect.left) / scaleX;
    lassoStartY = (e.clientY - rect.top) / scaleY;

    lassoElement = createLassoElement();
    if (lassoElement) {
      lassoElement.style.left = lassoStartX + "px";
      lassoElement.style.top = lassoStartY + "px";
      lassoElement.style.width = "0px";
      lassoElement.style.height = "0px";
      lassoElement.style.display = "block";
    }

    clearSelection();

    document.addEventListener("mousemove", onLassoMove);
    document.addEventListener("mouseup", onLassoUp);
  });

  // ===== FUNÇÕES DO LASSO =====
  function onLassoMove(e) {
    if (!isLassoSelecting || !lassoElement) return;
    const rect = container.getBoundingClientRect();
    const scaleX = rect.width / container.offsetWidth;
    const scaleY = rect.height / container.offsetHeight;
    const currentX = (e.clientX - rect.left) / scaleX;
    const currentY = (e.clientY - rect.top) / scaleY;

    const left = Math.min(lassoStartX, currentX);
    const top = Math.min(lassoStartY, currentY);
    const width = Math.abs(currentX - lassoStartX);
    const height = Math.abs(currentY - lassoStartY);

    lassoElement.style.left = left + "px";
    lassoElement.style.top = top + "px";
    lassoElement.style.width = width + "px";
    lassoElement.style.height = height + "px";

    const cards = document.querySelectorAll(".editable-item");
    const lassoRect = lassoElement.getBoundingClientRect();
    cards.forEach((card) => {
      const cardRect = card.getBoundingClientRect();
      const intersect = !(
        cardRect.right < lassoRect.left ||
        cardRect.left > lassoRect.right ||
        cardRect.bottom < lassoRect.top ||
        cardRect.top > lassoRect.bottom
      );
      if (intersect) {
        selectCard(card);
      } else {
        deselectCard(card);
      }
    });
  }

  function onLassoUp(e) {
    isLassoSelecting = false;
    document.removeEventListener("mousemove", onLassoMove);
    document.removeEventListener("mouseup", onLassoUp);
    if (lassoElement) {
      lassoElement.style.display = "none";
    }
    if (selectedItems.length === 0) {
      clearSelection();
    }
  }

  // Tecla ESC para limpar seleção
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      clearSelection();
    }
  });
});
