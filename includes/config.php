<?php

declare(strict_types=1);

const APP_NAME = 'WebView Executive Presenter';
const BASE_PATH = __DIR__ . '/..';
const UPLOADS_PATH = BASE_PATH . '/uploads';
const SESSIONS_PATH = BASE_PATH . '/sessions';
const LOGS_PATH = BASE_PATH . '/logs';
const APP_LOG_FILE = LOGS_PATH . '/app.log';
const MAX_UPLOAD_SIZE = 52428800; // 50 MB
const SESSION_TTL = 86400; // 24 hours
const POLL_INTERVAL_MS = 1500;
const MAX_TITLE_LENGTH = 120;
const MAX_ORGANIZATION_LENGTH = 120;
const MAX_PRESENTER_LENGTH = 80;

const ALLOWED_EXTENSIONS = [
    'pdf',
    'ppt',
    'pptx',
    'mp4',
    'webm',
    'mp3',
    'wav',
    'ogg',
    'jpg',
    'jpeg',
    'png',
    'gif',
    'txt',
];

const ALLOWED_MIME_TYPES = [
    'application/pdf',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'video/mp4',
    'video/webm',
    'audio/mpeg',
    'audio/wav',
    'audio/ogg',
    'image/jpeg',
    'image/png',
    'image/gif',
    'text/plain',
];
