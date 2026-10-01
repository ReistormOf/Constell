// ============================================================
//  FUNÇÕES DA BARRA LATERAL DIREITA (ADICIONAR ELEMENTOS)
// ============================================================

let selectedSize = "col-md-4";

function addParagraph() {
  pushState(); // <-- SALVA ESTADO ANTES DE CRIAR
  const colDiv = document.createElement("div");
  colDiv.classList.add("editable-item");
  colDiv.style.position = "absolute";
  // Passe a largura/altura aproximada do card (opcional)
  const pos = getVisiblePosition(300, 200); // ajuste conforme o tipo de card
  colDiv.style.left = pos.left + "px";
  colDiv.style.top = pos.top + "px";
  colDiv.style.width = "auto";
  colDiv.style.maxWidth = "90%";
  const p = document.createElement("p");
  p.innerHTML =
    "Selecione uma palavra, Shift+Clique para marcar alvos, aplique estilo na barra lateral.";
  p.style.padding = "8px";
  p.style.margin = "0";
  enableEditOnDoubleClick(p, plainTextOnBlur);
  colDiv.appendChild(p);
  ensureCardId(colDiv); // <-- ADICIONE AQUI
  addDeleteButton(colDiv);
  addDragHandle(colDiv);
  addPorts(colDiv);
  addResizeHandle(colDiv);
  document.getElementById("cards-container").appendChild(colDiv);
  // ⬇️ ADICIONE AQUI
  colDiv.classList.add("float-in");
  setTimeout(() => colDiv.classList.remove("float-in"), 700);
  initializeCard(colDiv);
  updateContainerHeight();
}

function addEmptyExplanation() {
  pushState(); // <-- SALVA ESTADO ANTES DE CRIAR
  const colDiv = document.createElement("div");
  colDiv.classList.add("editable-item");
  colDiv.style.position = "absolute";
  const pos = getVisiblePosition(400, 150);
  colDiv.style.left = pos.left + "px";
  colDiv.style.top = pos.top + "px";
  colDiv.style.width = "auto";
  colDiv.style.maxWidth = "90%";
  const vscodeExplanationDiv = document.createElement("div");
  vscodeExplanationDiv.classList.add("vscode-explanation");
  const h3 = document.createElement("h3");
  h3.innerHTML =
    "Selecione uma palavra (mouse) | Shift+Clique para marcar alvos | Aplique estilo na barra lateral";
  const p1 = document.createElement("p");
  p1.innerHTML = "Ctrl+Clique para editar o texto...";
  enableEditOnDoubleClick(h3, plainTextOnBlur);
  enableEditOnDoubleClick(p1, plainTextOnBlur);
  vscodeExplanationDiv.appendChild(h3);
  vscodeExplanationDiv.appendChild(p1);
  colDiv.appendChild(vscodeExplanationDiv);
  ensureCardId(colDiv); // <-- ADICIONE AQUI
  addDeleteButton(colDiv);
  addDragHandle(colDiv);
  addPorts(colDiv);
  addResizeHandle(colDiv);
  document.getElementById("cards-container").appendChild(colDiv);
  // ⬇️ ADICIONE AQUI
  colDiv.classList.add("float-in");
  setTimeout(() => colDiv.classList.remove("float-in"), 700);
  initializeCard(colDiv);
  updateContainerHeight();
}

function addEmptyList() {
  pushState(); // <-- SALVA ESTADO ANTES DE CRIAR
  const colDiv = document.createElement("div");
  colDiv.classList.add("editable-item");
  colDiv.style.position = "absolute";
  const pos = getVisiblePosition(300, 80);
  colDiv.style.left = pos.left + "px";
  colDiv.style.top = pos.top + "px";
  colDiv.style.width = "auto";
  colDiv.style.maxWidth = "90%";
  const ul = document.createElement("ul");
  ul.classList.add("styled-list", "list-group");
  const li = document.createElement("li");
  li.classList.add(
    "list-group-item",
    "d-flex",
    "justify-content-between",
    "align-items-center",
  );
  li.innerHTML = `<span><strong>Item:</strong> Selecione uma palavra, Shift+Clique para marcar alvos</span>`;
  enableEditOnDoubleClick(li, plainTextOnBlur);
  ul.appendChild(li);
  colDiv.appendChild(ul);
  ensureCardId(colDiv); // <-- ADICIONE AQUI
  addDeleteButton(colDiv);
  addDragHandle(colDiv);
  addPorts(colDiv);
  addResizeHandle(colDiv);
  document.getElementById("cards-container").appendChild(colDiv);
  // ⬇️ ADICIONE AQUI
  colDiv.classList.add("float-in");
  setTimeout(() => colDiv.classList.remove("float-in"), 700);
  initializeCard(colDiv);
  updateContainerHeight();
}

function addEmptyTitle(level) {
  pushState(); // <-- SALVA ESTADO ANTES DE CRIAR
  const titleText = `Título H${level} (Selecione uma palavra, Shift+Clique para marcar alvos)`;
  const titleElement = document.createElement(`h${level}`);
  titleElement.innerHTML = titleText;
  enableEditOnDoubleClick(titleElement, plainTextOnBlur);
  const titleWrapper = document.createElement("div");
  titleWrapper.classList.add("editable-item");
  titleWrapper.style.position = "absolute";
  const pos = getVisiblePosition(300, 60);
  titleWrapper.style.left = pos.left + "px";
  titleWrapper.style.top = pos.top + "px";
  titleWrapper.style.width = "auto"; // ← aqui!
  titleWrapper.style.maxWidth = "90%";
  titleWrapper.appendChild(titleElement);
  addDeleteButton(titleWrapper);
  addDragHandle(titleWrapper);
  addResizeHandle(titleWrapper);
  addPorts(titleWrapper);
  document.getElementById("cards-container").appendChild(titleWrapper);
  titleWrapper.classList.add("float-in");
  setTimeout(() => titleWrapper.classList.remove("float-in"), 700);
  initializeCard(titleWrapper);
  updateContainerHeight();
}
function addBlockquote() {
  pushState();
  const colDiv = document.createElement("div");
  colDiv.classList.add("editable-item");
  colDiv.style.position = "absolute";
  const pos = getVisiblePosition(400, 120);
  colDiv.style.left = pos.left + "px";
  colDiv.style.top = pos.top + "px";
  colDiv.style.width = "auto";
  colDiv.style.maxWidth = "90%";

  const blockquote = document.createElement("blockquote");
  blockquote.style.cssText = `
    border-left: 4px solid #4a7cf7;
    padding: 12px 20px;
    margin: 0;
    background: rgba(74, 124, 247, 0.05);
    border-radius: 4px;
    font-style: italic;
    color: var(--text-secondary);
  `;
  blockquote.innerHTML = `<p>"Digite sua citação aqui."</p><footer>— Autor</footer>`;
  // Tornar editável
  enableEditOnDoubleClick(blockquote, plainTextOnBlur);
  colDiv.appendChild(blockquote);

  ensureCardId(colDiv); // <-- ADICIONE AQUI
  addDeleteButton(colDiv);
  addDragHandle(colDiv);
  addPorts(colDiv);
  addResizeHandle(colDiv);
  document.getElementById("cards-container").appendChild(colDiv);
  colDiv.classList.add("float-in");
  setTimeout(() => colDiv.classList.remove("float-in"), 700);
  initializeCard(colDiv);
  updateContainerHeight();
}

