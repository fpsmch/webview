<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session_manager.php';
ensure_directories();
cleanup_expired_sessions();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$joinCode = sanitize_string($_GET['code'] ?? '');
if ($joinCode === '') {
    json_response(['success' => false, 'message' => 'Join code is required.'], 422);
}

$manager = new SessionManager();
$session = $manager->getByJoinCode($joinCode);
if ($session === null) {
    app_logger()->error('Join failed', ['join_code' => $joinCode]);
    json_response(['success' => false, 'message' => 'Session not found.'], 404);
}

$manager->markJoin((string)$session['session_id']);
json_response(['success' => true, 'session' => $session]);
