<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';

function app_logger(): Logger
{
    static $logger = null;

    if ($logger === null) {
        $logger = new Logger(APP_LOG_FILE);
    }

    return $logger;
}

function ensure_directories(): void
{
    foreach ([UPLOADS_PATH, SESSIONS_PATH, LOGS_PATH] as $path) {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function sanitize_string(string $value): string
{
    return trim(filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

function generate_session_id(): string
{
    return bin2hex(random_bytes(16));
}

function generate_join_code(): string
{
    return strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function session_file_path(string $sessionId): string
{
    $safeId = preg_replace('/[^a-f0-9]/', '', strtolower($sessionId));
    return SESSIONS_PATH . '/' . $safeId . '.json';
}

function session_url_path(string $filename): string
{
    return 'uploads/' . rawurlencode($filename);
}

function detect_file_category(string $extension): string
{
    return match (strtolower($extension)) {
        'pdf' => 'pdf',
        'ppt', 'pptx' => 'presentation',
        'mp4', 'webm' => 'video',
        'mp3', 'wav', 'ogg' => 'audio',
        'jpg', 'jpeg', 'png', 'gif' => 'image',
        default => 'document',
    };
}

function estimate_total_slides(array $metadata): int
{
    return match ($metadata['category'] ?? 'document') {
        'pdf' => 1,
        'presentation' => 1,
        'image' => 1,
        'video', 'audio' => 1,
        default => 1,
    };
}

function format_seconds(int $seconds): string
{
    $minutes = intdiv($seconds, 60);
    $remaining = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $remaining);
}

function read_json_file(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $contents = file_get_contents($path);
    if ($contents === false || $contents === '') {
        return [];
    }

    $data = json_decode($contents, true);
    return is_array($data) ? $data : [];
}

function write_json_file(string $path, array $data): bool
{
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
}

function cleanup_expired_sessions(): void
{
    $logger = app_logger();
    foreach (glob(SESSIONS_PATH . '/*.json') ?: [] as $file) {
        $session = read_json_file($file);
        $updatedAt = strtotime((string)($session['updated_at'] ?? '')) ?: filemtime($file);
        if ($updatedAt !== false && (time() - $updatedAt) > SESSION_TTL) {
            if (!empty($session['file']['stored_name'])) {
                $uploadFile = UPLOADS_PATH . '/' . basename($session['file']['stored_name']);
                if (is_file($uploadFile)) {
                    unlink($uploadFile);
                }
            }
            unlink($file);
            $logger->info('Expired session removed', ['session_id' => $session['session_id'] ?? basename($file, '.json')]);
        }
    }
}

function handle_upload(array $uploadedFile): array
{
    if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please try again.');
    }

    if (($uploadedFile['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        throw new RuntimeException('File exceeds the maximum size limit of 50 MB.');
    }

    $originalName = basename((string)($uploadedFile['name'] ?? ''));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
        throw new RuntimeException('Unsupported file type uploaded.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, (string)$uploadedFile['tmp_name']) : null;
    if ($finfo) {
        finfo_close($finfo);
    }

    if ($mimeType === false || $mimeType === null || !in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
        throw new RuntimeException('Uploaded file MIME type is not allowed.');
    }

    $storedName = sprintf('%s.%s', bin2hex(random_bytes(10)), $extension);
    $destination = UPLOADS_PATH . '/' . $storedName;

    if (!move_uploaded_file((string)$uploadedFile['tmp_name'], $destination)) {
        throw new RuntimeException('Unable to save uploaded file.');
    }

    return [
        'original_name' => $originalName,
        'stored_name' => $storedName,
        'mime_type' => $mimeType,
        'extension' => $extension,
        'size' => (int)$uploadedFile['size'],
        'category' => detect_file_category($extension),
        'url' => session_url_path($storedName),
    ];
}

function get_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

    if (str_ends_with($scriptDir, '/api')) {
        $scriptDir = substr($scriptDir, 0, -4);
    }

    return rtrim($scheme . '://' . $host . ($scriptDir !== '' ? $scriptDir : ''), '/');
}
