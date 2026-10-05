<?php
// ========== SEGURANÇA DE SESSÃO ==========
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Strict');
session_start();

// ========== HEADERS DE SEGURANÇA ==========
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
// (Opcional) Content-Security-Policy – ajuste conforme necessidade
// header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com;");

// 🔒 BLOQUEIA ACESSO DIRETO SEM LOGIN
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: auth/login.php');
    exit;
}
echo '<script>window.USER_LOGGED = true;</script>';
$csrf_token = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = $csrf_token;
}
echo '<script>window.CSRF_TOKEN = ' . json_encode($csrf_token) . ';</script>';
?>
<!DOCTYPE html>

<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constell Main</title>
    <!-- Bootstrap, Ícones e Fontes -->
    <link rel="stylesheet" href="assets/css/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- adicionar no head -->
    <!-- 🔥 Aplica tema salvo ANTES de renderizar (evita flash) -->
    <script>
        (function() {
            const saved = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pako/2.0.4/pako.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatted@3.2.9/min.js"></script>
    <style>
        /* ============================================================
               CSS UNIFICADO E OTIMIZADO
               ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            overflow-x: hidden;
        }

        :root {
            --bg-body: #0d1117;
            --bg-surface: #161b22;
            --bg-elevated: #1c2128;
            --bg-hover: #21262d;
            --bg-active: rgba(74, 124, 247, 0.12);
            --border-subtle: #30363d;
            --text-primary: #f0f6fc;
            --text-secondary: #c9d1d9;
            --text-muted: #8b949e;
            --accent: #4a7cf7;
            --accent-hover: #6f8cf7;
            --accent-gradient: linear-gradient(135deg, #4a7cf7, #6f42c1);
            --shadow-card: 0 8px 32px rgba(0, 0, 0, 0.6);
            --shadow-elevated: 0 12px 48px rgba(0, 0, 0, 0.7);
            --radius: 12px;
            --radius-sm: 8px;
            --transition: 0.25s ease;
            --sidebar-width: 280px;
            --sidebar-collapsed: 60px;
        }

        body {
            background-color: #0a0d12;
            color: var(--text-secondary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-image:
                radial-gradient(ellipse 1200px 800px at 50% 0%, rgba(74, 124, 247, 0.10), transparent 60%);
            background-size: 100% 100%;
            background-attachment: fixed;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }


        /* ===== SIDEBAR ESQUERDA (NAVEGAÇÃO) — BASE ===== */
        #nav-sidebar {
            position: fixed;
            top: 68px;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width, 280px);
            background: rgba(13, 17, 23, 0.96);
            backdrop-filter: blur(24px) saturate(1.6);
            -webkit-backdrop-filter: blur(24px) saturate(1.6);
            border-right: 1px solid rgba(48, 54, 61, 0.5);
            box-shadow: 4px 0 40px rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            overflow: visible;
            /* 🔥 deixa o dropdown escapar */
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', -apple-system, sans-serif;
            color: var(--text-secondary);
        }

        /* ===== NAVBAR PREMIUM ===== */
        .navbar-premium {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            height: 68px;
            padding: 0 28px;
            background: linear-gradient(180deg,
                    rgba(13, 17, 23, 0.88) 0%,
                    rgba(13, 17, 23, 0.72) 100%);
            backdrop-filter: blur(24px) saturate(1.8);
            -webkit-backdrop-filter: blur(24px) saturate(1.8);
            border-bottom: 1px solid rgba(74, 124, 247, 0.12);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.03) inset,
                0 8px 32px rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .navbar-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(74, 124, 247, 0.6) 20%,
                    rgba(167, 139, 250, 0.7) 50%,
                    rgba(74, 124, 247, 0.6) 80%,
                    transparent 100%);
            opacity: 0.7;
            pointer-events: none;
        }

        .navbar-premium:hover::before {
            opacity: 1;
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
            transition: 0.3s;
        }

        .navbar-premium .brand-wrapper:hover .brand-icon {
            transform: scale(1.05) rotate(-2deg);
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
            gap: 10px;
        }

        .navbar-premium .btn-theme-toggle {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid rgba(48, 54, 61, 0.6);
            background: rgba(255, 255, 255, 0.03);
            color: #8b949e;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
        }

        .navbar-premium .btn-theme-toggle:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(74, 124, 247, 0.3);
            color: #f0f6fc;
            transform: scale(1.05) rotate(-15deg);
        }

        [data-theme="light"] .navbar-premium .btn-theme-toggle {
            background: rgba(0, 0, 0, 0.03);
            border-color: rgba(208, 215, 222, 0.8);
            color: #57606a;
        }

        [data-theme="light"] .navbar-premium .btn-theme-toggle:hover {
            background: rgba(0, 0, 0, 0.06);
            color: #1f2328;
            border-color: rgba(9, 105, 218, 0.3);
        }

        .navbar-premium .btn-toggle-sidebar:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(74, 124, 247, 0.3);
            color: #f0f6fc;
            transform: scale(1.05);
        }

        .navbar-premium .btn-logout {
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid rgba(218, 54, 51, 0.25);
            background: rgba(218, 54, 51, 0.04);
            color: #f85149;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: 0.3s;
            cursor: pointer;
        }

        .navbar-premium .btn-logout:hover {
            background: rgba(218, 54, 51, 0.12);
            border-color: rgba(218, 54, 51, 0.4);
            color: #ff6b6b;
            transform: translateY(-1px);
        }

        .navbar-premium .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--accent-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            border: 2px solid rgba(48, 54, 61, 0.6);
            cursor: pointer;
            transition: 0.3s;
            position: relative;
        }

        .navbar-premium .user-avatar:hover {
            border-color: #4a7cf7;
            transform: scale(1.08);
        }

        .navbar-premium .user-avatar .status-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #2ea043;
            border: 2px solid #0d1117;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.6;
                transform: scale(0.9);
            }
        }

        .navbar-premium .nav-divider {
            width: 1px;
            height: 28px;
            background: rgba(48, 54, 61, 0.5);
            margin: 0 4px;
        }

        [data-tooltip] {
            position: relative;
        }

        [data-tooltip]::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: -32px;
            left: 50%;
            transform: translateX(-50%);
            background: #161b22;
            color: #c9d1d9;
            font-size: 10px;
            padding: 2px 12px;
            border-radius: 6px;
            border: 1px solid #30363d;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: 0.2s;
            font-family: 'Inter', sans-serif;
        }

        [data-tooltip]:hover::before {
            opacity: 1;
            bottom: -36px;
        }


        /* ===== NOTIFICAÇÃO DE SALVAMENTO ===== */
        .save-notification {
            position: fixed !important;
            top: 80px !important;
            right: 20px !important;
            z-index: 99999 !important;
            background: rgba(22, 27, 34, 0.92) !important;
            backdrop-filter: blur(16px) saturate(1.4) !important;
            -webkit-backdrop-filter: blur(16px) saturate(1.4) !important;
            border: 1px solid rgba(46, 160, 67, 0.3) !important;
            border-radius: 16px !important;
            padding: 12px 24px 12px 20px !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.04) !important;
            color: var(--text-primary) !important;
            font-family: 'Inter', sans-serif !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            transform: translateX(120%) !important;
            /* ← valor fixo, não calc */
            opacity: 0 !important;
            transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1),
                opacity 0.5s ease !important;
            pointer-events: none !important;
            white-space: nowrap !important;
        }

        .save-notification.visible {
            transform: translateX(0) !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        .save-notification i {
            font-size: 22px;
            color: #2ea043;
            flex-shrink: 0;
            filter: drop-shadow(0 0 8px rgba(46, 160, 67, 0.3));
        }

        .save-notification .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-top: 2px solid var(--accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .save-notification.saving .spinner {
            display: block;
        }

        .save-notification.saving i {
            display: none;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .save-notification.error i {
            color: #f85149;
            filter: drop-shadow(0 0 8px rgba(248, 81, 73, 0.3));
        }

        .save-notification.error {
            border-color: rgba(248, 81, 73, 0.3);
        }

        /* Responsivo */
        @media (max-width: 480px) {
            .save-notification {
                top: 72px !important;
                right: 12px !important;
                left: 12px !important;
                width: auto !important;
                justify-content: center !important;
                font-size: 13px !important;
                padding: 10px 16px !important;
                white-space: normal !important;
                border-radius: 12px !important;
            }
        }


        /* ============================================================
   HEADER — ESTILO IDE MODERNA
   ============================================================ */

        #nav-sidebar .nav-header {
            padding: 12px 12px 10px 12px;
            border-bottom: 1px solid rgba(48, 54, 61, 0.3);
            flex-shrink: 0;
            background: transparent;
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
        }

        /* Linha do título */
        #nav-sidebar .nav-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        #nav-sidebar .nav-title {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--text-secondary);
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-family: 'Inter', sans-serif;
        }

        #nav-sidebar .nav-title svg {
            color: var(--text-muted);
            flex-shrink: 0;
        }

        #nav-sidebar .nav-title span {
            line-height: 1;
        }

        /* Ações mini (ícone de recolher + botão +) */
        #nav-sidebar .nav-actions-mini {
            display: flex;
            align-items: center;
            gap: 2px;
        }

        #nav-sidebar .nav-icon-btn {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        #nav-sidebar .nav-icon-btn:hover {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-primary);
        }

        #nav-sidebar .nav-icon-btn.primary {
            background: rgba(74, 124, 247, 0.12);
            color: var(--accent);
        }

        #nav-sidebar .nav-icon-btn.primary:hover {
            background: rgba(74, 124, 247, 0.22);
            color: #93b0ff;
            transform: scale(1.05);
        }

        #nav-sidebar .nav-icon-btn.primary.open {
            background: rgba(74, 124, 247, 0.3);
            transform: rotate(45deg);
        }

        /* ===== MENU DROPDOWN DE CRIAÇÃO ===== */
        #nav-sidebar .nav-add-menu {
            position: absolute;
            top: calc(100% + 4px);
            left: 8px;
            right: 8px;
            background: rgba(22, 27, 34, 0.98);
            backdrop-filter: blur(20px) saturate(1.4);
            -webkit-backdrop-filter: blur(20px) saturate(1.4);
            border: 1px solid rgba(74, 124, 247, 0.25);
            border-radius: 10px;
            box-shadow:
                0 16px 48px rgba(0, 0, 0, 0.7),
                0 0 0 1px rgba(255, 255, 255, 0.02) inset;
            padding: 4px;
            z-index: 200;
            opacity: 0;
            transform: translateY(-8px) scale(0.96);
            pointer-events: none;
            transition: opacity 0.15s ease, transform 0.15s ease;
            transform-origin: top center;
        }

        #nav-sidebar .nav-add-menu.open {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        #nav-sidebar .nav-add-item {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 8px 10px;
            border-radius: 7px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            transition: background 0.12s ease;
            font-family: 'Inter', sans-serif;
            text-align: left;
        }

        #nav-sidebar .nav-add-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        #nav-sidebar .nav-add-item:active {
            background: rgba(74, 124, 247, 0.1);
        }

        #nav-sidebar .nav-add-icon {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.03);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid rgba(48, 54, 61, 0.3);
        }

        #nav-sidebar .nav-add-label {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1px;
            min-width: 0;
        }

        #nav-sidebar .nav-add-label strong {
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--text-primary);
            line-height: 1.3;
        }

        #nav-sidebar .nav-add-label small {
            font-size: 0.68rem;
            color: var(--text-muted);
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #nav-sidebar .nav-add-key {
            font-size: 0.6rem;
            font-family: 'Inter', sans-serif;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(48, 54, 61, 0.4);
            border-radius: 4px;
            padding: 2px 5px;
            line-height: 1;
            flex-shrink: 0;
            opacity: 0.7;
        }

        #nav-sidebar .nav-add-item:hover .nav-add-key {
            opacity: 1;
            background: rgba(255, 255, 255, 0.06);
        }

        /* ===== BARRA DE BUSCA ===== */
        #nav-sidebar .nav-search-row {
            padding: 0 12px 10px 12px;
            border-bottom: 1px solid rgba(48, 54, 61, 0.2);
        }

        #nav-sidebar .nav-search-box {
            position: relative;
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(48, 54, 61, 0.3);
            border-radius: 6px;
            padding: 5px 8px;
            transition: all 0.15s ease;
        }

        #nav-sidebar .nav-search-box:focus-within {
            border-color: rgba(74, 124, 247, 0.4);
            background: rgba(74, 124, 247, 0.04);
            box-shadow: 0 0 0 2px rgba(74, 124, 247, 0.08);
        }

        #nav-sidebar .nav-search-box svg {
            color: var(--text-muted);
            flex-shrink: 0;
            opacity: 0.6;
        }

        #nav-sidebar .nav-search-box input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-primary);
            font-size: 0.75rem;
            font-family: 'Inter', sans-serif;
            padding: 0;
            min-width: 0;
        }

        #nav-sidebar .nav-search-box input::placeholder {
            color: var(--text-muted);
            opacity: 0.6;
        }

        /* ============================================================
   ÁRVORE — ESTILO IDE MODERNA
   ============================================================ */

        #nav-sidebar #nav-tree {
            flex: 1;
            overflow-y: auto;
            padding: 10px 8px 20px 8px;
            background: transparent;
            scrollbar-width: thin;
            scrollbar-color: rgba(48, 54, 61, 0.3) transparent;
        }

        #nav-sidebar #nav-tree::-webkit-scrollbar {
            width: 3px;
        }

        #nav-sidebar #nav-tree::-webkit-scrollbar-thumb {
            background: rgba(48, 54, 61, 0.3);
            border-radius: 10px;
        }

        #nav-sidebar #nav-tree::-webkit-scrollbar-thumb:hover {
            background: rgba(74, 124, 247, 0.3);
        }

        /* Lista raiz */
        #nav-sidebar .nav-tree-ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        /* Item */
        #nav-sidebar .nav-tree-item {
            position: relative;
            margin: 0;
            padding: 0;
        }

        /* Header do item — linha da IDE */
        #nav-sidebar .nav-tree-header {
            position: relative;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px 8px 5px 6px;
            border-radius: 6px;
            color: var(--text-secondary);
            font-size: 0.82rem;
            font-weight: 400;
            line-height: 1.4;
            cursor: pointer;
            transition: background 0.12s ease, color 0.12s ease;
            user-select: none;
            min-height: 26px;
            border: none;
            font-family: 'Inter', sans-serif;
        }

        #nav-sidebar .nav-tree-header:hover {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-primary);
        }

        /* Seta de expandir — só aparece pra itens com filhos */
        #nav-sidebar .nav-tree-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            font-size: 9px;
            color: var(--text-muted);
            opacity: 0.5;
            transition: transform 0.15s ease, opacity 0.15s ease;
            font-weight: 600;
            transform-origin: center;
        }

        #nav-sidebar .nav-tree-header:hover .nav-tree-arrow {
            opacity: 1;
        }

        #nav-sidebar .nav-tree-header.expanded .nav-tree-arrow,
        #nav-sidebar .nav-tree-header .nav-tree-arrow:contains("▼") {
            transform: rotate(90deg);
        }

        /* Ícone de tipo — emoji/ícone que vem antes do label */
        #nav-sidebar .nav-tree-label {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.82rem;
            font-weight: 400;
            color: inherit;
            letter-spacing: -0.005em;
        }

        /* Highlight da parte da busca (opcional, se você implementar) */
        #nav-sidebar .nav-tree-label mark {
            background: rgba(74, 124, 247, 0.25);
            color: var(--text-primary);
            border-radius: 2px;
            padding: 0 1px;
        }

        /* Badge (contagem) — só aparece no hover */
        #nav-sidebar .nav-tree-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 16px;
            padding: 0 5px;
            border-radius: 4px;
            font-size: 0.6rem;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.04);
            opacity: 0;
            transition: opacity 0.12s ease, background 0.12s ease;
            flex-shrink: 0;
        }

        #nav-sidebar .nav-tree-header:hover .nav-tree-badge {
            opacity: 1;
        }

        /* Estado ativo — sutil, tipo IDE */
        #nav-sidebar .nav-tree-header.active {
            background: rgba(74, 124, 247, 0.12);
            color: var(--text-primary);
            font-weight: 500;
        }

        #nav-sidebar .nav-tree-header.active .nav-tree-label {
            font-weight: 500;
        }

        #nav-sidebar .nav-tree-header.active .nav-tree-arrow {
            opacity: 1;
            color: var(--accent);
        }

        #nav-sidebar .nav-tree-header.active .nav-tree-badge {
            opacity: 1;
            background: rgba(74, 124, 247, 0.18);
            color: var(--accent);
        }

        /* Barra indicadora à esquerda (só no ativo) */
        #nav-sidebar .nav-tree-header.active::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 2px;
            height: 60%;
            background: var(--accent);
            border-radius: 0 2px 2px 0;
            box-shadow: 0 0 8px rgba(74, 124, 247, 0.5);
        }

        /* Children — indentação e linha vertical */
        #nav-sidebar .nav-tree-children {
            list-style: none;
            padding-left: 12px;
            margin: 0;
            position: relative;
        }

        /* Linha vertical conectando filhos */
        #nav-sidebar .nav-tree-children::before {
            content: '';
            position: absolute;
            left: 14px;
            top: 0;
            bottom: 6px;
            width: 1px;
            background: rgba(48, 54, 61, 0.3);
            pointer-events: none;
        }

        #nav-sidebar .nav-tree-children .nav-tree-children::before {
            left: 26px;
        }

        /* Esconde as linhas no modo recolhido */
        #nav-sidebar.collapsed .nav-tree-children::before {
            display: none;
        }

        /* ===== ÍCONES POR TIPO (usa ::before no header) ===== */

        /* Caderno — cor roxa */
        #nav-sidebar .nav-tree-item[data-type="caderno"]>.nav-tree-header {
            font-weight: 600;
            font-size: 0.84rem;
            color: var(--text-primary);
            padding-top: 6px;
            padding-bottom: 6px;
            letter-spacing: -0.01em;
        }

        /* Matéria — cor azul */
        #nav-sidebar .nav-tree-item[data-type="materia"]>.nav-tree-header {
            color: var(--text-secondary);
        }

        /* Tópico — cor amarela/sutil */
        #nav-sidebar .nav-tree-item[data-type="topico"]>.nav-tree-header {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        /* Página — cor mais muted */
        #nav-sidebar .nav-tree-item[data-type="pagina"]>.nav-tree-header {
            color: var(--text-muted);
            font-size: 0.78rem;
            padding-top: 4px;
            padding-bottom: 4px;
            min-height: 24px;
        }

        #nav-sidebar .nav-tree-item[data-type="pagina"]>.nav-tree-header .nav-tree-label {
            font-weight: 400;
        }

        #nav-sidebar .nav-tree-item[data-type="pagina"]>.nav-tree-header.active .nav-tree-label {
            color: var(--text-primary);
            font-weight: 500;
        }

        /* ===== CORES DOS ÍCONES ===== */
        /* Caderno: roxo */
        #nav-sidebar .nav-tree-item[data-type="caderno"]>.nav-tree-header .nav-tree-label {
            color: inherit;
        }

        /* No modo colapsado, só mostra o ícone */
        #nav-sidebar.collapsed .nav-tree-header {
            justify-content: center;
            padding: 8px;
        }

        #nav-sidebar.collapsed .nav-tree-arrow,
        #nav-sidebar.collapsed .nav-tree-badge {
            display: none;
        }

        #nav-sidebar.collapsed .nav-tree-label {
            text-align: center;
            font-size: 1rem;
        }

        #nav-sidebar.collapsed .nav-tree-children {
            display: none !important;
        }

        #nav-sidebar.collapsed .nav-tree-header.active::before {
            left: -6px;
            height: 50%;
        }

        /* ===== RODAPÉ ===== */
        #nav-sidebar .nav-footer {
            padding: 14px 18px;
            border-top: 1px solid rgba(48, 54, 61, 0.3);
            background: rgba(13, 17, 23, 0.3);
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        /* Indicador de página */
        #nav-sidebar #page-indicator-sidebar {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            color: var(--text-muted);
            background: rgba(22, 27, 34, 0.6);
            padding: 3px 12px 3px 8px;
            border-radius: 30px;
            border: 1px solid rgba(48, 54, 61, 0.3);
            font-family: 'Share Tech Mono', monospace;
            transition: all 0.3s ease;
        }

        #nav-sidebar #page-indicator-sidebar:hover {
            border-color: rgba(74, 124, 247, 0.2);
            background: rgba(22, 27, 34, 0.8);
        }

        #nav-sidebar #page-indicator-sidebar i {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        #nav-sidebar #page-indicator-sidebar .num {
            font-weight: 500;
            color: var(--text-primary);
        }

        /* Dropdown de deletar */
        #nav-sidebar .dropdown-destroy {
            position: relative;
        }

        #nav-sidebar .dropdown-destroy>summary {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px 4px 8px;
            border-radius: 30px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 450;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: 'Inter', sans-serif;
        }

        #nav-sidebar .dropdown-destroy>summary:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(48, 54, 61, 0.3);
            color: var(--text-primary);
            transform: translateX(-2px);
        }

        #nav-sidebar .dropdown-destroy>summary::-webkit-details-marker {
            display: none;
        }

        #nav-sidebar .dropdown-destroy>summary i:first-child {
            font-size: 0.9rem;
            color: var(--text-muted);
            transition: color 0.3s ease;
        }

        #nav-sidebar .dropdown-destroy>summary:hover i:first-child {
            color: #f85149;
        }

        #nav-sidebar .dropdown-destroy>summary i:last-child {
            font-size: 0.6rem;
            opacity: 0.5;
            transition: transform 0.3s ease;
        }

        #nav-sidebar .dropdown-destroy[open]>summary i:last-child {
            transform: rotate(180deg);
        }

        /* Dropdown menu */
        #nav-sidebar .dropdown-destroy>ul {
            position: absolute;
            right: 0;
            bottom: calc(100% + 8px);
            min-width: 190px;
            margin: 0;
            padding: 6px 0;
            background: rgba(22, 27, 34, 0.95);
            border: 1px solid rgba(48, 54, 61, 0.5);
            border-radius: 12px;
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.02);
            list-style: none;
            display: none;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 9999;
            transform-origin: bottom right;
            overflow: hidden;
        }

        #nav-sidebar .dropdown-destroy[open]>ul {
            display: block;
            animation: fadeSlideUp 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeSlideUp {
            0% {
                opacity: 0;
                transform: translateY(12px) scale(0.96);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        #nav-sidebar .dropdown-destroy>ul li button {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 8px 18px;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 0.78rem;
            font-weight: 450;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
            letter-spacing: 0.01em;
        }

        #nav-sidebar .dropdown-destroy>ul li button i {
            font-size: 0.95rem;
            width: 20px;
            color: var(--text-muted);
            transition: all 0.3s ease;
        }

        #nav-sidebar .dropdown-destroy>ul li button:hover {
            background: rgba(218, 54, 51, 0.06);
            color: #f85149;
            padding-left: 22px;
        }

        #nav-sidebar .dropdown-destroy>ul li button:hover i {
            color: #f85149;
            transform: scale(1.1);
        }

        /* ===== ESTADO RECOLHIDO (colapsado) ===== */

        /* Esconde textos e badges, mantendo ícones */
        #nav-sidebar.collapsed .nav-header .brand span,
        #nav-sidebar.collapsed .nav-tree-label,
        #nav-sidebar.collapsed .nav-tree-badge,
        #nav-sidebar.collapsed .nav-footer #page-indicator-sidebar span:not(.num),
        #nav-sidebar.collapsed .dropdown-destroy>summary span {
            display: none !important;
        }

        /* Centraliza cabeçalho */
        #nav-sidebar.collapsed .nav-header {
            justify-content: center;
        }

        #nav-sidebar.collapsed .nav-header .brand i {
            font-size: 1.6rem;
        }

        /* Centraliza itens da árvore */
        #nav-sidebar.collapsed .nav-tree-header {
            padding: 8px 10px !important;
            justify-content: center;
        }

        #nav-sidebar.collapsed .nav-tree-arrow {
            display: none;
        }

        #nav-sidebar.collapsed .nav-tree-item[data-type="topico"]>.nav-tree-header::before,
        #nav-sidebar.collapsed .nav-tree-item[data-type="pagina"]>.nav-tree-header::before {
            display: none;
        }

        #nav-sidebar.collapsed .nav-tree-children {
            display: none !important;
        }

        /* Rodapé recolhido */
        #nav-sidebar.collapsed .nav-footer {
            justify-content: center;
        }

        #nav-sidebar.collapsed .nav-footer #page-indicator-sidebar {
            padding: 3px 6px;
        }

        #nav-sidebar.collapsed .nav-footer #page-indicator-sidebar i {
            margin-right: 0;
        }

        #nav-sidebar.collapsed .nav-footer #page-indicator-sidebar span:first-of-type {
            display: none;
        }

        #nav-sidebar.collapsed .dropdown-destroy>summary {
            padding: 4px 6px;
        }

        #nav-sidebar.collapsed .dropdown-destroy>summary i:first-child {
            font-size: 1.1rem;
        }

        /* ===== RESPONSIVIDADE DA SIDEBAR ===== */
        @media (max-width: 768px) {
            #nav-sidebar {
                transform: translateX(-100%);
                width: 280px !important;
            }

            #nav-sidebar.open {
                transform: translateX(0);
            }

            #nav-sidebar.collapsed {
                transform: translateX(-100%);
                width: 280px !important;
            }

            #nav-sidebar.collapsed.open {
                transform: translateX(0);
            }

            /* Em mobile, o container de cards ocupa toda a tela */
            #pan-zoom-container {
                left: 0 !important;
                right: 0 !important;
            }
        }

        /* ===== SCROLLBAR PERSONALIZADA (global para sidebar) ===== */
        #nav-sidebar::-webkit-scrollbar {
            width: 3px;
        }

        #nav-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        #nav-sidebar::-webkit-scrollbar-thumb {
            background: rgba(48, 54, 61, 0.3);
            border-radius: 10px;
        }

        #nav-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(74, 124, 247, 0.2);
        }

        /* ===== SIDEBAR DE FERRAMENTAS (DIREITA) ===== */
        /* ===== SIDEBAR DE FERRAMENTAS (DIREITA) — VERSÃO PREMIUM REFORMULADA ===== */
        #sidebar {
            position: fixed;
            top: 68px;
            right: 0;
            bottom: 0;
            width: 64px;
            /* largura confortável */
            background: rgba(13, 17, 23, 0.72);
            backdrop-filter: blur(18px) saturate(1.5);
            -webkit-backdrop-filter: blur(18px) saturate(1.5);
            border-left: 1px solid rgba(48, 54, 61, 0.35);
            box-shadow: -6px 0 28px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 14px 6px;
            gap: 8px;
            overflow-y: auto;
            z-index: 999;
            font-family: 'Inter', -apple-system, sans-serif;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        /* Scroll personalizada (fina e discreta) */
        #sidebar::-webkit-scrollbar {
            width: 2px;
        }

        #sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        #sidebar::-webkit-scrollbar-thumb {
            background: rgba(74, 124, 247, 0.3);
            border-radius: 10px;
        }

        #sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
        }

        /* Cada botão */
        #sidebar .sidebar-btn {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid transparent;
            color: var(--text-muted);
            font-size: 18px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            font-family: inherit;
            flex-shrink: 0;
        }

        #sidebar .sidebar-btn:hover {
            background: linear-gradient(135deg,
                    rgba(74, 124, 247, 0.15),
                    rgba(167, 139, 250, 0.12));
            border-color: rgba(74, 124, 247, 0.25);
            color: #fff;
            transform: scale(1.08) translateY(-2px);
            box-shadow:
                0 8px 24px rgba(74, 124, 247, 0.20),
                0 0 0 1px rgba(74, 124, 247, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.06);
        }

        /* Clique (efeito de pressionar) */
        #sidebar .sidebar-btn:active {
            transform: scale(0.92);
            transition-duration: 0.1s;
        }

        /* Tooltips – estilo consistente com os demais elementos */
        #sidebar .sidebar-btn .tooltip {
            position: absolute;
            right: 54px;
            /* posiciona à esquerda do botão */
            background: rgba(22, 27, 34, 0.92);
            backdrop-filter: blur(8px);
            color: var(--text-primary);
            border: 1px solid rgba(48, 54, 61, 0.5);
            border-radius: 30px;
            padding: 5px 16px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.25s ease;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.6);
            letter-spacing: 0.02em;
            font-family: 'Inter', sans-serif;
            transform: translateX(8px) scale(0.96);
        }

        #sidebar .sidebar-btn:hover .tooltip {
            opacity: 1;
            transform: translateX(0) scale(1);
        }

        /* Separadores com gradiente sutil */
        #sidebar .separator {
            width: 32px;
            height: 1.5px;
            background: linear-gradient(to right, transparent, rgba(48, 54, 61, 0.4), transparent);
            margin: 4px auto;
            border: 0;
            flex-shrink: 0;
            border-radius: 2px;
        }

        /* Categorias especiais: Zoom (azul), Adicionar (verde), Remover (vermelho) */
        #sidebar .sidebar-btn[title*="Zoom"]:hover {
            background: rgba(74, 124, 247, 0.1);
            color: var(--accent);
            box-shadow: 0 0 28px rgba(74, 124, 247, 0.2);
        }

        #sidebar .sidebar-btn[title*="Adicionar"]:hover,
        #sidebar .sidebar-btn[title*="Imagem"]:hover,
        #sidebar .sidebar-btn[title*="Explicação"]:hover,
        #sidebar .sidebar-btn[title*="Parágrafo"]:hover,
        #sidebar .sidebar-btn[title*="Lista"]:hover,
        #sidebar .sidebar-btn[title*="Título"]:hover,
        #sidebar .sidebar-btn[title*="Tabela"]:hover,
        #sidebar .sidebar-btn[title*="Código"]:hover {
            background: rgba(46, 160, 67, 0.08);
            color: #3fb950;
            box-shadow: 0 0 28px rgba(46, 160, 67, 0.15);
        }

        #sidebar .sidebar-btn[title*="Remover"]:hover,
        #sidebar .sidebar-btn[title*="Limpar"]:hover {
            background: rgba(218, 54, 51, 0.08);
            color: #f85149;
            box-shadow: 0 0 28px rgba(218, 54, 51, 0.15);
        }

        /* Ícones ficam mais nítidos */
        #sidebar .sidebar-btn i,
        #sidebar .sidebar-btn span[style*="font-size"] {
            font-size: 20px;
            line-height: 1;
        }

        /* Ajuste para o ícone de reset (⟲) */
        #sidebar .sidebar-btn[title="Resetar visualização"] {
            font-size: 22px;
            font-weight: 300;
        }

        /* Responsividade: em telas pequenas, some (já definido no @media geral) */
        @media (max-width: 768px) {
            #sidebar {
                display: none !important;
            }
        }

        /* ===== CONTAINER DOS CARDS ===== */
        #pan-zoom-container {
            position: fixed;
            top: 68px;
            left: var(--sidebar-width);
            right: 60px;
            /* alinhado com a nova largura da sidebar direita */
            bottom: 0;
            background: transparent;
            overflow: visible;
            pointer-events: auto;
            z-index: 10;
            transition: left 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #nav-sidebar.collapsed~#pan-zoom-container {
            left: var(--sidebar-collapsed);
        }

        #cards-container {
            width: 100%;
            min-height: 100%;
            position: relative;
            padding: 30px;
            box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.7);
            box-sizing: border-box;
            background-image:
                radial-gradient(circle, rgba(180, 200, 240, 0.35) 1.5px, transparent 1.5px),
                radial-gradient(ellipse at 50% 30%, rgba(74, 124, 247, 0.04) 0%, transparent 70%);
            background-size: 22px 22px, 100% 100%;
            background-position: 0 0, 0 0;
            background-repeat: repeat, no-repeat;
        }

        /* ===== CARDS (EDITABLE ITEMS) – VERSÃO PREMIUM ===== */
        .editable-item {
            position: absolute;
            background:
                linear-gradient(180deg,
                    rgba(30, 36, 46, 0.85) 0%,
                    rgba(22, 27, 34, 0.92) 100%);
            backdrop-filter: blur(16px) saturate(1.3);
            -webkit-backdrop-filter: blur(16px) saturate(1.3);
            border: 1px solid rgba(48, 54, 61, 0.5);
            border-radius: 14px;
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.03) inset,
                0 4px 24px rgba(0, 0, 0, 0.4);
            padding: 16px 20px;
            min-width: 60px;
            min-height: 40px;
            transition:
                box-shadow 0.35s ease,
                border-color 0.35s ease,
                transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1),
                background 0.3s ease;
            cursor: default;
            word-wrap: break-word;
            overflow-wrap: break-word;
            color: var(--text-secondary);
            font-family: 'Inter', sans-serif;
        }

        .editable-item:hover {
            border-color: rgba(74, 124, 247, 0.35);
            background:
                linear-gradient(180deg,
                    rgba(34, 42, 54, 0.88) 0%,
                    rgba(26, 32, 42, 0.95) 100%);
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.05) inset,
                0 12px 48px rgba(0, 0, 0, 0.55),
                0 0 0 1px rgba(74, 124, 247, 0.12),
                0 0 40px rgba(74, 124, 247, 0.06);
            transform: translateY(-3px);
        }

        /* Estado de edição ativa (active-editing) – destaque sutil */
        .editable-item .active-editing {
            outline: none;
            border-radius: 8px;
            box-shadow: 0 0 0 2px rgba(74, 124, 247, 0.25),
                0 0 20px rgba(74, 124, 247, 0.05);
            transition: box-shadow 0.2s;
        }

        .editable-item .active-editing:focus {
            box-shadow: 0 0 0 2px rgba(74, 124, 247, 0.4),
                0 0 30px rgba(74, 124, 247, 0.08);
        }


        #nav-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg,
                    rgba(74, 124, 247, 0) 0%,
                    rgba(74, 124, 247, 0.35) 30%,
                    rgba(167, 139, 250, 0.35) 70%,
                    rgba(167, 139, 250, 0) 100%);
            pointer-events: none;
            z-index: 1;
        }

        #nav-sidebar .nav-header {
            padding: 14px 12px 12px 12px;
            border-bottom: 1px solid rgba(48, 54, 61, 0.35);
            background: linear-gradient(180deg,
                    rgba(74, 124, 247, 0.03) 0%,
                    transparent 100%);
        }

        #nav-sidebar .nav-title {
            color: #f0f6fc;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        #nav-sidebar .nav-tree-header.active {
            background: linear-gradient(90deg,
                    rgba(74, 124, 247, 0.18) 0%,
                    rgba(74, 124, 247, 0.06) 100%);
            color: #fff;
            box-shadow:
                inset 0 1px 0 rgba(74, 124, 247, 0.1),
                0 0 20px rgba(74, 124, 247, 0.04);
        }

        /* ===== BOTÕES DE AÇÃO (DELETE, DUPLICAR) ===== */
        .editable-item .delete-btn,
        .editable-item .duplicate-btn {
            position: absolute;
            top: 10px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 15px;
            cursor: pointer;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            backdrop-filter: blur(6px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
            z-index: 30;
            border: none;
            font-family: 'Inter', sans-serif;
            line-height: 1;
        }

        .editable-item:hover .delete-btn,
        .editable-item:hover .duplicate-btn {
            opacity: 1;
            transform: scale(1);
        }

        /* Botão deletar – vermelho sutil */
        .editable-item .delete-btn {
            right: 10px;
            background: rgba(255, 70, 70, 0.06);
            color: #ff6b6b;
            border: 1px solid rgba(255, 70, 70, 0.15);
        }

        .editable-item .delete-btn:hover {
            background: rgba(255, 70, 70, 0.15);
            border-color: #ff6b6b;
            transform: scale(1.12);
            box-shadow: 0 0 24px rgba(255, 70, 70, 0.2);
        }

        .editable-item .delete-btn:active {
            transform: scale(0.9);
        }

        /* Botão duplicar – azul sutil */
        .editable-item .duplicate-btn {
            right: 48px;
            /* posicionado à esquerda do delete */
            background: rgba(74, 124, 247, 0.06);
            color: var(--accent);
            border: 1px solid rgba(74, 124, 247, 0.15);
        }

        .editable-item .duplicate-btn:hover {
            background: rgba(74, 124, 247, 0.15);
            border-color: var(--accent);
            transform: scale(1.12);
            box-shadow: 0 0 24px rgba(74, 124, 247, 0.2);
        }

        .editable-item .duplicate-btn:active {
            transform: scale(0.9);
        }

        /* ===== DRAG HANDLE (alça de arrasto) ===== */
        .editable-item .drag-handle {
            position: absolute;
            top: 50%;
            left: -10px;
            transform: translateY(-50%);
            width: 20px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.15);
            font-size: 22px;
            cursor: grab;
            opacity: 0;
            transition: all 0.25s ease;
            z-index: 10;
            user-select: none;
            border-radius: 6px;
            background: transparent;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .editable-item .drag-handle::before {
            content: "⠿";
            display: block;
            font-size: 22px;
            line-height: 1;
            letter-spacing: -2px;
            font-weight: 300;
        }

        .editable-item:hover .drag-handle {
            opacity: 0.6;
        }

        .editable-item .drag-handle:hover {
            opacity: 1;
            color: rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.04);
            transform: translateY(-50%) scale(1.1);
        }

        .editable-item .drag-handle:active {
            cursor: grabbing;
            opacity: 1;
            transform: translateY(-50%) scale(0.95);
        }

        /* ===== RESIZE HANDLE (alça de redimensionamento) ===== */
        .editable-item .resize-handle {
            position: absolute;
            bottom: 6px;
            right: 6px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: rgba(255, 255, 255, 0.15);
            cursor: nwse-resize;
            opacity: 0;
            transition: all 0.25s ease;
            z-index: 15;
            user-select: none;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
            border-radius: 4px;
        }

        .editable-item .resize-handle::before {
            content: "↘";
            display: block;
            line-height: 1;
            font-size: 22px;
        }

        .editable-item:hover .resize-handle {
            opacity: 0.6;
        }

        .editable-item .resize-handle:hover {
            opacity: 1;
            color: var(--accent);
            transform: scale(1.2);
            background: rgba(74, 124, 247, 0.05);
        }

        .editable-item .resize-handle:active {
            transform: scale(0.9);
            color: #6f42c1;
        }

        /* ===== PORTAS DE CONEXÃO (FLOW PORTS) – VERSÃO PREMIUM ===== */
        .editable-item .flow-port {
            position: absolute;
            width: 20px;
            height: 20px;
            background: rgba(136, 136, 136, 0.2);
            border-radius: 50%;
            border: 2px solid rgba(136, 136, 136, 0.3);
            cursor: crosshair;
            z-index: 20;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.4);
            pointer-events: none;
            backdrop-filter: blur(2px);
        }

        .editable-item:hover .flow-port {
            opacity: 1;
            pointer-events: all;
        }

        .editable-item .flow-port:hover {
            transform: scale(1.5);
            background: rgba(139, 92, 246, 0.3);
            border-color: #8b5cf6;
            box-shadow: 0 0 24px rgba(139, 92, 246, 0.3);
        }

        .editable-item .flow-port.drag-target {
            transform: scale(1.6);
            background: rgba(46, 160, 67, 0.3);
            border-color: #2ea043;
            box-shadow: 0 0 30px rgba(46, 160, 67, 0.5);
            animation: pulse-port 0.8s ease-in-out infinite alternate;
        }

        @keyframes pulse-port {
            0% {
                transform: scale(1.4);
                background: rgba(139, 92, 246, 0.2);
            }

            100% {
                transform: scale(1.8);
                background: rgba(139, 92, 246, 0.4);
                box-shadow: 0 0 40px rgba(139, 92, 246, 0.4);
            }
        }

        /* Posicionamento das portas (top, bottom, left, right) */
        .editable-item .flow-port[data-position="top"] {
            top: -10px;
            left: calc(50% - 10px);
        }

        .editable-item .flow-port[data-position="bottom"] {
            bottom: -10px;
            left: calc(50% - 10px);
        }

        .editable-item .flow-port[data-position="left"] {
            left: -10px;
            top: calc(50% - 10px);
        }

        .editable-item .flow-port[data-position="right"] {
            right: -10px;
            top: calc(50% - 10px);
        }

        /* ===== CONEXÕES SVG ===== */
        #flowchart-svg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 50;
            overflow: visible;
        }

        .connection-line {
            stroke: #ff6b6b;
            stroke-width: 3;
            fill: none;
            stroke-linecap: round;
            filter: drop-shadow(0 0 8px rgba(255, 107, 107, 0.3));
            transition: stroke-width 0.25s ease, stroke 0.25s ease, filter 0.25s ease;
        }

        .connection-line:hover {
            stroke-width: 5;
            stroke: #ff4444;
            filter: drop-shadow(0 0 16px rgba(255, 107, 107, 0.6));
        }

        /* ===== ESTILOS ADICIONAIS PARA TÍTULOS E TEXTOS DENTRO DOS CARDS ===== */
        .editable-item h1,
        .editable-item h2,
        .editable-item h3,
        .editable-item h4,
        .editable-item h5,
        .editable-item h6,
        .editable-item p,
        .editable-item li,
        .editable-item td,
        .editable-item th,
        .editable-item .code-block,
        .editable-item .file-name,
        .editable-item .terminal-content {
            font-family: 'Share Tech Mono', 'Courier New', monospace;
            margin: 0;
            padding: 2px 0;
            color: var(--text-primary);
        }

        .editable-item h1 {
            font-size: 2.2rem;
            font-weight: 700;
        }

        .editable-item h2 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .editable-item h3 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .editable-item h4 {
            font-size: 1.2rem;
            font-weight: 500;
        }

        .editable-item h5 {
            font-size: 1rem;
            font-weight: 500;
        }

        .editable-item h6 {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .editable-item p {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--text-secondary);
        }

        /* ===== RESPONSIVIDADE ===== */
        @media (max-width: 768px) {
            #pan-zoom-container {
                left: 0 !important;
                right: 0 !important;
            }

            .editable-item {
                padding: 12px 14px;
                border-radius: 12px;
                min-width: 40px;
                min-height: 30px;
            }

            .editable-item .delete-btn,
            .editable-item .duplicate-btn {
                width: 26px;
                height: 26px;
                font-size: 13px;
            }

            .editable-item .duplicate-btn {
                right: 42px;
            }

            .editable-item .drag-handle {
                left: -6px;
                width: 16px;
                height: 28px;
                font-size: 18px;
            }

            .editable-item .resize-handle {
                font-size: 18px;
                width: 20px;
                height: 20px;
            }

            .editable-item .flow-port {
                width: 16px;
                height: 16px;
            }
        }

        /* ===== FILTER-HIGHLIGHT – DESTAQUE PREMIUM ===== */
        .filter-highlight {
            display: inline-block;
            padding: 0 6px;
            border-radius: 6px;
            background: rgba(255, 215, 0, 0.25);
            /* amarelo suave */
            border: 1.5px solid rgba(255, 215, 0, 0.6);
            font-weight: 500;
            box-shadow: 0 0 12px rgba(255, 215, 0, 0.15);
            transition: all 0.25s ease;
            cursor: default;
            text-shadow: 0 0 8px rgba(255, 215, 0, 0.1);
        }

        /* Variação para quando estiver ativo (ex: clicado) */
        .filter-highlight.active {
            background: rgba(255, 215, 0, 0.4);
            border-color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            transform: scale(1.04);
        }

        /* Hover (opcional) */
        .filter-highlight:hover {
            background: rgba(255, 215, 0, 0.35);
            border-color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.25);
        }

        /* ===== TARGET-SELECTED – DESTAQUE DE ALVO ===== */
        .target-selected {
            outline: 2px solid rgba(255, 107, 107, 0.7) !important;
            outline-offset: 2px;
            background: rgba(255, 107, 107, 0.08) !important;
            border-radius: 6px;
            box-shadow: 0 0 20px rgba(255, 107, 107, 0.15), inset 0 0 20px rgba(255, 107, 107, 0.05);
            transition: all 0.3s ease;
            position: relative;
        }

        /* Variação para quando estiver ativo (ex: selecionado para conexão) */
        .target-selected.active {
            outline-color: #ff6b6b;
            background: rgba(255, 107, 107, 0.15) !important;
            box-shadow: 0 0 30px rgba(255, 107, 107, 0.25), inset 0 0 30px rgba(255, 107, 107, 0.08);
            transform: scale(1.02);
        }

        /* Opcional: um pequeno indicador de "alvo" (glow pulsante) */
        .target-selected::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 8px;
            background: rgba(255, 107, 107, 0.05);
            filter: blur(4px);
            z-index: -1;
            animation: pulse-target 1.5s ease-in-out infinite;
        }

        @keyframes pulse-target {

            0%,
            100% {
                opacity: 0.3;
                transform: scale(0.98);
            }

            50% {
                opacity: 0.8;
                transform: scale(1.02);
            }
        }

        /* ===== UTILITÁRIOS ===== */
        .spell-error {
            text-decoration: underline wavy #ff6b6b !important;
            cursor: pointer !important;
        }

        .multi-selected {
            outline: 3px solid #0d6efd !important;
            background-color: #1a3a5a !important;
        }

        .drag-target-card {
            box-shadow: 0 0 0 3px #4a7cf7, 0 0 30px rgba(74, 124, 247, 0.3) !important;
        }

        .editable-item.dragging {
            animation: floatContinuous 1s infinite;
            z-index: 1000;
            opacity: 0.9;
        }

        @keyframes floatContinuous {

            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-8px) scale(1.02);
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg,
                    rgba(74, 124, 247, 0.3),
                    rgba(167, 139, 250, 0.3));
            border-radius: 4px;
            border: 2px solid transparent;
            background-clip: padding-box;
            transition: background 0.2s;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg,
                    rgba(74, 124, 247, 0.5),
                    rgba(167, 139, 250, 0.5));
            background-clip: padding-box;
            border: 2px solid transparent;
        }

        ::-webkit-scrollbar-corner {
            background: transparent;
        }

        /* ============================================================
   TOOLBAR ULTRA PREMIUM – COM ORIENTAÇÃO HORIZONTAL/VERTICAL
   ============================================================ */

        /* ===== BASE ===== */
        .edit-toolbar {
            position: fixed;
            top: 80px;
            left: 24px;
            z-index: 9999;
            padding: 12px 16px;
            background: rgba(13, 17, 23, 0.78);
            backdrop-filter: blur(24px) saturate(1.8);
            -webkit-backdrop-filter: blur(24px) saturate(1.8);
            border: 1px solid rgba(74, 124, 247, 0.25);
            border-radius: 20px;
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.8),
                0 0 40px rgba(74, 124, 247, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.06);
            font-family: 'Inter', -apple-system, sans-serif;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            opacity: 0;
            transform: scale(0.92) translateY(-12px);
            pointer-events: none;
            overflow: visible;
        }

        .edit-toolbar.visible {
            opacity: 1;
            transform: scale(1) translateY(0);
            pointer-events: auto;
        }

        /* ===== DRAG HANDLE (CABEÇALHO) ===== */
        .toolbar-drag-handle {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 2px 0 6px 0;
            cursor: grab;
            user-select: none;
            color: var(--text-muted);
            border-bottom: 1px solid rgba(48, 54, 61, 0.12);
            width: 100%;
            flex-shrink: 0;
        }

        .toolbar-drag-handle:hover {
            color: var(--text-primary);
        }

        .toolbar-drag-handle i {
            font-size: 16px;
            letter-spacing: -2px;
            opacity: 0.4;
        }

        .toolbar-drag-handle .toolbar-title {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            flex: 1;
        }

        /* Ações do cabeçalho (toggle + close) */
        .toolbar-header-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }


        /* Estado "salvo" com fade */
        .btn-save.saved .save-status {
            background: rgba(46, 160, 67, 0.2);
            color: #3fb950;
        }

        .btn-save.saved i {
            color: #2ea043;
        }

        .toolbar-header-actions .toolbar-layout-toggle,
        .toolbar-header-actions .toolbar-close-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            padding: 2px 6px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 26px;
            min-width: 26px;
        }

        .toolbar-header-actions .toolbar-layout-toggle:hover {
            background: rgba(74, 124, 247, 0.08);
            color: var(--accent);
        }

        .toolbar-header-actions .toolbar-close-btn:hover {
            background: rgba(248, 81, 73, 0.08);
            color: #f85149;
            transform: rotate(90deg);
        }

        /* ===== MODO HORIZONTAL (PAISAGEM) – CONFORTÁVEL ===== */
        .edit-toolbar.layout-horizontal {
            width: auto;
            max-width: 60vw;
            min-width: 420px;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: flex-start;
            gap: 6px 16px;
            border-radius: 20px;
            padding: 10px 16px;
        }

        .edit-toolbar.layout-horizontal .toolbar-section {
            flex: 0 1 auto;
            min-width: 80px;
            flex-direction: column;
            gap: 3px;
        }

        .edit-toolbar.layout-horizontal .toolbar-section-label {
            font-size: 0.5rem;
            opacity: 0.5;
            margin-bottom: 1px;
            letter-spacing: 0.05em;
        }

        .edit-toolbar.layout-horizontal .toolbar-row {
            display: flex;
            gap: 3px;
            flex-wrap: nowrap;
            align-items: center;
        }

        /* Tamanho: select + botões na mesma linha (horizontal) */
        .edit-toolbar.layout-horizontal .size-row {
            display: flex;
            gap: 4px;
            align-items: center;
            justify-content: center;
            flex-wrap: nowrap;
        }

        .edit-toolbar.layout-horizontal .size-row .toolbar-select {
            flex: 0 0 60px;
            width: 60px;
            padding: 3px 6px;
            font-size: 9px;
            min-height: 24px;
        }

        .edit-toolbar.layout-horizontal .size-row .toolbar-buttons {
            display: flex;
            flex-direction: row;
            gap: 3px;
            align-items: center;
        }

        .edit-toolbar.layout-horizontal .size-row .toolbar-btn {
            flex: 0 1 auto;
            min-width: 24px;
            padding: 2px 6px;
            font-size: 9px;
            min-height: 24px;
        }

        /* Demais botões no horizontal */
        .edit-toolbar.layout-horizontal .toolbar-btn {
            flex: 0 1 auto;
            min-width: 28px;
            padding: 3px 8px;
            font-size: 9px;
            min-height: 26px;
            border-radius: 6px;
        }

        .edit-toolbar.layout-horizontal .toolbar-input,
        .edit-toolbar.layout-horizontal .toolbar-select {
            width: 80px;
            padding: 3px 8px;
            font-size: 9px;
            min-height: 26px;
            border-radius: 6px;
        }

        .edit-toolbar.layout-horizontal .toolbar-swatches {
            gap: 3px;
        }

        .edit-toolbar.layout-horizontal .toolbar-swatch {
            width: 20px;
            height: 20px;
        }

        .edit-toolbar.layout-horizontal .toolbar-footer {
            width: 100%;
            flex-direction: row;
            justify-content: flex-start;
            border-top: 1px solid rgba(48, 54, 61, 0.1);
            padding-top: 4px;
            margin-top: 0px;
        }

        /* ===== MODO VERTICAL (RETRATO) – CONFORTÁVEL ===== */
        .edit-toolbar.layout-vertical {
            width: 220px;
            height: auto;
            flex-direction: column;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 20px;
        }

        .edit-toolbar.layout-vertical .toolbar-section {
            flex-direction: column;
            gap: 4px;
        }

        .edit-toolbar.layout-vertical .toolbar-section-label {
            font-size: 0.55rem;
            margin-bottom: 1px;
        }

        .edit-toolbar.layout-vertical .toolbar-row {
            display: flex;
            gap: 3px;
            flex-wrap: wrap;
        }

        .edit-toolbar.layout-vertical .toolbar-btn {
            flex: 1 1 auto;
            min-width: 34px;
            padding: 4px 10px;
            font-size: 10px;
            min-height: 28px;
            border-radius: 8px;
        }

        .edit-toolbar.layout-vertical .toolbar-input,
        .edit-toolbar.layout-vertical .toolbar-select {
            width: 100%;
            padding: 4px 10px;
            font-size: 10px;
            min-height: 28px;
        }

        .edit-toolbar.layout-vertical .toolbar-swatches {
            gap: 4px;
        }

        .edit-toolbar.layout-vertical .toolbar-swatch {
            width: 24px;
            height: 24px;
        }

        .edit-toolbar.layout-vertical .toolbar-footer {
            flex-direction: row;
            justify-content: flex-start;
            border-top: 1px solid rgba(48, 54, 61, 0.15);
            padding-top: 8px;
            margin-top: 4px;
        }

        /* ===== ESPECÍFICO PARA TAMANHO NO MODO VERTICAL ===== */
        .edit-toolbar.layout-vertical .size-row {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: stretch;
            width: 100%;
        }

        .edit-toolbar.layout-vertical .size-row .toolbar-select {
            flex: 0 0 auto;
            width: 100%;
            min-height: 28px;
            padding: 4px 10px;
            font-size: 10px;
        }

        .edit-toolbar.layout-vertical .size-row .toolbar-buttons {
            display: flex;
            flex-direction: row;
            gap: 0;
            align-items: stretch;
            width: 100%;
        }

        .edit-toolbar.layout-vertical .size-row .toolbar-btn {
            flex: 1 1 0 !important;
            width: auto;
            min-width: 0;
            padding: 4px 2px;
            font-size: 10px;
            border-radius: 0;
            margin: 0;
            border: 1px solid rgba(48, 54, 61, 0.25);
            border-right: none;
            min-height: 28px;
        }

        .edit-toolbar.layout-vertical .size-row .toolbar-btn:last-child {
            border-right: 1px solid rgba(48, 54, 61, 0.25);
            border-radius: 0 8px 8px 0;
        }

        .edit-toolbar.layout-vertical .size-row .toolbar-btn:first-child {
            border-radius: 8px 0 0 8px;
        }

        .edit-toolbar.layout-vertical .size-row .toolbar-btn:hover {
            border-color: rgba(74, 124, 247, 0.3);
            z-index: 1;
        }

        /* ===== SCROLLBAR – NENHUMA ===== */
        .edit-toolbar::-webkit-scrollbar {
            display: none;
        }

        .edit-toolbar {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        /* ===== ANIMAÇÃO DE ENTRADA ===== */
        @keyframes toolbarSlideIn {
            0% {
                opacity: 0;
                transform: scale(0.92) translateY(-16px) rotate(-0.5deg);
            }

            60% {
                opacity: 1;
                transform: scale(1.02) translateY(2px) rotate(0.2deg);
            }

            100% {
                opacity: 1;
                transform: scale(1) translateY(0) rotate(0deg);
            }
        }

        .edit-toolbar.visible {
            animation: toolbarSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        /* ===== TRANSIÇÃO ENTRE LAYOUTS ===== */
        .edit-toolbar.layout-horizontal,
        .edit-toolbar.layout-vertical {
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* ===== ELEMENTOS INTERNOS (compartilhados) ===== */
        .toolbar-section {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .toolbar-section-label {
            color: var(--text-muted);
            font-size: 0.55rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            opacity: 0.6;
        }

        .toolbar-row {
            display: flex;
            gap: 3px;
            flex-wrap: wrap;
            align-items: center;
        }

        /* Botões (base) */
        .toolbar-btn {
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-secondary);
            border: 1px solid rgba(48, 54, 61, 0.25);
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            min-height: 28px;
        }

        .toolbar-btn i {
            font-size: 12px;
            line-height: 1;
        }

        .toolbar-btn:hover {
            background: rgba(74, 124, 247, 0.08);
            border-color: rgba(74, 124, 247, 0.3);
            color: var(--text-primary);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 124, 247, 0.08);
        }

        .toolbar-btn:active {
            transform: scale(0.94);
        }

        .toolbar-btn.primary {
            background: linear-gradient(135deg, rgba(74, 124, 247, 0.12), rgba(111, 66, 193, 0.12));
            border-color: rgba(74, 124, 247, 0.2);
            color: var(--accent);
        }

        .toolbar-btn.primary:hover {
            background: linear-gradient(135deg, rgba(74, 124, 247, 0.2), rgba(111, 66, 193, 0.2));
            border-color: var(--accent);
            box-shadow: 0 0 20px rgba(74, 124, 247, 0.1);
        }

        .toolbar-btn.danger {
            background: rgba(248, 81, 73, 0.06);
            border-color: rgba(248, 81, 73, 0.15);
            color: #f85149;
        }

        .toolbar-btn.danger:hover {
            background: rgba(248, 81, 73, 0.12);
            border-color: rgba(248, 81, 73, 0.3);
            box-shadow: 0 0 20px rgba(248, 81, 73, 0.08);
        }

        .toolbar-btn.success {
            background: rgba(46, 160, 67, 0.06);
            border-color: rgba(46, 160, 67, 0.15);
            color: #3fb950;
        }

        .toolbar-btn.success:hover {
            background: rgba(46, 160, 67, 0.12);
            border-color: rgba(46, 160, 67, 0.3);
            box-shadow: 0 0 20px rgba(46, 160, 67, 0.08);
        }

        /* Inputs e selects */
        .toolbar-input,
        .toolbar-select {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(48, 54, 61, 0.3);
            border-radius: 8px;
            color: var(--text-primary);
            padding: 4px 10px;
            font-size: 10px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.2s ease;
            backdrop-filter: blur(4px);
            width: 100%;
            box-sizing: border-box;
            min-height: 28px;
        }

        .toolbar-input:focus,
        .toolbar-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(74, 124, 247, 0.12);
            background: rgba(255, 255, 255, 0.06);
        }

        .toolbar-select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%238b949e'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            padding-right: 24px;
            cursor: pointer;
        }

        .toolbar-select option {
            background: #161b22;
            color: var(--text-primary);
        }

        /* Swatches */
        .toolbar-swatches {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            align-items: center;
        }

        .toolbar-swatch {
            border-radius: 50%;
            border: 2px solid rgba(48, 54, 61, 0.3);
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            flex-shrink: 0;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
        }

        .toolbar-swatch:hover {
            transform: scale(1.15);
            border-color: var(--accent);
            box-shadow: 0 0 12px rgba(74, 124, 247, 0.15);
        }

        /* Indicador de filtro */
        .filter-indicator {
            color: var(--accent);
            font-weight: 600;
            font-size: 9px;
            background: rgba(74, 124, 247, 0.06);
            padding: 2px 10px;
            border-radius: 30px;
            border: 1px solid rgba(74, 124, 247, 0.12);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            backdrop-filter: blur(4px);
        }

        /* Rodapé */
        .toolbar-footer {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 8px;
        }

        /* ===== RESPONSIVO ===== */
        @media (max-width: 640px) {
            .edit-toolbar.layout-horizontal {
                min-width: unset;
                width: calc(100vw - 20px);
                flex-direction: column;
                gap: 6px;
                top: 70px;
                left: 10px;
                right: 10px;
                border-radius: 16px;
                padding: 10px 12px;
            }

            .edit-toolbar.layout-horizontal .toolbar-section {
                min-width: unset;
                width: 100%;
            }

            .edit-toolbar.layout-horizontal .toolbar-input,
            .edit-toolbar.layout-horizontal .toolbar-select {
                width: 100%;
            }

            .edit-toolbar.layout-vertical {
                width: calc(100vw - 20px);
                left: 10px;
                right: 10px;
                top: 70px;
            }
        }

        /* ===== BOTÃO SALVAR COM STATUS INTEGRADO ===== */
        .btn-save {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px 6px 12px;
            border-radius: 30px;
            border: 1px solid rgba(46, 160, 67, 0.25);
            background: rgba(46, 160, 67, 0.06);
            color: #3fb950;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
        }

        .btn-save:hover {
            background: rgba(46, 160, 67, 0.12);
            border-color: rgba(46, 160, 67, 0.4);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(46, 160, 67, 0.15);
        }

        .btn-save:active {
            transform: scale(0.95);
        }

        .btn-save i {
            font-size: 18px;
            transition: transform 0.3s;
        }

        .btn-save .save-status {
            font-size: 12px;
            font-weight: 500;
            background: rgba(46, 160, 67, 0.08);
            padding: 0 8px;
            border-radius: 20px;
            line-height: 20px;
            transition: all 0.3s ease;
            color: #3fb950;
        }

        /* Estado "salvando" */
        .btn-save.saving {
            border-color: rgba(74, 124, 247, 0.3);
            background: rgba(74, 124, 247, 0.06);
            color: #4a7cf7;
        }

        .btn-save.saving .save-status {
            color: #f0f6fc;
            background: rgba(74, 124, 247, 0.2);
            animation: pulse-save 1s infinite;
        }

        .btn-save.saving i {
            animation: spin 1s linear infinite;
        }

        /* Estado "erro" */
        .btn-save.error {
            border-color: rgba(248, 81, 73, 0.3);
            background: rgba(248, 81, 73, 0.06);
            color: #f85149;
        }

        .btn-save.error .save-status {
            background: rgba(248, 81, 73, 0.12);
            color: #f85149;
        }

        .btn-save.error i {
            color: #f85149;
        }

        /* Estado "salvo" (sucesso) – volta ao normal após 2s */
        .btn-save.saved .save-status {
            background: rgba(46, 160, 67, 0.15);
            color: #2ea043;
        }

        .btn-save.saved i {
            color: #2ea043;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes pulse-save {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        /* ============================================================
   TABLE DESIGN – ELEGANTE, COM CABEÇALHO ESCURO
   ============================================================ */

        /* Container – borda sutil e fundo branco */
        .table-responsive {
            border-radius: 12px !important;
            overflow: hidden !important;
            border: 1px solid rgba(0, 0, 0, 0.06) !important;
            /* borda escura, nada de branco */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.02) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            margin: 0 !important;
        }

        /* Tabela base – zera todas as bordas residuais */
        .table-responsive .table {
            border-collapse: collapse !important;
            width: 100% !important;
            color: #1e1e1e !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            margin-bottom: 0 !important;
            border: none !important;
            /* garante que não haja borda ao redor da tabela */
        }

        /* Força remoção de qualquer borda que venha de outros estilos */
        .table-responsive .table,
        .table-responsive .table * {
            border-color: rgba(0, 0, 0, 0.06) !important;
        }

        /* ============================================================
   CABEÇALHO – FUNDO ESCURO COM GRADIENTE SUTIL
   ============================================================ */
        .table-responsive .table thead th {
            background: linear-gradient(145deg, #1a1e24 0%, #0f1217 100%) !important;
            color: #f0f4f8 !important;
            font-weight: 600 !important;
            font-size: 0.7rem !important;
            letter-spacing: 0.04em !important;
            padding: 14px 18px !important;
            border-bottom: 2px solid #2a3038 !important;
            /* escuro */
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            text-transform: uppercase !important;
            cursor: default !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04) !important;
            transition: all 0.2s ease !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 2 !important;
        }

        .table-responsive .table thead th:hover {
            background: linear-gradient(145deg, #222a32 0%, #14181e 100%) !important;
            border-bottom-color: #3a434e !important;
        }

        /* ============================================================
   LINHAS – ALTERNADAS COM CORES DISTINTAS
   ============================================================ */

        /* Linhas ímpares – fundo branco puro */
        .table-responsive .table tbody tr:nth-child(odd) {
            background: #ffffff !important;
        }

        /* Linhas pares – fundo levemente azulado (muito suave) */
        .table-responsive .table tbody tr:nth-child(even) {
            background: #f7f9fc !important;
        }

        /* Células – herdam da linha, sem bordas laterais */
        .table-responsive .table tbody td {
            padding: 14px 18px !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
            /* escuro */
            background: transparent !important;
            color: #2d2d2d !important;
            font-size: 0.85rem !important;
            font-weight: 400 !important;
            cursor: default !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
        }

        /* ============================================================
   HOVER – LINHA DESTACADA COM BORDA LATERAL AZUL
   ============================================================ */
        .table-responsive .table tbody tr:hover {
            background: #f0f5ff !important;
            box-shadow: inset 3px 0 0 #4a7cf7 !important;
            border-radius: 0 !important;
            transform: none !important;
        }

        .table-responsive .table tbody tr:hover td {
            background: transparent !important;
            border-bottom-color: rgba(74, 124, 247, 0.15) !important;
            color: #111 !important;
        }

        /* ============================================================
   ZEBRADO (já incluso nas linhas) – sem interferência
   ============================================================ */
        .table-responsive .table.table-striped-columns tbody tr:nth-child(odd) td,
        .table-responsive .table.table-striped-columns tbody tr:nth-child(even) td {
            background: transparent !important;
        }

        .table-responsive .table.table-striped-columns tbody tr:hover td {
            background: transparent !important;
        }

        /* ============================================================
   PRIMEIRA COLUNA – NÚMERO EM MONO COM BORDA DIREITA ESCURA
   ============================================================ */
        .table-responsive .table tbody td:first-child {
            font-weight: 600 !important;
            color: #6c7a8a !important;
            font-family: 'Inter', 'SF Mono', monospace !important;
            font-size: 0.7rem !important;
            text-align: center !important;
            width: 44px !important;
            border-right: 1px solid rgba(0, 0, 0, 0.04) !important;
            /* escuro */
            background: rgba(0, 0, 0, 0.01) !important;
        }

        /* ============================================================
   BADGE DE STATUS – CORES SUAVES COM ELEVAÇÃO NO HOVER
   ============================================================ */
        .table-responsive .table .status {
            display: inline-block !important;
            padding: 0 14px !important;
            border-radius: 30px !important;
            font-size: 0.6rem !important;
            font-weight: 600 !important;
            background: #eef1f4 !important;
            color: #4a5568 !important;
            border: 1px solid #dce0e5 !important;
            line-height: 26px !important;
            height: 26px !important;
            cursor: default !important;
            transition: all 0.25s ease !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
            letter-spacing: 0.02em !important;
        }

        .table-responsive .table .status.active {
            background: #e6f4ea !important;
            color: #1e7e34 !important;
            border-color: #b8dfc6 !important;
        }

        .table-responsive .table .status.inactive {
            background: #fde8e8 !important;
            color: #b33c3c !important;
            border-color: #f5cccc !important;
        }

        .table-responsive .table .status:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.04) !important;
        }

        .table-responsive .table .status.active:hover {
            background: #d4edda !important;
            border-color: #8cc9a0 !important;
        }

        .table-responsive .table .status.inactive:hover {
            background: #f5d0d0 !important;
            border-color: #e8b3b3 !important;
        }

        /* ============================================================
   CÉLULAS EDITÁVEIS (.c) – COM INDICADOR ✎
   ============================================================ */
        .table-responsive .table td.c {
            border-bottom: 2px dotted rgba(74, 124, 247, 0.25) !important;
            /* azul escuro */
            cursor: text !important;
            background: rgba(74, 124, 247, 0.015) !important;
            position: relative !important;
        }

        .table-responsive .table td.c::after {
            content: "✎";
            font-size: 10px;
            color: #b0b8c0;
            margin-left: 4px;
            opacity: 0.4;
            transition: opacity 0.2s;
        }

        .table-responsive .table td.c:hover {
            background: rgba(74, 124, 247, 0.04) !important;
            border-bottom-color: #4a7cf7 !important;
        }

        .table-responsive .table td.c:hover::after {
            opacity: 1;
        }

        /* ============================================================
   AJUSTE PARA .styled-table (se usado)
   ============================================================ */
        .styled-table {
            margin: 0 !important;
        }

        /* ============================================================
   RESPONSIVO
   ============================================================ */
        @media (max-width: 768px) {

            .table-responsive .table thead th,
            .table-responsive .table tbody td {
                padding: 10px 12px !important;
                font-size: 0.7rem !important;
            }

            .table-responsive .table .status {
                font-size: 0.5rem !important;
                padding: 0 10px !important;
                line-height: 22px !important;
                height: 22px !important;
            }

            .table-responsive .table tbody td:first-child {
                width: 32px !important;
            }
        }

        .toolbar-reset-btn {
            background: transparent;
            border: none;
            color: #888;
            font-size: 16px;
            cursor: pointer;
            padding: 2px 6px;
            transition: color 0.2s, transform 0.2s;
            line-height: 1;
        }

        .toolbar-reset-btn:hover {
            color: #fff !important;
            transform: rotate(60deg);
        }

        /* 🔥 Faz o plaintext-only herdar o estilo do card */
        [contenteditable="plaintext-only"] {
            font-family: 'Share Tech Mono', 'Courier New', monospace;
            font-size: inherit;
            color: inherit;
            line-height: inherit;
            white-space: pre-wrap;
            outline: none;
            background: transparent;
            cursor: text;
        }

        /* ============================================================
   TEMA CLARO — WHITE / CLEAN UI
   ============================================================ */
        [data-theme="light"] {
            --bg-body: #f4f7fb;
            --bg-surface: #ffffff;
            --bg-elevated: #ffffff;
            --bg-hover: #f6f8fc;
            --bg-active: rgba(74, 124, 247, 0.09);
            --border-subtle: #e2e8f0;
            --text-primary: #172033;
            --text-secondary: #4f5d73;
            --text-muted: #8a96a8;
            --accent: #4a7cf7;
            --accent-hover: #345fd4;
            --accent-gradient: linear-gradient(135deg, #4a7cf7 0%, #6f42c1 100%);
            --shadow-card: 0 2px 10px rgba(23, 32, 51, 0.06);
            --shadow-elevated: 0 14px 38px rgba(23, 32, 51, 0.10);
        }

        /* ===== Base ===== */
        [data-theme="light"] body {
            background-color: #f4f7fb;
            background-image:
                radial-gradient(ellipse 1000px 650px at 50% -10%, rgba(74, 124, 247, 0.07), transparent 68%);
            color: #4f5d73;
        }

        [data-theme="light"] html,
        [data-theme="light"] body {
            color-scheme: light;
        }

        /* ===== Navbar ===== */
        [data-theme="light"] .navbar-premium {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(22px) saturate(1.15);
            -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border-bottom: 1px solid #e2e8f0;
            box-shadow:
                0 1px 0 rgba(255, 255, 255, 0.95) inset,
                0 8px 28px rgba(23, 32, 51, 0.06);
        }

        [data-theme="light"] .navbar-premium::before {
            background: linear-gradient(90deg,
                    transparent 0%,
                    rgba(74, 124, 247, 0.25) 24%,
                    rgba(111, 66, 193, 0.35) 50%,
                    rgba(74, 124, 247, 0.25) 76%,
                    transparent 100%);
            opacity: 0.75;
        }

        [data-theme="light"] .navbar-premium:hover {
            background: rgba(255, 255, 255, 0.97);
        }

        [data-theme="light"] .navbar-premium .brand-icon {
            background: var(--accent-gradient);
            color: #fff;
            box-shadow: 0 5px 16px rgba(74, 124, 247, 0.22);
        }

        [data-theme="light"] .navbar-premium .brand-text {
            background: linear-gradient(135deg, #172033 0%, #4a5c78 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        [data-theme="light"] .navbar-premium .brand-badge {
            background: #eef3ff;
            border-color: #dbe5ff;
            color: #416de0;
        }

        [data-theme="light"] .navbar-premium .nav-divider {
            background: #e2e8f0;
        }

        [data-theme="light"] .navbar-premium .btn-toggle-sidebar,
        [data-theme="light"] .navbar-premium .btn-theme-toggle {
            background: #f8fafc;
            border-color: #dce3ec;
            color: #5d6b80;
        }

        [data-theme="light"] .navbar-premium .btn-toggle-sidebar:hover,
        [data-theme="light"] .navbar-premium .btn-theme-toggle:hover {
            background: #eef3ff;
            border-color: #b9c9f8;
            color: #345fd4;
            transform: scale(1.05);
        }

        [data-theme="light"] .navbar-premium .btn-logout {
            background: #fff6f6;
            border-color: #f2d6d6;
            color: #c24141;
        }

        [data-theme="light"] .navbar-premium .btn-logout:hover {
            background: #ffeded;
            border-color: #e9aaaa;
            color: #aa3030;
        }

        [data-theme="light"] .navbar-premium .btn-save {
            background: #eef3ff;
            border-color: #d6e1ff;
            color: #345fd4;
        }

        [data-theme="light"] .navbar-premium .btn-save:hover {
            background: #e4ecff;
            border-color: #b9c9f8;
        }

        [data-theme="light"] .navbar-premium .btn-save .save-status {
            background: #e8efff;
            color: #416de0;
        }

        [data-theme="light"] .navbar-premium .user-avatar {
            background: var(--accent-gradient);
            border-color: #d9e2f1;
            box-shadow: 0 3px 12px rgba(74, 124, 247, 0.16);
        }

        [data-theme="light"] .navbar-premium .user-avatar .status-dot {
            border-color: #fff;
        }

        /* ===== Tooltips globais ===== */
        [data-theme="light"] [data-tooltip]::before {
            background: #172033;
            color: #fff;
            border-color: #172033;
            box-shadow: 0 8px 22px rgba(23, 32, 51, 0.16);
        }

        /* ===== Sidebar esquerda ===== */
        [data-theme="light"] #nav-sidebar {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(20px) saturate(1.08);
            -webkit-backdrop-filter: blur(20px) saturate(1.08);
            border-right: 1px solid #e2e8f0;
            box-shadow: 4px 0 24px rgba(23, 32, 51, 0.05);
            color: #4f5d73;
        }

        [data-theme="light"] #nav-sidebar::before {
            background: linear-gradient(180deg,
                    rgba(74, 124, 247, 0) 0%,
                    rgba(74, 124, 247, 0.30) 34%,
                    rgba(111, 66, 193, 0.22) 68%,
                    rgba(111, 66, 193, 0) 100%);
        }

        [data-theme="light"] #nav-sidebar .nav-header {
            background: linear-gradient(180deg, #ffffff 0%, #f8faff 100%);
            border-bottom-color: #e6ebf2;
        }

        [data-theme="light"] #nav-sidebar .nav-title {
            color: #657289;
            text-shadow: none;
        }

        [data-theme="light"] #nav-sidebar .nav-title svg {
            color: #4a7cf7;
        }

        [data-theme="light"] #nav-sidebar .nav-icon-btn {
            color: #718096;
        }

        [data-theme="light"] #nav-sidebar .nav-icon-btn:hover {
            background: #f2f5fa;
            color: #26344d;
        }

        [data-theme="light"] #nav-sidebar .nav-icon-btn.primary {
            background: #eef3ff;
            color: #4a7cf7;
        }

        [data-theme="light"] #nav-sidebar .nav-icon-btn.primary:hover {
            background: #e3ebff;
            color: #345fd4;
        }

        [data-theme="light"] #nav-sidebar .nav-search-box {
            background: #f8fafc;
            border-color: #dfe6ef;
        }

        [data-theme="light"] #nav-sidebar .nav-search-box:focus-within {
            border-color: #9fb5f4;
            background: #fff;
            box-shadow: 0 0 0 2px rgba(74, 124, 247, 0.10);
        }

        [data-theme="light"] #nav-sidebar .nav-search-box input {
            color: #26344d;
        }

        [data-theme="light"] #nav-sidebar .nav-search-box input::placeholder {
            color: #99a4b5;
        }

        [data-theme="light"] #nav-sidebar #nav-tree {
            scrollbar-color: #cbd5e1 transparent;
        }

        [data-theme="light"] #nav-sidebar .nav-tree-header {
            color: #58667b;
        }

        [data-theme="light"] #nav-sidebar .nav-tree-header:hover {
            background: #f4f7fb;
            color: #1f2d43;
        }

        [data-theme="light"] #nav-sidebar .nav-tree-header.active {
            background: linear-gradient(90deg, rgba(74, 124, 247, 0.12), rgba(74, 124, 247, 0.04));
            color: #355fc9;
            font-weight: 500;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
        }

        [data-theme="light"] #nav-sidebar .nav-tree-header.active .nav-tree-label {
            color: #355fc9;
        }

        [data-theme="light"] #nav-sidebar .nav-tree-header.active::before {
            background: #4a7cf7;
            box-shadow: 0 0 8px rgba(74, 124, 247, 0.35);
        }

        [data-theme="light"] #nav-sidebar .nav-tree-header.active .nav-tree-arrow {
            color: #4a7cf7;
        }

        [data-theme="light"] #nav-sidebar .nav-tree-header.active .nav-tree-badge {
            background: #e8efff;
            color: #416de0;
        }

        [data-theme="light"] #nav-sidebar .nav-tree-badge {
            background: #f2f5f9;
            color: #758197;
        }

        [data-theme="light"] #nav-sidebar .nav-tree-children::before {
            background: #e2e8f0;
        }

        [data-theme="light"] #nav-sidebar .nav-add-menu {
            background: rgba(255, 255, 255, 0.98);
            border-color: #dfe6ef;
            box-shadow: 0 14px 32px rgba(23, 32, 51, 0.12);
        }

        [data-theme="light"] #nav-sidebar .nav-add-item {
            color: #58667b;
        }

        [data-theme="light"] #nav-sidebar .nav-add-item:hover {
            background: #f4f7fb;
        }

        [data-theme="light"] #nav-sidebar .nav-add-icon {
            background: #f6f8fc;
            border-color: #e1e7ef;
        }

        [data-theme="light"] #nav-sidebar .nav-add-label strong {
            color: #26344d;
        }

        [data-theme="light"] #nav-sidebar .nav-add-label small {
            color: #909bad;
        }

        [data-theme="light"] #nav-sidebar .nav-add-key {
            background: #f7f9fc;
            border-color: #e1e7ef;
            color: #718096;
        }

        [data-theme="light"] #nav-sidebar .nav-footer {
            background: #f9fbfd;
            border-top-color: #e2e8f0;
        }

        [data-theme="light"] #nav-sidebar #page-indicator-sidebar {
            background: #fff;
            border-color: #e1e7ef;
            color: #718096;
        }

        [data-theme="light"] #nav-sidebar #page-indicator-sidebar .num {
            color: #26344d;
        }

        [data-theme="light"] #nav-sidebar .dropdown-destroy>summary {
            color: #718096;
        }

        [data-theme="light"] #nav-sidebar .dropdown-destroy>summary:hover {
            background: #f3f6fa;
            border-color: #e1e7ef;
            color: #26344d;
        }

        [data-theme="light"] #nav-sidebar .dropdown-destroy>ul {
            background: rgba(255, 255, 255, 0.99);
            border-color: #e1e7ef;
            box-shadow: 0 14px 30px rgba(23, 32, 51, 0.13);
        }

        [data-theme="light"] #nav-sidebar .dropdown-destroy>ul li button {
            color: #58667b;
        }

        [data-theme="light"] #nav-sidebar .dropdown-destroy>ul li button:hover {
            background: #fff1f1;
            color: #b53c3c;
        }

        /* ===== Sidebar direita / ferramentas ===== */
        [data-theme="light"] #sidebar {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(18px) saturate(1.08);
            -webkit-backdrop-filter: blur(18px) saturate(1.08);
            border-left-color: #e2e8f0;
            box-shadow: -4px 0 24px rgba(23, 32, 51, 0.05);
        }

        [data-theme="light"] #sidebar .sidebar-btn {
            background: transparent;
            border-color: transparent;
            color: #68758a;
        }

        [data-theme="light"] #sidebar .sidebar-btn:hover {
            background: #eef3ff;
            border-color: #d4e0ff;
            color: #416de0;
            box-shadow: 0 6px 18px rgba(74, 124, 247, 0.10);
            transform: scale(1.05) translateY(-1px);
        }

        [data-theme="light"] #sidebar .sidebar-btn .tooltip {
            background: #172033;
            border-color: #172033;
            color: #fff;
            box-shadow: 0 10px 24px rgba(23, 32, 51, 0.18);
        }

        [data-theme="light"] #sidebar .separator {
            background: linear-gradient(to right, transparent, #dfe6ef, transparent);
        }

        [data-theme="light"] #sidebar .sidebar-btn[title*="Remover"]:hover,
        [data-theme="light"] #sidebar .sidebar-btn[title*="Limpar"]:hover {
            background: #fff1f1;
            border-color: #f0cccc;
            color: #bd3e3e;
            box-shadow: 0 6px 18px rgba(189, 62, 62, 0.08);
        }

        /* ===== Canvas ===== */
        [data-theme="light"] #cards-container {
            background-color: #f7f9fc;
            background-image:
                radial-gradient(circle, rgba(74, 124, 247, 0.16) 1.2px, transparent 1.2px),
                radial-gradient(ellipse at 50% 20%, rgba(74, 124, 247, 0.035) 0%, transparent 66%);
            box-shadow: inset 0 0 40px rgba(23, 32, 51, 0.025);
        }

        /* ===== Cards ===== */
        [data-theme="light"] .editable-item {
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid #dfe6ef;
            box-shadow:
                0 1px 2px rgba(23, 32, 51, 0.04),
                0 6px 18px rgba(23, 32, 51, 0.055);
            color: #4f5d73;
            backdrop-filter: blur(10px) saturate(1.02);
            -webkit-backdrop-filter: blur(10px) saturate(1.02);
        }

        [data-theme="light"] .editable-item:hover {
            background: #fff;
            border-color: #aec0ee;
            box-shadow:
                0 8px 26px rgba(23, 32, 51, 0.09),
                0 0 0 1px rgba(74, 124, 247, 0.10);
            transform: translateY(-2px);
        }

        [data-theme="light"] .editable-item h1,
        [data-theme="light"] .editable-item h2,
        [data-theme="light"] .editable-item h3,
        [data-theme="light"] .editable-item h4,
        [data-theme="light"] .editable-item h5,
        [data-theme="light"] .editable-item h6 {
            color: #172033;
        }

        [data-theme="light"] .editable-item p,
        [data-theme="light"] .editable-item li,
        [data-theme="light"] .editable-item td,
        [data-theme="light"] .editable-item th,
        [data-theme="light"] .editable-item .file-name {
            color: #536177;
        }

        [data-theme="light"] .editable-item .delete-btn {
            background: #fff6f6;
            border-color: #efd3d3;
            color: #bd3e3e;
        }

        [data-theme="light"] .editable-item .delete-btn:hover {
            background: #fff0f0;
            border-color: #dfa9a9;
            color: #a83232;
        }

        [data-theme="light"] .editable-item .duplicate-btn {
            background: #f2f6ff;
            border-color: #d7e2ff;
            color: #416de0;
        }

        [data-theme="light"] .editable-item .duplicate-btn:hover {
            background: #e9efff;
            border-color: #b9c9f8;
            color: #345fd4;
        }

        [data-theme="light"] .editable-item .drag-handle,
        [data-theme="light"] .editable-item .resize-handle {
            color: rgba(58, 73, 97, 0.28);
        }

        [data-theme="light"] .editable-item .drag-handle:hover,
        [data-theme="light"] .editable-item .resize-handle:hover {
            color: #4a7cf7;
            background: rgba(74, 124, 247, 0.06);
        }

        [data-theme="light"] .editable-item .flow-port {
            background: rgba(74, 124, 247, 0.10);
            border-color: rgba(74, 124, 247, 0.28);
        }

        [data-theme="light"] .editable-item .flow-port:hover {
            background: #4a7cf7;
            border-color: #345fd4;
        }

        [data-theme="light"] .editable-item .active-editing {
            box-shadow:
                0 0 0 2px rgba(74, 124, 247, 0.22),
                0 0 20px rgba(74, 124, 247, 0.06);
        }

        [data-theme="light"] .editable-item .active-editing:focus {
            box-shadow:
                0 0 0 2px rgba(74, 124, 247, 0.38),
                0 0 26px rgba(74, 124, 247, 0.09);
        }

        /* ===== Toolbar de edição ===== */
        [data-theme="light"] .edit-toolbar {
            background: rgba(255, 255, 255, 0.98);
            border-color: #dfe6ef;
            box-shadow:
                0 14px 34px rgba(23, 32, 51, 0.12),
                0 0 0 1px rgba(23, 32, 51, 0.02);
        }

        [data-theme="light"] .toolbar-drag-handle {
            color: #718096;
            border-bottom-color: #e5eaf1;
        }

        [data-theme="light"] .toolbar-title {
            color: #8894a7;
        }

        [data-theme="light"] .toolbar-section-label {
            color: #5e6b80;
        }

        [data-theme="light"] .toolbar-btn {
            background: #fff;
            border-color: #dfe6ef;
            color: #334158;
        }

        [data-theme="light"] .toolbar-btn:hover {
            background: #eef3ff;
            border-color: #b9c9f8;
            color: #345fd4;
        }

        [data-theme="light"] .toolbar-btn.primary {
            background: #eef3ff;
            border-color: #d5e0ff;
            color: #416de0;
        }

        [data-theme="light"] .toolbar-btn.danger {
            background: #fff1f1;
            border-color: #efd2d2;
            color: #b53c3c;
        }

        [data-theme="light"] .toolbar-input,
        [data-theme="light"] .toolbar-select {
            background: #fff;
            border-color: #dfe6ef;
            color: #334158;
        }

        [data-theme="light"] .toolbar-input:focus,
        [data-theme="light"] .toolbar-select:focus {
            border-color: #9fb5f4;
            box-shadow: 0 0 0 2px rgba(74, 124, 247, 0.10);
        }

        [data-theme="light"] .toolbar-select option {
            background: #fff;
            color: #334158;
        }

        [data-theme="light"] .toolbar-layout-toggle,
        [data-theme="light"] .toolbar-close-btn,
        [data-theme="light"] .toolbar-reset-btn {
            color: #718096;
        }

        [data-theme="light"] .toolbar-layout-toggle:hover,
        [data-theme="light"] .toolbar-reset-btn:hover {
            background: #eef3ff;
            color: #345fd4 !important;
        }

        [data-theme="light"] .toolbar-close-btn:hover {
            background: #fff1f1;
            color: #b53c3c;
        }

        [data-theme="light"] .filter-indicator {
            background: #eef3ff;
            border-color: #d5e0ff;
            color: #416de0;
        }

        /* ===== Tabelas / elementos de dados ===== */
        [data-theme="light"] .table-responsive .table,
        [data-theme="light"] .styled-table {
            color: #4f5d73;
        }

        [data-theme="light"] .table-responsive .table th {
            background: #f7f9fc !important;
            color: #43516a !important;
            border-color: #e2e8f0 !important;
        }

        [data-theme="light"] .table-responsive .table td {
            background: #fff !important;
            color: #536177 !important;
            border-color: #e6ebf2 !important;
        }

        [data-theme="light"] .table-responsive .table tbody tr:hover td {
            background: #f7f9fd !important;
        }

        [data-theme="light"] .table-responsive .table td.c {
            border-bottom-color: rgba(74, 124, 247, 0.24) !important;
            background: rgba(74, 124, 247, 0.012) !important;
            color: #46556d !important;
        }

        [data-theme="light"] .table-responsive .table td.c:hover {
            background: rgba(74, 124, 247, 0.055) !important;
            border-bottom-color: #4a7cf7 !important;
        }

        /* ===== Aviso de salvamento ===== */
        [data-theme="light"] .save-notification {
            background: rgba(255, 255, 255, 0.96) !important;
            border-color: #dce7df !important;
            color: #2c5940 !important;
            box-shadow:
                0 16px 40px rgba(23, 32, 51, 0.13),
                0 0 0 1px rgba(23, 32, 51, 0.02) !important;
        }

        [data-theme="light"] .save-notification .spinner {
            border-color: rgba(74, 124, 247, 0.14);
            border-top-color: #4a7cf7;
        }

        /* ===== Scrollbars ===== */
        [data-theme="light"] ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        [data-theme="light"] ::-webkit-scrollbar-thumb:hover {
            background: #8ea8ea;
            background-clip: padding-box;
            border: 2px solid transparent;
        }

        /* ===== Conexões ===== */
        [data-theme="light"] .connection-line {
            stroke: #4a7cf7;
            filter: drop-shadow(0 0 4px rgba(74, 124, 247, 0.22));
        }

        [data-theme="light"] .connection-line:hover {
            stroke: #345fd4;
            filter: drop-shadow(0 0 8px rgba(74, 124, 247, 0.36));
        }

        /* ===== Código / terminais ===== */
        [data-theme="light"] .editable-item .code-block,
        [data-theme="light"] .editable-item .terminal-content {
            background: #f6f8fb;
            color: #334158;
            border-color: #e0e7ef;
        }

        /* ===== Inputs nativos e placeholders dentro do tema ===== */
        [data-theme="light"] input,
        [data-theme="light"] textarea,
        [data-theme="light"] select {
            color: #334158;
            caret-color: #4a7cf7;
        }

        [data-theme="light"] input::placeholder,
        [data-theme="light"] textarea::placeholder {
            color: #99a4b5;
        }

        /* ===== BOTÃO TOGGLE SIDEBAR (navbar) ===== */
        .navbar-premium .btn-toggle-sidebar {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid rgba(48, 54, 61, 0.6);
            background: rgba(255, 255, 255, 0.03);
            color: #8b949e;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.25s ease,
                border-color 0.25s ease,
                color 0.25s ease,
                transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }

        .navbar-premium .btn-toggle-sidebar:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(74, 124, 247, 0.3);
            color: #f0f6fc;
            transform: scale(1.05);
        }

        .navbar-premium .btn-toggle-sidebar:active {
            transform: scale(0.92);
            transition-duration: 0.1s;
        }

        /* Ícone gira suavemente ao alternar */
        .navbar-premium .btn-toggle-sidebar i {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
            line-height: 1;
        }

        /* Estado "recolhido" — ícone rotacionado 180° pra indicar inversão */
        .navbar-premium .btn-toggle-sidebar.collapsed i {
            transform: rotate(180deg);
        }

        /* Estado ativo (quando sidebar está aberta) — leve destaque */
        .navbar-premium .btn-toggle-sidebar:not(.collapsed) {
            color: #c9d1d9;
        }

        /* Tema claro */
        [data-theme="light"] .navbar-premium .btn-toggle-sidebar {
            background: rgba(0, 0, 0, 0.03);
            border-color: rgba(208, 215, 222, 0.8);
            color: #57606a;
        }

        [data-theme="light"] .navbar-premium .btn-toggle-sidebar:hover {
            background: #eef3ff;
            border-color: #b9c9f8;
            color: #345fd4;
        }

        [data-theme="light"] .navbar-premium .btn-toggle-sidebar:not(.collapsed) {
            color: #1f2328;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <!-- ===== NAVBAR ===== -->
        <nav class="navbar-premium">
            <a class="brand-wrapper" href="#">
                <span class="brand-icon"><i class="bi bi-boxes"></i></span>
                <span class="brand-text">Constell</span>
                <span class="brand-badge">v2.0</span>
            </a>
            <div class="nav-actions">
                <!-- Botão Salvar (novo) -->
                <button class="btn-save" onclick="forceSave()" data-tooltip="Salvar agora (Ctrl+S)">
                    <i class="bi bi-cloud-upload"></i>
                    <span id="save-status" class="save-status">Salvo</span>
                </button>



                <span class="nav-divider"></span>

                <button class="btn-toggle-sidebar" onclick="toggleNavSidebar()" data-tooltip="Alternar sidebar">
                    <i class="bi bi-layout-sidebar"></i>
                </button>
                <span class="nav-divider"></span>


                <a href="auth/logout.php" class="btn-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sair</span>
                </a>
            </div>
        </nav>

        <!-- ===== SIDEBAR ESQUERDA (NAVEGAÇÃO) ===== -->
        <nav id="nav-sidebar" aria-label="Estrutura do projeto">
            <header class="nav-header">
                <div class="nav-title-row">
                    <span class="nav-title">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 4.5A1.5 1.5 0 0 1 3.5 3h3l1.5 1.5H12.5A1.5 1.5 0 0 1 14 6v6a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 12V4.5Z" />
                        </svg>
                        <span>Estrutura</span>
                    </span>
                    <div class="nav-actions-mini">
                        <button class="nav-icon-btn" onclick="toggleNavSidebar()" title="Recolher sidebar (Ctrl+B)">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                                <path d="M10 4l-4 4 4 4" />
                            </svg>
                        </button>
                        <button class="nav-icon-btn primary" id="btn-add-toggle" title="Criar novo">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                                <path d="M8 3v10M3 8h10" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Menu dropdown escondido -->
                <div class="nav-add-menu" id="nav-add-menu">
                    <button class="nav-add-item" onclick="criarCaderno(); closeAddMenu()">
                        <span class="nav-add-icon" style="color:#a78bfa;">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M2 2.5A1.5 1.5 0 0 1 3.5 1h9A1.5 1.5 0 0 1 14 2.5v11a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5v-11Z" />
                                <path d="M5 1v14" />
                            </svg>
                        </span>
                        <span class="nav-add-label">
                            <strong>Novo Caderno</strong>
                            <small>Criar um caderno para agrupar matérias</small>
                        </span>
                        <span class="nav-add-key">⌘N</span>
                    </button>

                    <button class="nav-add-item" onclick="criarMateria(); closeAddMenu()">
                        <span class="nav-add-icon" style="color:#60a5fa;">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M1.5 3.5A1.5 1.5 0 0 1 3 2h3.5l1.5 1.5H13A1.5 1.5 0 0 1 14.5 5v7A1.5 1.5 0 0 1 13 13.5H3A1.5 1.5 0 0 1 1.5 12v-8.5Z" />
                            </svg>
                        </span>
                        <span class="nav-add-label">
                            <strong>Nova Matéria</strong>
                            <small>Adicionar matéria ao caderno atual</small>
                        </span>
                        <span class="nav-add-key">⌘M</span>
                    </button>

                    <button class="nav-add-item" onclick="criarTopico(); closeAddMenu()">
                        <span class="nav-add-icon" style="color:#fbbf24;">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="8" cy="8" r="5.5" />
                                <circle cx="8" cy="8" r="2" fill="currentColor" />
                            </svg>
                        </span>
                        <span class="nav-add-label">
                            <strong>Novo Tópico</strong>
                            <small>Agrupar páginas dentro da matéria</small>
                        </span>
                        <span class="nav-add-key">⌘T</span>
                    </button>

                    <button class="nav-add-item" onclick="novaPagina(); closeAddMenu()">
                        <span class="nav-add-icon" style="color:#34d399;">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 1.5h6.5L13.5 5.5V14A.5.5 0 0 1 13 14.5H3a.5.5 0 0 1-.5-.5V2a.5.5 0 0 1 .5-.5Z" />
                                <path d="M9.5 1.5V5.5H13.5" />
                            </svg>
                        </span>
                        <span class="nav-add-label">
                            <strong>Nova Página</strong>
                            <small>Criar uma nova página em branco</small>
                        </span>
                        <span class="nav-add-key">⌘P</span>
                    </button>
                </div>

                <!-- BARRA DE BUSCA -->
                <div class="nav-search-row">
                    <div class="nav-search-box">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                            <circle cx="7" cy="7" r="4.5" />
                            <path d="M10.5 10.5L13.5 13.5" />
                        </svg>
                        <input type="text" id="nav-search-input" placeholder="Filtrar matérias..." />
                    </div>
                </div>
            </header>
            <div id="nav-tree">
                <ul class="nav-tree-ul">
                    <li class="nav-tree-item" data-type="caderno" data-name="Caderno Principal">
                        <div class="nav-tree-header active" onclick="toggleNavTree(this)">
                            <span class="nav-tree-arrow">▼</span>
                            <span class="nav-tree-label" data-action="goToCaderno" data-value="Caderno Principal">
                                📓 Caderno Principal
                            </span>
                            <span class="nav-tree-badge">1</span>
                        </div>

                        <ul class="nav-tree-children">
                            <li class="nav-tree-item" data-type="materia" data-name="teste">
                                <div class="nav-tree-header active" onclick="toggleNavTree(this)">
                                    <span class="nav-tree-arrow">▼</span>
                                    <span class="nav-tree-label" data-action="goToMateria" data-value="teste">📘 teste</span>
                                    <span class="nav-tree-badge">1</span>
                                </div>

                                <ul class="nav-tree-children">
                                    <li class="nav-tree-item" data-type="topico" data-name="Tópico 1">
                                        <div class="nav-tree-header active" onclick="toggleNavTree(this)">
                                            <span class="nav-tree-arrow">▼</span>
                                            <span class="nav-tree-label" data-action="goToTopico" data-value="Tópico 1">📂 Tópico 1</span>
                                            <span class="nav-tree-badge">2</span>
                                        </div>
                                        <ul class="nav-tree-children">
                                            <li class="nav-tree-item" data-type="pagina" data-index="0">
                                                <div class="nav-tree-header">
                                                    <span class="nav-tree-label" data-action="goToPagina" data-value="0">Página 1</span>
                                                </div>
                                            </li>
                                            <li class="nav-tree-item" data-type="pagina" data-index="1">
                                                <div class="nav-tree-header active">
                                                    <span class="nav-tree-label" data-action="goToPagina" data-value="1">Página 2</span>
                                                </div>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <footer class="nav-footer">
                <span id="page-indicator-sidebar">
                    <i class="bi bi-file-earmark-text"></i>
                    <span class="num" id="current-page-num">1</span>
                    <span style="color:var(--text-muted);">/</span>
                    <span class="num" id="total-page-num">1</span>
                </span>
                <details id="delete-details" class="dropdown-destroy">
                    <summary>
                        <i class="bi bi-trash3"></i>
                        <span>Deletar</span>
                        <i class="bi bi-chevron-down"></i>
                    </summary>
                    <ul>
                        <li><button onclick="deletarPagina()"><i class="bi bi-file-earmark-x"></i> Página atual</button></li>
                        <li><button onclick="deletarTopico()"><i class="bi bi-hash"></i> Tópico atual</button></li>
                        <li><button onclick="deletarMateria()"><i class="bi bi-folder-x"></i> Matéria atual</button></li>
                    </ul>
                </details>
            </footer>
        </nav>

        <!-- ===== SIDEBAR DIREITA (FERRAMENTAS) ===== -->
        <div id="sidebar">
            <button class="sidebar-btn" onclick="zoomIn()" title="Zoom +">
                🔍+ <span class="tooltip">Aumentar</span>
            </button>
            <button class="sidebar-btn" onclick="zoomOut()" title="Zoom -">
                🔍− <span class="tooltip">Diminuir</span>
            </button>
            <button class="sidebar-btn" onclick="resetView()" title="Resetar visualização">
                ⟲ <span class="tooltip">Resetar</span>
            </button>
            <div class="separator"></div>
            <button class="sidebar-btn" onclick="addImageCard()" title="Adicionar Imagem">
                🖼️ <span class="tooltip">Imagem</span>
            </button>
            <button class="sidebar-btn" onclick="addEmptyExplanation()" title="Adicionar Explicação">
                📝 <span class="tooltip">Explicação</span>
            </button>
            <button class="sidebar-btn" onclick="addParagraph()" title="Adicionar Parágrafo">
                ¶ <span class="tooltip">Parágrafo</span>
            </button>
            <button class="sidebar-btn" onclick="addEmptyList()" title="Adicionar Lista">
                📋 <span class="tooltip">Lista</span>
            </button>
            <div class="separator"></div>
            <button class="sidebar-btn" onclick="addEmptyTitle(1)" title="Título H1">H1 <span class="tooltip">H1</span></button>
            <button class="sidebar-btn" onclick="addEmptyTitle(2)" title="Título H2">H2 <span class="tooltip">H2</span></button>
            <button class="sidebar-btn" onclick="addEmptyTitle(3)" title="Título H3">H3 <span class="tooltip">H3</span></button>
            <button class="sidebar-btn" onclick="addEmptyTitle(4)" title="Título H4">H4 <span class="tooltip">H4</span></button>
            <button class="sidebar-btn" onclick="addEmptyTitle(5)" title="Título H5">H5 <span class="tooltip">H5</span></button>
            <button class="sidebar-btn" onclick="addEmptyTitle(6)" title="Título H6">H6 <span class="tooltip">H6</span></button>
            <div class="separator"></div>
            <button class="sidebar-btn" onclick="addEmptyTable()" title="Adicionar Tabela">
                📊 <span class="tooltip">Tabela</span>
            </button>
            <button class="sidebar-btn" onclick="addEmptyCodeCard()" title="Adicionar Código">
                💻 <span class="tooltip">Código</span>
            </button>
        </div>

        <!-- ===== ÁREA DOS CARDS ===== -->
        <div id="pan-zoom-container">
            <div id="cards-container">
                <!-- Cards inseridos pelo JS -->
            </div>
        </div>
    </div>

    <!-- ===== BOTÃO FLUTUANTE E PAINEL ===== -->
    <button id="add-item-fab" class="fab-btn" title="Adicionar item (Ctrl+Shift+A)">
        <i class="bi bi-plus-lg"></i>
    </button>
    <div id="add-item-panel" class="add-item-panel" style="display:none;">
        <div class="panel-header">
            <h3><i class="bi bi-grid-3x3-gap-fill"></i> Adicionar item</h3>
            <button class="panel-close" id="close-add-panel">✕</button>
        </div>
        <div class="panel-search">
            <input type="text" id="item-search" placeholder="Buscar item..." />
        </div>
        <div class="panel-grid" id="item-grid"></div>
    </div>
    <div id="add-item-overlay" class="add-item-overlay" style="display:none;"></div>

    <!-- ===== SCRIPTS ===== -->
    <script>
        // === FUNÇÃO PARA RECOLHER/EXPANDIR A SIDEBAR ===
        // === TOGGLE SIDEBAR (navbar + header interno) ===
        function toggleNavSidebar() {
            const sidebar = document.getElementById('nav-sidebar');
            const btn = document.querySelector('.navbar-premium .btn-toggle-sidebar');
            if (!sidebar) return;

            // Mobile: usa classe 'open' (slide)
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('open');
                return;
            }

            // Desktop: alterna 'collapsed'
            const isCollapsed = sidebar.classList.toggle('collapsed');

            // Sincroniza o botão da navbar
            if (btn) btn.classList.toggle('collapsed', isCollapsed);

            // Persiste preferência
            localStorage.setItem('navCollapsed', isCollapsed ? '1' : '0');

            // NÃO mexa em container.style.left — deixe o CSS cuidar
            // (você já tem: #nav-sidebar.collapsed ~ #pan-zoom-container { left: var(--sidebar-collapsed); })
        }

        // === Aplica estado salvo ao carregar ===
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('nav-sidebar');
            const btn = document.querySelector('.navbar-premium .btn-toggle-sidebar');
            if (!sidebar) return;

            // Só aplica em desktop
            if (window.innerWidth > 768) {
                const collapsed = localStorage.getItem('navCollapsed') === '1';
                sidebar.classList.toggle('collapsed', collapsed);
                if (btn) btn.classList.toggle('collapsed', collapsed);
            }
        });

        // === REDIMENSIONAMENTO: manter consistência ===
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('nav-sidebar');
            if (!sidebar) return;

            if (window.innerWidth <= 768) {
                // Mobile: remove collapsed, mostra/oculta via 'open'
                sidebar.classList.remove('collapsed');
                sidebar.classList.remove('open');
            } else {
                // Desktop: reaplica estado salvo
                sidebar.classList.remove('open');
                const collapsed = localStorage.getItem('navCollapsed') === '1';
                sidebar.classList.toggle('collapsed', collapsed);
                const btn = document.querySelector('.navbar-premium .btn-toggle-sidebar');
                if (btn) btn.classList.toggle('collapsed', collapsed);
            }
        });

        // Inicializa a posição correta
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('nav-sidebar');
            const container = document.getElementById('pan-zoom-container');
            if (sidebar && container) {
                if (window.innerWidth > 768) {
                    container.style.left = '280px';
                } else {
                    container.style.left = '0px';
                }
            }
        });
    </script>

    <!-- Seus JS externos -->
    <script src="assets/js/Globals.js"></script>
    <script src="assets/js/Conections.js"></script>
    <script src="assets/js/Toolbar.js"></script>
    <script src="assets/js/UITools.js"></script>
    <script src="assets/js/Sidebar.js"></script>
    <script src="assets/js/AddItens.js"></script>
    <script src="assets/js/HotKeys.js"></script>
    <script src="assets/js/Dom.js"></script>
    <script src="assets/js/PanZoom.js"></script>

</body>

</html>