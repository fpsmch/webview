<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session_manager.php';
ensure_directories();
cleanup_expired_sessions();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
    }

    if (!isset($_FILES['presentation_file'])) {
        throw new RuntimeException('No file was provided.');
    }

    $details = [
        'title' => sanitize_string($_POST['session_title'] ?? '', MAX_TITLE_LENGTH),
        'organization' => sanitize_string($_POST['organization'] ?? '', MAX_ORGANIZATION_LENGTH),
        'presenter_name' => sanitize_string($_POST['presenter_name'] ?? '', MAX_PRESENTER_LENGTH),
        'audience_note' => sanitize_string($_POST['audience_note'] ?? '', 220),
        'viewer_password' => (string)($_POST['viewer_password'] ?? ''),
    ];

    if ($details['title'] === '') {
        throw new RuntimeException('Session title is required.');
    }

    $fileMetadata = handle_upload($_FILES['presentation_file']);
    $manager = new SessionManager();
    $session = $manager->create($fileMetadata, $details);

    app_logger()->info('Upload stored', ['file' => $fileMetadata['stored_name'], 'session_id' => $session['session_id']]);

    json_response([
        'success' => true,
        'message' => 'Session created successfully.',
        'session' => [
            'id' => $session['session_id'],
            'join_code' => $session['join_code'],
            'presenter_url' => presenter_url($session['session_id'], $session['security']['presenter_token']),
            'viewer_url' => viewer_url($session['join_code']),
            'password_required' => (bool)$session['security']['password_required'],
            'title' => $session['details']['title'],
        ],
    ]);
} catch (Throwable $exception) {
    app_logger()->error('Session creation failed', ['error' => $exception->getMessage()]);
    json_response(['success' => false, 'message' => $exception->getMessage()], 400);
}
