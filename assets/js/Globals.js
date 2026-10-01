// ============================================================
// VARIÁVEIS GLOBAIS (declaradas uma única vez)
// ============================================================

// 🔥 CONTROLE DE EDIÇÃO
let isEditing = false;

// 🔥 SISTEMA DE UNDO/REDO
let version = 1;
let undoStack = [];
let redoStack = [];
let isUndoRedo = false;

// 🔥 SALVAMENTO (debounce)
let saveFullStateTimer = null;
let saveFullStatePending = false;

// 🔥 OUTRAS VARIÁVEIS GLOBAIS
let colorHistory = ["#ff0000", "#00aaff", "#ffcc00"];
let currentToolbar = null;
let activeToolbar = null;
let closeHandler = null;
let dragData = null;
let filterWord = "";
let targetElements = [];
let lastColor = null;
let lastFontSize = null;
let lastFontFamily = null;

const API_URL = "/knowsnow1/api.php";
const USER_TOKEN = "seu_token_secreto";

let currentMateria = "";
let currentTopico = "";
let currentPagina = 0;

let dadosCompletos = {
  materias: {},
  current: { materia: "", topico: "", pagina: 0 },
};

let savedSelection = null;
let selectedItems = [];
let isLassoSelecting = false;
let lassoStartX = 0,
  lassoStartY = 0;
let lassoElement = null;
let dragGroupData = null;

const CONNECTION_TYPES = {
  default: {
    stroke: "#ff6b6b",
    strokeWidth: 3,
    dashArray: null,
    markerFill: "#ff6b6b",
    markerSize: 4,
    curve: 0.45,
    label: "Padrão",
  },
  dashed: {
    stroke: "#4a7cf7",
    strokeWidth: 2,
    dashArray: "8 6",
    markerFill: "#4a7cf7",
    markerSize: 3,
    curve: 0.3,
    label: "Tracejado",
  },
  thick: {
    stroke: "#2ea043",
    strokeWidth: 5,
    dashArray: null,
    markerFill: "#2ea043",
    markerSize: 6,
    curve: 0.5,
    label: "Grosso",
  },
  dotted: {
    stroke: "#ffc107",
    strokeWidth: 2,
    dashArray: "2 4",
    markerFill: "#ffc107",
    markerSize: 3,
    curve: 0.4,
    label: "Pontilhado",
  },
};

let currentConnectionType = "default"; // tipo usado nas novas conexões

// ============================================================
// FUNÇÕES GLOBAIS
// ============================================================

// 🔥 DEBOUNCE PARA SAVEFULLSTATE
function debouncedSaveFullState(immediate = false) {
  if (immediate) {
    clearTimeout(saveFullStateTimer);
    saveFullStateTimer = null;
    saveFullStatePending = false;
    return saveFullState();
  }

  if (saveFullStatePending) return;

  clearTimeout(saveFullStateTimer);
  saveFullStatePending = true;

  saveFullStateTimer = setTimeout(() => {
    saveFullStatePending = false;
    saveFullStateTimer = null;
    saveFullState();
  }, 300);
}

function adjustMetricValue(card, delta) {
  const state = JSON.parse(
    card.dataset.state || '{"value":0,"goal":10000,"progress":0}',
  );
  state.value = Math.max(0, state.value + delta);
  state.progress = Math.min(100, Math.max(0, (state.value / state.goal) * 100));
  card.dataset.state = JSON.stringify(state);
  renderMetrics(card);
  pushState();
}
// ============================================================
// CONTROLE DE EDIÇÃO – VERSÃO DEFINITIVA (SEM DUPLICAÇÕES)
// ============================================================

let editingTimeout = null;