function addStickyNote() {
  pushState();
  const card = document.createElement("div");
  card.className = "editable-item";
  card.style.cssText =
    "position:absolute; left:120px; top:680px; width:240px; max-width:90%; transition:0.3s;";

  // Nota
  const note = document.createElement("div");
  note.className = "sticky-note";
  note.style.cssText = `
    background:#f9e076; color:#2d2d2d; padding:20px 18px 16px; border-radius:12px;
    box-shadow:0 8px 30px rgba(0,0,0,0.15), 0 2px 8px rgba(0,0,0,0.06);
    font-family:'Segoe UI', system-ui, sans-serif; min-height:140px;
    transition:0.3s; position:relative; border:1px solid rgba(255,255,255,0.3);
    cursor:pointer; word-wrap:break-word; line-height:1.6; font-size:14px;
  `;
  note.textContent = "Escreva sua nota aqui...";
  card.appendChild(note);

  // Toolbar
  const toolbar = document.createElement("div");
  toolbar.className = "sticky-toolbar";
  toolbar.style.cssText = `
    display:flex; flex-wrap:wrap; gap:4px; margin-top:8px; padding:6px 8px;
    background:var(--bg-elevated); border-radius:10px; border:1px solid var(--border-subtle);
    opacity:0; transform:translateY(-4px); transition:0.3s; pointer-events:none;
  `;

  // Botões de cor
  const colors = [
    { name: "Amarelo", value: "#f9e076" },
    { name: "Verde", value: "#b5e6b5" },
    { name: "Azul", value: "#a8d8ea" },
    { name: "Rosa", value: "#f7c5cc" },
    { name: "Lilás", value: "#d4b8d9" },
    { name: "Laranja", value: "#fad6a5" },
    { name: "Cinza", value: "#d4d4d4" },
    { name: "Branco", value: "#ffffff" },
  ];
  colors.forEach((c) => {
    const btn = document.createElement("button");
    btn.className = "color-btn";
    btn.dataset.color = c.value;
    btn.style.cssText = `
      width:24px; height:24px; border-radius:50%; background:${c.value};
      border:2px solid ${c.value === "#f9e076" ? "#4a7cf7" : "transparent"};
      cursor:pointer; transition:0.2s; box-shadow:${c.value === "#f9e076" ? "0 0 0 2px #4a7cf7" : "none"};
      padding:0; flex-shrink:0;
    `;
    btn.title = c.name;
    toolbar.appendChild(btn);
  });

  // Tamanho
  const sep1 = document.createElement("span");
  sep1.style.cssText =
    "width:1px; height:24px; background:var(--border-subtle); margin:0 4px;";
  toolbar.appendChild(sep1);

  const btnMinus = document.createElement("button");
  btnMinus.className = "font-size-btn";
  btnMinus.dataset.delta = "-2";
  btnMinus.textContent = "A−";
  btnMinus.style.cssText =
    "background:transparent; border:1px solid var(--border-subtle); color:var(--text-secondary); border-radius:4px; padding:2px 6px; cursor:pointer; font-size:12px; transition:0.2s;";
  toolbar.appendChild(btnMinus);

  const sizeSpan = document.createElement("span");
  sizeSpan.style.cssText =
    "font-size:11px; color:var(--text-muted); min-width:28px; text-align:center;";
  sizeSpan.textContent = "14px";
  toolbar.appendChild(sizeSpan);

  const btnPlus = document.createElement("button");
  btnPlus.className = "font-size-btn";
  btnPlus.dataset.delta = "2";
  btnPlus.textContent = "A+";
  btnPlus.style.cssText =
    "background:transparent; border:1px solid var(--border-subtle); color:var(--text-secondary); border-radius:4px; padding:2px 6px; cursor:pointer; font-size:12px; transition:0.2s;";
  toolbar.appendChild(btnPlus);

  const sep2 = document.createElement("span");
  sep2.style.cssText =
    "width:1px; height:24px; background:var(--border-subtle); margin:0 4px;";
  toolbar.appendChild(sep2);

  const pinBtn = document.createElement("button");
  pinBtn.className = "pin-btn";
  pinBtn.textContent = "📌";
  pinBtn.style.cssText =
    "background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:16px; padding:0 4px; transition:0.2s;";
  pinBtn.title = "Fixar";
  toolbar.appendChild(pinBtn);

  const resetBtn = document.createElement("button");
  resetBtn.className = "reset-btn";
  resetBtn.textContent = "↺";
  resetBtn.style.cssText =
    "background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; padding:0 4px; transition:0.2s;";
  resetBtn.title = "Redefinir nota";
  toolbar.appendChild(resetBtn);

  card.appendChild(toolbar);
  ensureCardId(card);
  // Botões comuns (deletar, duplicar, drag, resize, ports) – podem ser injetados por uma função auxiliar
  addCommonButtons(card);

  container.appendChild(card);
  card.classList.add("float-in");
  setTimeout(() => card.classList.remove("float-in"), 700);
  updateContainerHeight();
}

// Função auxiliar para adicionar botões comuns (delete, duplicate, drag, resize, ports)
function addCommonButtons(card) {
  // Não faz nada – todos os botões extras foram removidos
}

