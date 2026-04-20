<?php
require_once 'functions.php';
$books = getBooksList();
?>
<!DOCTYPE html>
<html>
<head>
    <title>FB2 Генератор сборников</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>FB2 Генератор сборников</h1>
        
        <div class="actions">
            <a href="create_book.php" class="button">Создать новую книгу</a>
        </div>
        
        <h2>Список книг</h2>
        
        <?php if (empty($books)): ?>
            <p>Пока нет ни одной книги. Создайте первую!</p>
        <?php else: ?>
            <div class="books-grid">
                <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                        
                        <?php if (!empty($book['authors'])): ?>
                            <p><strong>Авторы:</strong> <?php echo implode(', ', $book['authors']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($book['tags'])): ?>
                            <p><strong>Теги:</strong> <?php echo htmlspecialchars($book['tags']); ?></p>
                        <?php endif; ?>
                        
                        <div class="book-actions">
                            <a href="edit_book.php?book=<?php echo urlencode($book['folder']); ?>" class="button small">Редактировать</a>
                            <a href="compile.php?book=<?php echo urlencode($book['folder']); ?>" class="button small">Компилировать</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>