function updateEditingState() {
  const active = document.activeElement;

  // Verifica se o elemento ativo ou qualquer ancestral é contenteditable
  let isActiveEditable = false;
  if (active) {
    if (active.contentEditable === "true" || active.contentEditable === "") {
      isActiveEditable = true;
    } else if (active.closest) {
      const parent = active.closest('[contenteditable="true"]');
      if (parent) isActiveEditable = true;
    }
  }

  // Se o foco estiver no body ou html, NÃO está editando
  if (active === document.body || active === document.documentElement) {
    isActiveEditable = false;
  }

  // Só atualiza se houve mudança
  if (isActiveEditable !== isEditing) {
    isEditing = isActiveEditable;
    if (isEditing) {
      console.log("✏️ Editando ativado");
    } else {
      console.log("✅ Editando desativado");
      // Dispara salvamento imediato ao sair da edição
      clearTimeout(saveFullStateTimer);
      saveFullStateTimer = setTimeout(() => {
        saveFullStatePending = false;
        saveFullStateTimer = null;
        saveFullState();
      }, 100);

      // 🔥 SALVA O ESTADO NO HISTÓRICO DE UNDO/REDO
      // Aguarda o DOM atualizar antes de salvar
      setTimeout(() => {
        if (typeof pushState === "function") pushState();
      }, 150);
    }
  }

  // Timeout de segurança (reduzido para 2 segundos)
  if (isEditing) {
    clearTimeout(editingTimeout);
    editingTimeout = setTimeout(() => {
      if (isEditing) {
        console.warn("⏰ Timeout de segurança: forçando isEditing = false");
        isEditing = false;
        // Dispara salvamento após timeout
        clearTimeout(saveFullStateTimer);
        saveFullStateTimer = setTimeout(() => {
          saveFullStatePending = false;
          saveFullStateTimer = null;
          saveFullState();
        }, 100);
      }
    }, 2000);
  }
}

// 🔥 Eventos de foco (mais confiáveis)
document.addEventListener("focusin", updateEditingState);
document.addEventListener("focusout", updateEditingState);

// 🔥 Monitora cliques (caso o foco mude sem eventos de foco)
document.addEventListener("mousedown", function (e) {
  setTimeout(updateEditingState, 5);
});

// 🔥 Monitora teclas que podem mudar o foco
document.addEventListener("keydown", function (e) {
  if (e.key === "Tab" || e.key === "Escape" || e.key === "Enter") {
    setTimeout(updateEditingState, 5);
  }
});

// 🔥 Verificação periódica (a cada 500ms) para garantir consistência
setInterval(updateEditingState, 500);

// Inicializa o estado
updateEditingState();

console.log(
  "✅ Sistema de controle de edição inicializado (versão definitiva)",
);

// ============================================================
// LASSO (SELEÇÃO POR RETÂNGULO)
// ============================================================

function createLassoElement() {
  let lasso = document.getElementById("lasso-selection");
  if (!lasso) {
    lasso = document.createElement("div");
    lasso.id = "lasso-selection";
    lasso.style.cssText = `
      position: absolute;
      border: 2px dashed #4a9eff;
      background: rgba(74, 158, 255, 0.1);
      pointer-events: none;
      display: none;
      z-index: 9999;
    `;
    const container = document.getElementById("cards-container");
    if (container) {
      container.style.position = "relative";
      container.appendChild(lasso);
    }
  }
  return lasso;
}

function getVisiblePosition(width, height) {
    const container = document.getElementById("cards-container");
    if (!container) return { left: 20, top: 20 };

    const style = window.getComputedStyle(container);
    const transform = style.transform || 'matrix(1,0,0,1,0,0)';
    const matrix = new DOMMatrix(transform);
    const inverse = matrix.inverse();

    const centerX = window.innerWidth / 2;
    const centerY = window.innerHeight / 2;

    const targetScreenX = centerX - width / 2;
    const targetScreenY = centerY - height / 2;

    const localPoint = inverse.transformPoint({ x: targetScreenX, y: targetScreenY });

    let left = Math.round(localPoint.x);
    let top = Math.round(localPoint.y);

    // 🔥 DESLOCAR PARA A ESQUERDA (subtrai 80px)
    left = left - 80;

    // Clamp para não ultrapassar as bordas do container
    const maxLeft = container.clientWidth - width;
    const maxTop = container.clientHeight - height;
    left = Math.max(0, Math.min(left, maxLeft));
    top = Math.max(0, Math.min(top, maxTop));

    // Fallback: se ainda estiver fora, centraliza no container
    if (left < 0 || top < 0 || left + width > container.clientWidth || top + height > container.clientHeight) {
        left = (container.clientWidth - width) / 2;
        top = (container.clientHeight - height) / 2;
        left = Math.round(left / 20) * 20;
        top = Math.round(top / 20) * 20;
        return { left, top };
    }

    left = Math.round(left / 20) * 20;
    top = Math.round(top / 20) * 20;

    return { left, top };
}
// ============================================================
// CORREÇÃO DE TEXTO (LanguageTool API)
// ============================================================

