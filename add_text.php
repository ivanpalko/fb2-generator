<?php
require_once 'functions.php';

$bookFolder = $_GET['book'] ?? '';
$author = $_GET['author'] ?? '';
$editFile = $_GET['edit'] ?? '';

if (empty($bookFolder) || empty($author) || !is_dir(__DIR__ . '/data/' . $bookFolder)) {
    header('Location: index.php');
    exit;
}

$authorDir = __DIR__ . '/data/' . $bookFolder . '/authors/' . $author;
if (!is_dir($authorDir)) {
    mkdir($authorDir, 0777, true);
}

$content = '';
$title = '';
$is_poetry = false;

if ($editFile && file_exists($authorDir . '/' . $editFile)) {
    // Читаем содержимое файла
    $fileContent = file_get_contents($authorDir . '/' . $editFile);
    $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'Windows-1251', 'KOI8-R'], true);
    
    if ($encoding && $encoding != 'UTF-8') {
        $content = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
    } else {
        $content = $fileContent;
    }
    
    $title = pathinfo($editFile, PATHINFO_FILENAME);
    
    // Проверяем, есть ли файл с метаданными
    $metaFile = $authorDir . '/' . pathinfo($editFile, PATHINFO_FILENAME) . '.meta.json';
    if (file_exists($metaFile)) {
        $meta = json_decode(file_get_contents($metaFile), true);
        $is_poetry = $meta['is_poetry'] ?? false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $textTitle = trim($_POST['title']);
    $textContent = $_POST['content'];
    $isPoetry = isset($_POST['is_poetry']) ? true : false;
    
    if (empty($textTitle) || empty($textContent)) {
        $error = 'Заполните название и текст';
    } else {
        $filename = preg_replace('/[^a-zA-Zа-яА-Я0-9\s]/u', '', $textTitle);
        $filename = str_replace(' ', '_', $filename) . '.txt';
        
        if ($editFile && $editFile != $filename) {
            @unlink($authorDir . '/' . $editFile);
            // Удаляем и метафайл, если есть
            $oldMetaFile = $authorDir . '/' . pathinfo($editFile, PATHINFO_FILENAME) . '.meta.json';
            if (file_exists($oldMetaFile)) {
                @unlink($oldMetaFile);
            }
        }
        
        // Сохраняем текст
        file_put_contents($authorDir . '/' . $filename, $textContent);
        
        // Сохраняем метаданные
        $metaFile = $authorDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.meta.json';
        $meta = [
            'is_poetry' => $isPoetry,
            'title' => $textTitle
        ];
        file_put_contents($metaFile, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        header('Location: edit_book.php?book=' . urlencode($bookFolder));
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $editFile ? 'Редактирование' : 'Добавление'; ?> текста</title>
    <link rel="stylesheet" href="style.css">
    <meta charset="utf-8">
    <style>
        .poetry-note {
            background: #f3e5f5;
            border-left: 4px solid #9c27b0;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .prose-note {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $editFile ? 'Редактирование' : 'Добавление'; ?> текста</h1>
        <h2>Автор: <?php echo htmlspecialchars($author); ?></h2>
        
        <a href="edit_book.php?book=<?php echo urlencode($bookFolder); ?>" class="button small">← Назад к книге</a>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="<?php echo $is_poetry ? 'poetry-note' : 'prose-note'; ?>">
            <strong>
                <?php if ($is_poetry): ?>
                    📝 Режим стихотворения: строки будут отображаться с одинарным интервалом
                <?php else: ?>
                    📄 Режим прозы: абзацы будут разделены пустыми строками
                <?php endif; ?>
            </strong>
        </div>
        
        <form method="post" class="book-form">
            <div class="form-group">
                <label for="title">Название произведения:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="content">Текст:</label>
                <textarea id="content" name="content" rows="20" required><?php echo htmlspecialchars($content); ?></textarea>
                <small>
                    <?php if ($is_poetry): ?>
                        Для стихов: каждая строка с новой строки, пустая строка между строфами.
                    <?php else: ?>
                        Для прозы: используйте пустые строки для разделения абзацев.
                    <?php endif; ?>
                    Можно вставлять текст из Word. Кодировка определится автоматически.
                </small>
            </div>
            
            <!-- Чекбокс для стихов -->
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="is_poetry" value="1" <?php echo $is_poetry ? 'checked' : ''; ?>>
                    <span style="font-weight: normal;">📝 Это стихотворение (отключить двойные интервалы между строками)</span>
                </label>
                <small style="display: block; margin-top: 5px; color: #666;">
                    Отметьте эту опцию для стихов, песен и других текстов, где важна каждая строка.
                </small>
            </div>
            
            <button type="submit" class="button">Сохранить</button>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
            <h3>📌 Примеры форматирования:</h3>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <h4>Проза (чекбокс снят):</h4>
                    <pre style="background: #fff; padding: 10px; border-radius: 4px; font-size: 12px;">
Первый абзац текста.
Он может быть длинным.

Второй абзац текста.
Между абзацами пустая строка.

Третий абзац текста.
                    </pre>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <h4>Стихи (чекбокс отмечен):</h4>
                    <pre style="background: #fff; padding: 10px; border-radius: 4px; font-size: 12px;">
Строка первая стихотворения
Строка вторая стихотворения

Строка первая второй строфы
Строка вторая второй строфы
                    </pre>
                </div>
            </div>
        </div>
    </div>
</body>
</html>