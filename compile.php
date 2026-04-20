<?php
require_once 'functions.php';

$bookFolder = $_GET['book'] ?? '';
if (empty($bookFolder) || !is_dir(__DIR__ . '/data/' . $bookFolder)) {
    header('Location: index.php');
    exit;
}

$fb2File = generateFB2($bookFolder);

if ($fb2File && file_exists($fb2File)) {
    header('Content-Type: application/fb2');
    header('Content-Disposition: attachment; filename="' . basename($fb2File) . '"');
    header('Content-Length: ' . filesize($fb2File));
    readfile($fb2File);
    exit;
} else {
    header('Location: edit_book.php?book=' . urlencode($bookFolder) . '&error=compile');
    exit;
}
?>