async function checkSpelling(text, language = "pt-BR") {
  try {
    const params = new URLSearchParams({
      text: text,
      language: language,
    });
    const response = await fetch(
      `https://api.languagetool.org/v2/check?${params}`,
    );
    const data = await response.json();
    return data.matches || [];
  } catch (e) {
    console.warn("⚠️ Erro ao verificar ortografia:", e);
    return [];
  }
}

// ============================================================
// DEFINIÇÃO DE PALAVRA (Free Dictionary API)
// ============================================================

async function getDefinition(word) {
  try {
    const response = await fetch(
      `https://api.dictionaryapi.dev/api/v2/entries/pt_BR/${encodeURIComponent(word)}`,
    );
    if (!response.ok) return null;
    const data = await response.json();
    if (data.length > 0 && data[0].meanings) {
      return (
        data[0]?.meanings[0]?.definitions[0]?.definition ||
        "Definição não encontrada"
      );
    }
    return null;
  } catch (e) {
    console.warn("⚠️ Erro ao buscar definição:", e);
    return null;
  }
}

// ============================================================
// PUSHSTATE (placeholder – será sobrescrito em Conections.js)
// ============================================================

function pushState() {
  console.log("📝 pushState placeholder");
}

// ============================================================
// INICIALIZAÇÃO DO MENU DE ADIÇÃO DE ITENS
// ============================================================

document.addEventListener("DOMContentLoaded", function () {
  const addPanel = document.getElementById("add-item-panel");
  const addOverlay = document.getElementById("add-item-overlay");
  const addFab = document.getElementById("add-item-fab");
  const closePanelBtn = document.getElementById("close-add-panel");
  const searchInput = document.getElementById("item-search");
  const gridContainer = document.getElementById("item-grid");

  if (
    !addFab ||
    !addPanel ||
    !addOverlay ||
    !closePanelBtn ||
    !searchInput ||
    !gridContainer
  ) {
    console.warn("⚠️ Elementos do menu não encontrados. Verifique os IDs.");
    return;
  }

  const ITEMS_CATALOG = [
    { id: "paragraph", icon: "¶", label: "Parágrafo", action: addParagraph },
    {
      id: "explanation",
      icon: "📝",
      label: "Explicação",
      action: addEmptyExplanation,
    },
    { id: "list", icon: "📋", label: "Lista", action: addEmptyList },
    {
      id: "h1",
      icon: "H1",
      label: "Título H1",
      action: () => addEmptyTitle(1),
    },
    {
      id: "h2",
      icon: "H2",
      label: "Título H2",
      action: () => addEmptyTitle(2),
    },
    {
      id: "h3",
      icon: "H3",
      label: "Título H3",
      action: () => addEmptyTitle(3),
    },
    {
      id: "h4",
      icon: "H4",
      label: "Título H4",
      action: () => addEmptyTitle(4),
    },
    {
      id: "h5",
      icon: "H5",
      label: "Título H5",
      action: () => addEmptyTitle(5),
    },
    {
      id: "h6",
      icon: "H6",
      label: "Título H6",
      action: () => addEmptyTitle(6),
    },
    { id: "table", icon: "📊", label: "Tabela", action: addEmptyTable },
    { id: "code", icon: "💻", label: "Código", action: addEmptyCodeCard },
    { id: "blockquote", icon: "❝", label: "Citação", action: addBlockquote },
    { id: "sticky", icon: "📌", label: "Nota adesiva", action: addStickyNote },
    { id: "checklist", icon: "✅", label: "Checklist", action: addChecklist },
    { id: "profile", icon: "👤", label: "Perfil", action: addProfileCard },
    { id: "metrics", icon: "📊", label: "Métricas", action: addMetricsCard },
    {
      id: "timeline",
      icon: "📅",
      label: "Linha do tempo",
      action: addTimelineCard,
    },
    { id: "progress", icon: "📈", label: "Progresso", action: addProgressCard },
    { id: "embed", icon: "▶️", label: "Vídeo", action: addEmbedCard },
    { id: "kanban", icon: "📌", label: "Kanban", action: addKanbanCard },
  ];

  function renderItemGrid(filter = "") {
    const filtered = ITEMS_CATALOG.filter(
      (item) =>
        item.label.toLowerCase().includes(filter.toLowerCase()) ||
        item.id.toLowerCase().includes(filter.toLowerCase()),
    );
    gridContainer.innerHTML = "";
    filtered.forEach((item) => {
      const div = document.createElement("div");
      div.className = "item-option";
      div.innerHTML = `<span class="icon">${item.icon}</span><span class="label">${item.label}</span>`;
      div.addEventListener("click", function (e) {
        e.stopPropagation();
        closeAddPanel();
        if (typeof item.action === "function") item.action();
        else console.warn("Ação não definida para", item.id);
      });
      gridContainer.appendChild(div);
    });
    if (filtered.length === 0) {
      gridContainer.innerHTML = `
        <div style="grid-column:1/-1; text-align:center; color:var(--text-muted); padding:30px 0;">
          Nenhum item encontrado para "<strong>${filter}</strong>"
        </div>
      `;
    }
  }

  function openAddPanel() {
    addPanel.style.display = "flex";
    addOverlay.style.display = "block";
    searchInput.value = "";
    renderItemGrid("");
    searchInput.focus();
    document.addEventListener("keydown", handlePanelKeydown);
  }

  function closeAddPanel() {
    addPanel.style.display = "none";
    addOverlay.style.display = "none";
    document.removeEventListener("keydown", handlePanelKeydown);
  }

  function handlePanelKeydown(e) {
    if (e.key === "Escape") closeAddPanel();
  }

  addFab.addEventListener("click", openAddPanel);
  closePanelBtn.addEventListener("click", closeAddPanel);
  addOverlay.addEventListener("click", closeAddPanel);
  searchInput.addEventListener("input", function () {
    renderItemGrid(this.value);
  });

  document.addEventListener("keydown", function (e) {
    if (
      (e.ctrlKey || e.metaKey) &&
      e.shiftKey &&
      (e.key === "A" || e.key === "a")
    ) {
      e.preventDefault();
      if (addPanel.style.display === "none" || addPanel.style.display === "") {
        openAddPanel();
      } else {
        closeAddPanel();
      }
    }
  });

  addPanel.style.display = "none";
  addOverlay.style.display = "none";
  renderItemGrid("");
});

