<?php

define('ASSEMBLYAI_API_KEY', 'c4619c9266524495b6656f1bb8f0220a');
?>

<?php


// config.php (UPDATED)

// --- DB CONFIG ---
// Change these to match your phpMyAdmin / MySQL credentials:
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'podintellect');   // the DB you created
define('DB_USER', 'root');           // your MySQL user
define('DB_PASS', '');               // your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Site base URL (no trailing slash). Adjust if using subfolders or localhost:port
define('BASE_URL', 'http://localhost/PodIntellect');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Strong session defaults
// ini_set('session.cookie_httponly', 1);
// ini_set('session.use_strict_mode', 1);
// if (session_status() === PHP_SESSION_NONE) {
//   session_start();
// }




error_reporting(E_ALL);
ini_set('display_errors', 1);


define('APP_NAME', 'PodIntellect');
define('APP_VERSION', '1.0.0');
define('SUPPORTED_PLATFORMS', ['youtube']);


define('YOUTUBE_EMBED_URL', 'https://www.youtube.com/embed/');
define('YOUTUBE_WATCH_URL', 'https://www.youtube.com/watch?v=');


define('YOUTUBE_PATTERNS', [
    '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
    '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
    '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
    '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
    '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/'
]);


define('VIDEO_ID_LENGTH', 11);
define('MAX_URL_LENGTH', 2048);


define('ALLOWED_DOMAINS', [
    'youtube.com',
    'www.youtube.com',
    'youtu.be',
    'www.youtu.be'
]);


define('MESSAGES', [
    'INVALID_URL' => 'Please enter a valid URL.',
    'UNSUPPORTED_PLATFORM' => 'Currently, only YouTube URLs are supported. Please enter a YouTube URL.',
    'NO_VIDEO_ID' => 'Could not extract video ID from the YouTube URL. Please check the URL and try again.',
    'EMPTY_URL' => 'Please enter a podcast URL.',
    'SUCCESS' => 'Video loaded successfully'
]);


function getMessage($key) {
    return MESSAGES[$key] ?? 'An unknown error occurred.';
}
?> 