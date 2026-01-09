<?php
// download.php
require_once 'includes/session.php';
require_once 'config/database.php';

if (!isset($_GET['file'])) {
    die("No file specified.");
}

$file_path = $_GET['file'];

// Security check: Prevent directory traversal
if (strpos($file_path, '..') !== false) {
    die("Invalid file path.");
}

// Security Check: Only allow downloads from assets/uploads
if (strpos($file_path, 'assets/uploads/') !== 0) {
    die("Access denied.");
}

$full_path = __DIR__ . '/' . $file_path;

if (file_exists($full_path)) {
    // Get file extension/mime type
    $ext = pathinfo($full_path, PATHINFO_EXTENSION);
    $filename = basename($full_path);

    // Define headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($full_path));

    // Clear output buffer
    flush();
    readfile($full_path);
    exit;
} else {
    die("File not found: " . htmlspecialchars($full_path));
}
?>