console.log("✅ Globals.js carregado com sucesso!");

function initializeCard(card) {
  if (card.dataset.initialized === "true") return;
  card.dataset.initialized = "true";

  // 1. Edição por duplo clique – verificar existência
  const fileName = card.querySelector(".file-name");
  if (fileName) enableEditOnDoubleClick(fileName, plainTextOnBlur);

  const codeBlock = card.querySelector(".code-block");
  if (codeBlock) enableEditOnDoubleClick(codeBlock, highlightOnBlur);

  const terminalContent = card.querySelector(".terminal-content");
  if (terminalContent)
    enableEditOnDoubleClick(terminalContent, plainTextOnBlur);

  // 2. Botão "Executar Código" – toggle do terminal
  const btn = card.querySelector(".btn-executar");
  const terminal = card.querySelector(".vscode-terminal");
  if (btn && terminal) {
    btn.removeEventListener("click", btn._listener);
    btn._listener = function () {
      terminal.style.display =
        terminal.style.display === "flex" ? "none" : "flex";
    };
    btn.addEventListener("click", btn._listener);
  }

  // 3. Botão de deletar
  addDeleteButton(card);
  // 4. Arrastar
  addDragHandle(card);
  // 5. Redimensionar
  addResizeHandle(card);
  // 6. Portas
  addPorts(card);
  // 7. Atualiza altura do container (se necessário)
  updateContainerHeight();
}

document.addEventListener("DOMContentLoaded", function () {
  document
    .querySelectorAll(".editable-item")
    .forEach((card) => initializeCard(card));
});

document.addEventListener("DOMContentLoaded", function () {
  const container = document.getElementById("cards-container");
  if (!container) return;

  container.addEventListener("click", function (e) {
    const button = e.target.closest(".btn-executar");
    if (!button) return;

    const card = button.closest(".editable-item");
    if (!card) return;

    const terminal = card.querySelector(".vscode-terminal");
    if (!terminal) return;

    // Alterna a visibilidade
    terminal.style.display =
      terminal.style.display === "flex" ? "none" : "flex";
  });
});

