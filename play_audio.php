<?php
// play_audio.php

if (isset($_GET['file'])) {
    $filepath = $_GET['file'];

    // Sanitize the filepath to prevent directory traversal attacks
    $base_dir = '/var/spool/asterisk/monitorDONE/MP3/';
    $safe_path = realpath($base_dir . $_GET['file']);

    // Check if the requested file is within the allowed directory
    if ($safe_path === false || strpos($safe_path, $base_dir) !== 0) {
        header("HTTP/1.0 403 Forbidden");
        echo "Access denied.";
        exit;
    }

    // Check if the file exists and is readable
    if (file_exists($safe_path) && is_readable($safe_path)) {
        // Set the appropriate Content-Type header
        header('Content-Type: audio/mpeg');
        header('Content-Disposition: inline; filename="' . basename($safe_path) . '"');
        header('Content-Length: ' . filesize($safe_path));
        header('Cache-Control: no-cache');
        session_cache_limiter('nocache');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

        // Output the file content
        readfile($safe_path);
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "Audio file not found.";
        exit;
    }
} else {
    header("HTTP/1.0 400 Bad Request");
    echo "Invalid request.";
    exit;
}
?>
