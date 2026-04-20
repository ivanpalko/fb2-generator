<?php
// Глобальная переменная для конфигурации
$fb2_config = [];

function getBooksList() {
    $books = [];
    $dataDir = __DIR__ . '/data';
    
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0777, true);
        return $books;
    }
    
    $items = scandir($dataDir);
    foreach ($items as $item) {
        if ($item != '.' && $item != '..' && is_dir($dataDir . '/' . $item)) {
            $infoFile = $dataDir . '/' . $item . '/info.json';
            if (file_exists($infoFile)) {
                $info = json_decode(file_get_contents($infoFile), true);
                $books[] = [
                    'folder' => $item,
                    'title' => $info['title'] ?? $item,
                    'tags' => $info['tags'] ?? [],
                    'authors' => $info['authors'] ?? []
                ];
            }
        }
    }
    
    return $books;
}

function getAuthorsList($bookFolder) {
    $authorsDir = __DIR__ . '/data/' . $bookFolder . '/authors';
    $authors = [];
    
    if (is_dir($authorsDir)) {
        $items = scandir($authorsDir);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..' && is_dir($authorsDir . '/' . $item)) {
                $authors[] = $item;
            }
        }
    }
    
    return $authors;
}

function getAuthorTexts($bookFolder, $author) {
    $authorDir = __DIR__ . '/data/' . $bookFolder . '/authors/' . $author;
    $texts = [];
    
    if (is_dir($authorDir)) {
        $files = scandir($authorDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'txt') {
                // Пробуем разные кодировки
                $content = file_get_contents($authorDir . '/' . $file);
                $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'KOI8-R'], true);
                
                if ($encoding && $encoding != 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                }
                
                // Очищаем текст от лишних символов Word
                $content = cleanWordText($content);
                
                // Читаем метаданные, если есть
                $metaFile = $authorDir . '/' . pathinfo($file, PATHINFO_FILENAME) . '.meta.json';
                $isPoetry = false;
                if (file_exists($metaFile)) {
                    $meta = json_decode(file_get_contents($metaFile), true);
                    $isPoetry = $meta['is_poetry'] ?? false;
                }
                
                // Для стихов НЕ форматируем абзацы, для прозы - форматируем
                if (!$isPoetry) {
                    $content = preg_replace("/([^\n])\n([^\n])/", "$1\n\n$2", $content);
                }
                
                $texts[] = [
                    'filename' => $file,
                    'title' => pathinfo($file, PATHINFO_FILENAME),
                    'content' => $content,
                    'is_poetry' => $isPoetry
                ];
            }
        }
    }
    
    return $texts;
}

function cleanWordText($text) {
    // 1. Приводим переводы строк к единому формату \n
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "\n", $text);
    
    // 2. Заменяем множественные переводы строк (3 и более) на двойной перевод строки (разделитель абзацев)
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    
    // 3. Убираем пробелы и табуляции в начале и конце каждой строки
    $lines = explode("\n", $text);
    foreach ($lines as &$line) {
        $line = trim($line);
    }
    $text = implode("\n", $lines);
    
    // 4. Убираем спецсимволы Word
    $text = str_replace(chr(0xE2) . chr(0x80) . chr(0x99), "'", $text); // ’
    $text = str_replace(chr(0xE2) . chr(0x80) . chr(0x9C), '"', $text); // “
    $text = str_replace(chr(0xE2) . chr(0x80) . chr(0x9D), '"', $text); // ”
    $text = str_replace(chr(0xE2) . chr(0x80) . chr(0x93), '-', $text); // –
    $text = str_replace(chr(0xE2) . chr(0x80) . chr(0x94), '—', $text); // —
    
    // 5. Убираем пустые строки в начале и конце текста
    $text = trim($text);
    
    return $text;
}

function resizeImage($sourcePath, $destPath, $maxWidth = 600, $maxHeight = 800) {
    list($width, $height, $type) = getimagesize($sourcePath);
    
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = $width * $ratio;
    $newHeight = $height * $ratio;
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        default:
            return false;
    }
    
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    imagejpeg($thumb, $destPath, 85);
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return true;
}

