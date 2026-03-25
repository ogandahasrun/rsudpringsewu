<?php
/**
 * Script Download Report Log
 */

if (!isset($_GET['file'])) {
    header("HTTP/1.0 404 Not Found");
    die("File not found");
}

$fileName = basename($_GET['file']); // Keamanan: hanya basename
$filePath = __DIR__ . '/batch_rename_reports/' . $fileName;

// Validasi file
if (!file_exists($filePath) || !is_file($filePath)) {
    header("HTTP/1.0 404 Not Found");
    die("File not found");
}

// Security: hanya izinkan file dengan pattern tertentu
if (!preg_match('/^report_\d{8}_\d{6}\.txt$/', $fileName)) {
    header("HTTP/1.0 403 Forbidden");
    die("Invalid file");
}

// Download file
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));
header('Pragma: public');
header('Cache-Control: public, must-revalidate');
header('Expires: 0');

readfile($filePath);
exit;
?>
