<?php
// view.php – Página pública com visual premium e pan/zoom
session_start();
require_once 'config.php';

$public_id = $_GET['public_id'] ?? '';
if (empty($public_id)) {
    die('Identificador público não informado.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Página Pública</title>
    <!-- Bootstrap, Ícones e Fontes -->
    <link rel="stylesheet" href="assets/css/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Pan & Zoom (já tem no index, vamos reutilizar) -->
    <script src="assets/js/PanZoom.js"></script>
    <!-- Bibliotecas para criptografia (caso precise) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pako/2.0.4/pako.min.js"></script>
    <style>
        /* ===== CSS BASE – mesmo do index ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        body {
            background-color: #0d1117;
            color: #c9d1d9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-image:
                radial-gradient(circle, #2a2a2a 1.5px, transparent 1.5px),
                repeating-linear-gradient(45deg, rgba(255, 255, 255, 0.01) 0px, rgba(255, 255, 255, 0.01) 2px, transparent 2px, transparent 4px);
            background-size: 20px 20px, 8px 8px;
            background-position: 20px 20px;
        }

        :root {
            --bg-body: #0d1117;
            --bg-surface: #161b22;
            --bg-elevated: #1c2128;
            --text-primary: #f0f6fc;
            --text-secondary: #c9d1d9;
            --text-muted: #8b949e;
            --accent: #4a7cf7;
            --accent-gradient: linear-gradient(135deg, #4a7cf7, #6f42c1);
            --border-subtle: #30363d;
            --shadow-card: 0 8px 32px rgba(0, 0, 0, 0.6);
        }

        /* ===== NAVBAR PREMIUM (com controles de zoom) ===== */
        .navbar-premium {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            height: 68px;
            padding: 0 32px;
            background: rgba(13, 17, 23, 0.85);
            backdrop-filter: blur(20px) saturate(1.4);
            border-bottom: 1px solid rgba(48, 54, 61, 0.5);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: 'Inter', sans-serif;
        }

        .navbar-premium .brand-wrapper {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
        }

        .navbar-premium .brand-wrapper .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: var(--accent-gradient);
            color: #fff;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(74, 124, 247, 0.3);
        }

        .navbar-premium .brand-wrapper .brand-text {
            font-weight: 700;
            font-size: 22px;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #f0f6fc 0%, #c9d1d9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .navbar-premium .brand-wrapper .brand-badge {
            font-size: 10px;
            font-weight: 600;
            background: rgba(74, 124, 247, 0.12);
            border: 1px solid rgba(74, 124, 247, 0.15);
            padding: 2px 12px;
            border-radius: 30px;
            color: #4a7cf7;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-family: 'Share Tech Mono', monospace;
        }

        .navbar-premium .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-premium .nav-actions .btn-zoom {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid rgba(48, 54, 61, 0.4);
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-muted);
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
        }

        .navbar-premium .nav-actions .btn-zoom:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(74, 124, 247, 0.3);
            color: var(--text-primary);
        }

        .navbar-premium .nav-actions .btn-outline {
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid rgba(48, 54, 61, 0.4);
            background: transparent;
            color: var(--text-secondary);
            text-decoration: none;
            transition: 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .navbar-premium .nav-actions .btn-outline:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(74, 124, 247, 0.3);
            color: var(--text-primary);
        }

        .navbar-premium .nav-actions .btn-fork-nav {
            background: var(--accent-gradient);
            border: none;
            color: #fff;
            padding: 6px 20px;
            border-radius: 30px;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .navbar-premium .nav-actions .btn-fork-nav:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(74, 124, 247, 0.3);
            color: #fff;
        }

        .navbar-premium .nav-divider {
            width: 1px;
            height: 28px;
            background: rgba(48, 54, 61, 0.3);
            margin: 0 4px;
        }

        /* ===== CONTAINER DOS CARDS ===== */
        #pan-zoom-container {
            position: fixed;
            top: 68px;
            left: 0;
            right: 0;
            bottom: 0;
            background: transparent;
            overflow: hidden;
            z-index: 10;
        }

        #cards-container {
            width: 100%;
            min-height: 100%;
            position: relative;
            padding: 30px;
            transform-origin: 0 0;
            box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.7);
            background: radial-gradient(ellipse at 50% 30%, rgba(74, 124, 247, 0.02) 0%, transparent 70%);
        }

        /* ===== CARDS (mesmo estilo do editor) ===== */
        .editable-item {
            position: absolute;
            background: rgba(22, 27, 34, 0.88);
            backdrop-filter: blur(12px) saturate(1.2);
            border: 1px solid rgba(48, 54, 61, 0.4);
            border-radius: 16px;
            padding: 16px 20px;
            min-width: 60px;
            min-height: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            word-wrap: break-word;
            overflow-wrap: break-word;
            color: var(--text-secondary);
            font-family: 'Inter', sans-serif;
            cursor: default;
            transition: box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .editable-item h1,
        .editable-item h2,
        .editable-item h3,
        .editable-item h4,
        .editable-item h5,
        .editable-item h6 {
            color: var(--text-primary);
            margin: 0;
            padding: 2px 0;
        }

        .editable-item p {
            line-height: 1.6;
            margin: 0;
        }

        .editable-item:hover {
            border-color: rgba(74, 124, 247, 0.3);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(74, 124, 247, 0.1) inset;
        }

        /* ===== PÁGINA VAZIA ===== */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-muted);
            font-size: 1.2rem;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* ===== METADADOS FLUTUANTES ===== */
        .page-meta {
            position: fixed;
            top: 80px;
            right: 24px;
            z-index: 20;
            background: rgba(13, 17, 23, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(48, 54, 61, 0.3);
            border-radius: 12px;
            padding: 12px 20px;
            color: var(--text-secondary);
            font-size: 0.85rem;
            max-width: 280px;
            pointer-events: none;
            user-select: none;
        }

        .page-meta strong {
            color: var(--text-primary);
        }

        .page-meta .fork-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(74, 124, 247, 0.1);
            border: 1px solid rgba(74, 124, 247, 0.2);
            border-radius: 30px;
            padding: 0 12px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        @media (max-width: 640px) {
            .page-meta {
                top: 76px;
                right: 12px;
                left: 12px;
                max-width: none;
                font-size: 0.75rem;
                padding: 8px 14px;
            }
        }
    </style>
</head>

<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar-premium">
        <a class="brand-wrapper" href="index.php">
            <span class="brand-icon"><i class="bi bi-boxes"></i></span>
            <span class="brand-text">Constell</span>
            <span class="brand-badge">pública</span>
        </a>
        <div class="nav-actions">
            <!-- Controles de Zoom -->
            <button class="btn-zoom" id="zoomInBtn" title="Zoom + (Ctrl+Scroll)"><i class="bi bi-zoom-in"></i></button>
            <button class="btn-zoom" id="zoomOutBtn" title="Zoom -"><i class="bi bi-zoom-out"></i></button>
            <button class="btn-zoom" id="resetViewBtn" title="Resetar visualização"><i class="bi bi-arrow-counterclockwise"></i></button>
            <span class="nav-divider"></span>
            <a href="index.php" class="btn-outline"><i class="bi bi-house"></i> Início</a>
            <?php if (isset($_SESSION['logado']) && $_SESSION['logado'] === true): ?>
                <button class="btn-fork-nav" id="btnForkNav"><i class="bi bi-git-fork"></i> Copiar</button>
            <?php else: ?>
                <a href="auth/login.php?redirect=view.php?public_id=<?= urlencode($public_id) ?>" class="btn-outline"><i class="bi bi-box-arrow-in-right"></i> Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ===== ÁREA DOS CARDS ===== -->
    <div id="pan-zoom-container">
        <div id="cards-container">
            <div style="text-align:center; padding: 80px; color: var(--text-muted);">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3">Carregando página pública...</p>
            </div>
        </div>
    </div>

    <!-- ===== METADADOS FLUTUANTES ===== -->
    <div class="page-meta" id="pageMeta" style="display:none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
            <strong id="pageTitle">Título</strong>
            <span class="fork-badge" id="forkBadge"><i class="bi bi-git-fork"></i> <span id="forkCount">0</span></span>
        </div>
        <div style="font-size:0.75rem; color:var(--text-muted); display:flex; flex-wrap:wrap; gap:8px;">
            <span><i class="bi bi-person"></i> <span id="pageAuthor">-</span></span>
            <span><i class="bi bi-folder"></i> <span id="pageMateria">-</span></span>
            <span><i class="bi bi-hash"></i> <span id="pageTopico">-</span></span>
        </div>
    </div>

    <!-- ===== BIBLIOTECAS ===== -->
    <script src="https://cdn.jsdelivr.net/npm/pako@2.1.0/dist/pako.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>

    <script>
        // ===== VARIÁVEL GLOBAL =====
        const publicId = <?= json_encode($public_id) ?>;
        let paginaData = null;
        let panZoomInstance = null; // guardar a instância se precisar

        // ===== CARREGAR PÁGINA PÚBLICA =====
        async function loadPublicPage() {
            const container = document.getElementById('cards-container');
            try {
                const response = await fetch(`/knowsnow1/api.php?action=view_public_page&public_id=${encodeURIComponent(publicId)}`);
                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Erro ao carregar página');
                }

                paginaData = result.pagina;

                // Metadados
                document.getElementById('pageTitle').textContent = paginaData.titulo;
                document.getElementById('pageAuthor').textContent = paginaData.autor;
                document.getElementById('pageMateria').textContent = paginaData.materia;
                document.getElementById('pageTopico').textContent = paginaData.topico;
                document.getElementById('forkCount').textContent = paginaData.fork_count || 0;
                document.getElementById('pageMeta').style.display = 'block';

                renderCards(paginaData.cards);

                // 🔥 Inicializar Pan & Zoom após renderizar
                inicializarPanZoom();

            } catch (err) {
                console.error(err);
                container.innerHTML = `
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:#f85149; padding:40px;">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size:4rem;"></i>
                        <p class="mt-3" style="font-size:1.2rem;">${err.message}</p>
                        <a href="index.php" class="btn btn-secondary mt-4">Voltar para o início</a>
                    </div>
                `;
            }
        }

        // ===== RENDERIZAR CARDS (absolutos) =====
        function renderCards(cards) {
            const container = document.getElementById('cards-container');
            container.innerHTML = '';

            if (!cards || cards.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-file-earmark-text"></i>
                        <p>Esta página está vazia.</p>
                    </div>
                `;
                return;
            }

            cards.forEach(card => {
                const div = document.createElement('div');
                div.className = 'editable-item';
                div.style.left = card.pos_left || '20px';
                div.style.top = card.pos_top || '20px';
                div.style.width = card.width || 'auto';
                div.style.height = card.height || 'auto';
                div.style.position = 'absolute';
                div.innerHTML = card.html || '';
                container.appendChild(div);
            });
        }

        // ===== INICIALIZAR PAN & ZOOM =====
        let panZoomInitialized = false; // flag

        function inicializarPanZoom() {
            if (panZoomInitialized) return; // já foi inicializado
            panZoomInitialized = true;

            // Verifica se a função global do PanZoom.js existe
            if (typeof window.inicializarPanZoom === 'function') {
                panZoomInstance = window.inicializarPanZoom();
                console.log('✅ Pan & Zoom inicializado via função global.');
            } else if (typeof window.setupPanZoom === 'function') {
                panZoomInstance = window.setupPanZoom();
                console.log('✅ Pan & Zoom inicializado via setupPanZoom.');
            } else {
                // Fallback simples
                console.warn('⚠️ PanZoom.js não encontrado, usando fallback.');
                setupSimplePanZoom();
            }
        }

        // ===== FALLBACK SIMPLES (caso PanZoom.js não carregue) =====
        function setupSimplePanZoom() {
            const container = document.getElementById('pan-zoom-container');
            const cardsContainer = document.getElementById('cards-container');
            let scale = 1;
            let translateX = 0,
                translateY = 0;
            let isDragging = false;
            let startX, startY, origX, origY;

            function updateTransform() {
                cardsContainer.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
            }

            // Zoom com roda do mouse
            container.addEventListener('wheel', (e) => {
                e.preventDefault();
                const delta = e.deltaY > 0 ? -0.1 : 0.1;
                scale = Math.min(Math.max(scale + delta, 0.3), 3);
                updateTransform();
            }, {
                passive: false
            });

            // Pan com mouse (arrastar)
            container.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                origX = translateX;
                origY = translateY;
                container.style.cursor = 'grabbing';
            });

            window.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                translateX = origX + dx;
                translateY = origY + dy;
                updateTransform();
            });

            window.addEventListener('mouseup', () => {
                isDragging = false;
                container.style.cursor = 'default';
            });

            // Botões de zoom
            document.getElementById('zoomInBtn').addEventListener('click', () => {
                scale = Math.min(scale + 0.2, 3);
                updateTransform();
            });
            document.getElementById('zoomOutBtn').addEventListener('click', () => {
                scale = Math.max(scale - 0.2, 0.3);
                updateTransform();
            });
            document.getElementById('resetViewBtn').addEventListener('click', () => {
                scale = 1;
                translateX = 0;
                translateY = 0;
                updateTransform();
            });

            updateTransform();
            console.log('✅ Pan & Zoom fallback ativado.');
        }

        // ===== FORK (via botão na navbar) =====
        document.getElementById('btnForkNav')?.addEventListener('click', async function() {
            if (!publicId) return;
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Copiando...';

            try {
                const infoRes = await fetch(`/knowsnow1/api.php?action=view_public_page&public_id=${encodeURIComponent(publicId)}`);
                const infoData = await infoRes.json();
                if (!infoData.success) throw new Error(infoData.error || 'Página não encontrada');

                const paginaId = infoData.pagina.id;
                const response = await fetch('/knowsnow1/api.php?action=fork_page', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.CSRF_TOKEN || ''
                    },
                    body: JSON.stringify({
                        pagina_id: paginaId
                    })
                });
                const result = await response.json();

                if (result.success) {
                    this.innerHTML = '<i class="bi bi-check-circle-fill"></i> Copiado!';
                    this.style.background = '#2ea043';
                    document.getElementById('forkCount').textContent =
                        parseInt(document.getElementById('forkCount').textContent) + 1;

                    setTimeout(() => {
                        window.location.href = `/knowsnow1/index.php?nova_pagina=${result.nova_pagina_id}`;
                    }, 1500);
                } else {
                    throw new Error(result.error || 'Erro ao copiar');
                }
            } catch (err) {
                console.error(err);
                this.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Erro';
                this.style.background = '#f85149';
                setTimeout(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-git-fork"></i> Copiar';
                    this.style.background = '';
                }, 3000);
            }
        });

        // ===== INICIAR =====
        document.addEventListener('DOMContentLoaded', loadPublicPage);
    </script>
</body>

</html>