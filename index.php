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
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constell Main</title>
    <!-- Bootstrap, Ícones e Fontes -->
    <link rel="stylesheet" href="assets/css/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- adicionar no head -->
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
            background-color: var(--bg-body);
            color: var(--text-secondary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-image:
                radial-gradient(circle, #2a2a2a 1.5px, transparent 1.5px),
                repeating-linear-gradient(45deg, rgba(255, 255, 255, 0.01) 0px, rgba(255, 255, 255, 0.01) 2px, transparent 2px, transparent 4px);
            background-size: 20px 20px, 8px 8px;
            background-position: 20px 20px;
            overflow: hidden;
        }

        /* ===== NAVBAR PREMIUM ===== */
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
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .navbar-premium:hover {
            background: rgba(13, 17, 23, 0.95);
            border-bottom-color: rgba(74, 124, 247, 0.2);
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

        .navbar-premium .btn-toggle-sidebar {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid rgba(48, 54, 61, 0.6);
            background: rgba(255, 255, 255, 0.03);
            color: #8b949e;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.3s;
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
   NAV-SIDEBAR REFORMULADA (PREMIUM)
   ============================================================ */
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
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            font-family: 'Inter', -apple-system, sans-serif;
            color: var(--text-secondary);
        }

        /* Estado recolhido */
        #nav-sidebar.collapsed {
            width: var(--sidebar-collapsed, 60px);
        }

        /* ===== HEADER ===== */
        #nav-sidebar .nav-header {
            padding: 18px 20px 14px 20px;
            border-bottom: 1px solid rgba(48, 54, 61, 0.3);
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 56px;
            background: rgba(13, 17, 23, 0.4);
        }

        #nav-sidebar .nav-header .brand {
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
            font-family: 'Share Tech Mono', monospace;
        }

        #nav-sidebar .nav-header .brand i {
            font-size: 1.3rem;
            color: var(--accent);
            filter: drop-shadow(0 0 8px rgba(74, 124, 247, 0.3));
            transition: transform 0.3s ease;
        }

        #nav-sidebar .nav-header .brand:hover i {
            transform: rotate(-8deg) scale(1.1);
        }

        /* Botões de adição */
        #nav-sidebar .btn-add-group {
            display: flex;
            gap: 4px;
        }

        #nav-sidebar .btn-add-group .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid transparent;
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-muted);
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        #nav-sidebar .btn-add-group .btn-icon:hover {
            background: rgba(74, 124, 247, 0.08);
            border-color: rgba(74, 124, 247, 0.2);
            color: var(--text-primary);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 124, 247, 0.1);
        }

        #nav-sidebar .btn-add-group .btn-icon[title="Nova Página"] {
            color: #2ea043;
        }

        #nav-sidebar .btn-add-group .btn-icon[title="Nova Página"]:hover {
            background: rgba(46, 160, 67, 0.08);
            border-color: rgba(46, 160, 67, 0.2);
            color: #3fb950;
        }

        /* Tooltips dos botões */
        #nav-sidebar .btn-add-group .btn-icon::after {
            content: attr(title);
            position: absolute;
            bottom: -28px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--bg-elevated);
            color: var(--text-secondary);
            font-size: 0.6rem;
            font-weight: 500;
            padding: 2px 10px;
            border-radius: 6px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s ease;
            border: 1px solid var(--border-subtle);
            font-family: 'Inter', sans-serif;
        }

        #nav-sidebar .btn-add-group .btn-icon:hover::after {
            opacity: 1;
            bottom: -32px;
        }

        /* ===== ÁRVORE ===== */
        #nav-sidebar #nav-tree {
            flex: 1;
            overflow-y: auto;
            padding: 12px 14px 20px 14px;
            background: transparent;
            scrollbar-width: thin;
            scrollbar-color: rgba(48, 54, 61, 0.3) transparent;
        }

        #nav-sidebar #nav-tree::-webkit-scrollbar {
            width: 3px;
        }

        #nav-sidebar #nav-tree::-webkit-scrollbar-track {
            background: transparent;
        }

        #nav-sidebar #nav-tree::-webkit-scrollbar-thumb {
            background: rgba(48, 54, 61, 0.3);
            border-radius: 10px;
        }

        #nav-sidebar #nav-tree::-webkit-scrollbar-thumb:hover {
            background: rgba(74, 124, 247, 0.3);
        }

        /* Lista */
        #nav-sidebar .nav-tree-ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        /* Item */
        #nav-sidebar .nav-tree-item {
            margin-bottom: 1px;
            position: relative;
            transition: all 0.2s ease;
        }

        /* Header do item */
        #nav-sidebar .nav-tree-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 10px;
            color: var(--text-muted);
            font-size: 0.85rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            background: transparent;
            border: none;
            min-height: 38px;
            width: 100%;
            text-align: left;
            font-family: 'Inter', sans-serif;
            position: relative;
        }

        #nav-sidebar .nav-tree-header:hover {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-primary);
            transform: translateX(4px);
        }

        /* Seta */
        #nav-sidebar .nav-tree-arrow {
            font-size: 10px;
            width: 18px;
            text-align: center;
            color: var(--text-muted);
            flex-shrink: 0;
            transition: transform 0.3s ease, color 0.2s;
            font-weight: 300;
        }

        #nav-sidebar .nav-tree-header:not(.expanded) .nav-tree-arrow {
            transform: rotate(-90deg);
        }

        #nav-sidebar .nav-tree-header.expanded .nav-tree-arrow {
            transform: rotate(0deg);
        }

        /* Label */
        #nav-sidebar .nav-tree-label {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 400;
            font-size: 0.85rem;
            cursor: pointer;
            color: inherit;
            transition: all 0.2s ease;
        }

        #nav-sidebar .nav-tree-header:hover .nav-tree-label {
            font-weight: 500;
            letter-spacing: 0.02em;
        }

        /* Badge */
        #nav-sidebar .nav-tree-badge {
            background: rgba(22, 27, 34, 0.6);
            color: var(--text-muted);
            border-radius: 30px;
            padding: 0 10px;
            font-size: 0.65rem;
            line-height: 20px;
            font-weight: 500;
            border: 1px solid rgba(48, 54, 61, 0.2);
            font-family: 'Share Tech Mono', monospace;
            transition: all 0.3s ease;
        }

        #nav-sidebar .nav-tree-header:hover .nav-tree-badge {
            background: rgba(74, 124, 247, 0.1);
            border-color: rgba(74, 124, 247, 0.2);
            color: var(--text-secondary);
        }

        /* Estado ativo */
        #nav-sidebar .nav-tree-header.active {
            background: rgba(74, 124, 247, 0.08);
            color: var(--text-primary);
            border-left: 3px solid var(--accent);
            padding-left: 11px;
            box-shadow: inset 0 0 40px rgba(74, 124, 247, 0.02), 0 2px 12px rgba(74, 124, 247, 0.05);
        }

        #nav-sidebar .nav-tree-header.active .nav-tree-label {
            font-weight: 500;
        }

        #nav-sidebar .nav-tree-header.active .nav-tree-arrow {
            color: var(--accent);
        }

        #nav-sidebar .nav-tree-header.active .nav-tree-badge {
            background: rgba(74, 124, 247, 0.15);
            border-color: rgba(74, 124, 247, 0.2);
            color: var(--text-primary);
        }

        /* Linha indicadora no estado ativo (lado direito) */
        #nav-sidebar .nav-tree-header.active::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: linear-gradient(to bottom, var(--accent), #6f42c1);
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 16px rgba(74, 124, 247, 0.2);
        }

        /* Children */
        #nav-sidebar .nav-tree-children {
            list-style: none;
            padding-left: 20px;
            margin: 0;
            overflow: hidden;
            transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
        }

        /* Linhas de indentação (conectores) - opcional, mas moderno */
        #nav-sidebar .nav-tree-children::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 1px;
            background: rgba(48, 54, 61, 0.15);
        }

        /* Tipos específicos */
        #nav-sidebar .nav-tree-item[data-type="materia"]>.nav-tree-header {
            font-weight: 500;
            color: var(--text-secondary);
        }

        #nav-sidebar .nav-tree-item[data-type="topico"]>.nav-tree-header {
            padding-left: 34px;
            font-size: 0.82rem;
            position: relative;
        }

        #nav-sidebar .nav-tree-item[data-type="topico"]>.nav-tree-header::before {
            content: '';
            position: absolute;
            left: 18px;
            top: 50%;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(48, 54, 61, 0.3);
            transform: translateY(-50%);
            border: 1px solid rgba(48, 54, 61, 0.1);
            transition: all 0.3s ease;
        }

        #nav-sidebar .nav-tree-item[data-type="topico"]>.nav-tree-header:hover::before {
            background: rgba(74, 124, 247, 0.3);
            border-color: rgba(74, 124, 247, 0.2);
            box-shadow: 0 0 12px rgba(74, 124, 247, 0.15);
            transform: translateY(-50%) scale(1.2);
        }

        #nav-sidebar .nav-tree-item[data-type="topico"]>.nav-tree-header.active::before {
            background: var(--accent);
            border-color: var(--accent);
            box-shadow: 0 0 16px rgba(74, 124, 247, 0.4);
            transform: translateY(-50%) scale(1.3);
        }

        #nav-sidebar .nav-tree-item[data-type="pagina"]>.nav-tree-header {
            padding-left: 54px;
            font-size: 0.78rem;
            color: var(--text-muted);
            position: relative;
        }

        #nav-sidebar .nav-tree-item[data-type="pagina"]>.nav-tree-header::before {
            content: '▸';
            position: absolute;
            left: 36px;
            color: rgba(48, 54, 61, 0.3);
            font-size: 10px;
            transition: all 0.3s ease;
        }

        #nav-sidebar .nav-tree-item[data-type="pagina"]>.nav-tree-header:hover::before {
            color: rgba(74, 124, 247, 0.4);
            transform: translateX(2px);
        }

        #nav-sidebar .nav-tree-item[data-type="pagina"]>.nav-tree-header.active::before {
            color: var(--accent);
            transform: translateX(4px);
        }

        #nav-sidebar .nav-tree-item[data-type="pagina"]>.nav-tree-header.active {
            color: var(--text-primary);
            background: rgba(74, 124, 247, 0.05);
            border-left-color: var(--accent);
        }

        #nav-sidebar .nav-tree-item[data-type="pagina"]>.nav-tree-header .nav-tree-label {
            font-weight: 400;
        }

        #nav-sidebar .nav-tree-item[data-type="pagina"]>.nav-tree-header.active .nav-tree-label {
            font-weight: 500;
        }

        /* Animação de entrada dos itens */
        #nav-sidebar .nav-tree-item {
            animation: slideInNav 0.4s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        #nav-sidebar .nav-tree-item:nth-child(1) {
            animation-delay: 0.02s;
        }

        #nav-sidebar .nav-tree-item:nth-child(2) {
            animation-delay: 0.04s;
        }

        #nav-sidebar .nav-tree-item:nth-child(3) {
            animation-delay: 0.06s;
        }

        #nav-sidebar .nav-tree-item:nth-child(4) {
            animation-delay: 0.08s;
        }

        #nav-sidebar .nav-tree-item:nth-child(5) {
            animation-delay: 0.10s;
        }

        #nav-sidebar .nav-tree-item:nth-child(6) {
            animation-delay: 0.12s;
        }

        #nav-sidebar .nav-tree-item:nth-child(7) {
            animation-delay: 0.14s;
        }

        @keyframes slideInNav {
            0% {
                opacity: 0;
                transform: translateX(-16px) scale(0.96);
            }

            100% {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
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
            border-radius: 12px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            font-family: inherit;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.01);
        }

        /* Efeito hover com brilho e leve elevação */
        #sidebar .sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-primary);
            transform: scale(1.1) translateY(-3px);
            box-shadow: 0 8px 24px rgba(74, 124, 247, 0.15),
                inset 0 0 30px rgba(74, 124, 247, 0.05);
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
            background: radial-gradient(ellipse at 50% 30%, rgba(74, 124, 247, 0.02) 0%, transparent 70%);
        }

        /* ===== CARDS (EDITABLE ITEMS) – VERSÃO PREMIUM ===== */
        .editable-item {
            position: absolute;
            background: rgba(22, 27, 34, 0.88);
            backdrop-filter: blur(12px) saturate(1.2);
            -webkit-backdrop-filter: blur(12px) saturate(1.2);
            border: 1px solid rgba(48, 54, 61, 0.4);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.02) inset;
            padding: 16px 20px;
            min-width: 60px;
            min-height: 40px;
            transition: box-shadow 0.35s ease,
                border-color 0.35s ease,
                transform 0.2s ease,
                background 0.3s ease;
            cursor: default;
            word-wrap: break-word;
            overflow-wrap: break-word;
            color: var(--text-secondary);
            font-family: 'Inter', sans-serif;
        }

        /* Efeito hover com brilho e leve elevação */
        .editable-item:hover {
            border-color: rgba(74, 124, 247, 0.3);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(74, 124, 247, 0.1) inset,
                0 0 30px rgba(74, 124, 247, 0.05);
            transform: translateY(-2px);
            background: rgba(22, 27, 34, 0.94);
        }

        /* Estado de edição ativa (active-editing) – destaque sutil */
        .editable-item .active-editing {
            outline: none;
            border-radius: 8px;
            padding: 4px 8px;
            background: rgba(74, 124, 247, 0.04);
            box-shadow: 0 0 0 2px rgba(74, 124, 247, 0.15), 0 0 20px rgba(74, 124, 247, 0.02);
            transition: background 0.2s, box-shadow 0.2s;
        }

        .editable-item .active-editing:focus {
            background: rgba(74, 124, 247, 0.06);
            box-shadow: 0 0 0 2px rgba(74, 124, 247, 0.3), 0 0 30px rgba(74, 124, 247, 0.05);
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

        /* Scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-body);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-subtle);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
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

        /* ===== BOTÃO SALVAR ===== */
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
        .btn-save.saving .save-status {
            color: #f0f6fc;
            background: rgba(74, 124, 247, 0.2);
            animation: pulse-save 1s infinite;
        }

        .btn-save.saving i {
            animation: spin 1s linear infinite;
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
                <span class="brand">
                    <i class="bi bi-grid-3x3-gap-fill"></i>
                    <span>Estrutura</span>
                </span>
                <div class="btn-add-group" role="group">
                    <button class="btn-icon" onclick="criarMateria()" title="Nova Matéria">
                        <i class="bi bi-folder-plus"></i>
                    </button>
                    <button class="btn-icon" onclick="criarTopico()" title="Novo Tópico">
                        <i class="bi bi-hash"></i>
                    </button>
                    <button class="btn-icon" onclick="novaPagina()" title="Nova Página" style="color:#2ea043;">
                        <i class="bi bi-file-earmark-plus"></i>
                    </button>
                </div>
            </header>
            <div id="nav-tree">
                <!-- Árvore gerada dinamicamente pelo JS -->
                <ul class="nav-tree-ul">
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
        function toggleNavSidebar() {
            const sidebar = document.getElementById('nav-sidebar');
            if (!sidebar) return;

            // Em telas pequenas usa classe 'open' para slide
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('open');
                return;
            }

            // Em telas grandes alterna a largura
            sidebar.classList.toggle('collapsed');
            // Ajusta a margem do container de cards
            const container = document.getElementById('pan-zoom-container');
            if (container) {
                const isCollapsed = sidebar.classList.contains('collapsed');
                container.style.left = isCollapsed ? '60px' : '280px';
            }
        }

        // === REDIMENSIONAMENTO: manter consistência ===
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('nav-sidebar');
            const container = document.getElementById('pan-zoom-container');
            if (!sidebar || !container) return;

            if (window.innerWidth <= 768) {
                // No mobile, remove collapsed e ajusta left
                sidebar.classList.remove('collapsed');
                container.style.left = '0px';
            } else {
                // Se estava collapsed, mantém; senão, volta para 280px
                if (!sidebar.classList.contains('collapsed')) {
                    container.style.left = '280px';
                } else {
                    container.style.left = '60px';
                }
                sidebar.classList.remove('open');
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

        // (Opcional) Exemplo de funções usadas nos botões – defina as suas
        function toggleNavTree(el) {
            el.classList.toggle('expanded');
            const children = el.nextElementSibling;
            if (children && children.classList.contains('nav-tree-children')) {
                children.style.display = children.style.display === 'none' ? 'block' : 'none';
            }
        }
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