document.addEventListener("DOMContentLoaded", function () {
  const container = document.getElementById("cards-container");
  if (!container) return;

  container.addEventListener("click", function (e) {
    const target = e.target.closest("button");
    if (!target) return;

    const card = target.closest(".editable-item");
    if (!card) return;

    // --- Botões de Métricas ---
    if (target.classList.contains("metric-apply-btn")) {
      const input = card.querySelector(".metric-adjust-input");
      if (!input) return;
      const val = parseFloat(input.value);
      if (!isNaN(val) && val !== 0) {
        // Ajusta o valor (precisa recuperar o estado do card)
        adjustMetricValue(card, val);
        input.value = "";
      } else {
        alert("Digite um valor válido");
      }
      return;
    }

    if (target.classList.contains("metric-percent-btn")) {
      const pct = parseFloat(target.dataset.pct) / 100;
      // Recupera o valor atual do card (pode estar no dataset)
      const state = JSON.parse(card.dataset.state || "{}");
      const delta = Math.round((state.value || 0) * pct);
      adjustMetricValue(card, delta);
      return;
    }

    // --- Checklist ---
    if (target.classList.contains("add-task-btn")) {
      const state = JSON.parse(card.dataset.state || "{}");
      if (!state.tasks) state.tasks = [];
      state.tasks.push({
        id: Date.now(),
        text: "Nova tarefa",
        done: false,
        category: "📌 Geral",
      });
      card.dataset.state = JSON.stringify(state);
      renderChecklist(card); // função que renderiza a lista
      pushState();
      return;
    }

    // --- Timeline ---
    if (target.classList.contains("add-event-btn")) {
      const state = JSON.parse(card.dataset.state || "{}");
      if (!state.events) state.events = [];
      state.events.push({
        date: "Data",
        title: "Novo evento",
        time: "00:00",
        done: false,
      });
      card.dataset.state = JSON.stringify(state);
      renderTimeline(card);
      pushState();
      return;
    }

    // --- Kanban: adicionar coluna ---
    if (target.id === "add-column-btn") {
      // ou classe .add-column-btn
      const state = JSON.parse(card.dataset.state || "{}");
      if (!state.columns) state.columns = [];
      const name = prompt("Nome da nova coluna:", "Nova Coluna");
      if (name && name.trim()) {
        state.columns.push({ title: name.trim(), tasks: [] });
        card.dataset.state = JSON.stringify(state);
        renderKanban(card);
        pushState();
      }
      return;
    }

    // --- Embed: trocar vídeo ---
    if (target.classList.contains("change-video-btn")) {
      const iframe = card.querySelector("#embed-iframe");
      if (!iframe) return;
      const currentSrc = iframe.src;
      const newUrl = prompt("Cole o link do vídeo:", currentSrc);
      if (newUrl) {
        let embedUrl = newUrl.trim();
        // (mesma lógica de conversão que você já tem)
        if (embedUrl.includes("youtube.com/watch?v=")) {
          const id = embedUrl.split("v=")[1]?.split("&")[0];
          if (id) embedUrl = `https://www.youtube.com/embed/${id}`;
        } // etc.
        iframe.src = embedUrl;
      }
      return;
    }

    // --- Sticky: cores, tamanhos, pin, reset ---
    if (target.classList.contains("color-btn")) {
      const state = JSON.parse(card.dataset.state || "{}");
      state.color = target.dataset.color;
      card.dataset.state = JSON.stringify(state);
      // Atualiza visual
      const note = card.querySelector(".sticky-note");
      if (note) note.style.background = state.color;
      // Atualiza bordas dos botões
      card.querySelectorAll(".color-btn").forEach((b) => {
        b.style.border = "2px solid transparent";
        b.style.boxShadow = "none";
      });
      target.style.border = "2px solid #4a7cf7";
      target.style.boxShadow = "0 0 0 2px #4a7cf7";
      pushState();
      return;
    }

    if (target.classList.contains("font-size-btn")) {
      const state = JSON.parse(card.dataset.state || "{}");
      const delta = parseInt(target.dataset.delta);
      state.fontSize = Math.min(
        24,
        Math.max(10, (state.fontSize || 14) + delta),
      );
      card.dataset.state = JSON.stringify(state);
      const note = card.querySelector(".sticky-note");
      if (note) note.style.fontSize = state.fontSize + "px";
      // Atualiza display do tamanho
      const span = card.querySelector(".font-size-btn + span");
      if (span) span.textContent = state.fontSize + "px";
      pushState();
      return;
    }

    if (target.classList.contains("pin-btn")) {
      const state = JSON.parse(card.dataset.state || "{}");
      state.pinned = !state.pinned;
      card.dataset.state = JSON.stringify(state);
      // Atualiza visual
      if (state.pinned) {
        card.style.boxShadow = "0 8px 40px rgba(74,124,247,0.25)";
        card.style.border = "1px solid rgba(74,124,247,0.3)";
      } else {
        card.style.boxShadow = "";
        card.style.border = "";
      }
      target.style.color = state.pinned ? "#4a7cf7" : "var(--text-muted)";
      pushState();
      return;
    }

    if (target.classList.contains("reset-btn")) {
      if (!confirm("Redefinir nota?")) return;
      const state = {
        text: "Escreva sua nota aqui...",
        color: "#f9e076",
        fontSize: 14,
        pinned: false,
      };
      card.dataset.state = JSON.stringify(state);
      // Aplica visual
      const note = card.querySelector(".sticky-note");
      if (note) {
        note.textContent = state.text;
        note.style.background = state.color;
        note.style.fontSize = state.fontSize + "px";
      }
      // Reseta botões de cor
      card.querySelectorAll(".color-btn").forEach((b) => {
        b.style.border = "2px solid transparent";
        b.style.boxShadow = "none";
      });
      const defaultBtn = card.querySelector('.color-btn[data-color="#f9e076"]');
      if (defaultBtn) {
        defaultBtn.style.border = "2px solid #4a7cf7";
        defaultBtn.style.boxShadow = "0 0 0 2px #4a7cf7";
      }
      const pinBtn = card.querySelector(".pin-btn");
      if (pinBtn) pinBtn.style.color = "var(--text-muted)";
      card.style.boxShadow = "";
      card.style.border = "";
      pushState();
      return;
    }
  });
});

