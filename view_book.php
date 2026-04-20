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
$config = include 'config.php';

// Проверяем, есть ли скомпилированный FB2 файл
$fb2File = null;
$files = scandir($bookPath);
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) == 'fb2') {
        $fb2File = $file;
        break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Просмотр книги - <?php echo htmlspecialchars($info['title']); ?></title>
    <link rel="stylesheet" href="style.css">
    <meta charset="utf-8">
    <style>
        .book-viewer {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .book-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        
        .book-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .book-subtitle {
            color: #666;
            font-size: 18px;
            font-style: italic;
            margin-bottom: 15px;
        }
        
        .book-header .authors {
            color: #666;
            font-size: 16px;
        }
        
        .book-header .tags {
            color: #28a745;
            margin-top: 10px;
        }
        
        .book-cover {
            text-align: center;
            margin: 20px 0;
        }
        
        .book-cover img {
            max-width: 300px;
            max-height: 400px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .book-description {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }
        
        .book-description h3 {
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .book-description p {
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .author-section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .author-name {
            color: #007bff;
            margin-bottom: 20px;
            font-size: 20px;
            font-style: italic;
        }
        
        .text-item {
            margin: 20px 0;
            padding: 15px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .text-title {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: bold;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        .text-content {
            line-height: 1.6;
            color: #444;
        }
        
        .text-content p {
            margin: 10px 0;
            text-indent: <?php echo ($config['spacing']['paragraph_indent'] ?? true) ? '2em' : '0'; ?>;
        }
        
        .download-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #e9ecef;
            border-radius: 5px;
        }
        
        .download-button {
            display: inline-block;
            padding: 15px 30px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            margin: 0 10px;
        }
        
        .download-button:hover {
            background: #218838;
        }
        
        .compile-button {
            display: inline-block;
            padding: 15px 30px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            margin: 0 10px;
        }
        
        .compile-button:hover {
            background: #0056b3;
        }
        
        .back-link {
            display: inline-block;
            margin: 20px 0;
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Назад к списку книг</a>
        
        <div class="book-viewer">
            <div class="book-header">
                <h1><?php echo htmlspecialchars($info['title']); ?></h1>
                
                <?php if (!empty($info['subtitle'])): ?>
                    <div class="book-subtitle">
                        <?php echo htmlspecialchars($info['subtitle']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="authors">
                    Авторы: <?php echo implode(', ', array_map('htmlspecialchars', $info['authors'])); ?>
                </div>
                
                <?php if (!empty($info['tags'])): ?>
                    <div class="tags">
                        Теги: <?php echo htmlspecialchars($info['tags']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (file_exists($bookPath . '/cover.jpg')): ?>
                <div class="book-cover">
                    <img src="data/<?php echo $bookFolder; ?>/cover.jpg?<?php echo time(); ?>" alt="Обложка">
                </div>
            <?php endif; ?>
            
            <?php if (!empty($info['description'])): ?>
                <div class="book-description">
                    <h3>Аннотация</h3>
                    <?php 
                    $descParagraphs = explode("\n\n", $info['description']);
                    foreach ($descParagraphs as $para) {
                        $para = trim($para);
                        if (!empty($para)) {
                            echo '<p>' . nl2br(htmlspecialchars($para)) . '</p>';
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="download-section">
                <?php if ($fb2File): ?>
                    <a href="data/<?php echo $bookFolder; ?>/<?php echo $fb2File; ?>" class="download-button" download>📥 Скачать FB2</a>
                <?php endif; ?>
                <a href="compile.php?book=<?php echo urlencode($bookFolder); ?>" class="compile-button">📚 Скомпилировать FB2</a>
            </div>
            
            <h2 style="margin: 30px 0 20px; color: #333;">Содержание</h2>
            
            <?php foreach ($info['authors'] as $author): ?>
                <?php $texts = getAuthorTexts($bookFolder, $author); ?>
                <?php if (!empty($texts)): ?>
                    <div class="author-section">
                        <h3 class="author-name"><?php echo htmlspecialchars($author); ?></h3>
                        
                        <?php foreach ($texts as $text): ?>
                            <div class="text-item">
                                <h4 class="text-title"><?php echo htmlspecialchars($text['title']); ?></h4>
                                <div class="text-content">
                                    <?php 
                                    $paragraphs = explode("\n\n", $text['content']);
                                    foreach ($paragraphs as $para) {
                                        $para = trim($para);
                                        if (!empty($para)) {
                                            echo '<p>' . nl2br(htmlspecialchars($para)) . '</p>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
