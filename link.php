<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$linksDir = __DIR__ . '/links/';
if (!is_dir($linksDir)) { mkdir($linksDir, 0755, true); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Kein Inhalt']);
        exit;
    }

    $token = null;
    for ($i = 0; $i < 20; $i++) {
        $t = substr(bin2hex(random_bytes(4)), 0, 6);
        if (!file_exists($linksDir . $t . '.json')) { $token = $t; break; }
    }
    if (!$token) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Token-Fehler']);
        exit;
    }

    $body['_created'] = time();
    file_put_contents($linksDir . $token . '.json', json_encode($body, JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true, 'token' => $token]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = preg_replace('/[^a-f0-9]/i', '', $_GET['get'] ?? '');
    if (strlen($token) < 4) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Kein Token']);
        exit;
    }

    $file = $linksDir . $token . '.json';
    if (!file_exists($file)) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Link nicht gefunden']);
        exit;
    }

    $data = json_decode(file_get_contents($file), true);
    if (isset($data['_created']) && (time() - $data['_created']) > 90 * 86400) {
        unlink($file);
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Link abgelaufen (90 Tage)']);
        exit;
    }

    unset($data['_created']);
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
}
?>