function generateFB2($bookFolder) {
    global $fb2_config;
    
    $bookPath = __DIR__ . '/data/' . $bookFolder;
    $infoFile = $bookPath . '/info.json';
    $config = include 'config.php';
    
    // Сохраняем конфиг в глобальную переменную
    $fb2_config = $config;
    
    if (!file_exists($infoFile)) {
        return false;
    }
    
    $info = json_decode(file_get_contents($infoFile), true);
    
    // Создаем DOM документ
    $fb2 = new DOMDocument('1.0', 'utf-8');
    $fb2->formatOutput = true;
    $fb2->preserveWhiteSpace = false;
    
    // Создаем корневой элемент с правильными пространствами имен
    $fictionBook = $fb2->createElement('FictionBook');
    $fictionBook->setAttribute('xmlns', 'http://www.gribuser.ru/xml/fictionbook/2.0');
    $fictionBook->setAttribute('xmlns:l', 'http://www.w3.org/1999/xlink');
    $fb2->appendChild($fictionBook);
    
    // Description
    $description = $fb2->createElement('description');
    $fictionBook->appendChild($description);
    
    $titleInfo = $fb2->createElement('title-info');
    $description->appendChild($titleInfo);
    
    // Book title (только название, без авторов)
    $bookTitle = $fb2->createElement('book-title');
    $bookTitle->appendChild($fb2->createTextNode($info['title']));
    $titleInfo->appendChild($bookTitle);
    
    // Language
    $lang = $fb2->createElement('lang', 'ru');
    $titleInfo->appendChild($lang);
    
    // Tags as genres
    if (!empty($info['tags'])) {
        $tags = explode(',', $info['tags']);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                $genre = $fb2->createElement('genre');
                $genre->appendChild($fb2->createTextNode($tag));
                $titleInfo->appendChild($genre);
            }
        }
    }
    
    // Cover
    $coverFile = $bookPath . '/cover.jpg';
    if (file_exists($coverFile) && $config['general']['show_cover']) {
        $coverpage = $fb2->createElement('coverpage');
        $image = $fb2->createElement('image');
        $image->setAttribute('l:href', '#cover.jpg');
        $coverpage->appendChild($image);
        $titleInfo->appendChild($coverpage);
    }
    
    // Publish info
    $publishInfo = $fb2->createElement('publish-info');
    $year = $fb2->createElement('year', date('Y'));
    $publishInfo->appendChild($year);
    $description->appendChild($publishInfo);
    
    // Document info
    $documentInfo = $fb2->createElement('document-info');
    $description->appendChild($documentInfo);
    
    $author = $fb2->createElement('author');
    $nickname = $fb2->createElement('nickname');
    $nickname->appendChild($fb2->createTextNode('FB2 Generator'));
    $author->appendChild($nickname);
    $documentInfo->appendChild($author);
    
    $date = $fb2->createElement('date');
    $date->setAttribute('value', date('Y-m-d'));
    $date->appendChild($fb2->createTextNode(date('Y-m-d')));
    $documentInfo->appendChild($date);
    
    // Body
    $body = $fb2->createElement('body');
    $fictionBook->appendChild($body);
    
    // Добавляем название книги
    if ($config['general']['show_book_title']) {
        $titleElement = $fb2->createElement('title');
        $p = createFormattedText($fb2, $info['title'], [
            'bold' => $config['general']['book_title_bold'],
            'italic' => false,
            'size' => $config['general']['book_title_size']
        ]);
        $titleElement->appendChild($p);
        $body->appendChild($titleElement);
        
        // Отступ после названия
        for ($i = 0; $i < $config['spacing']['after_book_title']; $i++) {
            $body->appendChild($fb2->createElement('empty-line'));
        }
        
        // Добавляем подзаголовок, если он есть
        if (!empty($info['subtitle']) && $config['subtitle']['enabled']) {
            $subtitlePara = createFormattedText($fb2, 
                $config['subtitle']['prefix'] . $info['subtitle'] . $config['subtitle']['suffix'], 
                [
                    'bold' => $config['subtitle']['bold'],
                    'italic' => $config['subtitle']['italic'],
                    'size' => $config['subtitle']['size']
                ]
            );
            $body->appendChild($subtitlePara);
            
            // Отступ после подзаголовка
            for ($i = 0; $i < $config['spacing']['after_subtitle']; $i++) {
                $body->appendChild($fb2->createElement('empty-line'));
            }
        }
    }
    
    // Добавляем описание книги, если оно есть в JSON
    if (!empty($info['description']) && $config['description']['enabled']) {
        // Добавляем заголовок описания
        $annotationTitle = $fb2->createElement('title');
        $p = $fb2->createElement('p');
        
        if ($config['description']['title_bold']) {
            $strong = $fb2->createElement('strong');
            $strong->appendChild($fb2->createTextNode($config['description']['title']));
            $p->appendChild($strong);
        } else {
            $p->appendChild($fb2->createTextNode($config['description']['title']));
        }
        
        $annotationTitle->appendChild($p);
        $body->appendChild($annotationTitle);
        
        // Отступ после заголовка
        $body->appendChild($fb2->createElement('empty-line'));
        
        // Добавляем текст описания
        $descriptionContent = cleanWordText($info['description']);
        $paragraphs = explode("\n\n", $descriptionContent);
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (!empty($para)) {
                $p = createFormattedText($fb2, $para, [
                    'bold' => $config['description']['text_bold'],
                    'italic' => $config['description']['text_italic'],
                    'size' => 'normal'
                ]);
                $body->appendChild($p);
                
                // Отступ между абзацами описания
                $body->appendChild($fb2->createElement('empty-line'));
            }
        }
        
        // Отступ после описания перед авторами
        for ($i = 0; $i < $config['spacing']['after_description']; $i++) {
            $body->appendChild($fb2->createElement('empty-line'));
        }
    } else {
        // Если описания нет, можно ничего не добавлять или добавить заглушку
        if ($config['description']['enabled']) {
            $annotationTitle = $fb2->createElement('title');
            $p = $fb2->createElement('p');
            $strong = $fb2->createElement('strong');
            $strong->appendChild($fb2->createTextNode($config['description']['title']));
            $p->appendChild($strong);
            $annotationTitle->appendChild($p);
            $body->appendChild($annotationTitle);
            
            $body->appendChild($fb2->createElement('empty-line'));
            
            // Текст-заглушка из конфига
            $p = $fb2->createElement('p');
            $p->appendChild($fb2->createTextNode($config['description']['placeholder']));
            $body->appendChild($p);
            
            $body->appendChild($fb2->createElement('empty-line'));
            
            for ($i = 0; $i < $config['spacing']['after_description']; $i++) {
                $body->appendChild($fb2->createElement('empty-line'));
            }
        }
    }
    
    // Добавляем произведения авторов
    foreach ($info['authors'] as $authorIndex => $author) {
        $authorDir = $bookPath . '/authors/' . $author;
        
        if (is_dir($authorDir)) {
            $texts = getAuthorTexts($bookFolder, $author);
            if (!empty($texts)) {
                // Создаем секцию для автора
                $authorSection = $fb2->createElement('section');
                
                // Имя автора с настройками из конфига
                $authorPara = createFormattedText($fb2, 
                    $config['author']['prefix'] . $author . $config['author']['suffix'], 
                    [
                        'bold' => $config['author']['bold'],
                        'italic' => $config['author']['italic'],
                        'size' => $config['author']['size']
                    ]
                );
                $authorSection->appendChild($authorPara);
                
                // Отступ после имени автора
                for ($i = 0; $i < $config['spacing']['after_author']; $i++) {
                    $authorSection->appendChild($fb2->createElement('empty-line'));
                }
                
                // Добавляем произведения автора
                foreach ($texts as $textIndex => $text) {
                    // Название произведения с настройками из конфига
                    $titlePara = createFormattedText($fb2, 
                        $config['title']['prefix'] . $text['title'] . $config['title']['suffix'], 
                        [
                            'bold' => $config['title']['bold'],
                            'italic' => $config['title']['italic'],
                            'size' => $config['title']['size']
                        ]
                    );
                    $authorSection->appendChild($titlePara);
                    
                    // Отступ после названия
                    for ($i = 0; $i < $config['spacing']['after_title']; $i++) {
                        $authorSection->appendChild($fb2->createElement('empty-line'));
                    }
                    
                    // Текст произведения
                    if ($text['is_poetry'] ?? false) {
                        // Это стихотворение - обрабатываем построчно
                        $lines = explode("\n", $text['content']);
                        $emptyLineCount = 0;
                        
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (empty($line)) {
                                $emptyLineCount++;
                                // Пустая строка - разделитель строф
                                if ($emptyLineCount == 1) {
                                    for ($i = 0; $i < $config['spacing']['between_stanzas']; $i++) {
                                        $authorSection->appendChild($fb2->createElement('empty-line'));
                                    }
                                }
                            } else {
                                $emptyLineCount = 0;
                                $p = createFormattedText($fb2, $line, [
                                    'bold' => $config['text']['bold'],
                                    'italic' => $config['text']['italic'],
                                    'size' => $config['text']['size']
                                ]);
                                
                                // Для стихов можно добавить выравнивание по центру
                                if ($config['poetry']['align_center']) {
                                    $p->setAttribute('align', 'center');
                                }
                                
                                $authorSection->appendChild($p);
                                
                                // Между строками стиха не добавляем empty-line
                                // или добавляем если настроено
                                if ($config['poetry']['line_spacing'] > 0) {
                                    for ($i = 0; $i < $config['poetry']['line_spacing']; $i++) {
                                        $authorSection->appendChild($fb2->createElement('empty-line'));
                                    }
                                }
                            }
                        }
                    } else {
                        // Это проза - обрабатываем абзацы
                        $paragraphs = explode("\n\n", $text['content']);
                        $firstPara = true;
                        foreach ($paragraphs as $para) {
                            $para = trim($para);
                            if (!empty($para)) {
                                // Добавляем отступ для всех абзацев кроме первого
                                if (!$firstPara) {
                                    // Проверяем, является ли абзац диалогом (начинается с тире)
                                    if (strpos($para, '-') === 0 || strpos($para, '—') === 0) {
                                        // Для диалогов НЕ добавляем empty-line
                                        // Просто ничего не делаем
                                    } else {
                                        // Обычный абзац - добавляем empty-line
                                        $authorSection->appendChild($fb2->createElement('empty-line'));
                                    }
                                }
                                $firstPara = false;
                                
                                $p = createFormattedText($fb2, $para, [
                                    'bold' => $config['text']['bold'],
                                    'italic' => $config['text']['italic'],
                                    'size' => $config['text']['size']
                                ]);
                                $authorSection->appendChild($p);
                            }
                        }
                    }
                    
                    // Разделитель между произведениями
                    if ($textIndex < count($texts) - 1) {
                        for ($i = 0; $i < $config['spacing']['between_works']; $i++) {
                            $authorSection->appendChild($fb2->createElement('empty-line'));
                        }
                    }
                }
                
                $body->appendChild($authorSection);
                
                // Разделитель между авторами
                if ($authorIndex < count($info['authors']) - 1) {
                    for ($i = 0; $i < $config['spacing']['between_authors']; $i++) {
                        $body->appendChild($fb2->createElement('empty-line'));
                    }
                }
            }
        }
    }
    
    // Binary (cover)
    if (file_exists($coverFile) && $config['general']['show_cover']) {
        $coverData = base64_encode(file_get_contents($coverFile));
        $binary = $fb2->createElement('binary');
        $binary->setAttribute('id', 'cover.jpg');
        $binary->setAttribute('content-type', 'image/jpeg');
        
        // Разбиваем base64 на строки по 76 символов
        $wrappedData = wordwrap($coverData, 76, "\n", true);
        $binary->appendChild($fb2->createTextNode("\n" . $wrappedData . "\n"));
        
        $fictionBook->appendChild($binary);
    }
    
    // Сохраняем файл
    $fb2File = $bookPath . '/' . preg_replace('/[^a-zA-Zа-яА-Я0-9\s]/u', '', $info['title']) . '.fb2';
    $fb2File = str_replace(' ', '_', $fb2File);
    
    $fb2->save($fb2File);
    
    return $fb2File;
}

