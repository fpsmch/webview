<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session_manager.php';
ensure_directories();
cleanup_expired_sessions();

$manager = new SessionManager();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sessionId = sanitize_string($_GET['session'] ?? '');
    $session = $manager->getBySessionId($sessionId);

    if ($session === null) {
        json_response(['success' => false, 'message' => 'Session not found.'], 404);
    }

    json_response(['success' => true, 'session' => $session]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
    if (!is_array($payload)) {
        json_response(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
    }

    $sessionId = sanitize_string((string)($payload['session_id'] ?? ''));
    if ($sessionId === '') {
        json_response(['success' => false, 'message' => 'Session ID is required.'], 422);
    }

    $session = $manager->updateState($sessionId, $payload['state'] ?? []);
    if ($session === null) {
        app_logger()->error('State sync failed', ['session_id' => $sessionId]);
        json_response(['success' => false, 'message' => 'Session not found.'], 404);
    }

    json_response(['success' => true, 'session' => $session]);
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