function addChecklist() {
  pushState();
  const card = document.createElement("div");
  card.className = "editable-item";
  card.style.position = "absolute";
  const pos = getVisiblePosition(340, 280);
  card.style.left = pos.left + "px";
  card.style.top = pos.top + "px";
  card.style.width = "340px";
  card.style.maxWidth = "90%";
  card.style.background = "var(--bg-surface)";
  card.style.borderRadius = "16px";
  card.style.padding = "20px 22px";
  card.style.boxShadow = "var(--shadow-card)";
  card.style.border = "1px solid var(--border-subtle)";
  card.style.transition = "all 0.3s ease";

  // ===== ESTADO =====
  let state = {
    type: "checklist",
    tasks: [
      {
        id: Date.now() + 1,
        text: "Definir objetivos do projeto",
        done: false,
        category: "📋 Planejamento",
      },
      {
        id: Date.now() + 2,
        text: "Criar wireframes",
        done: false,
        category: "🎨 Design",
      },
      {
        id: Date.now() + 3,
        text: "Desenvolver protótipo",
        done: false,
        category: "💻 Desenvolvimento",
      },
      {
        id: Date.now() + 4,
        text: "Testar com usuários",
        done: false,
        category: "🧪 QA",
      },
      {
        id: Date.now() + 5,
        text: "Lançar versão 1.0",
        done: false,
        category: "🚀 Lançamento",
      },
    ],
  };
  let filterText = "";

  // ===== FUNÇÃO SALVAR =====
  function saveState() {
    card.dataset.state = JSON.stringify(state);
    pushState();
  }

  // ===== RENDER =====
  function render() {
    const container = card.querySelector(".checklist-container");
    const progressFill = card.querySelector(".progress-fill");
    const progressText = card.querySelector(".progress-text");
    const counter = card.querySelector(".checklist-counter");
    if (!container) return;

    const filtered = filterText
      ? state.tasks.filter((t) =>
          t.text.toLowerCase().includes(filterText.toLowerCase()),
        )
      : state.tasks;

    container.innerHTML = "";
    if (filtered.length === 0) {
      container.innerHTML = `
        <div style="text-align:center; color:var(--text-muted); padding:20px 0; font-size:13px;">
          ${filterText ? "🔍 Nenhuma tarefa encontrada" : "🎯 Nenhuma tarefa cadastrada"}
        </div>
      `;
    } else {
      filtered.forEach((task, index) => {
        const realIndex = state.tasks.indexOf(task);
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
          ? `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`
          : "";
        checkboxWrapper.appendChild(checkbox);
        checkboxWrapper.appendChild(customCheckbox);

        // Conteúdo
        const contentWrapper = document.createElement("div");
        contentWrapper.style.cssText = `flex: 1; min-width: 0;`;
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
        textSpan.addEventListener("input", function () {
          task.text = this.textContent;
          saveState();
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
            "Digite a nova categoria (ex: 📋 Planejamento):",
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
          const idx = state.tasks.indexOf(task);
          if (idx > 0) {
            [state.tasks[idx], state.tasks[idx - 1]] = [
              state.tasks[idx - 1],
              state.tasks[idx],
            ];
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
          const idx = state.tasks.indexOf(task);
          if (idx < state.tasks.length - 1) {
            [state.tasks[idx], state.tasks[idx + 1]] = [
              state.tasks[idx + 1],
              state.tasks[idx],
            ];
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
          if (state.tasks.length <= 1) {
            alert("Não é possível remover a última tarefa.");
            return;
          }
          const idx = state.tasks.indexOf(task);
          if (idx !== -1) {
            state.tasks.splice(idx, 1);
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
    const total = state.tasks.length;
    const done = state.tasks.filter((t) => t.done).length;
    const pct = total > 0 ? Math.round((done / total) * 100) : 0;
    const progressFill = card.querySelector(".progress-fill");
    const progressText = card.querySelector(".progress-text");
    const counter = card.querySelector(".checklist-counter");
    if (progressFill) {
      progressFill.style.width = pct + "%";
      const color = pct === 100 ? "#4cd9a0" : pct > 50 ? "#4a7cf7" : "#ffc107";
      progressFill.style.background = `linear-gradient(90deg, ${color}, ${color}dd)`;
    }
    if (progressText) progressText.textContent = `${pct}%`;
    if (counter) counter.textContent = `${done}/${total} concluídos`;
  }

  // ===== HTML =====
  card.innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
      <span style="font-weight:600; color:var(--text-primary); font-size:16px;">✅ Checklist</span>
      <span class="checklist-counter" style="font-size:12px; color:var(--text-muted);">0/0 concluídos</span>
    </div>
    <div style="margin-bottom:12px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
        <span style="font-size:11px; color:var(--text-muted);">Progresso</span>
        <span class="progress-text" style="font-size:12px; font-weight:600; color:#4a7cf7;">0%</span>
      </div>
      <div style="width:100%; height:6px; background:var(--bg-elevated); border-radius:4px; overflow:hidden; border:1px solid var(--border-subtle);">
        <div class="progress-fill" style="width:0%; height:100%; background:linear-gradient(90deg, #4a7cf7, #6f42c1); border-radius:4px; transition:width 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);"></div>
      </div>
    </div>
    <div style="margin-bottom:10px;">
      <input type="text" class="search-tasks" placeholder="🔍 Buscar tarefa..." style="width:100%; padding:6px 12px; background:var(--bg-elevated); border:1px solid var(--border-subtle); border-radius:8px; color:var(--text-primary); font-size:13px; outline:none; transition:border-color 0.2s;">
    </div>
    <div class="checklist-container" style="display:flex; flex-direction:column; gap:2px; max-height:280px; overflow-y:auto; padding-right:2px;"></div>
    <button class="add-task-btn" style="margin-top:10px; background:transparent; border:2px dashed var(--border-subtle); color:var(--text-muted); border-radius:8px; padding:8px; width:100%; cursor:pointer; font-size:13px; transition:all 0.2s; font-weight:500;">
      + Adicionar tarefa
    </button>
  `;

  // ===== EVENTOS =====
  const searchInput = card.querySelector(".search-tasks");
  searchInput.addEventListener("input", function () {
    filterText = this.value;
    render();
  });

  const addBtn = card.querySelector(".add-task-btn");
  addBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    state.tasks.push({
      id: Date.now(),
      text: `Nova tarefa ${state.tasks.length + 1}`,
      done: false,
      category: "📌 Geral",
    });
    render();
    saveState();
    const container = card.querySelector(".checklist-container");
    if (container) container.scrollTop = container.scrollHeight;
  });

  render();
  saveState(); // <-- GRAVA ESTADO INICIAL

  // ===== ELEMENTOS COMUNS =====
  addDeleteButton(card);
  addDragHandle(card);
  card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
  addPorts(card);
  addResizeHandle(card);
  document.getElementById("cards-container").appendChild(card);
  card.classList.add("float-in");
  setTimeout(() => card.classList.remove("float-in"), 700);
  initializeCard(card);
  updateContainerHeight();
}

function addEmptyTable() {
  pushState(); // <-- SALVA ESTADO ANTES DE CRIAR
  const colDiv = document.createElement("div");
  colDiv.classList.add("editable-item");
  colDiv.style.position = "absolute";
  const pos = getVisiblePosition(500, 200);
  colDiv.style.left = pos.left + "px";
  colDiv.style.top = pos.top + "px";
  colDiv.style.width = "auto";
  colDiv.style.maxWidth = "90%";

  const tableResponsiveDiv = document.createElement("div");
  tableResponsiveDiv.classList.add("table-responsive");

  const table = document.createElement("table");
  // Dentro de renderTable, após encontrar a tabela:
  table.classList.add("table", "table-striped-columns", "styled-table");

  const thead = table.createTHead();
  const headerRow = thead.insertRow();
  const headers = ["#", "Nome", "Descrição", "Status"];
  headers.forEach((text) => {
    const th = document.createElement("th");
    th.textContent = text;
    th.contentEditable = true;
    enableEditOnDoubleClick(th, plainTextOnBlur);
    headerRow.appendChild(th);
  });

  const tbody = table.createTBody();
  const row = tbody.insertRow();
  const rowData = [
    "1",
    "Selecione uma palavra",
    "Shift+Clique para marcar alvos",
    '<span class="status active">Ativo</span>',
  ];
  rowData.forEach((html, idx) => {
    const cell = row.insertCell();
    cell.innerHTML = html;
    cell.contentEditable = true;
    enableEditOnDoubleClick(cell, plainTextOnBlur);
  });

  tableResponsiveDiv.appendChild(table);
  colDiv.appendChild(tableResponsiveDiv);

  const toolbarDiv = document.createElement("div");
  toolbarDiv.className = "d-flex gap-2 mt-2 flex-wrap table-toolbar hidden";
  toolbarDiv.innerHTML = `
        <button class="btn btn-sm btn-outline-primary" onclick="addRowToTable(this)">➕ Linha</button>
        <button class="btn btn-sm btn-outline-primary" onclick="addColumnToTable(this)">➕ Coluna</button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteRowFromTable(this)">➖ Linha</button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteColumnFromTable(this)">➖ Coluna</button>
        <button class="btn btn-sm btn-success" onclick="finalizeThisTable(this)">✅ Finalizar</button>
    `;
  colDiv.appendChild(toolbarDiv);

  ensureCardId(colDiv); // <-- ADICIONE AQUI
  addDeleteButton(colDiv);
  addDragHandle(colDiv);
  addPorts(colDiv);
  addResizeHandle(colDiv);
  document.getElementById("cards-container").appendChild(colDiv);
  // ⬇️ ADICIONE AQUI
  colDiv.classList.add("float-in");
  setTimeout(() => colDiv.classList.remove("float-in"), 700);
  initializeCard(colDiv);
  updateContainerHeight();
}

function addEmptyCodeCard() {
  pushState(); // <-- SALVA ESTADO ANTES DE CRIAR
  createCodeCard(
    "main.py",
    "# Selecione uma palavra, Shift+Clique para marcar alvos",
    "Aplique estilo na barra lateral",
  );
}

// ===== AUXILIARES TABELA =====
function getTableFromButton(btn) {
  const colDiv = btn.closest(".editable-item");
  if (!colDiv) return null;
  return colDiv.querySelector("table");
}

function getColumnCount(table) {
  const headerRow = table.tHead?.rows[0];
  if (headerRow) return headerRow.cells.length;
  const firstBodyRow = table.tBodies[0]?.rows[0];
  if (firstBodyRow) return firstBodyRow.cells.length;
  return 0;
}

function addRowToTable(btn) {
  pushState(); // <-- SALVA ESTADO ANTES DE MODIFICAR
  const table = getTableFromButton(btn);
  if (!table) return;
  const colCount = getColumnCount(table);
  if (colCount === 0) return;
  const tbody = table.tBodies[0];
  if (!tbody) return;
  const rowCount = tbody.rows.length + 1;
  const row = tbody.insertRow();
  for (let i = 0; i < colCount; i++) {
    const cell = row.insertCell();
    cell.textContent = i === 0 ? rowCount : "Novo Valor";
    cell.contentEditable = true;
    enableEditOnDoubleClick(cell, plainTextOnBlur);
  }
  const firstCell = row.cells[0];
  if (firstCell && !isNaN(firstCell.textContent)) {
    firstCell.textContent = rowCount;
  }
}

function addColumnToTable(btn) {
  pushState(); // <-- SALVA ESTADO ANTES DE MODIFICAR
  const table = getTableFromButton(btn);
  if (!table) return;
  const headerRow = table.tHead?.rows[0];
  if (!headerRow) return;
  const th = document.createElement("th");
  th.textContent = "Nova Coluna";
  th.contentEditable = true;
  enableEditOnDoubleClick(th, plainTextOnBlur);
  headerRow.appendChild(th);
  const tbody = table.tBodies[0];
  if (tbody) {
    for (const row of tbody.rows) {
      const cell = row.insertCell();
      cell.textContent = "Novo Valor";
      cell.contentEditable = true;
      enableEditOnDoubleClick(cell, plainTextOnBlur);
    }
  }
}

function deleteRowFromTable(btn) {
  pushState(); // <-- SALVA ESTADO ANTES DE MODIFICAR
  const table = getTableFromButton(btn);
  if (!table) return;
  const tbody = table.tBodies[0];
  if (!tbody) return;
  if (tbody.rows.length > 1) {
    tbody.deleteRow(tbody.rows.length - 1);
  } else {
    alert("Não é possível remover a última linha.");
  }
}

function deleteColumnFromTable(btn) {
  pushState(); // <-- SALVA ESTADO ANTES DE MODIFICAR
  const table = getTableFromButton(btn);
  if (!table) return;
  const headerRow = table.tHead?.rows[0];
  if (!headerRow) return;
  if (headerRow.cells.length > 1) {
    const columnIndex = headerRow.cells.length - 1;
    headerRow.deleteCell(columnIndex);
    const tbody = table.tBodies[0];
    if (tbody) {
      for (const row of tbody.rows) {
        if (row.cells.length > columnIndex) {
          row.deleteCell(columnIndex);
        }
      }
    }
  } else {
    alert("Não é possível remover a última coluna.");
  }
}

function finalizeThisTable(btn) {
  // NÃO CHAMA pushState() porque não altera a estrutura do card (apenas esconde toolbar e remove contenteditable)
  const colDiv = btn.closest(".editable-item");
  if (!colDiv) return;
  const table = colDiv.querySelector("table");
  if (!table) return;
  table.querySelectorAll('[contenteditable="true"]').forEach((cell) => {
    cell.removeAttribute("contenteditable");
  });
  const toolbar = colDiv.querySelector(".table-toolbar");
  if (toolbar) {
    toolbar.classList.add("hidden");
    toolbar.classList.remove("visible");
  }
}

// ============================================================
//  CODE CARD
// ============================================================
let num = 1;

function highlightCode(codeContent) {
  // ... (já existe, não precisa de pushState)
}

function createCodeCard(fileName, codeContent, terminalContent) {
  pushState(); // <-- SALVA ESTADO ANTES DE CRIAR
  const container = document.getElementById("cards-container");
  const card = document.createElement("div");
  card.className = "editable-item";
  card.style.position = "absolute";
  const pos = getVisiblePosition(500, 300);
  card.style.left = pos.left + "px";
  card.style.top = pos.top + "px";
  card.style.width = "auto";
  card.style.maxWidth = "90%";
  card.id = `card-${num}`;
  const formattedCodeContent = highlightCode(codeContent);
  card.innerHTML = `
        <div class="vscode-card">
            <div class="vscode-header">
                <div class="dots">
                    <div class="dot red"></div>
                    <div class="dot yellow"></div>
                    <div class="dot green"></div>
                </div>
                <span class="file-name">${fileName}</span>
            </div>
            <div class="vscode-content">
                <pre class="code-block">${formattedCodeContent}</pre>
            </div>
            <div class="vscode-terminal" id="terminal${num}">
                C:UserProject><br><br><pre class="terminal-content"><span>${terminalContent}</span></pre>
            </div>
            <div class="vscode-footer">
                <button id="btn-${num}" class="btn-executar">Executar Código</button>
            </div>
        </div>
    `;
  container.appendChild(card);
  card.classList.add("float-in");
  setTimeout(() => card.classList.remove("float-in"), 700);
  enableEditOnDoubleClick(card.querySelector(".file-name"), plainTextOnBlur);
  enableEditOnDoubleClick(card.querySelector(".code-block"), highlightOnBlur);
  enableEditOnDoubleClick(
    card.querySelector(".terminal-content"),
    plainTextOnBlur,
  );
  const btn = document.getElementById(`btn-${num}`);
  const terminal = document.getElementById(`terminal${num}`);
  btn.addEventListener("click", () => {
    terminal.style.display =
      terminal.style.display === "flex" ? "none" : "flex";
  });
  addDeleteButton(card);
  addDragHandle(card);
  addResizeHandle(card);
  card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
  addPorts(card);
  num++;
  initializeCard(card);
  updateContainerHeight();
  return card;
}

// ============================================================
//  NOVOS CARDS COMPLEXOS
// ============================================================

// 1. CARD DE IDENTIFICAÇÃO (Cabeçalho do Caderno)
function addProfileCard() {
  pushState();
  const card = document.createElement("div");
  card.className = "editable-item";
  card.style.position = "absolute";
  const pos = getVisiblePosition(320, 200);
  card.style.left = pos.left + "px";
  card.style.top = pos.top + "px";
  card.style.width = "320px";
  card.style.maxWidth = "90%";
  card.style.background = "var(--bg-surface)";
  card.style.borderRadius = "16px";
  card.style.padding = "0";
  card.style.boxShadow = "var(--shadow-card)";
  card.style.border = "1px solid var(--border-subtle)";
  // ⚠️ REMOVIDO: card.style.overflow = "hidden";
  card.style.transition = "all 0.3s ease";

  // Estado interno
  let state = {
    title: "Meu Caderno de Estudos",
    subtitle: "Matemática - 2024",
    author: "João Silva",
    date: new Date().toLocaleDateString("pt-BR"),
    description: "Anotações e resumos das aulas de Matemática.",
  };

  function render() {
    const titleEl = card.querySelector(".profile-title");
    const subtitleEl = card.querySelector(".profile-subtitle");
    const authorEl = card.querySelector(".profile-author");
    const dateEl = card.querySelector(".profile-date");
    const descEl = card.querySelector(".profile-desc");

    if (titleEl) titleEl.textContent = state.title;
    if (subtitleEl) subtitleEl.textContent = state.subtitle;
    if (authorEl) authorEl.textContent = `👤 ${state.author}`;
    if (dateEl) dateEl.textContent = `📅 ${state.date}`;
    if (descEl) descEl.textContent = state.description;
  }

  card.innerHTML = `
    <!-- Cabeçalho com gradiente -->
    <div style="padding:20px 24px 16px; background: linear-gradient(135deg, #4a7cf7, #6f42c1);">
      <div class="profile-title" contenteditable="true" style="font-size:20px; font-weight:700; color:#fff; text-align:center; background:transparent; border:none; outline:none; width:100%; padding:0;">Meu Caderno de Estudos</div>
      <div class="profile-subtitle" contenteditable="true" style="font-size:13px; opacity:0.85; color:#fff; text-align:center; background:transparent; border:none; outline:none; width:100%; padding:0;">Matemática - 2024</div>
    </div>
    
    <!-- Corpo -->
    <div style="padding:16px 20px 20px;">
      <!-- Descrição -->
      <div class="profile-desc" contenteditable="true" style="font-size:13px; color:var(--text-secondary); text-align:center; background:transparent; border:none; outline:none; width:100%; padding:6px 0 12px; border-bottom:1px solid var(--border-subtle); margin-bottom:12px;">Anotações e resumos das aulas de Matemática.</div>
      
      <!-- Informações úteis -->
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
        <div style="display:flex; align-items:center; gap:8px; background:var(--bg-elevated); border-radius:8px; padding:8px 12px; border:1px solid var(--border-subtle);">
          <span style="font-size:16px;">👤</span>
          <div>
            <div style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.3px;">Autor</div>
            <div class="profile-author" contenteditable="true" style="font-size:13px; font-weight:500; color:var(--text-primary); background:transparent; border:none; outline:none; width:100%; padding:0;">João Silva</div>
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:8px; background:var(--bg-elevated); border-radius:8px; padding:8px 12px; border:1px solid var(--border-subtle);">
          <span style="font-size:16px;">📅</span>
          <div>
            <div style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.3px;">Data</div>
            <div class="profile-date" contenteditable="true" style="font-size:13px; font-weight:500; color:var(--text-primary); background:transparent; border:none; outline:none; width:100%; padding:0;">${new Date().toLocaleDateString("pt-BR")}</div>
          </div>
        </div>
      </div>
    </div>
  `;

  // ===== TORNAR TODOS OS CAMPOS EDITÁVEIS =====
  const editables = card.querySelectorAll('[contenteditable="true"]');
  editables.forEach((el) => {
    const field = el.className.split("-")[1];
    el.addEventListener("input", function () {
      if (field === "title") state.title = this.textContent;
      else if (field === "subtitle") state.subtitle = this.textContent;
      else if (field === "desc") state.description = this.textContent;
      else if (field === "author") state.author = this.textContent;
      else if (field === "date") state.date = this.textContent;
      pushState();
    });
    enableEditOnDoubleClick(el, plainTextOnBlur);
  });

  // ===== INICIALIZA =====
  render();

  addDeleteButton(card);
  addDragHandle(card);
  card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
  addPorts(card);
  addResizeHandle(card);
  document.getElementById("cards-container").appendChild(card);
  card.classList.add("float-in");
  setTimeout(() => card.classList.remove("float-in"), 700);
  initializeCard(card);
  updateContainerHeight();
}

// 2. CARD DE MÉTRICAS (KPI) – com variação dinâmica baseada na meta
function addMetricsCard() {
  pushState();
  const card = document.createElement("div");
  card.className = "editable-item";
  card.style.position = "absolute";
  const pos = getVisiblePosition(320, 240);
  card.style.left = pos.left + "px";
  card.style.top = pos.top + "px";
  card.style.width = "320px";
  card.style.maxWidth = "90%";
  card.style.background = "var(--bg-surface)";
  card.style.borderRadius = "16px";
  card.style.padding = "20px 24px";
  card.style.boxShadow = "var(--shadow-card)";
  card.style.border = "1px solid var(--border-subtle)";
  card.style.transition = "all 0.3s ease";

  // ===== ESTADO =====
  let state = {
    type: "metrics",
    value: 12450,
    label: "Receita total",
    changeLabel: "vs meta",
    goal: 15000,
    goalLabel: "Meta",
    progress: 83,
    icon: "📊",
    color: "#4a7cf7",
  };

  // ===== SALVAR ESTADO =====
  function saveMetricsState() {
    card.dataset.state = JSON.stringify({ type: "metrics", ...state });
    pushState();
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(value);
  }

  function render() {
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

    if (valueDisplay) valueDisplay.textContent = formatCurrency(state.value);
    if (labelDisplay) labelDisplay.textContent = state.label;
    if (changeDisplay) {
      const diff = ((state.value - state.goal) / state.goal) * 100;
      const absDiff = Math.abs(diff);
      if (Math.abs(diff) < 0.1) {
        changeDisplay.textContent = `✓ Meta atingida!`;
        changeDisplay.style.color = "#4cd9a0";
      } else {
        changeDisplay.textContent = `${diff > 0 ? "▲" : "▼"} ${absDiff.toFixed(1)}%`;
        changeDisplay.style.color = diff >= 0 ? "#4cd9a0" : "#ff6b6b";
      }
    }
    if (changeLabelDisplay) changeLabelDisplay.textContent = state.changeLabel;
    if (goalDisplay) goalDisplay.textContent = formatCurrency(state.goal);
    if (goalLabelDisplay) goalLabelDisplay.textContent = state.goalLabel;
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
    if (iconDisplay) iconDisplay.textContent = state.icon;
    if (adjustInput)
      adjustInput.placeholder = `+/- (ex: ${Math.round(state.value * 0.05)})`;
  }

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
          render();
          saveMetricsState();
        } else {
          alert("Valor inválido. Use números apenas.");
        }
      }
    });
  }

  function adjustValue(delta) {
    state.value = Math.max(0, state.value + delta);
    state.progress = Math.min(
      100,
      Math.max(0, (state.value / state.goal) * 100),
    );
    render();
    saveMetricsState();
  }

  // ===== HTML =====
  card.innerHTML = `
    <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:14px;">
      <div class="metric-icon" style="font-size:30px; width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; background:rgba(74,124,247,0.08); flex-shrink:0; cursor:pointer;" title="Clique para trocar o ícone">📊</div>
      <div style="flex:1; min-width:0;">
        <div class="metric-value" style="font-size:24px; font-weight:700; color:var(--text-primary); letter-spacing:-0.5px; line-height:1.2; cursor:pointer; border-bottom:2px dotted transparent; transition:border-color 0.2s;" title="Clique para editar">$ 12.450</div>
        <div class="metric-label" style="font-size:13px; color:var(--text-muted); cursor:pointer; border-bottom:1px dotted transparent; transition:border-color 0.2s;" title="Clique para editar">Receita total</div>
        <div style="display:flex; align-items:center; gap:8px; margin-top:4px; flex-wrap:wrap;">
          <span class="metric-change" style="font-size:13px; font-weight:600; color:#4cd9a0;">▼ 17.0%</span>
          <span class="metric-change-label" style="font-size:12px; color:var(--text-muted);">vs meta</span>
        </div>
      </div>
    </div>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:4px;">
      <div style="background:var(--bg-elevated); border-radius:10px; padding:10px 12px; border:1px solid var(--border-subtle);">
        <div style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px;" class="metric-goal-label">Meta</div>
        <div class="metric-goal" style="font-size:16px; font-weight:600; color:var(--text-primary); cursor:pointer; border-bottom:2px dotted transparent; transition:border-color 0.2s;" title="Clique para editar">$ 15.000</div>
      </div>
      <div style="background:var(--bg-elevated); border-radius:10px; padding:10px 12px; border:1px solid var(--border-subtle);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2px;">
          <span style="font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Progresso</span>
          <span class="metric-progress" style="font-size:14px; font-weight:600; color:#4a7cf7; white-space:nowrap;">83.0%</span>
        </div>
        <div style="width:100%; height:5px; background:var(--bg-elevated); border-radius:3px; overflow:hidden; border:1px solid var(--border-subtle);">
          <div class="metric-progress-fill" style="width:83%; height:100%; background:linear-gradient(90deg, #4cd9a0, #4a7cf7); border-radius:3px; transition:width 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);"></div>
        </div>
      </div>
    </div>
    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border-subtle);">
      <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
        <input type="number" class="metric-adjust-input" placeholder="+/-" style="flex:1; min-width:60px; background:var(--bg-elevated); border:1px solid var(--border-subtle); border-radius:6px; color:var(--text-primary); padding:4px 8px; font-size:12px; outline:none; width:80px;">
        <button class="metric-apply-btn" style="background:var(--accent); color:#fff; border:none; border-radius:6px; padding:4px 12px; font-size:12px; cursor:pointer; font-weight:500;">Aplicar</button>
      </div>
      <div style="display:flex; gap:4px; margin-top:6px; flex-wrap:wrap;">
        <button class="metric-percent-btn" data-pct="5" style="background:var(--bg-elevated); border:1px solid var(--border-subtle); color:var(--text-secondary); border-radius:4px; padding:2px 10px; font-size:10px; cursor:pointer; transition:all 0.2s;">+5%</button>
        <button class="metric-percent-btn" data-pct="10" style="background:var(--bg-elevated); border:1px solid var(--border-subtle); color:var(--text-secondary); border-radius:4px; padding:2px 10px; font-size:10px; cursor:pointer; transition:all 0.2s;">+10%</button>
        <button class="metric-percent-btn" data-pct="25" style="background:var(--bg-elevated); border:1px solid var(--border-subtle); color:var(--text-secondary); border-radius:4px; padding:2px 10px; font-size:10px; cursor:pointer; transition:all 0.2s;">+25%</button>
        <button class="metric-percent-btn" data-pct="-5" style="background:var(--bg-elevated); border:1px solid var(--border-subtle); color:var(--text-secondary); border-radius:4px; padding:2px 10px; font-size:10px; cursor:pointer; transition:all 0.2s;">-5%</button>
        <button class="metric-percent-btn" data-pct="-10" style="background:var(--bg-elevated); border:1px solid var(--border-subtle); color:var(--text-secondary); border-radius:4px; padding:2px 10px; font-size:10px; cursor:pointer; transition:all 0.2s;">-10%</button>
        <button class="metric-percent-btn" data-pct="-25" style="background:var(--bg-elevated); border:1px solid var(--border-subtle); color:var(--text-secondary); border-radius:4px; padding:2px 10px; font-size:10px; cursor:pointer; transition:all 0.2s;">-25%</button>
      </div>
    </div>
  `;

  // ===== EVENTOS =====
  const adjustInput = card.querySelector(".metric-adjust-input");
  const applyBtn = card.querySelector(".metric-apply-btn");

  applyBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    const val = parseFloat(adjustInput.value);
    if (!isNaN(val) && val !== 0) {
      adjustValue(val);
      adjustInput.value = "";
    } else {
      alert("Digite um valor válido (ex: 500 ou -300)");
    }
  });

  adjustInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
      applyBtn.click();
    }
  });

  card.querySelectorAll(".metric-percent-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      const pct = parseFloat(this.dataset.pct) / 100;
      const delta = Math.round(state.value * pct);
      adjustValue(delta);
    });
    btn.addEventListener("mouseenter", function () {
      this.style.background = "var(--bg-hover)";
      this.style.borderColor = "var(--accent)";
    });
    btn.addEventListener("mouseleave", function () {
      this.style.background = "var(--bg-elevated)";
      this.style.borderColor = "var(--border-subtle)";
    });
  });

  const valueEl = card.querySelector(".metric-value");
  const labelEl = card.querySelector(".metric-label");
  const goalEl = card.querySelector(".metric-goal");

  makeEditable(
    valueEl,
    (val) => {
      state.value = val;
      state.progress = Math.min(
        100,
        Math.max(0, (state.value / state.goal) * 100),
      );
    },
    "Digite o novo valor (ex: 15000)",
  );

  makeEditable(
    goalEl,
    (val) => {
      state.goal = val;
      state.progress = Math.min(
        100,
        Math.max(0, (state.value / state.goal) * 100),
      );
    },
    "Digite a nova meta (ex: 20000)",
  );

  makeEditable(
    labelEl,
    (val) => {
      state.label = val;
    },
    "Digite o novo rótulo",
  );

  const iconEl = card.querySelector(".metric-icon");
  iconEl.addEventListener("click", function (e) {
    e.stopPropagation();
    const newIcon = prompt(
      "Digite o emoji ou ícone (ex: 💰, 📈, 🚀):",
      state.icon,
    );
    if (newIcon && newIcon.trim()) {
      state.icon = newIcon.trim();
      render();
      saveMetricsState();
    }
  });

  render();
  saveMetricsState(); // <-- GRAVA ESTADO INICIAL

  addDeleteButton(card);
  addDragHandle(card);
  card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
  addPorts(card);
  addResizeHandle(card);
  document.getElementById("cards-container").appendChild(card);
  card.classList.add("float-in");
  setTimeout(() => card.classList.remove("float-in"), 700);
  initializeCard(card);
  updateContainerHeight();
}

function addTimelineCard() {
  pushState();
  const card = document.createElement("div");
  card.className = "editable-item";
  card.style.position = "absolute";
  const pos = getVisiblePosition(340, 300);
  card.style.left = pos.left + "px";
  card.style.top = pos.top + "px";
  card.style.width = "340px";
  card.style.maxWidth = "90%";
  card.style.background = "var(--bg-surface)";
  card.style.borderRadius = "12px";
  card.style.padding = "16px 20px";
  card.style.boxShadow = "var(--shadow-card)";
  card.style.border = "1px solid var(--border-subtle)";
  card.style.overflow = "hidden";

  // ===== ESTADO =====
  let state = {
    type: "timeline",
    events: [
      {
        date: "Hoje",
        title: "Reunião de projeto",
        time: "10:00 - 11:30",
        done: false,
      },
      {
        date: "Amanhã",
        title: "Entrega de relatório",
        time: "Até 18:00",
        done: false,
      },
      {
        date: "Sexta",
        title: "Evento de lançamento",
        time: "19:00",
        done: false,
      },
    ],
  };

  // ===== SALVAR =====
  function saveState() {
    card.dataset.state = JSON.stringify(state);
    pushState();
  }

  function render() {
    const container = card.querySelector(".timeline-container");
    if (!container) return;
    container.innerHTML = "";

    state.events.forEach((ev, idx) => {
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

      const lineWrapper = document.createElement("div");
      lineWrapper.style.cssText = `
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 70px;
        position: relative;
      `;
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
      dateDiv.addEventListener("input", () => {
        ev.date = dateDiv.textContent;
        saveState();
      });
      lineWrapper.appendChild(dateDiv);

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
      if (idx < state.events.length - 1) {
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

      const contentDiv = document.createElement("div");
      contentDiv.style.cssText = `
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding-left: 4px;
        padding-bottom: ${idx < state.events.length - 1 ? "4px" : "0"};
        border-left: 2px solid ${ev.done ? "#4cd9a0" : "#4a7cf7"};
        padding-left: 12px;
        position: relative;
      `;

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
      titleDiv.addEventListener("input", () => {
        ev.title = titleDiv.textContent;
        saveState();
      });
      contentDiv.appendChild(titleDiv);

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
      timeDiv.addEventListener("input", () => {
        ev.time = timeDiv.textContent;
        saveState();
      });
      contentDiv.appendChild(timeDiv);

      item.appendChild(lineWrapper);
      item.appendChild(contentDiv);

      item
        .querySelector(".delete-event")
        .addEventListener("click", function (e) {
          e.stopPropagation();
          if (state.events.length <= 1) {
            alert("Não é possível remover o último evento.");
            return;
          }
          state.events.splice(idx, 1);
          render();
          saveState();
        });
      item.querySelector(".move-up").addEventListener("click", function (e) {
        e.stopPropagation();
        if (idx > 0) {
          [state.events[idx], state.events[idx - 1]] = [
            state.events[idx - 1],
            state.events[idx],
          ];
          render();
          saveState();
        }
      });
      item.querySelector(".move-down").addEventListener("click", function (e) {
        e.stopPropagation();
        if (idx < state.events.length - 1) {
          [state.events[idx], state.events[idx + 1]] = [
            state.events[idx + 1],
            state.events[idx],
          ];
          render();
          saveState();
        }
      });

      item.addEventListener("mouseenter", () => (actions.style.opacity = "1"));
      item.addEventListener("mouseleave", () => (actions.style.opacity = "0"));

      container.appendChild(item);
    });

    const counter = card.querySelector(".event-counter");
    if (counter) {
      const done = state.events.filter((e) => e.done).length;
      counter.textContent = `${done}/${state.events.length} concluídos`;
    }
  }

  // ===== HTML =====
  card.innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <span style="font-weight:600; color:var(--text-primary);">📅 Linha do Tempo</span>
      <span class="event-counter" style="font-size:12px; color:var(--text-muted);">0/0 concluídos</span>
    </div>
    <div class="timeline-container" style="display:flex; flex-direction:column; gap:4px; max-height:400px; overflow-y:auto; padding-right:4px;"></div>
    <button class="add-event-btn" style="margin-top:12px; background:transparent; border:1px dashed var(--border-subtle); color:var(--text-muted); border-radius:6px; padding:6px; width:100%; cursor:pointer; font-size:12px; transition:all 0.2s;">
      + Adicionar evento
    </button>
  `;

  // ===== EVENTO ADICIONAR =====
  card.querySelector(".add-event-btn").addEventListener("click", function (e) {
    e.stopPropagation();
    state.events.push({
      date: "Data",
      title: "Novo evento",
      time: "00:00",
      done: false,
    });
    render();
    saveState();
  });

  // Aplica edição nos textos iniciais
  card.querySelectorAll('[contenteditable="true"]').forEach((el) => {
    enableEditOnDoubleClick(el, plainTextOnBlur);
  });

  render();
  saveState(); // <-- GRAVA ESTADO INICIAL

  addDeleteButton(card);
  addDragHandle(card);
  card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
  addPorts(card);
  addResizeHandle(card);
  document.getElementById("cards-container").appendChild(card);
  card.classList.add("float-in");
  setTimeout(() => card.classList.remove("float-in"), 700);
  initializeCard(card);
  updateContainerHeight();
}

function addProgressCard() {
  pushState();
  const card = document.createElement("div");
  card.className = "editable-item";
  card.style.position = "absolute";
  const pos = getVisiblePosition(320, 220);
  card.style.left = pos.left + "px";
  card.style.top = pos.top + "px";
  card.style.width = "320px";
  card.style.maxWidth = "90%";
  card.style.background = "var(--bg-surface)";
  card.style.borderRadius = "12px";
  card.style.padding = "16px 20px";
  card.style.boxShadow = "var(--shadow-card)";
  card.style.border = "1px solid var(--border-subtle)";
  card.style.transition = "box-shadow 0.3s ease";

  // ===== ESTADO =====
  let state = {
    type: "progress",
    tasks: [
      { id: 1, text: "Definir escopo", done: true },
      { id: 2, text: "Desenvolver protótipo", done: false },
      { id: 3, text: "Testes finais", done: false },
      { id: 4, text: "Lançamento", done: false },
    ],
  };
  let taskIdCounter = 5;

  // ===== SALVAR =====
  function saveState() {
    card.dataset.state = JSON.stringify(state);
    pushState();
  }

  function renderTasks() {
    const taskContainer = card.querySelector(".tasks-container");
    if (!taskContainer) return;

    taskContainer.innerHTML = "";
    state.tasks.forEach((task, index) => {
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
        state.tasks.splice(index, 1);
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
    const total = state.tasks.length;
    const completed = state.tasks.filter((t) => t.done).length;
    const percent = total === 0 ? 0 : Math.round((completed / total) * 100);
    const percentDisplay = card.querySelector(".progress-percent");
    const barFill = card.querySelector(".progress-bar-fill");
    const summary = card.querySelector(".progress-summary");
    if (percentDisplay) percentDisplay.textContent = `${percent}%`;
    if (barFill) barFill.style.width = `${percent}%`;
    if (summary)
      summary.textContent = `${completed} de ${total} tarefas concluídas`;
  }

  // ===== HTML =====
  card.innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <span style="font-weight:600; color:var(--text-primary); font-size:16px;">📈 Progresso</span>
      <button class="add-task-btn" style="background:transparent; border:1px solid var(--border-subtle); color:var(--text-secondary); border-radius:6px; padding:2px 10px; cursor:pointer; font-size:12px; transition:background 0.2s;">+ Adicionar</button>
    </div>
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
      <div style="flex:1; height:8px; background:var(--bg-elevated); border-radius:4px; overflow:hidden; position:relative;">
        <div class="progress-bar-fill" style="height:100%; width:0%; background:linear-gradient(90deg, #4a7cf7, #6f42c1); border-radius:4px; transition:width 0.5s cubic-bezier(0.4, 0, 0.2, 1);"></div>
      </div>
      <span class="progress-percent" style="font-size:14px; font-weight:600; color:#4a7cf7; min-width:44px; text-align:right;">0%</span>
    </div>
    <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
      <span class="progress-summary" style="font-size:12px; color:var(--text-muted);">0 de 0 tarefas</span>
    </div>
    <div class="tasks-container" style="display:flex; flex-direction:column; gap:6px; max-height:220px; overflow-y:auto; padding-right:4px;"></div>
  `;

  // ===== ADICIONAR TAREFA =====
  const addBtn = card.querySelector(".add-task-btn");
  addBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    const newTaskText = prompt("Digite o nome da nova tarefa:", "Nova tarefa");
    if (newTaskText && newTaskText.trim() !== "") {
      state.tasks.push({
        id: taskIdCounter++,
        text: newTaskText.trim(),
        done: false,
      });
      renderTasks();
      saveState();
    }
  });

  // Aplica edição nos textos que já existem (fallback)
  card.querySelectorAll('[contenteditable="true"]').forEach((el) => {
    enableEditOnDoubleClick(el, plainTextOnBlur);
  });

  renderTasks();
  saveState(); // <-- GRAVA ESTADO INICIAL

  addDeleteButton(card);
  addDragHandle(card);
  card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
  addPorts(card);
  addResizeHandle(card);
  document.getElementById("cards-container").appendChild(card);
  card.classList.add("float-in");
  setTimeout(() => card.classList.remove("float-in"), 700);
  initializeCard(card);
  updateContainerHeight();
}

function addEmbedCard() {
  pushState();
  const card = document.createElement("div");
  card.className = "editable-item";
  card.style.position = "absolute";
  const pos = getVisiblePosition(400, 280);
  card.style.left = pos.left + "px";
  card.style.top = pos.top + "px";
  card.style.width = "400px";
  card.style.maxWidth = "90%";
  card.style.background = "var(--bg-surface)";
  card.style.borderRadius = "12px";
  card.style.padding = "12px";
  card.style.boxShadow = "var(--shadow-card)";
  card.style.border = "1px solid var(--border-subtle)";

  const defaultEmbed =
    "https://www.youtube.com/embed/dQw4w9WgXcQ?controls=0&modestbranding=1&rel=0";

  card.innerHTML = `
    <div class="video-wrapper" style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:8px;">
      <iframe id="embed-iframe" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" src="${defaultEmbed}" allowfullscreen></iframe>
      <button id="change-video-btn" style="position:absolute; top:8px; right:8px; z-index:20; background:rgba(0,0,0,0.7); color:#fff; border:none; border-radius:6px; padding:4px 10px; font-size:12px; cursor:pointer; font-family:'Share Tech Mono', monospace; opacity:0; transition:opacity 0.3s;">
        🔄 Trocar
      </button>
    </div>
    <div contenteditable="true" style="margin-top:10px; font-weight:500; background:transparent; border:none; color:var(--text-primary); width:100%; padding:0;">Título do vídeo</div>
    <div contenteditable="true" style="font-size:13px; color:var(--text-muted); background:transparent; border:none; width:100%; padding:0;">Descrição curta do conteúdo.</div>
  `;

  const iframe = card.querySelector("#embed-iframe");
  const changeBtn = card.querySelector("#change-video-btn");
  const textElements = card.querySelectorAll('[contenteditable="true"]');
  const titleDiv = textElements[0];
  const descDiv = textElements[1];

  // ===== SALVA O ESTADO INICIAL =====
  const initialState = {
    type: "embed",
    url: defaultEmbed,
    title: "Título do vídeo",
    description: "Descrição curta do conteúdo.",
  };
  card.dataset.state = JSON.stringify(initialState);

  // ===== HOVER DO BOTÃO =====
  card.addEventListener("mouseenter", () => {
    changeBtn.style.opacity = "1";
  });
  card.addEventListener("mouseleave", () => {
    changeBtn.style.opacity = "0";
  });

  // ===== BOTÃO TROCAR VÍDEO =====
  changeBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    const currentSrc = iframe.src;
    const newUrl = prompt(
      "Cole o link do vídeo (YouTube, Vimeo, etc.):",
      currentSrc,
    );
    if (newUrl) {
      let embedUrl = newUrl.trim();
      if (embedUrl.includes("youtube.com/watch?v=")) {
        const id = embedUrl.split("v=")[1]?.split("&")[0];
        if (id) embedUrl = `https://www.youtube.com/embed/${id}`;
      } else if (embedUrl.includes("youtu.be/")) {
        const id = embedUrl.split("youtu.be/")[1]?.split("?")[0];
        if (id) embedUrl = `https://www.youtube.com/embed/${id}`;
      } else if (embedUrl.includes("vimeo.com/")) {
        const id = embedUrl.split("vimeo.com/")[1]?.split("/")[0];
        if (id) embedUrl = `https://player.vimeo.com/video/${id}`;
      }
      if (embedUrl.includes("youtube.com/embed/")) {
        const hasParams = embedUrl.includes("?");
        embedUrl += hasParams ? "&" : "?";
        embedUrl += "controls=0&modestbranding=1&rel=0";
      }
      iframe.src = embedUrl;

      const currentState = JSON.parse(card.dataset.state);
      currentState.url = embedUrl;
      card.dataset.state = JSON.stringify(currentState);
      pushState();
    }
  });

  // ===== SALVAR AO EDITAR =====
  if (titleDiv) {
    titleDiv.addEventListener("input", function () {
      const currentState = JSON.parse(card.dataset.state);
      currentState.title = this.textContent;
      card.dataset.state = JSON.stringify(currentState);
      pushState();
    });
  }
  if (descDiv) {
    descDiv.addEventListener("input", function () {
      const currentState = JSON.parse(card.dataset.state);
      currentState.description = this.textContent;
      card.dataset.state = JSON.stringify(currentState);
      pushState();
    });
  }

  // ===== EDITÁVEL POR DUPLO CLIQUE =====
  card.querySelectorAll('[contenteditable="true"]').forEach((el) => {
    enableEditOnDoubleClick(el, plainTextOnBlur);
  });

  // ===== ELEMENTOS COMUNS =====
  addDeleteButton(card);
  addDragHandle(card);
  card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
  addPorts(card);
  addResizeHandle(card);
  document.getElementById("cards-container").appendChild(card);
  card.classList.add("float-in");
  setTimeout(() => card.classList.remove("float-in"), 700);
  initializeCard(card);
  updateContainerHeight();
}

function addKanbanCard() {
  pushState();
  const card = document.createElement("div");
  card.className = "editable-item";
  card.style.position = "absolute";
  const pos = getVisiblePosition(600, 320);
  card.style.left = pos.left + "px";
  card.style.top = pos.top + "px";
  card.style.width = "600px";
  card.style.maxWidth = "90%";
  card.style.background = "var(--bg-surface)";
  card.style.borderRadius = "12px";
  card.style.padding = "16px 20px";
  card.style.boxShadow = "var(--shadow-card)";
  card.style.border = "1px solid var(--border-subtle)";

  // ===== ESTADO =====
  let state = {
    type: "kanban",
    columns: [
      { title: "📌 A Fazer", tasks: [] },
      { title: "🔄 Em Andamento", tasks: [] },
      { title: "✅ Concluído", tasks: [] },
    ],
  };

  // ===== FUNÇÃO SALVAR =====
  function saveState() {
    card.dataset.state = JSON.stringify(state);
    pushState();
  }

  // ===== RENDERIZAR COLUNAS =====
  function renderColumns() {
    const container = card.querySelector("#kanban-container");
    container.innerHTML = "";
    state.columns.forEach((col, colIndex) => {
      const column = document.createElement("div");
      column.className = "kanban-column";
      column.style.cssText =
        "flex:1; min-width:140px; background:var(--bg-elevated); border-radius:8px; padding:10px; position:relative;";

      column.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <div contenteditable="true" style="font-weight:600; color:var(--text-primary); background:transparent; border:none; padding:0; outline:none; flex:1;">${col.title}</div>
          <button class="delete-column-btn" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:14px; padding:0 4px;">✕</button>
        </div>
        <div class="kanban-tasks" style="display:flex; flex-direction:column; gap:6px;"></div>
        <button class="add-task-btn" style="background:transparent; border:1px dashed var(--border-subtle); color:var(--text-muted); border-radius:6px; padding:4px; width:100%; cursor:pointer; font-size:12px; margin-top:6px;">+ Adicionar</button>
      `;

      const titleDiv = column.querySelector('[contenteditable="true"]');
      enableEditOnDoubleClick(titleDiv, plainTextOnBlur);
      titleDiv.addEventListener("input", function () {
        state.columns[colIndex].title = this.textContent;
        saveState();
      });

      const tasksContainer = column.querySelector(".kanban-tasks");
      col.tasks.forEach((taskText, taskIndex) => {
        const task = document.createElement("div");
        task.style.cssText =
          "background:var(--bg-surface); border-radius:6px; padding:8px; border:1px solid var(--border-subtle); cursor:pointer;";
        task.innerHTML = `<div contenteditable="true" style="background:transparent; border:none; color:var(--text-primary); width:100%; padding:0; outline:none;">${taskText}</div>`;
        const taskEdit = task.querySelector('[contenteditable="true"]');
        enableEditOnDoubleClick(taskEdit, plainTextOnBlur);
        taskEdit.addEventListener("input", function () {
          state.columns[colIndex].tasks[taskIndex] = this.textContent;
          saveState();
        });
        tasksContainer.appendChild(task);
      });

      // Botão adicionar tarefa
      column
        .querySelector(".add-task-btn")
        .addEventListener("click", function (e) {
          e.stopPropagation();
          state.columns[colIndex].tasks.push("Nova tarefa");
          saveState();
          renderColumns();
        });

      // Deletar coluna
      column
        .querySelector(".delete-column-btn")
        .addEventListener("click", function (e) {
          e.stopPropagation();
          if (state.columns.length <= 1) {
            alert("Não é possível remover a última coluna.");
            return;
          }
          state.columns.splice(colIndex, 1);
          saveState();
          renderColumns();
        });

      container.appendChild(column);
    });
  }

  // ===== HTML =====
  card.innerHTML = `
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <span style="font-weight:600; color:var(--text-primary);">📋 Kanban</span>
      <button id="add-column-btn" style="background:transparent; border:1px solid var(--border-subtle); color:var(--text-secondary); border-radius:6px; padding:4px 12px; cursor:pointer; font-size:12px;">➕ Coluna</button>
    </div>
    <div id="kanban-container" style="display:flex; gap:12px; overflow-x:auto; padding-bottom:8px;"></div>
  `;

  // ===== EVENTO ADICIONAR COLUNA =====
  const addColumnBtn = card.querySelector("#add-column-btn");
  addColumnBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    const name = prompt("Nome da nova coluna:", "Nova Coluna");
    if (name && name.trim() !== "") {
      state.columns.push({ title: name.trim(), tasks: [] });
      saveState();
      renderColumns();
    }
  });

  renderColumns();
  saveState(); // <-- GRAVA ESTADO INICIAL

  // ===== ELEMENTOS COMUNS =====
  addDeleteButton(card);
  addDragHandle(card);
  card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
  addPorts(card);
  addResizeHandle(card);
  document.getElementById("cards-container").appendChild(card);
  card.classList.add("float-in");
  setTimeout(() => card.classList.remove("float-in"), 700);
  initializeCard(card);
  updateContainerHeight();
}

function compressImage(file, maxWidth = 1000, targetMaxSizeMB = 1.5) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        try {
          // Verifica se a imagem original tem área muito grande (limite de canvas)
          const MAX_AREA = 16000000; // ~16 megapixels (seguro para maioria dos navegadores)
          let originalWidth = img.width;
          let originalHeight = img.height;

          // Se a área original for maior que o limite, reduz a largura inicial
          if (originalWidth * originalHeight > MAX_AREA) {
            const ratio = Math.sqrt(
              MAX_AREA / (originalWidth * originalHeight),
            );
            originalWidth = Math.floor(originalWidth * ratio);
            originalHeight = Math.floor(originalHeight * ratio);
          }

          // Define largura inicial (não maior que maxWidth nem que a largura reduzida)
          let width = Math.min(originalWidth, maxWidth);

          const tryCompress = (w, quality) => {
            const height = Math.round((w / originalWidth) * originalHeight);
            const canvas = document.createElement("canvas");
            canvas.width = w;
            canvas.height = height;
            const ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0, w, height);
            return canvas.toDataURL("image/jpeg", quality);
          };

          // Função para calcular tamanho aproximado em MB a partir do dataURL
          const getSizeMB = (dataUrl) => {
            const base64 = dataUrl.split(",")[1];
            const bytes = Math.floor((base64.length * 3) / 4);
            return bytes / (1024 * 1024);
          };

          let quality = 0.8;
          let dataUrl = tryCompress(width, quality);
          let sizeMB = getSizeMB(dataUrl);

          // Reduz qualidade até caber ou chegar a 0.3
          while (sizeMB > targetMaxSizeMB && quality > 0.3) {
            quality -= 0.1;
            dataUrl = tryCompress(width, quality);
            sizeMB = getSizeMB(dataUrl);
          }

          // Se ainda estiver grande, reduz largura e repete
          while (sizeMB > targetMaxSizeMB && width > 200) {
            width = Math.max(200, Math.floor(width * 0.8));
            quality = 0.7;
            dataUrl = tryCompress(width, quality);
            sizeMB = getSizeMB(dataUrl);
            while (sizeMB > targetMaxSizeMB && quality > 0.3) {
              quality -= 0.1;
              dataUrl = tryCompress(width, quality);
              sizeMB = getSizeMB(dataUrl);
            }
          }

          // Se ainda estiver acima (casos extremos), força qualidade mínima e largura mínima
          if (sizeMB > targetMaxSizeMB) {
            dataUrl = tryCompress(200, 0.3);
          }

          resolve(dataUrl);
        } catch (err) {
          reject(err);
        }
      };
      img.onerror = reject;
      img.src = e.target.result;
    };
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

function insertImageIntoEditable(dataUrl) {
  const selection = window.getSelection();
  if (!selection.rangeCount) return;

  const range = selection.getRangeAt(0);
  const img = document.createElement("img");
  img.src = dataUrl;
  img.style.maxWidth = "100%";
  img.style.borderRadius = "8px";
  img.style.margin = "8px 0";
  img.style.display = "block";

  range.deleteContents(); // remove seleção, se houver
  range.insertNode(img);

  // Move o cursor para depois da imagem
  range.setStartAfter(img);
  range.collapse(true);
  selection.removeAllRanges();
  selection.addRange(range);

  // Atualiza estado e altura do container
  if (typeof pushState === "function") pushState();
  if (typeof updateContainerHeight === "function") updateContainerHeight();
}

function addImageCard() {
  pushState();
  const card = document.createElement("div");
  card.className = "editable-item";
  card.style.position = "absolute";
  const pos = getVisiblePosition(300, 220);
  card.style.left = pos.left + "px";
  card.style.top = pos.top + "px";
  card.style.width = "300px";
  card.style.maxWidth = "90%";
  card.style.background = "var(--bg-surface)";
  card.style.borderRadius = "16px";
  card.style.padding = "12px";
  card.style.boxShadow = "var(--shadow-card)";
  card.style.border = "1px solid var(--border-subtle)";
  card.style.transition = "all 0.3s ease";

  // Estrutura interna (inclui legenda)
  card.innerHTML = `
    <div class="image-card-container" style="position:relative; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer;">
      <div class="image-placeholder" style="width:100%; min-height:160px; border:2px dashed var(--border-subtle); border-radius:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; color:var(--text-muted); transition:all 0.3s;">
        <i class="bi bi-image" style="font-size:40px;"></i>
        <span style="font-size:13px;">Clique ou arraste uma imagem</span>
      </div>
      <img class="image-preview" style="display:none; max-width:100%; max-height:100%; border-radius:8px;" />
      <div class="image-actions" style="position:absolute; top:10px; right:10px; display:none; gap:6px; z-index:30;">
        <button class="btn-change-image" title="Trocar imagem" style="background:rgba(0,0,0,0.6); color:#fff; border:none; border-radius:50%; width:28px; height:28px; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center;">
          <i class="bi bi-arrow-repeat"></i>
        </button>
        <button class="btn-remove-image" title="Remover imagem" style="background:rgba(0,0,0,0.6); color:#fff; border:none; border-radius:50%; width:28px; height:28px; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center;">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="image-caption" contenteditable="false" 
     style="display:none; margin-top:8px; font-size:13px; color:var(--text-secondary); background:transparent; border:none; outline:none; width:100%; text-align:center; cursor:text; padding:4px; min-height:22px;">
  Legenda da imagem
</div>
    </div>
  `;

  // Controles comuns
  addDeleteButton(card);
  addDragHandle(card);
  card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
  addPorts(card);
  addResizeHandle(card);

  // Inicializa eventos específicos do card de imagem
  initializeImageCard(card);

  document.getElementById("cards-container").appendChild(card);
  card.classList.add("float-in");
  setTimeout(() => card.classList.remove("float-in"), 700);

  initializeCard(card);
  updateContainerHeight();
}

function initializeImageCard(card) {
  const container = card.querySelector(".image-card-container");
  const placeholder = card.querySelector(".image-placeholder");
  const preview = card.querySelector(".image-preview");
  const actionsDiv = card.querySelector(".image-actions");
  const changeBtn = card.querySelector(".btn-change-image");
  const removeBtn = card.querySelector(".btn-remove-image");
  const caption = card.querySelector(".image-caption"); // 👈 adicionado

  if (
    !container ||
    !placeholder ||
    !preview ||
    !actionsDiv ||
    !changeBtn ||
    !removeBtn
  ) {
    console.warn("Estrutura do card de imagem incompleta.");
    return;
  }

  // Função para carregar imagem (com compressão)
  async function loadImage(file) {
    if (!file || !file.type.startsWith("image/")) return;
    try {
      const dataUrl = await compressImage(file);
      placeholder.style.display = "none";
      preview.src = dataUrl;
      preview.style.display = "block";
      actionsDiv.style.display = "flex";
      // Exibe a legenda
      if (caption) {
        caption.style.display = "block";
        if (!caption.textContent.trim())
          caption.textContent = "Legenda da imagem";
      }
      if (typeof pushState === "function") pushState();
      if (typeof updateContainerHeight === "function") updateContainerHeight();
    } catch (err) {
      console.error("Erro ao processar imagem:", err);
      alert("Erro ao processar a imagem.");
    }
  }

  function removeImage() {
    preview.src = "";
    preview.style.display = "none";
    placeholder.style.display = "flex";
    actionsDiv.style.display = "none";
    // Oculta e limpa a legenda
    if (caption) {
      caption.style.display = "none";
      caption.textContent = "Legenda da imagem";
    }
    if (typeof pushState === "function") pushState();
    if (typeof updateContainerHeight === "function") updateContainerHeight();
  }

  // Função para abrir seletor de arquivo
  function openFileSelector() {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.onchange = (e) => {
      if (e.target.files[0]) loadImage(e.target.files[0]);
    };
    input.click();
  }

  // Eventos
  container.addEventListener("click", (e) => {
    if (e.target.closest(".image-actions")) return;
    openFileSelector();
  });

  changeBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    openFileSelector();
  });

  removeBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    removeImage();
  });

  // Drag & drop
  container.addEventListener("dragover", (e) => {
    e.preventDefault();
    container.style.borderColor = "var(--accent)";
  });
  container.addEventListener("dragleave", () => {
    container.style.borderColor = "";
  });
  container.addEventListener("drop", (e) => {
    e.preventDefault();
    container.style.borderColor = "";
    const file = e.dataTransfer.files[0];
    if (file) loadImage(file);
  });

  // Mostrar/esconder botões ao passar o mouse
  card.addEventListener("mouseenter", () => {
    if (preview.style.display === "block") {
      actionsDiv.style.display = "flex";
    }
  });
  card.addEventListener("mouseleave", () => {
    actionsDiv.style.display = "none";
  });

  // 👇 Habilita edição da legenda (sempre disponível)
  if (caption) {
    enableEditOnDoubleClick(caption, plainTextOnBlur);
    caption.addEventListener("input", function () {
      if (typeof pushState === "function") pushState();
    });
  }
}
window.initializeImageCard = initializeImageCard;