// ============================================================
// AUTENTICAÇÃO – REDIRECIONA PARA login.php
// ============================================================

// (Estas funções são apenas para compatibilidade com chamadas antigas)
function showLoginModal() {
  window.location.href = "login.php";
}

function hideLoginModal() {
  // Não faz nada – não há modal
}

// ============================================================
// INICIALIZAÇÃO – SÓ EXECUTA SE LOGADO
// ============================================================
// ============================================================
// INICIALIZAÇÃO – SEM VERIFICAÇÃO DE TOKEN (usa sessão PHP)
// ============================================================
document.addEventListener("DOMContentLoaded", async function () {
  // Não verifica token – o PHP já protegeu a página
  console.log("✅ Inicializando editor...");

  // 2. Inicializa o SVG e o container
  const container = document.getElementById("cards-container");
  if (!container) {
    console.warn("⚠️ Container não encontrado");
    return;
  }

  // Cria o SVG se não existir
  let svg = document.getElementById("flowchart-svg");
  if (!svg) {
    svg = inicializarSvg(); // sua função
    if (svg) {
      window.svg = svg;
    }
  }

  // 3. Configura observers e listeners (resize, zoom, etc.)
  // (seu código existente)

  // 4. Carrega os dados do usuário
  await loadFullState();
  updateMenuUI();
  updateContainerHeight();

  // 5. Configura salvamento automático
  setInterval(() => debouncedSaveFullState(), 5000);
  window.addEventListener("beforeunload", () => debouncedSaveFullState(true));

  console.log("🚀 Editor inicializado com sucesso!");
});

// ============================================================
// LOGOUT
// ============================================================
function logout() {
  // Remove token se existir (mas não é mais necessário)
  localStorage.removeItem("user_token");
  localStorage.removeItem("user_name");
  // Redireciona para login.php que limpa a sessão
  window.location.href = "login.php";
}

// ===== FORÇAR SALVAMENTO + INDICADOR =====
const saveBtn = document.querySelector(".btn-save");
const statusEl = document.getElementById("save-status");

