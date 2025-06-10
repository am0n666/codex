<?php
session_start();

if (!isset($_POST['file'])) {
    header('Location: index.php');
    exit;
}

$filename = basename($_POST['file']);
$filepath = __DIR__ . '/output/' . $filename;

if (!file_exists($filepath)) {
    $_SESSION['error'] = 'Plik nie istnieje';
    header('Location: index.php');
    exit;
}

$fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$contentTypes = [
    'epub' => 'application/epub+zip',
    'mobi' => 'application/x-mobipocket-ebook',
    'pdf' => 'application/pdf',
    'azw3' => 'application/vnd.amazon.ebook',
    'fb2' => 'application/x-fictionbook+xml'
];

$contentType = $contentTypes[$fileExt] ?? 'application/octet-stream';

header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($filepath);

@unlink($filepath);
exit;
?>