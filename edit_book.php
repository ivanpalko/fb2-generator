<?php
require_once 'functions.php';

$bookFolder = $_GET['book'] ?? '';
if (empty($bookFolder) || !is_dir(__DIR__ . '/data/' . $bookFolder)) {
    header('Location: index.php');
    exit;
}

$bookPath = __DIR__ . '/data/' . $bookFolder;
$infoFile = $bookPath . '/info.json';
$info = json_decode(file_get_contents($infoFile), true);

// Обработка удаления текста
if (isset($_GET['delete']) && isset($_GET['author'])) {
    $author = $_GET['author'];
    $file = $_GET['delete'];
    $filePath = $bookPath . '/authors/' . $author . '/' . $file;
    if (file_exists($filePath)) {
        unlink($filePath);
        // Удаляем метафайл, если есть
        $metaFile = $bookPath . '/authors/' . $author . '/' . pathinfo($file, PATHINFO_FILENAME) . '.meta.json';
        if (file_exists($metaFile)) {
            unlink($metaFile);
        }
    }
    header('Location: edit_book.php?book=' . urlencode($bookFolder));
    exit;
}

// Обработка удаления обложки
if (isset($_GET['delete_cover'])) {
    $coverPath = $bookPath . '/cover.jpg';
    if (file_exists($coverPath)) {
        unlink($coverPath);
    }
    header('Location: edit_book.php?book=' . urlencode($bookFolder));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_info'])) {
        $info['title'] = trim($_POST['title']);
        $info['subtitle'] = trim($_POST['subtitle']);
        $info['description'] = trim($_POST['description']);
        $info['tags'] = trim($_POST['tags']);
        file_put_contents($infoFile, json_encode($info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $message = 'Информация обновлена';
    }
    
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] == UPLOAD_ERR_OK) {
        $uploadFile = $bookPath . '/cover.jpg';
        if (move_uploaded_file($_FILES['cover']['tmp_name'], $uploadFile)) {
            resizeImage($uploadFile, $uploadFile, 600, 800);
            $message = 'Обложка загружена';
        }
    }
}

