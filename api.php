<?php
// ============================================================
// 1. CONFIGURAÇÕES DE SEGURANÇA E SESSÃO
// ============================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_path', '/');
session_start();

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Limite de tamanho do payload (2 MB)
$maxPayloadSize = 10 * 1024 * 1024; // 10 MB
if ($_SERVER['CONTENT_LENGTH'] > $maxPayloadSize) {
    http_response_code(413);
    echo json_encode(['success' => false, 'error' => 'Payload muito grande']);
    exit;
}

// CORS (opcional, ajuste conforme necessidade)
$allowedOrigins = ['https://seudominio.com', 'http://localhost:3000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');
}

// Responde preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================================
// 2. CONEXÃO COM BANCO
// ============================================================
require_once __DIR__ . '/config.php';

$host = DB_HOST;
$dbname = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno no servidor']);
    error_log('Erro de conexão DB: ' . $e->getMessage());
    exit;
}

// ============================================================
// 3. FUNÇÕES AUXILIARES
// ============================================================
function sanitizeRecursive(&$value, $maxLength = 10000000)
{
    if (is_string($value)) {
        $value = trim($value);
        if (strlen($value) > $maxLength) $value = substr($value, 0, $maxLength);
        return;
    }
    if (is_array($value)) {
        foreach ($value as $key => &$sub) {
            sanitizeRecursive($sub, $maxLength);
        }
    }
}

function sanitizeHtml($html)
{
    if (!is_string($html)) return '';
    $html = trim($html);
    // Permite até 10 MB de caracteres (suficiente para base64 de ~2MB)
    if (strlen($html) > 10000000) $html = substr($html, 0, 10000000);
    $allowedTags = '<b><i><u><strong><em><p><br><span><div><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><sub><sup><blockquote><pre><code><table><thead><tbody><tr><td><th><caption><button>';
    $html = strip_tags($html, $allowedTags);
    $html = preg_replace('/\s*on\w+="[^"]*"/i', '', $html);
    $html = preg_replace("/\s*on\w+='[^']*'/i", '', $html);
    $html = preg_replace('/\s*href\s*=\s*["\']javascript:/i', ' href="#"', $html);
    $html = preg_replace('/\s*src\s*=\s*["\']javascript:/i', ' src="#"', $html);
    return $html;
}

function isValidCssValue($value)
{
    if (!is_string($value)) return false;
    $value = trim($value);
    return preg_match('/^(\d+\.?\d*)(px|%|em|rem|vw|vh)?$|^(auto|inherit|initial)$/', $value) === 1;
}

function validateJson($value)
{
    if (is_null($value)) return null;
    if (is_string($value)) {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE ? $value : null;
    }
    if (is_array($value) || is_object($value)) {
        return json_encode($value);
    }
    return null;
}

// ============================================================
// 3.1 FUNÇÃO DE VERIFICAÇÃO CSRF (centralizada)
// ============================================================
function verifyCsrf()
{
    $expectedToken = $_SESSION['csrf_token'] ?? null;
    $receivedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$expectedToken || !$receivedToken || !hash_equals($expectedToken, $receivedToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
        exit;
    }
}

// ============================================================
// 4. ROTAS PÚBLICAS (login / register)
// ============================================================
$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit;
    }

    $nome = trim($input['nome'] ?? '');
    $email = trim($input['email'] ?? '');
    $senha = $input['senha'] ?? '';

    if (!$nome || !$email || !$senha) {
        echo json_encode(['success' => false, 'error' => 'Campos obrigatórios']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Email inválido']);
        exit;
    }
    if (strlen($nome) > 100 || strlen($email) > 100 || strlen($senha) > 100) {
        echo json_encode(['success' => false, 'error' => 'Campos excedem tamanho máximo']);
        exit;
    }

    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Email já cadastrado']);
        exit;
    }

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, token) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nome, $email, $hash, $token]);

    echo json_encode(['success' => true, 'token' => $token, 'usuario' => $nome]);
    exit;
}

