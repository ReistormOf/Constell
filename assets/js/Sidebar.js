// ===== AUTENTICAÇÃO =====
function getUserToken() {
  return localStorage.getItem("user_token");
}

function setUserToken(token, nome) {
  localStorage.setItem("user_token", token);
  localStorage.setItem("user_name", nome);
}

function clearUser() {
  localStorage.removeItem("user_token");
  localStorage.removeItem("user_name");
}

async function login(email, senha) {
  const response = await fetch("api.php?action=login", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, senha }),
  });
  const result = await response.json();
  if (result.success) {
    setUserToken(result.token, result.usuario);
    return true;
  } else {
    alert(result.error);
    return false;
  }
}

async function register(nome, email, senha) {
  const response = await fetch("api.php?action=register", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ nome, email, senha }),
  });
  const result = await response.json();
  if (result.success) {
    setUserToken(result.token, result.usuario);
    return true;
  } else {
    alert(result.error);
    return false;
  }
}

// ===== SUBSTITUIR saveFullState e loadFullState =====
// Substitua USER_TOKEN pela função getUserToken()
async function saveFullState() {
  if (isEditing) return;
  if (window._savingFullState) return;

  window._savingFullState = true;

  try {
    await new Promise((resolve) => requestAnimationFrame(resolve));

    const container = document.getElementById("cards-container");
    if (!container) return;

    // ---- 1. Extrai os cards ----
    const items = container.querySelectorAll(".editable-item");
    const cardsData = [];

    items.forEach((card, index) => {
      const clone = card.cloneNode(true);
      clone
        .querySelectorAll(
          ".delete-btn, .duplicate-btn, .drag-handle, .resize-handle, .flow-port, .edit-toolbar",
        )
        .forEach((el) => el.remove());
      clone
        .querySelectorAll('[contenteditable="true"]')
        .forEach((el) => el.removeAttribute("contenteditable"));

      cardsData.push({
        cardId: card.dataset.cardId || null, // <-- CORRIGIDO: cardId
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

    // ---- 2. Extrai as conexões DIRETAMENTE DO SVG (com IDs) ----
    const svg = document.getElementById("flowchart-svg");
    const connectionsData = [];

    if (svg) {
      const pathElements = svg.querySelectorAll("path.connection-line");
      const allCards = Array.from(container.querySelectorAll(".editable-item"));

      pathElements.forEach((path) => {
        const fromId = path.dataset.fromId;
        const toId = path.dataset.toId;
        const fromPos = path.dataset.fromPos || "right";
        const toPos = path.dataset.toPos || "left";

        if (!fromId || !toId) {
          console.warn("⚠️ Conexão sem IDs (ignorada):", path);
          return;
        }

        // Encontra índices atuais (para fallback)
        let fromIndex = -1;
        let toIndex = -1;
        allCards.forEach((card, idx) => {
          const cardId = card.dataset.cardId || card.id || "";
          if (cardId === fromId) fromIndex = idx;
          if (cardId === toId) toIndex = idx;
        });

        connectionsData.push({
          fromId: fromId,
          fromIndex: fromIndex,
          fromPos: fromPos,
          toId: toId,
          toIndex: toIndex,
          toPos: toPos,
        });
      });

      console.log(`🔗 Extraídas ${connectionsData.length} conexões do SVG`);
    } else {
      console.warn("⚠️ SVG não encontrado – nenhuma conexão será salva");
    }

    // ---- 3. Atualiza dadosCompletos ----
    if (!dadosCompletos.materias[currentMateria]) {
      dadosCompletos.materias[currentMateria] = { topicos: {} };
    }
    if (!dadosCompletos.materias[currentMateria].topicos[currentTopico]) {
      dadosCompletos.materias[currentMateria].topicos[currentTopico] = {
        paginas: [],
      };
    }
    const paginas =
      dadosCompletos.materias[currentMateria].topicos[currentTopico].paginas;
    paginas[currentPagina] = {
      public_id: paginas[currentPagina]?.public_id || null,
      is_public: paginas[currentPagina]?.is_public || 0,
      titulo: paginas[currentPagina]?.titulo || `Página ${currentPagina + 1}`,
      cards: cardsData,
      connections: connectionsData,
    };
    dadosCompletos.page = {
      cards: cardsData,
      connections: connectionsData,
      is_public: paginas[currentPagina]?.is_public || 0,
    };
    dadosCompletos.current = {
      materia: currentMateria,
      topico: currentTopico,
      pagina: currentPagina,
    };

    // ---- 4. Envia para o servidor ----
    const response = await fetch("api.php?action=save", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": window.CSRF_TOKEN || "",
      },
      credentials: "same-origin",
      body: JSON.stringify({ data: dadosCompletos }),
    });

    if (response.status === 401) {
      console.warn("Sessão expirada – redirecionando para login");
      window.location.href = "login.php";
      return;
    }

    const result = await response.json();
    if (result.success) {
      console.log(
        "✅ Dados salvos no servidor com",
        connectionsData.length,
        "conexões",
      );
    } else {
      console.warn("⚠️ Falha ao salvar:", result.error);
    }
  } catch (e) {
    console.error("Erro ao salvar:", e);
  } finally {
    window._savingFullState = false;
  }
}

async function loadFullState() {
  // Não precisa de token; usa sessão
  try {
    const response = await fetch("api.php?action=load", {
      credentials: "same-origin",
    });

    if (response.status === 401) {
      console.warn("Sessão expirada – redirecionando para login");
      window.location.href = "login.php";
      return false;
    }

    if (response.ok) {
      const result = await response.json();
      if (result.success && result.data) {
        dadosCompletos = result.data;

        // 🔥 Captura IDs e public_id da página atual
        // Dentro de loadFullState, após definir window.currentPublicId
        const pageData = dadosCompletos.page || { cards: [], connections: [] };
        window.currentPaginaId = pageData.id || null;
        window.currentPublicId = pageData.public_id || null;
        window.currentIsPublic = pageData.is_public || 0; // 🔥 ADICIONE ESTA LINHA

        // 🔥 Define as variáveis GLOBAIS (window)
        window.currentMateria = dadosCompletos.current?.materia || "";
        window.currentTopico = dadosCompletos.current?.topico || "";
        window.currentPagina = dadosCompletos.current?.pagina || 0;

        // Também atualiza as variáveis locais (se usadas em outras funções)
        currentMateria = window.currentMateria;
        currentTopico = window.currentTopico;
        currentPagina = window.currentPagina;

        // Restaura os dados da página (apenas uma vez)
        if (pageData.cards && pageData.cards.length > 0) {
          console.log(
            `🔄 Restaurando ${pageData.cards.length} cards e ${pageData.connections?.length || 0} conexões`,
          );
          restoreState(pageData);
        } else {
          console.warn("⚠️ Nenhum dado de página encontrado");
          // Se não houver cards, ainda assim restaura para limpar a tela
          restoreState({ cards: [], connections: [] });
        }

        updateMenuUI();
        localStorage.setItem("devstudio_data", JSON.stringify(dadosCompletos));
        return true;
      }
    }

    // Fallback localStorage
    const localData = localStorage.getItem("devstudio_data");
    if (localData) {
      const parsed = JSON.parse(localData);
      dadosCompletos = parsed;

      // 🔥 Define as variáveis GLOBAIS (window)
      window.currentMateria = dadosCompletos.current?.materia || "";
      window.currentTopico = dadosCompletos.current?.topico || "";
      window.currentPagina = dadosCompletos.current?.pagina || 0;
      window.currentPaginaId = dadosCompletos.page?.id || null;
      window.currentPublicId = dadosCompletos.page?.public_id || null;

      currentMateria = window.currentMateria;
      currentTopico = window.currentTopico;
      currentPagina = window.currentPagina;

      restoreState(dadosCompletos.page || { cards: [], connections: [] });
      updateMenuUI();
      return true;
    }

    // Inicializa vazio
    dadosCompletos = {
      materias: {},
      current: { materia: "", topico: "", pagina: 0 },
      page: { cards: [], connections: [] },
    };
    window.currentPublicId = null;
    localStorage.setItem("devstudio_data", JSON.stringify(dadosCompletos));
    return true;
  } catch (e) {
    console.warn("Falha ao carregar:", e);
    return false;
  }
}

function toggleNavSidebar() {
  const sidebar = document.getElementById("nav-sidebar");
  sidebar.style.display = sidebar.style.display === "none" ? "flex" : "none";
}

function restorePage() {
  const container = document.getElementById("cards-container");
  if (!container) return;

  // Remove cards
  container.querySelectorAll(".editable-item").forEach((el) => el.remove());

  // 🔥 Remove apenas as conexões que estão no array (e seus elementos)
  // Isso não remove setas órfãs, mas como restoreState recriará todas as conexões,
  // as setas atuais serão substituídas. Não há necessidade de limpar o SVG inteiro.
  connections.forEach((conn) => {
    if (conn.lineElement) conn.lineElement.remove();
    if (conn.arrowElement) conn.arrowElement.remove();
    if (conn.deleteBtn) conn.deleteBtn.remove();
  });
  connections = [];

  // Remove mensagem vazia
  const oldMsg = container.querySelector(".empty-message");
  if (oldMsg) oldMsg.remove();

  // Limpa estados de edição
  if (window.activeElement) {
    window.activeElement.classList.remove("active-editing");
    window.activeElement = null;
  }
  if (window.currentToolbar) {
    window.currentToolbar.remove();
    window.currentToolbar = null;
  }

  // Obtém dados da página
  let pageData = dadosCompletos.page || null;
  if (!pageData || !pageData.cards || pageData.cards.length === 0) {
    if (!currentMateria || !dadosCompletos.materias[currentMateria]) {
      showEmptyMessage(container, "Selecione uma matéria ou crie uma nova.");
      window.currentPublicId = null;
      updateMenuUI();
      return;
    }
    const materia = dadosCompletos.materias[currentMateria];
    const topico = materia?.topicos[currentTopico];
    pageData = topico?.paginas?.[currentPagina] || null;
    if (pageData) {
      dadosCompletos.page = pageData;
    }
  }

  if (pageData) {
    const msg = container.querySelector(".empty-message");
    if (msg) msg.remove();

    window.currentPublicId = pageData.public_id || null;
    window.currentIsPublic = pageData.is_public || 0;
    restoreState(pageData); // recria cards e conexões (setas)
  } else {
    showEmptyMessage(container, "Página vazia – adicione cards.");
    window.currentPublicId = null;
  }

  updateMenuUI();
  updateContainerHeight();
}

// Função auxiliar para mostrar mensagem sem destruir o SVG
function showEmptyMessage(container, text) {
  let msg = container.querySelector(".empty-message");
  if (!msg) {
    msg = document.createElement("div");
    msg.className = "empty-message";
    msg.style.cssText = `
      color: #888;
      padding: 40px;
      text-align: center;
      font-size: 16px;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none;
    `;
    container.appendChild(msg);
  }
  msg.textContent = text;
}

async function goToMateria(materia) {
  if (!materia || !dadosCompletos.materias[materia]) {
    console.warn("Matéria não encontrada:", materia);
    return;
  }
  await saveFullState();
  currentMateria = materia;
  const topicos = Object.keys(dadosCompletos.materias[materia].topicos);
  if (topicos.length === 0) {
    dadosCompletos.materias[materia].topicos["Tópico 1"] = { paginas: [] };
    currentTopico = "Tópico 1";
  } else {
    currentTopico = topicos[0];
  }
  currentPagina = 0;
  window.currentPagina = 0;

  // 🔥 Atualiza dadosCompletos.page com a página atual
  const paginas =
    dadosCompletos.materias[materia].topicos[currentTopico].paginas;
  const pageData =
    paginas.length > 0 ? paginas[0] : { cards: [], connections: [] };
  dadosCompletos.page = pageData;
  window.currentPublicId = pageData.public_id || null;
  window.currentIsPublic = pageData.is_public || 0;

  restorePage(); // agora usa dadosCompletos.page
  updateMenuUI();
}

async function goToTopico(materia, topico) {
  if (
    !materia ||
    !dadosCompletos.materias[materia] ||
    !topico ||
    !dadosCompletos.materias[materia].topicos[topico]
  ) {
    console.warn("Tópico não encontrado:", topico);
    return;
  }
  await saveFullState();
  currentMateria = materia;
  currentTopico = topico;
  currentPagina = 0;
  window.currentPagina = 0;

  // 🔥 Atualiza dadosCompletos.page com a primeira página do tópico
  const paginas = dadosCompletos.materias[materia].topicos[topico].paginas;
  const pageData =
    paginas.length > 0 ? paginas[0] : { cards: [], connections: [] };
  dadosCompletos.page = pageData;
  window.currentPublicId = pageData.public_id || null;
  window.currentIsPublic = pageData.is_public || 0;

  restorePage();
  updateMenuUI();
}

async function goToPagina(materia, topico, paginaIndex) {
  await saveFullState();
  currentMateria = materia;
  currentTopico = topico;
  currentPagina = paginaIndex;
  window.currentPagina = paginaIndex; // <-- ADICIONE ESTA LINHA

  const paginas =
    dadosCompletos.materias[materia]?.topicos[topico]?.paginas || [];
  const pageData = paginas[paginaIndex] || { cards: [], connections: [] };
  dadosCompletos.page = pageData;
  window.currentPublicId = pageData.public_id || null;
  window.currentIsPublic = pageData.is_public || 0;

  restorePage();
  updateMenuUI();
}

function mudarPagina(delta) {
  const paginas =
    dadosCompletos.materias[currentMateria]?.topicos[currentTopico]?.paginas ||
    [];
  const nova = currentPagina + delta;
  if (nova >= 0 && nova < paginas.length) {
    goToPagina(currentMateria, currentTopico, nova);
  }
}

function criarMateria() {
  pushState();
  const nome = prompt("Nome da nova matéria:");
  if (!nome || nome.trim() === "") {
    alert("O nome da matéria não pode estar vazio.");
    return;
  }
  if (!dadosCompletos.materias || Array.isArray(dadosCompletos.materias)) {
    dadosCompletos.materias = {};
  }
  if (dadosCompletos.materias[nome]) {
    alert("Já existe uma matéria com esse nome.");
    return;
  }
  dadosCompletos.materias[nome] = { topicos: {} };
  saveFullState().then(() => {
    goToMateria(nome);
  });
}

function criarTopico() {
  pushState();
  if (!currentMateria || !dadosCompletos.materias[currentMateria]) {
    alert("Selecione uma matéria antes de criar um tópico.");
    return;
  }
  const nome = prompt("Nome do novo tópico:");
  if (!nome || nome.trim() === "") {
    alert("O nome do tópico não pode estar vazio.");
    return;
  }
  if (dadosCompletos.materias[currentMateria].topicos[nome]) {
    alert("Já existe um tópico com esse nome.");
    return;
  }
  dadosCompletos.materias[currentMateria].topicos[nome] = { paginas: [] };
  saveFullState().then(() => {
    goToTopico(currentMateria, nome);
  });
}

function limparTodasConexoesDoSVG() {
  const svg = document.getElementById("flowchart-svg");
  if (!svg) return;

  // Remove linhas
  svg.querySelectorAll("path.connection-line").forEach((el) => el.remove());

  // Remove setas (paths com fill #ff6b6b fora do defs)
  svg.querySelectorAll("path[fill='#ff6b6b']").forEach((el) => {
    if (!el.closest("defs")) el.remove();
  });

  // Remove qualquer path com formato de triângulo (seta) fora do defs
  svg.querySelectorAll("path").forEach((el) => {
    const d = el.getAttribute("d") || "";
    if (
      d.includes("M") &&
      d.includes("L") &&
      d.includes("Z") &&
      !el.closest("defs")
    ) {
      el.remove();
    }
  });

  // Remove linha temporária
  const tempLine = document.getElementById("temp-line");
  if (tempLine) tempLine.remove();

  // Zera o array de conexões e remove elementos associados
  connections.forEach((conn) => {
    if (conn.lineElement) conn.lineElement.remove();
    if (conn.arrowElement) conn.arrowElement.remove();
    if (conn.deleteBtn) conn.deleteBtn.remove();
  });
  connections = [];
}

async function novaPagina() {
  pushState();
  if (!currentMateria || !dadosCompletos.materias[currentMateria]) {
    alert("Selecione uma matéria antes de criar uma página.");
    return;
  }
  if (
    !currentTopico ||
    !dadosCompletos.materias[currentMateria].topicos[currentTopico]
  ) {
    alert("Selecione um tópico antes de criar uma página.");
    return;
  }

  // 🔥 LIMPEZA AGRESSIVA: remove todas as setas e linhas existentes
  limparTodasConexoesDoSVG();

  const paginas =
    dadosCompletos.materias[currentMateria].topicos[currentTopico].paginas;
  const novoIndice = paginas.length;

  const novaPaginaData = {
    titulo: `Página ${novoIndice + 1}`,
    cards: [],
    connections: [],
    public_id: null,
    is_public: 0,
  };

  paginas.push(novaPaginaData);
  currentPagina = novoIndice;
  dadosCompletos.page = novaPaginaData;
  window.currentPublicId = null;
  window.currentIsPublic = 0;

  restorePage(); // agora restaura a página vazia (não tem conexões)
  updateMenuUI();
  await saveFullState();
}

function fecharModalDelecao() {
  const details = document.getElementById("delete-details");
  if (details) {
    details.removeAttribute("open");
  }
}

// ============================================================
// DELETAR PÁGINA (corrigido)
// ============================================================
function deletarPagina() {
  pushState();
  const paginas =
    dadosCompletos.materias[currentMateria]?.topicos[currentTopico]?.paginas;
  if (!paginas || paginas.length <= 1) {
    alert("Não é possível deletar a única página do tópico.");
    return;
  }
  if (
    !confirm(
      `Deletar página ${currentPagina + 1} do tópico "${currentTopico}"?`,
    )
  )
    return;

  // 1. Remove a página do array
  paginas.splice(currentPagina, 1);

  // 2. Ajusta o índice da página atual
  if (currentPagina >= paginas.length) {
    currentPagina = paginas.length - 1;
  }

  // 🔥 ATUALIZA dadosCompletos.page com a página que agora está no índice currentPagina
  const novaPaginaData = paginas[currentPagina] || {
    cards: [],
    connections: [],
    titulo: "Página " + (currentPagina + 1),
  };
  dadosCompletos.page = novaPaginaData;
  window.currentPublicId = novaPaginaData.public_id || null;
  window.currentIsPublic = novaPaginaData.is_public || 0;

  // 3. Atualiza a tela IMEDIATAMENTE
  restorePage();
  renderNavTree();
  updateMenuUI();
  fecharModalDelecao();

  // 4. Salva em segundo plano
  saveFullState().catch((err) =>
    console.error("Erro ao salvar após deletar página:", err),
  );
}

// ============================================================
// DELETAR TÓPICO (corrigido)
// ============================================================
function deletarTopico() {
  pushState();
  const materia = dadosCompletos.materias[currentMateria];
  if (!materia) return;
  const topicos = Object.keys(materia.topicos);
  if (topicos.length <= 1) {
    alert("Não é possível deletar o único tópico da matéria.");
    return;
  }
  if (!confirm(`Deletar tópico "${currentTopico}" e todas as suas páginas?`))
    return;

  // 1. Remove o tópico
  delete materia.topicos[currentTopico];

  // 2. Seleciona o primeiro tópico restante
  const novosTopicos = Object.keys(materia.topicos);
  currentTopico = novosTopicos[0];
  currentPagina = 0;

  // 🔥 ATUALIZA dadosCompletos.page com a primeira página do novo tópico
  const paginas = materia.topicos[currentTopico].paginas;
  const novaPaginaData =
    paginas.length > 0
      ? paginas[0]
      : { cards: [], connections: [], titulo: "Página 1" };
  dadosCompletos.page = novaPaginaData;
  window.currentPublicId = novaPaginaData.public_id || null;
  window.currentIsPublic = novaPaginaData.is_public || 0;

  // 3. Atualiza a tela IMEDIATAMENTE
  restorePage();
  renderNavTree();
  updateMenuUI();
  fecharModalDelecao();

  // 4. Salva em segundo plano
  saveFullState().catch((err) =>
    console.error("Erro ao salvar após deletar tópico:", err),
  );
}

// ============================================================
// DELETAR MATÉRIA (corrigido)
// ============================================================
function deletarMateria() {
  pushState();
  const materiasKeys = Object.keys(dadosCompletos.materias);
  if (materiasKeys.length <= 1) {
    alert("Não é possível deletar a única matéria.");
    return;
  }
  if (!confirm(`Deletar matéria "${currentMateria}" e todos os seus tópicos?`))
    return;

  // 1. Remove a matéria
  delete dadosCompletos.materias[currentMateria];

  // 2. Seleciona a primeira matéria restante
  const novaMateria = Object.keys(dadosCompletos.materias)[0];
  currentMateria = novaMateria;
  const topicos = Object.keys(dadosCompletos.materias[novaMateria].topicos);
  currentTopico = topicos[0] || "Tópico 1";
  currentPagina = 0;

  // 🔥 ATUALIZA dadosCompletos.page com a primeira página do novo tópico
  const paginas =
    dadosCompletos.materias[novaMateria].topicos[currentTopico].paginas;
  const novaPaginaData =
    paginas.length > 0
      ? paginas[0]
      : { cards: [], connections: [], titulo: "Página 1" };
  dadosCompletos.page = novaPaginaData;
  window.currentPublicId = novaPaginaData.public_id || null;
  window.currentIsPublic = novaPaginaData.is_public || 0;

  // 3. Atualiza a tela IMEDIATAMENTE
  restorePage();
  renderNavTree();
  updateMenuUI();
  fecharModalDelecao();

  // 4. Salva em segundo plano
  saveFullState().catch((err) =>
    console.error("Erro ao salvar após deletar matéria:", err),
  );
}

// ============================================================
//  RENDERIZAÇÃO DA ÁRVORE DE NAVEGAÇÃO
// ============================================================

function renderNavTree() {
  const container = document.getElementById("nav-tree");
  if (!container) return;

  if (!dadosCompletos || Object.keys(dadosCompletos.materias).length === 0) {
    container.innerHTML =
      '<div class="nav-empty">Nenhuma matéria criada.<br>Clique em "+ Mat" no topo.</div>';
    return;
  }

  let html =
    '<ul class="nav-tree-ul" style="list-style:none; padding-left:0;">';
  const materias = dadosCompletos.materias || {};

  for (const [materia, dadosMateria] of Object.entries(materias)) {
    if (!materia || materia.trim() === "") continue;
    const isActiveMateria = materia === currentMateria;
    const topicos = dadosMateria.topicos || {};
    const hasTopicos = Object.keys(topicos).length > 0;

    html += `<li class="nav-tree-item" data-type="materia" data-name="${materia}">`;
    html += `<div class="nav-tree-header ${isActiveMateria ? "active" : ""}">`;
    html += `<span class="nav-tree-arrow">${hasTopicos ? "▶" : "▪"}</span>`;
    html += `<span class="nav-tree-label" data-action="goToMateria" data-value="${materia}">📘 ${materia}</span>`;
    html += `<span class="nav-tree-badge">${Object.keys(topicos).length}</span>`;
    html += `</div>`;

    if (hasTopicos) {
      html += `<ul class="nav-tree-children" style="list-style:none; padding-left:20px; display:${isActiveMateria ? "block" : "none"};">`;
      for (const [topico, dadosTopico] of Object.entries(topicos)) {
        const isActiveTopico =
          topico === currentTopico && materia === currentMateria;
        const paginas = dadosTopico.paginas || [];
        const hasPaginas = paginas.length > 0;

        html += `<li class="nav-tree-item" data-type="topico" data-name="${topico}">`;
        html += `<div class="nav-tree-header ${isActiveTopico ? "active" : ""}" onclick="toggleNavTree(this)">`;
        html += `<span class="nav-tree-arrow">${hasPaginas ? "▶" : "▪"}</span>`;
        html += `<span class="nav-tree-label" data-action="goToTopico" data-value="${topico}">📂 ${topico}</span>`;
        html += `<span class="nav-tree-badge">${paginas.length}</span>`;
        html += `</div>`;

        if (hasPaginas) {
          html += `<ul class="nav-tree-children" style="list-style:none; padding-left:20px; display:${isActiveTopico ? "block" : "none"};">`;
          paginas.forEach((pagina, idx) => {
            const isActivePagina =
              idx === currentPagina &&
              topico === currentTopico &&
              materia === currentMateria;
            const label = pagina.titulo || `📄 Pág. ${idx + 1}`;
            html += `<li class="nav-tree-item" data-type="pagina" data-index="${idx}">`;
            html += `<div class="nav-tree-header ${isActivePagina ? "active" : ""}" style="padding-left:8px;">`;
            html += `<span class="nav-tree-label" data-action="goToPagina" data-value="${idx}">${label}</span>`;
            html += `</div>`;
            html += `</li>`;
          });
          html += `</ul>`;
        }
        html += `</li>`;
      }
      html += `</ul>`;
    }
    html += `</li>`;
  }

  html += "</ul>";
  container.innerHTML = html;
  expandPathToCurrent();
}

// Renomear itens da árvore com duplo clique
let navTimer = null;

// ===== NAVEGAÇÃO INSTANTÂNEA + RENOMEAR COM Ctrl+Clique =====
document.getElementById("nav-sidebar").addEventListener("click", function (e) {
  // 1. Encontra o header clicado
  const header = e.target.closest(".nav-tree-header");
  if (!header) return;

  // 2. Se clicou na seta → apenas toggle e para
  if (e.target.closest(".nav-tree-arrow")) {
    toggleNavTree(header);
    return;
  }

  // 3. Ctrl+Clique → renomear (em qualquer lugar do header)
  if (e.ctrlKey) {
    e.preventDefault();
    const item = header.closest(".nav-tree-item");
    if (!item) return;
    const type = item.dataset.type;
    const label = header.querySelector(".nav-tree-label");
    if (!label) return;

    // Lógica de renomear (igual ao que já existia)
    if (type === "pagina") {
      const index = parseInt(item.dataset.index);
      const materia = currentMateria;
      const topico = currentTopico;
      if (!materia || !topico) return;
      const paginas =
        dadosCompletos.materias[materia]?.topicos[topico]?.paginas;
      if (!paginas || !paginas[index]) return;
      const novoNome = prompt(
        "Novo nome da página:",
        paginas[index].titulo || label.textContent.trim(),
      );
      if (novoNome && novoNome.trim() !== "") {
        paginas[index].titulo = novoNome.trim();
        renderNavTree();
        saveFullState();
      }
    } else if (type === "topico") {
      const nomeAntigo = item.dataset.name;
      const materia = currentMateria;
      if (!materia) return;
      const topicoObj = dadosCompletos.materias[materia]?.topicos[nomeAntigo];
      if (!topicoObj) return;
      const novoNome = prompt("Novo nome do tópico:", nomeAntigo);
      if (novoNome && novoNome.trim() !== "" && novoNome !== nomeAntigo) {
        dadosCompletos.materias[materia].topicos[novoNome.trim()] = topicoObj;
        delete dadosCompletos.materias[materia].topicos[nomeAntigo];
        if (currentTopico === nomeAntigo) currentTopico = novoNome.trim();
        renderNavTree();
        saveFullState();
      }
    } else if (type === "materia") {
      const nomeAntigo = item.dataset.name;
      const materiaObj = dadosCompletos.materias[nomeAntigo];
      if (!materiaObj) return;
      const novoNome = prompt("Novo nome da matéria:", nomeAntigo);
      if (novoNome && novoNome.trim() !== "" && novoNome !== nomeAntigo) {
        dadosCompletos.materias[novoNome.trim()] = materiaObj;
        delete dadosCompletos.materias[nomeAntigo];
        if (currentMateria === nomeAntigo) currentMateria = novoNome.trim();
        renderNavTree();
        saveFullState();
      }
    }
    return;
  }

  // 4. Clique normal → navegação
  const label = header.querySelector(".nav-tree-label");
  if (!label) return;
  const action = label.dataset.action;
  const value = label.dataset.value;
  if (!action) return;

  if (action === "goToMateria") {
    goToMateria(value);
  } else if (action === "goToTopico") {
    const item = header.closest(".nav-tree-item");
    const materiaPai = item?.closest('[data-type="materia"]')?.dataset.name;
    if (materiaPai) goToTopico(materiaPai, value);
  } else if (action === "goToPagina") {
    const idx = parseInt(value);
    const item = header.closest(".nav-tree-item");
    const topicoItem = item?.closest('[data-type="topico"]');
    const materiaPai = topicoItem?.closest('[data-type="materia"]')?.dataset
      .name;
    const topicoNome = topicoItem?.dataset.name;
    if (materiaPai && topicoNome) goToPagina(materiaPai, topicoNome, idx);
  }
});

function expandPathToCurrent() {
  const activeItems = document.querySelectorAll(".nav-tree-header.active");
  activeItems.forEach((header) => {
    let parent = header.closest(".nav-tree-item");
    while (parent) {
      const childList = parent.querySelector(":scope > .nav-tree-children");
      if (childList) {
        childList.style.display = "block";
        const arrow = parent.querySelector(
          ":scope > .nav-tree-header > .nav-tree-arrow",
        );
        if (arrow) arrow.textContent = "▼";
      }
      parent = parent.parentElement?.closest(".nav-tree-item");
    }
  });
}

function toggleNavTree(headerElement) {
  const arrow = headerElement.querySelector(".nav-tree-arrow");
  const childrenList = headerElement.nextElementSibling;
  if (!childrenList || !childrenList.classList.contains("nav-tree-children"))
    return;

  const isHidden =
    childrenList.style.display === "none" || !childrenList.style.display;
  childrenList.style.display = isHidden ? "block" : "none";
  if (arrow) {
    arrow.textContent = isHidden ? "▼" : "▶";
  }
}

function updateMenuUI() {
  renderNavTree();
  const total =
    dadosCompletos.materias[currentMateria]?.topicos[currentTopico]?.paginas
      .length || 0;
  const indicator = document.getElementById("page-indicator-sidebar");
  if (indicator) {
    if (currentMateria) {
      indicator.textContent = `Página ${currentPagina + 1} de ${total}`;
    } else {
      indicator.textContent = "Nenhuma matéria";
    }
  }
}

// ============================================================
//  INICIALIZAÇÃO
// ============================================================
document.addEventListener("DOMContentLoaded", async function () {
  const svg = inicializarSvg();
  if (svg) {
    window.svg = svg;
    updateSvgSize();
    window.addEventListener("resize", updateSvgSize);
    const resizeObserver = new ResizeObserver(() => updateSvgSize());
    resizeObserver.observe(container);
  }

  await loadFullState(); // ← única chamada
  updateMenuUI();
  updateContainerHeight();

  setInterval(() => debouncedSaveFullState(), 5000);
  window.addEventListener("beforeunload", () => debouncedSaveFullState(true));
});

function ajustarSidebars() {
  const navbar = document.querySelector(".navbar");
  if (!navbar) return;
  const altura = navbar.offsetHeight;

  const navSidebar = document.querySelector("#nav-sidebar");
  const sidebar = document.querySelector("#sidebar");
  const container = document.querySelector("#cards-container");

  if (navSidebar) {
    navSidebar.style.top = altura + "px";
    navSidebar.style.height = "calc(100vh - " + altura + "px)";
  }
  if (sidebar) {
    sidebar.style.top = altura + "px";
    sidebar.style.height = "calc(100vh - " + altura + "px)";
  }
  if (container) {
    container.style.paddingTop = altura + "px"; // compensação
    container.style.paddingLeft = "280px";
    container.style.paddingRight = "80px";
    container.style.paddingBottom = "20px";
    container.style.height = "auto";
    container.style.minHeight = "100vh";
  }
}
window.addEventListener("load", ajustarSidebars);
window.addEventListener("resize", ajustarSidebars);

// SELETOR DE TAMANHO DOS CARDS
document.querySelectorAll(".size-btn").forEach((btn) => {
  btn.addEventListener("click", function () {
    document
      .querySelectorAll(".size-btn")
      .forEach((b) => b.classList.remove("active"));
    this.classList.add("active");
    selectedSize = this.dataset.size;
    const width = getWidthFromSize(selectedSize);
    const activeElement = document.querySelector(".active-editing");
    if (activeElement) {
      const card = activeElement.closest(".editable-item");
      if (card) {
        card.style.width = width;
        updateContainerHeight();
      }
    }
  });
});

// CAPTURAR TEXTO SELECIONADO COM O MOUSE
document.addEventListener("mouseup", function (e) {
  // 1) Só ativa se Shift estiver pressionado
  if (!e.shiftKey) return;

  // 2) Se a toolbar estiver aberta, não interfere (opcional)
  if (activeToolbar || currentToolbar) return;

  const sel = window.getSelection();
  const text = sel.toString().trim();
  if (text.length > 0 && text.length < 30 && !text.includes(" ")) {
    const anchorNode = sel.anchorNode;
    if (anchorNode) {
      const parent =
        anchorNode.nodeType === Node.TEXT_NODE
          ? anchorNode.parentElement
          : anchorNode;
      if (parent && parent.closest('[contenteditable="true"]')) {
        filterWord = text;
        const toolbar = activeToolbar || currentToolbar;
        if (toolbar) {
          const input = toolbar.querySelector(".filter-input");
          if (input) input.value = text;
        }
        if (targetElements.length > 0) {
          updateHighlights();
          updateFilterIndicator();
        }
      }
    }
  }
});

// ===== NOTIFICAÇÃO DE SALVAMENTO =====
const saveNotif = document.getElementById("save-notification");
const saveNotifText = document.getElementById("save-notification-text");

// Mostra a notificação com tipo e mensagem (agora só muda o ícone)
// ===== NOTIFICAÇÃO DE SALVAMENTO (ícone apenas) =====
function showSaveNotification(type = "success") {
  const btn = document.querySelector(".btn-save");
  const icon = btn?.querySelector("i");
  if (!btn) return;

  btn.classList.remove("saving", "error", "saved");

  if (type === "saving") {
    btn.classList.add("saving");
    if (icon) icon.className = "bi bi-arrow-repeat";
  } else if (type === "error") {
    btn.classList.add("error");
    if (icon) icon.className = "bi bi-exclamation-circle-fill";
  } else {
    btn.classList.add("saved");
    if (icon) icon.className = "bi bi-check-circle-fill";

    clearTimeout(btn._timeout);
    btn._timeout = setTimeout(() => {
      btn.classList.remove("saved");
      if (icon) icon.className = "bi bi-cloud-upload";
    }, 1500);
  }
}

// ===== WRAPPER COM DELAY MÍNIMO =====
const originalSaveFullState = window.saveFullState || saveFullState;

window.saveFullState = async function () {
  // Mostra carregando imediatamente
  showSaveNotification("saving");

  const start = Date.now();
  let error = null;

  try {
    await originalSaveFullState();
  } catch (e) {
    error = e;
    console.error(e);
  }

  // Calcula quanto tempo já passou
  const elapsed = Date.now() - start;
  const minDisplay = 300; // 300ms mínimo

  if (elapsed < minDisplay) {
    // Aguarda o restante do tempo mínimo
    await new Promise((resolve) => setTimeout(resolve, minDisplay - elapsed));
  }

  // Agora sim mostra o resultado final
  if (error) {
    showSaveNotification("error");
    throw error;
  } else {
    showSaveNotification("success");
  }
};

function forceSave() {
  window.saveFullState();
}
