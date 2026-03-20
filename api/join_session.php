<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session_manager.php';
ensure_directories();
cleanup_expired_sessions();

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$joinCode = strtoupper(sanitize_string($_REQUEST['code'] ?? '', 6));
$password = (string)($_REQUEST['viewer_password'] ?? '');
if ($joinCode === '') {
    json_response(['success' => false, 'message' => 'Join code is required.'], 422);
}

$manager = new SessionManager();
$session = $manager->getByJoinCode($joinCode);
if ($session === null) {
    app_logger()->error('Join failed', ['join_code' => $joinCode]);
    json_response(['success' => false, 'message' => 'Session not found.'], 404);
}

if (requires_session_password($session) && !$manager->viewerAuthorized($session, $password)) {
    app_logger()->error('Join blocked by password', ['join_code' => $joinCode]);
    json_response(['success' => false, 'message' => 'Password required or incorrect.'], 403);
}

$manager->markJoin((string)$session['session_id']);
json_response(['success' => true, 'session' => $session]);
