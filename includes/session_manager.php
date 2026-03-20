<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

final class SessionManager
{
    public function create(array $fileMetadata): array
    {
        $sessionId = generate_session_id();
        $joinCode = generate_join_code();
        $session = [
            'session_id' => $sessionId,
            'join_code' => $joinCode,
            'created_at' => date('c'),
            'updated_at' => date('c'),
            'last_joined_at' => null,
            'file' => $fileMetadata,
            'state' => [
                'current_slide' => 1,
                'timestamp' => 0,
                'status' => 'stopped',
                'started_at' => null,
                'elapsed_seconds' => 0,
                'total_slides' => estimate_total_slides($fileMetadata),
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
        $joinCode = strtoupper(sanitize_string($joinCode));
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

    public function markJoin(string $sessionId): void
    {
        $session = $this->getBySessionId($sessionId);
        if ($session === null) {
            return;
        }

        $session['last_joined_at'] = date('c');
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
        $status = in_array($newState['status'] ?? '', ['playing', 'paused', 'stopped'], true) ? $newState['status'] : ($state['status'] ?? 'stopped');
        $currentSlide = max(1, (int)($newState['current_slide'] ?? $state['current_slide'] ?? 1));
        $elapsedSeconds = max(0, (int)($newState['elapsed_seconds'] ?? $state['elapsed_seconds'] ?? 0));
        $timestamp = max(0, (int)($newState['timestamp'] ?? time()));

        $session['state'] = array_merge($state, [
            'current_slide' => $currentSlide,
            'timestamp' => $timestamp,
            'status' => $status,
            'elapsed_seconds' => $elapsedSeconds,
            'started_at' => $status === 'playing' ? ($state['started_at'] ?? date('c')) : ($newState['started_at'] ?? $state['started_at'] ?? null),
        ]);

        $this->save($session);
        app_logger()->info('Session state updated', ['session_id' => $sessionId, 'state' => $session['state']]);
        return $session;
    }
}
