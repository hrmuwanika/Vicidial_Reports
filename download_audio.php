<?php
// download_audio.php

if (isset($_GET['file'])) {
    $relative_path = $_GET['file'];
    $base_dir = '/var/spool/asterisk/monitorDONE/MP3/';
    $safe_path = realpath($base_dir . $relative_path);

    if ($safe_path === false || strpos($safe_path, $base_dir) !== 0) {
        header("HTTP/1.0 403 Forbidden");
        echo "Access denied.";
        exit;
    }

    if (file_exists($safe_path) && is_readable($safe_path)) {
        header('Content-Type: audio/mpeg');
        header('Content-Disposition: attachment; filename="' . basename($safe_path) . '"');
        header('Content-Length: ' . filesize($safe_path));
        header('Cache-Control: public');
        header('Pragma: public');
        ob_clean();
        flush();
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
