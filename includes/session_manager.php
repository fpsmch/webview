<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

final class SessionManager
{
    public function create(array $fileMetadata, array $details): array
    {
        $sessionId = generate_session_id();
        $joinCode = generate_join_code();
        $presenterToken = generate_access_token();

        $session = [
            'session_id' => $sessionId,
            'join_code' => $joinCode,
            'created_at' => date('c'),
            'updated_at' => date('c'),
            'last_joined_at' => null,
            'details' => [
                'title' => sanitize_string((string)($details['title'] ?? $fileMetadata['original_name']), MAX_TITLE_LENGTH),
                'organization' => sanitize_string((string)($details['organization'] ?? ''), MAX_ORGANIZATION_LENGTH),
                'presenter_name' => sanitize_string((string)($details['presenter_name'] ?? ''), MAX_PRESENTER_LENGTH),
                'audience_note' => sanitize_string((string)($details['audience_note'] ?? ''), 220),
                'created_label' => date('M j, Y g:i A'),
            ],
            'security' => [
                'viewer_password_hash' => hash_optional_password((string)($details['viewer_password'] ?? '')),
                'presenter_token' => $presenterToken,
                'password_required' => trim((string)($details['viewer_password'] ?? '')) !== '',
            ],
            'analytics' => [
                'viewer_count' => 0,
                'last_event' => 'Session created',
            ],
            'file' => $fileMetadata,
            'state' => [
                'current_slide' => 1,
                'timestamp' => time(),
                'status' => 'ready',
                'started_at' => null,
                'elapsed_seconds' => 0,
                'total_slides' => estimate_total_slides($fileMetadata),
                'highlight_message' => 'Waiting to begin',
                'focus_mode' => false,
            ],
        ];

        write_json_file(session_file_path($sessionId), $session);
        app_logger()->info('Session created', ['session_id' => $sessionId, 'join_code' => $joinCode]);
        return $session;
    }

    public function getBySessionId(string $sessionId): ?array
    {
        $session = read_json_file(session_file_path($sessionId));
        return $session !== [] ? $session : null;
    }

    public function getByJoinCode(string $joinCode): ?array
    {
        $joinCode = strtoupper(sanitize_string($joinCode, 6));
        foreach (glob(SESSIONS_PATH . '/*.json') ?: [] as $file) {
            $session = read_json_file($file);
            if (($session['join_code'] ?? '') === $joinCode) {
                return $session;
            }
        }

        return null;
    }

    public function save(array $session): bool
    {
        $session['updated_at'] = date('c');
        return write_json_file(session_file_path((string)$session['session_id']), $session);
    }

    public function presenterAuthorized(array $session, string $token): bool
    {
        return hash_equals((string)($session['security']['presenter_token'] ?? ''), $token);
    }

    public function viewerAuthorized(array $session, string $password): bool
    {
        return verify_optional_password($session['security']['viewer_password_hash'] ?? null, $password);
    }

    public function markJoin(string $sessionId): void
    {
        $session = $this->getBySessionId($sessionId);
        if ($session === null) {
            return;
        }

        $session['last_joined_at'] = date('c');
        $session['analytics']['viewer_count'] = (int)($session['analytics']['viewer_count'] ?? 0) + 1;
        $session['analytics']['last_event'] = 'Viewer joined';
        $this->save($session);
        app_logger()->info('Viewer joined session', ['session_id' => $sessionId]);
    }

    public function updateState(string $sessionId, array $newState): ?array
    {
        $session = $this->getBySessionId($sessionId);
        if ($session === null) {
            return null;
        }

        $state = $session['state'] ?? [];
        $validStatuses = ['ready', 'playing', 'paused', 'stopped'];
        $status = in_array($newState['status'] ?? '', $validStatuses, true)
            ? $newState['status']
            : ($state['status'] ?? 'ready');

        $currentSlide = max(1, (int)($newState['current_slide'] ?? $state['current_slide'] ?? 1));
        $elapsedSeconds = max(0, (int)($newState['elapsed_seconds'] ?? $state['elapsed_seconds'] ?? 0));
        $timestamp = max(0, (int)($newState['timestamp'] ?? time()));
        $focusMode = (bool)($newState['focus_mode'] ?? $state['focus_mode'] ?? false);
        $message = sanitize_string((string)($newState['highlight_message'] ?? $state['highlight_message'] ?? ''), 120);

        $session['state'] = array_merge($state, [
            'current_slide' => $currentSlide,
            'timestamp' => $timestamp,
            'status' => $status,
            'elapsed_seconds' => $elapsedSeconds,
            'started_at' => $status === 'playing' ? ($state['started_at'] ?? date('c')) : ($newState['started_at'] ?? $state['started_at'] ?? null),
            'highlight_message' => $message !== '' ? $message : 'Live update',
            'focus_mode' => $focusMode,
        ]);

        $session['analytics']['last_event'] = 'Presenter updated session';
        $this->save($session);
        app_logger()->info('Session state updated', ['session_id' => $sessionId, 'state' => $session['state']]);
        return $session;
    }
}
