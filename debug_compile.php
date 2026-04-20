<?php
require_once 'functions.php';

$bookFolder = $_GET['book'] ?? '';
if (empty($bookFolder) || !is_dir(__DIR__ . '/data/' . $bookFolder)) {
    die("Папка книги не найдена: " . $bookFolder);
}

echo "<h2>Отладка компиляции книги: " . htmlspecialchars($bookFolder) . "</h2>";

// Проверяем структуру папок
$bookPath = __DIR__ . '/data/' . $bookFolder;
echo "<h3>Проверка структуры:</h3>";
echo "Путь к книге: " . $bookPath . "<br>";
echo "Существует: " . (is_dir($bookPath) ? 'Да' : 'Нет') . "<br>";

// Проверяем info.json
$infoFile = $bookPath . '/info.json';
echo "<h3>Проверка info.json:</h3>";
if (file_exists($infoFile)) {
    echo "Файл существует<br>";
    $info = json_decode(file_get_contents($infoFile), true);
    echo "Содержимое:<br>";
    echo "<pre>";
    print_r($info);
    echo "</pre>";
} else {
    echo "Файл НЕ существует!<br>";
}

// Проверяем авторов
echo "<h3>Проверка авторов:</h3>";
$authorsDir = $bookPath . '/authors';
if (is_dir($authorsDir)) {
    $authors = scandir($authorsDir);
    echo "Папка authors существует<br>";
    echo "Содержимое: <br>";
    foreach ($authors as $author) {
        if ($author != '.' && $author != '..') {
            echo " - " . $author . "<br>";
            
            // Проверяем тексты автора
            $authorPath = $authorsDir . '/' . $author;
            $texts = scandir($authorPath);
            foreach ($texts as $text) {
                if ($text != '.' && $text != '..') {
                    echo " &nbsp; &nbsp; 📄 " . $text . " (" . filesize($authorPath . '/' . $text) . " байт)<br>";
                }
            }
        }
    }
} else {
    echo "Папка authors НЕ существует!<br>";
}

// Проверяем обложку
echo "<h3>Проверка обложки:</h3>";
$coverFile = $bookPath . '/cover.jpg';
if (file_exists($coverFile)) {
    echo "Обложка существует, размер: " . filesize($coverFile) . " байт<br>";
} else {
    echo "Обложка отсутствует<br>";
}

// Пробуем сгенерировать FB2
echo "<h3>Генерация FB2:</h3>";
$fb2File = generateFB2($bookFolder);

if ($fb2File && file_exists($fb2File)) {
    echo "FB2 файл создан: " . $fb2File . "<br>";
    echo "Размер файла: " . filesize($fb2File) . " байт<br>";
    
    // Покажем первые 500 символов файла
    echo "<h4>Первые 500 символов файла:</h4>";
    echo "<pre>";
    $content = file_get_contents($fb2File);
    echo htmlspecialchars(substr($content, 0, 500));
    echo "...</pre>";
    
    // Проверим валидность
    echo "<h4>Проверка валидности:</h4>";
    $validation = validateFB2($fb2File);
    if ($validation === true) {
        echo "✅ Файл валидный<br>";
    } else {
        echo "❌ Ошибка: " . $validation . "<br>";
    }
} else {
    echo "❌ Ошибка генерации FB2!<br>";
}

echo "<br><br>";
echo '<a href="compile.php?book=' . urlencode($bookFolder) . '">Скачать FB2</a><br>';
echo '<a href="edit_book.php?book=' . urlencode($bookFolder) . '">Вернуться к редактированию</a>';
?>
