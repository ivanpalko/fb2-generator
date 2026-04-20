<?php
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $description = trim($_POST['description']);
    $tags = trim($_POST['tags']);
    $authors = array_filter(array_map('trim', explode("\n", $_POST['authors'])));
    
    if (empty($title)) {
        $error = 'Введите название книги';
    } elseif (empty($authors)) {
        $error = 'Добавьте хотя бы одного автора';
    } else {
        $folderName = preg_replace('/[^a-zA-Zа-яА-Я0-9\s]/u', '', $title);
        $folderName = str_replace(' ', '_', $folderName);
        $bookPath = __DIR__ . '/data/' . $folderName;
        
        if (!is_dir($bookPath)) {
            mkdir($bookPath . '/authors', 0777, true);
            
            // Create info.json
            $info = [
                'title' => $title,
                'subtitle' => $subtitle,
                'description' => $description,
                'tags' => $tags,
                'authors' => $authors,
                'created' => date('Y-m-d H:i:s')
            ];
            
            file_put_contents($bookPath . '/info.json', json_encode($info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            
            // Create author folders
            foreach ($authors as $author) {
                $authorFolder = preg_replace('/[^a-zA-Zа-яА-Я0-9\s]/u', '', $author);
                $authorFolder = str_replace(' ', '_', $authorFolder);
                mkdir($bookPath . '/authors/' . $authorFolder, 0777, true);
            }
            
            header('Location: edit_book.php?book=' . urlencode($folderName));
            exit;
        } else {
            $error = 'Книга с таким названием уже существует';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Создание книги - FB2 Генератор</title>
    <link rel="stylesheet" href="style.css">
    <meta charset="utf-8">
</head>
<body>
    <div class="container">
        <h1>Создание новой книги</h1>
        
        <a href="index.php" class="button small">← Назад к списку</a>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" class="book-form">
            <div class="form-group">
                <label for="title">Название книги:</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="subtitle">Подзаголовок (например, "Сборник рассказов"):</label>
                <input type="text" id="subtitle" name="subtitle" placeholder="Сборник рассказов">
            </div>
            
            <div class="form-group">
                <label for="description">Описание книги (аннотация):</label>
                <textarea id="description" name="description" rows="5" placeholder="Краткое описание сборника..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="tags">Теги (через запятую):</label>
                <input type="text" id="tags" name="tags" placeholder="фантастика, рассказы">
            </div>
            
            <div class="form-group">
                <label for="authors">Авторы (каждый с новой строки):</label>
                <textarea id="authors" name="authors" rows="5" required placeholder="Иванов Иван&#10;Петров Петр&#10;Сидоров Сидор"></textarea>
            </div>
            
            <button type="submit" class="button">Создать книгу</button>
        </form>
    </div>
</body>
</html>