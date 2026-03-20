<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session_manager.php';
ensure_directories();
cleanup_expired_sessions();

$manager = new SessionManager();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sessionId = sanitize_string($_GET['session'] ?? '', 64);
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

    $sessionId = sanitize_string((string)($payload['session_id'] ?? ''), 64);
    $presenterToken = sanitize_string((string)($payload['presenter_token'] ?? ''), 128);
    if ($sessionId === '' || $presenterToken === '') {
        json_response(['success' => false, 'message' => 'Session ID and presenter token are required.'], 422);
    }

    $existingSession = $manager->getBySessionId($sessionId);
    if ($existingSession === null || !$manager->presenterAuthorized($existingSession, $presenterToken)) {
        app_logger()->error('Unauthorized state sync attempt', ['session_id' => $sessionId]);
        json_response(['success' => false, 'message' => 'Presenter authorization failed.'], 403);
    }

    $session = $manager->updateState($sessionId, $payload['state'] ?? []);
    if ($session === null) {
        app_logger()->error('State sync failed', ['session_id' => $sessionId]);
        json_response(['success' => false, 'message' => 'Session not found.'], 404);
    }

    json_response(['success' => true, 'session' => $session]);
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