if ($action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit;
    }

    $username = trim($input['username'] ?? '');
    $senha = $input['senha'] ?? '';

    if (!$username || !$senha) {
        echo json_encode(['success' => false, 'error' => 'Usuário e senha obrigatórios']);
        exit;
    }
    if (strlen($username) > 100 || strlen($senha) > 100) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, nome, senha, token FROM usuarios WHERE nome = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        $novoToken = bin2hex(random_bytes(32));
        $stmt = $db->prepare("UPDATE usuarios SET token = ? WHERE id = ?");
        $stmt->execute([$novoToken, $user['id']]);
        echo json_encode(['success' => true, 'token' => $novoToken, 'usuario' => $user['nome']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Usuário ou senha inválidos']);
    }
    exit;
}

// ============================================================
// 5. AUTENTICAÇÃO VIA SESSÃO PARA ROTAS PROTEGIDAS
// ============================================================
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$usuario_id = (int)$_SESSION['user_id'];

// ============================================================
// 6. ROTA PROTEGIDA: SAVE (com CSRF)
// ============================================================
if ($action === 'save') {
    verifyCsrf();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit;
    }

    $data = $input['data'] ?? [];
    if (empty($data) || !is_array($data)) {
        echo json_encode(['success' => false, 'error' => 'Dados vazios ou malformados']);
        exit;
    }

    sanitizeRecursive($data, 10000000);

    if (!isset($data['materias']) || !is_array($data['materias'])) {
        echo json_encode(['success' => false, 'error' => 'Estrutura de dados inválida']);
        exit;
    }

    try {
        $db->beginTransaction();

        // Remove dados antigos do usuário
        $stmt = $db->prepare("DELETE FROM materias WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);

        $materias = $data['materias'] ?? [];

        foreach ($materias as $nomeMateria => $materia) {
            $nomeMateria = trim($nomeMateria);
            if ($nomeMateria === '') continue;
            if (strlen($nomeMateria) > 100) $nomeMateria = substr($nomeMateria, 0, 100);

            $stmt = $db->prepare("INSERT INTO materias (usuario_id, nome) VALUES (?, ?)");
            $stmt->execute([$usuario_id, $nomeMateria]);
            $materia_id = (int)$db->lastInsertId();

            $topicos = $materia['topicos'] ?? [];
            if (!is_array($topicos)) continue;

            foreach ($topicos as $nomeTopico => $topico) {
                $nomeTopico = trim($nomeTopico);
                if ($nomeTopico === '') continue;
                if (strlen($nomeTopico) > 100) $nomeTopico = substr($nomeTopico, 0, 100);

                $stmt = $db->prepare("INSERT INTO topicos (materia_id, nome) VALUES (?, ?)");
                $stmt->execute([$materia_id, $nomeTopico]);
                $topico_id = (int)$db->lastInsertId();

                $paginas = $topico['paginas'] ?? [];
                if (!is_array($paginas)) continue;

                foreach ($paginas as $ordem => $pagina) {
                    $ordem = (int)$ordem;
                    if ($ordem < 0) $ordem = 0;

                    // 🔥 public_id – preserva se existir ou gera novo
                    $public_id = trim($pagina['public_id'] ?? '');
                    if (empty($public_id)) {
                        $public_id = bin2hex(random_bytes(16)); // 32 caracteres hex
                    }

                    $titulo = trim($pagina['titulo'] ?? "Página " . ($ordem + 1));
                    if (strlen($titulo) > 200) $titulo = substr($titulo, 0, 200);

                    // 🔥 Inclui is_public no INSERT
                    $is_public = isset($pagina['is_public']) ? (int)$pagina['is_public'] : 0;
                    $stmt = $db->prepare("INSERT INTO paginas (topico_id, titulo, ordem, public_id, is_public) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$topico_id, $titulo, $ordem, $public_id, $is_public]);
                    $pagina_id = (int)$db->lastInsertId();

                    // Cards
                    // Cards
                    $cards = $pagina['cards'] ?? [];
                    if (!is_array($cards)) continue;

                    foreach ($cards as $card) {
                        if (!is_array($card)) continue;

                        $html = sanitizeHtml($card['html'] ?? '');
                        $pos_left = trim($card['left'] ?? '20px');
                        $pos_top  = trim($card['top'] ?? '20px');
                        $width    = trim($card['width'] ?? 'auto');
                        $height   = trim($card['height'] ?? 'auto');
                        $class_name = trim($card['className'] ?? 'editable-item');
                        $sort_order = isset($card['sort_order']) ? (int)$card['sort_order'] : 0;
                        $state_json = $card['state'] ?? null;
                        $card_id   = trim($card['cardId'] ?? '');   // <-- NOVO
                        if (strlen($card_id) > 50) $card_id = substr($card_id, 0, 50);

                        if (!isValidCssValue($pos_left)) $pos_left = '20px';
                        if (!isValidCssValue($pos_top)) $pos_top = '20px';
                        if (!isValidCssValue($width)) $width = 'auto';
                        if (!isValidCssValue($height)) $height = 'auto';
                        if (strlen($class_name) > 100) $class_name = substr($class_name, 0, 100);

                        $state_json = validateJson($state_json);

                        $stmt = $db->prepare("INSERT INTO cards 
        (pagina_id, html, pos_left, pos_top, width, height, class_name, sort_order, state_json, card_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $pagina_id,
                            $html,
                            $pos_left,
                            $pos_top,
                            $width,
                            $height,
                            $class_name,
                            $sort_order,
                            $state_json,
                            $card_id   // <-- NOVO
                        ]);
                    }

                    // Connections
                    $connections = $pagina['connections'] ?? [];
                    if (!is_array($connections)) continue;

                    foreach ($connections as $conn) {
                        if (!is_array($conn)) continue;
                        $fromIndex = isset($conn['fromIndex']) ? (int)$conn['fromIndex'] : 0;
                        $fromPos   = trim($conn['fromPos'] ?? 'bottom');
                        $toIndex   = isset($conn['toIndex']) ? (int)$conn['toIndex'] : 0;
                        $toPos     = trim($conn['toPos'] ?? 'top');

                        $validPos = ['top', 'bottom', 'left', 'right'];
                        if (!in_array($fromPos, $validPos)) $fromPos = 'bottom';
                        if (!in_array($toPos, $validPos)) $toPos = 'top';

                        $stmt = $db->prepare("INSERT INTO connections 
                            (pagina_id, from_index, from_pos, to_index, to_pos)
                            VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $pagina_id,
                            $fromIndex,
                            $fromPos,
                            $toIndex,
                            $toPos
                        ]);
                    }
                }
            }
        }

        // Salva estado atual
        $current = $data['current'] ?? [];
        sanitizeRecursive($current, 50000);
        $jsonCurrent = json_encode($current);
        if ($jsonCurrent === false) $jsonCurrent = '{}';

        $stmt = $db->prepare("UPDATE usuarios SET dados_atual = ? WHERE id = ?");
        $stmt->execute([$jsonCurrent, $usuario_id]);

        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar dados']);
        error_log('Erro save: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    }
    exit;
}

// ============================================================
// 7. ROTA PROTEGIDA: LOAD
// ============================================================
if ($action === 'load') {
    try {
        $data = [
            'materias' => [],
            'current' => null,
            'page' => ['cards' => [], 'connections' => []]
        ];

        // Carregar estado atual
        $stmt = $db->prepare("SELECT dados_atual FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $currentJson = $stmt->fetchColumn();
        $data['current'] = $currentJson ? json_decode($currentJson, true) : null;

        // Carregar matérias existentes
        $stmt = $db->prepare("SELECT id, nome FROM materias WHERE usuario_id = ? ORDER BY nome");
        $stmt->execute([$usuario_id]);
        $materias = $stmt->fetchAll();

        // SE NÃO HOUVER MATÉRIAS, CRIAR ESTRUTURA PADRÃO
        if (empty($materias)) {
            // Inserir matéria padrão
            $stmt = $db->prepare("INSERT INTO materias (usuario_id, nome) VALUES (?, ?)");
            $stmt->execute([$usuario_id, 'Geral']);
            $materiaId = (int)$db->lastInsertId();

            // Inserir tópico padrão
            $stmt = $db->prepare("INSERT INTO topicos (materia_id, nome) VALUES (?, ?)");
            $stmt->execute([$materiaId, 'Introdução']);
            $topicoId = (int)$db->lastInsertId();

            // Inserir página padrão com public_id
            $public_id = bin2hex(random_bytes(16));
            $stmt = $db->prepare("INSERT INTO paginas (topico_id, titulo, ordem, public_id, is_public) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$topicoId, 'Página 1', 0, $public_id, 0]); // is_public =
            $paginaId = (int)$db->lastInsertId();

            // Inserir um card de boas-vindas
            $stmt = $db->prepare("INSERT INTO cards (pagina_id, html, pos_left, pos_top, width, height, class_name, sort_order) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $paginaId,
                '50px',
                '50px',
                '400px',
                'auto',
                'editable-item',
                0
            ]);

            // Atualizar estado atual no usuário
            $currentDefault = json_encode(['materia' => 'Geral', 'topico' => 'Introdução', 'pagina' => 0]);
            $stmt = $db->prepare("UPDATE usuarios SET dados_atual = ? WHERE id = ?");
            $stmt->execute([$currentDefault, $usuario_id]);

            // Agora recarregar os dados para montar a resposta
            $stmt = $db->prepare("SELECT id, nome FROM materias WHERE usuario_id = ? ORDER BY nome");
            $stmt->execute([$usuario_id]);
            $materias = $stmt->fetchAll();
        }

        // Montar a estrutura completa
        foreach ($materias as $m) {
            $materiaNome = $m['nome'];
            $materiaId = (int)$m['id'];
            $data['materias'][$materiaNome] = ['topicos' => []];

            $stmt2 = $db->prepare("SELECT id, nome FROM topicos WHERE materia_id = ? ORDER BY nome");
            $stmt2->execute([$materiaId]);
            $topicos = $stmt2->fetchAll();

            foreach ($topicos as $t) {
                $topicoNome = $t['nome'];
                $topicoId = (int)$t['id'];
                $data['materias'][$materiaNome]['topicos'][$topicoNome] = ['paginas' => []];

                $stmt3 = $db->prepare("SELECT id, titulo, ordem, public_id FROM paginas WHERE topico_id = ? ORDER BY ordem");
                $stmt3->execute([$topicoId]);
                $paginas = $stmt3->fetchAll();

                foreach ($paginas as $p) {
                    $paginaId = (int)$p['id'];
                    $public_id = $p['public_id']; // 🔥 busca o public_id
                    $paginaData = [
                        'id' => $paginaId,
                        'public_id' => $public_id,
                        'is_public' => (int)$p['is_public'],  // 🔥 ADICIONE ESTA LINHA
                        'titulo' => $p['titulo'],
                        'cards' => [],
                        'connections' => []
                    ];

                    // Cards
                    $stmt4 = $db->prepare("SELECT html, pos_left, pos_top, width, height, class_name, sort_order, state_json, card_id 
                       FROM cards WHERE pagina_id = ? ORDER BY sort_order");
                    $stmt4->execute([$paginaId]);
                    $cards = $stmt4->fetchAll();

                    foreach ($cards as $c) {
                        $paginaData['cards'][] = [
                            'id' => null,
                            'html' => $c['html'],
                            'left' => $c['pos_left'],
                            'top' => $c['pos_top'],
                            'width' => $c['width'],
                            'height' => $c['height'],
                            'className' => $c['class_name'],
                            'sort_order' => (int)$c['sort_order'],
                            'state' => $c['state_json'],
                            'cardId' => $c['card_id']   // <-- NOVO
                        ];
                    }

                    // Connections
                    $stmt5 = $db->prepare("SELECT from_index, from_pos, to_index, to_pos 
                                           FROM connections WHERE pagina_id = ?");
                    $stmt5->execute([$paginaId]);
                    $connections = $stmt5->fetchAll();

                    foreach ($connections as $conn) {
                        $paginaData['connections'][] = [
                            'fromIndex' => (int)$conn['from_index'],
                            'fromPos' => $conn['from_pos'],
                            'toIndex' => (int)$conn['to_index'],
                            'toPos' => $conn['to_pos']
                        ];
                    }

                    $data['materias'][$materiaNome]['topicos'][$topicoNome]['paginas'][] = $paginaData;
                }
            }
        }

        // Monta página atual
        if ($data['current']) {
            $m = $data['current']['materia'] ?? '';
            $t = $data['current']['topico'] ?? '';
            $p = isset($data['current']['pagina']) ? (int)$data['current']['pagina'] : 0;
            if (isset($data['materias'][$m]['topicos'][$t]['paginas'][$p])) {
                $data['page'] = $data['materias'][$m]['topicos'][$t]['paginas'][$p];
                if (!isset($data['page']['id'])) {
                    $data['page']['id'] = $data['page']['id'] ?? 0;
                }
            } else {
                $primeiraMateria = array_key_first($data['materias']);
                if ($primeiraMateria) {
                    $primeiroTopico = array_key_first($data['materias'][$primeiraMateria]['topicos']);
                    if ($primeiroTopico && !empty($data['materias'][$primeiraMateria]['topicos'][$primeiroTopico]['paginas'])) {
                        $data['page'] = $data['materias'][$primeiraMateria]['topicos'][$primeiroTopico]['paginas'][0];
                        $data['current'] = ['materia' => $primeiraMateria, 'topico' => $primeiroTopico, 'pagina' => 0];
                    }
                }
            }
        }

        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao carregar dados']);
        error_log('Erro load: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    }
    exit;
}

// ============================================================
// 8. ROTA PROTEGIDA: EXPORTAR PÁGINA (GET – sem CSRF)
// ============================================================
if ($action === 'export') {
    $materia = $_GET['materia'] ?? '';
    $topico  = $_GET['topico'] ?? '';
    $pagina  = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 0;

    if (!$materia || !$topico) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Matéria e tópico são obrigatórios']);
        exit;
    }

    try {
        // Busca o ID da página com base no usuário, matéria, tópico e ordem
        $stmt = $db->prepare("
            SELECT p.id, p.titulo, p.public_id, p.is_public
            FROM paginas p
            JOIN topicos t ON p.topico_id = t.id
            JOIN materias m ON t.materia_id = m.id
            WHERE m.usuario_id = ? AND m.nome = ? AND t.nome = ? AND p.ordem = ?
        ");
        $stmt->execute([$usuario_id, $materia, $topico, $pagina]);
        $paginaData = $stmt->fetch();

        if (!$paginaData) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Página não encontrada']);
            exit;
        }

        $paginaId = $paginaData['id'];

        // Busca os cards
        $stmt = $db->prepare("SELECT html, pos_left, pos_top, width, height, class_name, sort_order, state_json 
                              FROM cards WHERE pagina_id = ? ORDER BY sort_order");
        $stmt->execute([$paginaId]);
        $cards = $stmt->fetchAll();

        // Busca as conexões
        $stmt = $db->prepare("SELECT from_index, from_pos, to_index, to_pos 
                              FROM connections WHERE pagina_id = ?");
        $stmt->execute([$paginaId]);
        $connections = $stmt->fetchAll();

        // Mapeia os cards para o formato esperado pelo frontend
        $cardsMapped = [];
        foreach ($cards as $c) {
            $cardsMapped[] = [
                'html'      => $c['html'],
                'left'      => $c['pos_left'],
                'top'       => $c['pos_top'],
                'width'     => $c['width'],
                'height'    => $c['height'],
                'className' => $c['class_name'],
                'state'     => $c['state_json'],
                'cardId'    => $c['card_id']   // <-- NOVO
            ];
        }

        // Mapeia as connections (já estão no formato correto)
        $connectionsMapped = [];
        foreach ($connections as $conn) {
            $connectionsMapped[] = [
                'fromIndex' => (int)$conn['from_index'],
                'fromPos'   => $conn['from_pos'],
                'toIndex'   => (int)$conn['to_index'],
                'toPos'     => $conn['to_pos']
            ];
        }

        $paginaCompleta = [
            'id'          => $paginaData['id'],
            'public_id'   => $paginaData['public_id'],
            'is_public'   => (int)$paginaData['is_public'],
            'titulo'      => $paginaData['titulo'],
            'cards'       => $cardsMapped,
            'connections' => $connectionsMapped
        ];

        echo json_encode(['success' => true, 'pagina' => $paginaCompleta]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao exportar: ' . $e->getMessage()]);
        error_log('Erro export: ' . $e->getMessage());
    }
    exit;
}
// ============================================================
// 9. ROTA PROTEGIDA: IMPORTAR PÁGINA (POST – com CSRF)
// ============================================================
if ($action === 'import') {
    verifyCsrf();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit;
    }

    $materiaNome = trim($input['materia'] ?? '');
    $topicoNome  = trim($input['topico'] ?? '');
    $paginaData  = $input['pagina'] ?? [];

    if (!$materiaNome || !$topicoNome || empty($paginaData) || !is_array($paginaData)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Campos obrigatórios: materia, topico e pagina']);
        exit;
    }

    // Sanitiza os nomes
    $materiaNome = substr($materiaNome, 0, 100);
    $topicoNome  = substr($topicoNome, 0, 100);

    try {
        $db->beginTransaction();

        // --- 1. Verifica ou cria a matéria ---
        $stmt = $db->prepare("SELECT id FROM materias WHERE usuario_id = ? AND nome = ?");
        $stmt->execute([$usuario_id, $materiaNome]);
        $materia = $stmt->fetch();
        if (!$materia) {
            $stmt = $db->prepare("INSERT INTO materias (usuario_id, nome) VALUES (?, ?)");
            $stmt->execute([$usuario_id, $materiaNome]);
            $materiaId = (int)$db->lastInsertId();
        } else {
            $materiaId = (int)$materia['id'];
        }

        // --- 2. Verifica ou cria o tópico ---
        $stmt = $db->prepare("SELECT id FROM topicos WHERE materia_id = ? AND nome = ?");
        $stmt->execute([$materiaId, $topicoNome]);
        $topico = $stmt->fetch();
        if (!$topico) {
            $stmt = $db->prepare("INSERT INTO topicos (materia_id, nome) VALUES (?, ?)");
            $stmt->execute([$materiaId, $topicoNome]);
            $topicoId = (int)$db->lastInsertId();
        } else {
            $topicoId = (int)$topico['id'];
        }

        // --- 3. Determina a ordem da nova página ---
        $stmt = $db->prepare("SELECT COUNT(*) FROM paginas WHERE topico_id = ?");
        $stmt->execute([$topicoId]);
        $ordem = (int)$stmt->fetchColumn();

        // --- 4. Gera novo public_id (importação sempre gera um novo identificador público) ---
        $public_id = bin2hex(random_bytes(16));

        // Título da página (vem do import, senão usa padrão)
        $titulo = trim($paginaData['titulo'] ?? "Página " . ($ordem + 1));
        if (strlen($titulo) > 200) $titulo = substr($titulo, 0, 200);

        // Insere a página
        $stmt = $db->prepare("INSERT INTO paginas (topico_id, titulo, ordem, public_id, is_public) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$topicoId, $titulo, $ordem, $public_id, 0]); // is_public = 0 por padrão
        $paginaId = (int)$db->lastInsertId();

        // --- 5. Insere os cards ---
        // --- 5. Insere os cards ---
        $cards = $paginaData['cards'] ?? [];
        if (!is_array($cards)) $cards = [];

        $sortOrder = 0;
        foreach ($cards as $card) {
            if (!is_array($card)) continue;

            $html = sanitizeHtml($card['html'] ?? '');
            $pos_left = trim($card['left'] ?? '20px');
            $pos_top  = trim($card['top'] ?? '20px');
            $width    = trim($card['width'] ?? 'auto');
            $height   = trim($card['height'] ?? 'auto');
            $class_name = trim($card['className'] ?? 'editable-item');
            $state_json = $card['state'] ?? null;
            $card_id   = trim($card['cardId'] ?? '');   // <-- NOVO
            if (strlen($card_id) > 50) $card_id = substr($card_id, 0, 50);

            if (!isValidCssValue($pos_left)) $pos_left = '20px';
            if (!isValidCssValue($pos_top)) $pos_top = '20px';
            if (!isValidCssValue($width)) $width = 'auto';
            if (!isValidCssValue($height)) $height = 'auto';
            if (strlen($class_name) > 100) $class_name = substr($class_name, 0, 100);

            $state_json = validateJson($state_json);

            $stmt = $db->prepare("INSERT INTO cards 
        (pagina_id, html, pos_left, pos_top, width, height, class_name, sort_order, state_json, card_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $paginaId,
                $html,
                $pos_left,
                $pos_top,
                $width,
                $height,
                $class_name,
                $sortOrder++,
                $state_json,
                $card_id   // <-- NOVO
            ]);
        }

        // --- 6. Insere as connections ---
        $connections = $paginaData['connections'] ?? [];
        if (!is_array($connections)) $connections = [];

        foreach ($connections as $conn) {
            if (!is_array($conn)) continue;
            $fromIndex = isset($conn['fromIndex']) ? (int)$conn['fromIndex'] : 0;
            $fromPos   = trim($conn['fromPos'] ?? 'bottom');
            $toIndex   = isset($conn['toIndex']) ? (int)$conn['toIndex'] : 0;
            $toPos     = trim($conn['toPos'] ?? 'top');

            $validPos = ['top', 'bottom', 'left', 'right'];
            if (!in_array($fromPos, $validPos)) $fromPos = 'bottom';
            if (!in_array($toPos, $validPos)) $toPos = 'top';

            $stmt = $db->prepare("INSERT INTO connections 
                (pagina_id, from_index, from_pos, to_index, to_pos)
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$paginaId, $fromIndex, $fromPos, $toIndex, $toPos]);
        }

        // --- 7. Atualiza o estado atual do usuário para apontar para a nova página ---
        $current = [
            'materia' => $materiaNome,
            'topico'  => $topicoNome,
            'pagina'  => $ordem  // a ordem da página recém-criada
        ];
        $jsonCurrent = json_encode($current);
        if ($jsonCurrent === false) $jsonCurrent = '{}';

        $stmt = $db->prepare("UPDATE usuarios SET dados_atual = ? WHERE id = ?");
        $stmt->execute([$jsonCurrent, $usuario_id]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Página importada com sucesso!',
            'pagina' => [
                'id' => $paginaId,
                'public_id' => $public_id,
                'ordem' => $ordem,
                'titulo' => $titulo
            ]
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao importar página: ' . $e->getMessage()]);
        error_log('Erro import: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    }
    exit;
}


// ============================================================
// 10. ROTA PÚBLICA: VISUALIZAR PÁGINA PÚBLICA (sem autenticação)
// ============================================================
if ($action === 'view_public_page') {
    // 🔥 AGORA USA public_id EM VEZ DE id
    $public_id = $_GET['public_id'] ?? '';
    if (empty($public_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Identificador público não informado']);
        exit;
    }

    try {
        $stmt = $db->prepare("
            SELECT p.*, u.nome as autor, 
                   m.nome as materia_nome, t.nome as topico_nome,
                   (SELECT COUNT(*) FROM paginas WHERE original_author_id = p.original_author_id AND id != p.id) as total_forks
            FROM paginas p
            JOIN topicos t ON p.topico_id = t.id
            JOIN materias m ON t.materia_id = m.id
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE p.public_id = ? AND p.is_public = 1
        ");
        $stmt->execute([$public_id]);
        $pagina = $stmt->fetch();

        if (!$pagina) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Página não encontrada ou não é pública']);
            exit;
        }

        // Busca cards
        $stmt = $db->prepare("SELECT * FROM cards WHERE pagina_id = ? ORDER BY sort_order");
        $stmt->execute([$pagina['id']]);
        $cards = $stmt->fetchAll();

        // Busca connections
        $stmt = $db->prepare("SELECT * FROM connections WHERE pagina_id = ?");
        $stmt->execute([$pagina['id']]);
        $connections = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'pagina' => [
                'id' => $pagina['id'],
                'public_id' => $pagina['public_id'],
                'titulo' => $pagina['titulo'],
                'autor' => $pagina['autor'],
                'materia' => $pagina['materia_nome'],
                'topico' => $pagina['topico_nome'],
                'fork_count' => $pagina['fork_count'] ?? 0,
                'total_forks' => $pagina['total_forks'] ?? 0,
                'cards' => $cards,
                'connections' => $connections
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao carregar página pública']);
        error_log('Erro view_public_page: ' . $e->getMessage());
    }
    exit;
}

// ============================================================
// 11. ROTA PROTEGIDA: FORK DE PÁGINA PÚBLICA (somente logado)
// ============================================================
// (mantido igual – pode usar id ou public_id, mas o fork usa id)
if ($action === 'fork_page') {
    // ... seu código existente ...
}

// ============================================================
// 12. ROTA PROTEGIDA: ALTERAR VISIBILIDADE PÚBLICA (somente autor)
// ============================================================
if ($action === 'toggle_public') {
    verifyCsrf();

    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Não autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $public_id = trim($input['public_id'] ?? '');
    $isPublic = isset($input['is_public']) ? (int)$input['is_public'] : 0;

    if (empty($public_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Identificador público inválido']);
        exit;
    }

    try {
        // Verifica se a página pertence ao usuário usando public_id
        $stmt = $db->prepare("
            SELECT p.id FROM paginas p
            JOIN topicos t ON p.topico_id = t.id
            JOIN materias m ON t.materia_id = m.id
            WHERE p.public_id = ? AND m.usuario_id = ?
        ");
        $stmt->execute([$public_id, $usuario_id]);
        $pagina = $stmt->fetch();
        if (!$pagina) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Você não é o autor desta página']);
            exit;
        }

        $paginaId = $pagina['id'];

        $stmt = $db->prepare("UPDATE paginas SET is_public = ? WHERE id = ?");
        $stmt->execute([$isPublic, $paginaId]);

        echo json_encode([
            'success' => true,
            'message' => $isPublic ? 'Página agora é pública!' : 'Página agora é privada',
            'public_id' => $public_id
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao alterar visibilidade']);
        error_log('Erro toggle_public: ' . $e->getMessage());
    }
    exit;
}

// ============================================================
// 13. AÇÃO DESCONHECIDA
// ============================================================
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Ação inválida']);
