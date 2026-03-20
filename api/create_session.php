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

    $fileMetadata = handle_upload($_FILES['presentation_file']);
    $manager = new SessionManager();
    $session = $manager->create($fileMetadata);

    app_logger()->info('Upload stored', ['file' => $fileMetadata['stored_name'], 'session_id' => $session['session_id']]);

    json_response([
        'success' => true,
        'message' => 'Session created successfully.',
        'session' => [
            'id' => $session['session_id'],
            'join_code' => $session['join_code'],
            'presenter_url' => get_base_url() . '/presenter.php?session=' . urlencode($session['session_id']),
            'viewer_url' => get_base_url() . '/viewer.php?code=' . urlencode($session['join_code']),
        ],
    ]);
} catch (Throwable $exception) {
    app_logger()->error('Session creation failed', ['error' => $exception->getMessage()]);
    json_response(['success' => false, 'message' => $exception->getMessage()], 400);
}