$authors = getAuthorsList($bookFolder);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Редактирование книги - <?php echo htmlspecialchars($info['title']); ?></title>
    <link rel="stylesheet" href="style.css">
    <meta charset="utf-8">
    <style>
        .poetry-badge {
            display: inline-block;
            background: #9c27b0;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 8px;
            vertical-align: middle;
        }
        .prose-badge {
            display: inline-block;
            background: #2196f3;
            color: white;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            margin-left: 8px;
            vertical-align: middle;
        }
        .preview-box {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        .toggle-preview {
            cursor: pointer;
            color: #007bff;
            text-decoration: underline;
            margin: 5px 0;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Редактирование книги: <?php echo htmlspecialchars($info['title']); ?></h1>
        
        <a href="index.php" class="button small">← Назад к списку</a>
        
        <?php if (isset($message)): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error']) && $_GET['error'] == 'compile'): ?>
            <div class="error">Ошибка при компиляции FB2. Проверьте наличие текстов у авторов.</div>
        <?php endif; ?>
        
        <div class="book-sections">
            <div class="section">
                <h2>Информация о книге</h2>
                <form method="post" class="book-form">
                    <div class="form-group">
                        <label for="title">Название:</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($info['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subtitle">Подзаголовок (например, "Сборник рассказов"):</label>
                        <input type="text" id="subtitle" name="subtitle" value="<?php echo htmlspecialchars($info['subtitle'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Описание книги (аннотация):</label>
                        <textarea id="description" name="description" rows="5" placeholder="Краткое описание сборника..."><?php echo htmlspecialchars($info['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="tags">Теги:</label>
                        <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($info['tags']); ?>">
                    </div>
                    
                    <button type="submit" name="update_info" class="button small">Обновить</button>
                </form>
            </div>
            
            <div class="section">
                <h2>Обложка</h2>
                <?php if (file_exists($bookPath . '/cover.jpg')): ?>
                    <div class="cover-preview">
                        <img src="data/<?php echo $bookFolder; ?>/cover.jpg?<?php echo time(); ?>" alt="Обложка" style="max-width: 200px;">
                        <p><a href="?book=<?php echo urlencode($bookFolder); ?>&delete_cover=1" class="button small danger" onclick="return confirm('Удалить обложку?')">Удалить</a></p>
                    </div>
                <?php endif; ?>
                
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="cover">Загрузить обложку (JPG, PNG):</label>
                        <input type="file" id="cover" name="cover" accept="image/jpeg,image/png">
                    </div>
                    <button type="submit" class="button small">Загрузить</button>
                </form>
            </div>
            
            <div class="section">
                <h2>Авторы</h2>
                <div class="authors-list">
                    <?php foreach ($info['authors'] as $author): ?>
                        <div class="author-item">
                            <h3><?php echo htmlspecialchars($author); ?></h3>
                            <a href="add_text.php?book=<?php echo urlencode($bookFolder); ?>&author=<?php echo urlencode($author); ?>" class="button small">Добавить текст</a>
                            
                            <?php
                            $texts = getAuthorTexts($bookFolder, $author);
                            if (!empty($texts)):
                            ?>
                                <ul class="texts-list">
                                    <?php foreach ($texts as $text): ?>
                                        <li style="display: flex; align-items: center; flex-wrap: wrap;">
                                            <span style="margin-right: 5px;">
                                                <?php if ($text['is_poetry'] ?? false): ?>
                                                    <span style="color: #9c27b0;" title="Стихотворение">📝</span>
                                                <?php else: ?>
                                                    <span style="color: #2196f3;" title="Проза">📄</span>
                                                <?php endif; ?>
                                            </span>
                                            <span style="flex-grow: 1;">
                                                <?php echo htmlspecialchars($text['title']); ?>
                                                <?php if ($text['is_poetry'] ?? false): ?>
                                                    <span class="poetry-badge">стихи</span>
                                                <?php else: ?>
                                                    <span class="prose-badge">проза</span>
                                                <?php endif; ?>
                                            </span>
                                            <span style="white-space: nowrap;">
                                                <a href="add_text.php?book=<?php echo urlencode($bookFolder); ?>&author=<?php echo urlencode($author); ?>&edit=<?php echo urlencode($text['filename']); ?>" title="Редактировать">✏️</a>
                                                <a href="?book=<?php echo urlencode($bookFolder); ?>&author=<?php echo urlencode($author); ?>&delete=<?php echo urlencode($text['filename']); ?>" onclick="return confirm('Удалить это произведение?')" title="Удалить">🗑️</a>
                                                <span class="toggle-preview" onclick="togglePreview('preview-<?php echo md5($text['filename']); ?>')">👁️</span>
                                            </span>
                                            <div id="preview-<?php echo md5($text['filename']); ?>" class="preview-box" style="display: none; width: 100%; margin-top: 10px;">
                                                <?php 
                                                $previewText = mb_substr($text['content'], 0, 300);
                                                if (mb_strlen($text['content']) > 300) {
                                                    $previewText .= '...';
                                                }
                                                echo htmlspecialchars($previewText);
                                                ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p style="color: #999; margin-top: 10px;">Нет текстов. Нажмите "Добавить текст"</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="section">
                <h2>Действия</h2>
                <a href="view_book.php?book=<?php echo urlencode($bookFolder); ?>" class="button" target="_blank">👁️ Просмотреть книгу</a>
                <a href="compile.php?book=<?php echo urlencode($bookFolder); ?>" class="button">📚 Скомпилировать FB2</a>
            </div>
        </div>
    </div>
    
    <script>
    function togglePreview(id) {
        var preview = document.getElementById(id);
        if (preview.style.display === 'none') {
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }
    </script>
</body>
</html>