// Вспомогательная функция для создания форматированного текста
function createFormattedText($fb2, $text, $formatting) {
    global $fb2_config;
    
    $p = $fb2->createElement('p');
    $current = $p;
    
    // Добавляем отступ если нужно
    if (isset($fb2_config['spacing']['paragraph_indent']) && 
        $fb2_config['spacing']['paragraph_indent']) {
        $indent = str_repeat("\u{00A0}", $fb2_config['spacing']['paragraph_indent_size']);
        $text = $indent . $text;
    }
    
    // Сначала применяем размер
    if ($formatting['size'] == 'large') {
        if (!$formatting['bold']) {
            $strong = $fb2->createElement('strong');
            $p->appendChild($strong);
            $current = $strong;
        }
    } elseif ($formatting['size'] == 'xlarge') {
        $strong = $fb2->createElement('strong');
        $p->appendChild($strong);
        
        if ($formatting['italic']) {
            $emphasis = $fb2->createElement('emphasis');
            $strong->appendChild($emphasis);
            $current = $emphasis;
        } else {
            $current = $strong;
        }
    }
    
    if ($formatting['bold'] && $formatting['size'] == 'normal') {
        $strong = $fb2->createElement('strong');
        $current->appendChild($strong);
        $current = $strong;
    }
    
    if ($formatting['italic'] && $formatting['size'] != 'xlarge') {
        $emphasis = $fb2->createElement('emphasis');
        $current->appendChild($emphasis);
        $current = $emphasis;
    }
    
    $current->appendChild($fb2->createTextNode($text));
    
    return $p;
}

function validateFB2($filePath) {
    if (!file_exists($filePath)) {
        return "Файл не найден";
    }
    
    // Проверяем размер файла
    $size = filesize($filePath);
    if ($size < 100) {
        return "Файл слишком маленький: " . $size . " байт";
    }
    
    // Проверяем начало файла
    $handle = fopen($filePath, 'r');
    $header = fread($handle, 100);
    fclose($handle);
    
    if (strpos($header, '<?xml') === false) {
        return "Файл не начинается с XML декларации";
    }
    
    if (strpos($header, '<FictionBook') === false) {
        return "Файл не содержит тег FictionBook";
    }
    
    // Пробуем загрузить как XML
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    if (!$dom->load($filePath)) {
        $errors = libxml_get_errors();
        $errorMsg = "";
        foreach ($errors as $error) {
            $errorMsg .= $error->message . "\n";
        }
        libxml_clear_errors();
        return "Ошибка XML: " . $errorMsg;
    }
    
    return true;
}
?>