// Função para atualizar o indicador
function setSaveStatus(state, message) {
  if (!saveBtn) return;
  // Remove classes anteriores
  saveBtn.classList.remove("saving", "saved");
  if (state === "saving") {
    saveBtn.classList.add("saving");
    if (statusEl) statusEl.textContent = message || "Salvando...";
  } else if (state === "saved") {
    saveBtn.classList.add("saved");
    if (statusEl) statusEl.textContent = message || "Salvo ✅";
    // Volta ao estado normal após 2 segundos
    clearTimeout(saveBtn._timeout);
    saveBtn._timeout = setTimeout(() => {
      saveBtn.classList.remove("saved");
      if (statusEl) statusEl.textContent = "Salvo";
    }, 2000);
  } else {
    // estado neutro
    if (statusEl) statusEl.textContent = message || "Salvo";
  }
}

// Função principal de salvamento forçado (chamada pelo botão)
async function forceSave() {
  // Verifica se a função saveFullState existe
  if (typeof saveFullState !== "function") {
    console.warn("saveFullState não definida");
    setSaveStatus("idle", "Erro");
    return;
  }

  // Se já estiver salvando, ignora
  if (window._savingFullState) {
    console.log("⏳ Salvamento em andamento...");
    return;
  }

  setSaveStatus("saving", "Salvando...");

  try {
    // Chama o save real (que já existe no Sidebar.js)
    await saveFullState();
    setSaveStatus("saved", "Salvo ✅");
    console.log("✅ Salvamento forçado concluído");
  } catch (error) {
    console.error("❌ Erro ao salvar:", error);
    setSaveStatus("idle", "Erro");
    // Mostra erro por 3 segundos
    setTimeout(() => {
      setSaveStatus("idle", "Salvo");
    }, 3000);
  }
}

// ===== ATALHO DE TECLADO: Ctrl+S =====
document.addEventListener("keydown", function (e) {
  // Ctrl+S (ou Cmd+S no Mac)
  if ((e.ctrlKey || e.metaKey) && e.key === "s") {
    e.preventDefault(); // impede o salvamento da página
    forceSave();
  }
});

// ===== INICIALIZAÇÃO: estado padrão =====
document.addEventListener("DOMContentLoaded", function () {
  setSaveStatus("idle", "Salvo");
});

function compressImage(file, maxWidth = 800, quality = 0.7, callback) {
  const reader = new FileReader();
  reader.onload = function (e) {
    const img = new Image();
    img.onload = function () {
      const canvas = document.createElement("canvas");
      let width = img.width;
      let height = img.height;
      if (width > maxWidth) {
        height = (height * maxWidth) / width;
        width = maxWidth;
      }
      canvas.width = width;
      canvas.height = height;
      const ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0, width, height);
      // Converte para JPEG com qualidade (0.7 = 70%)
      const base64 = canvas.toDataURL("image/jpeg", quality);
      callback(base64);
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

// ===== FUNÇÃO DE DIAGNÓSTICO PARA CONEXÕES =====
function debugConnections() {
  console.log("🔍 DIAGNÓSTICO DE CONEXÕES:");
  console.log(`📊 Total de conexões: ${connections.length}`);

  connections.forEach((conn, index) => {
    console.log(`  Conexão ${index + 1}:`);
    console.log(
      `    From: ${conn.fromCard?.dataset?.cardId || "SEM ID"} (${conn.fromPos})`,
    );
    console.log(
      `    To: ${conn.toCard?.dataset?.cardId || "SEM ID"} (${conn.toPos})`,
    );
    console.log(`    Line: ${conn.lineElement ? "✅" : "❌"}`);
    console.log(`    Arrow: ${conn.arrowElement ? "✅" : "❌"}`);
  });

  // Verifica cards
  const cards = document.querySelectorAll(".editable-item");
  console.log(`📇 Total de cards: ${cards.length}`);
  cards.forEach((card) => {
    console.log(`  Card: ${card.dataset.cardId || "SEM ID"}`);
  });
}

function ensureCardId(card) {
  if (!card.dataset.cardId) {
    card.dataset.cardId = `card_${Date.now()}_${Math.random().toString(36).substr(2, 6)}`;
  }
  return card;
}
