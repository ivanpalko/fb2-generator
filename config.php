<?php
return [
    // Настройки отступов
    'spacing' => [
        'after_cover' => 1,
        'after_description' => 2,
        'after_author' => 1,
        'after_title' => 1,
        'between_paragraphs' => 1,      // Между абзацами прозы
        'between_verses' => 0,           // Между строками стихов (0 - без отступа)
        'between_stanzas' => 1,           // Между строфами
        'between_works' => 2,
        'between_authors' => 3,
        'paragraph_indent' => true,
        'paragraph_indent_size' => 2,
        'after_book_title' => 1,
        'after_subtitle' => 1,
    ],
    
    // Настройки для подзаголовка книги
    'subtitle' => [
        'enabled' => true,
        'text' => 'Сборник рассказов',
        'bold' => false,
        'italic' => true,
        'size' => 'large',
        'prefix' => '',
        'suffix' => '',
    ],
    
    // Настройки для описания книги
    'description' => [
        'enabled' => true,
        'title' => 'Аннотация',
        'title_bold' => true,
        'text_bold' => false,
        'text_italic' => false,
        'placeholder' => 'Краткое описание сборника...',
    ],
    
    // Настройки форматирования имен авторов
    'author' => [
        'bold' => false,
        'italic' => true,
        'size' => 'large',
        'prefix' => '',
        'suffix' => '',
    ],
    
    // Настройки форматирования названий произведений
    'title' => [
        'bold' => true,
        'italic' => false,
        'size' => 'xlarge',
        'prefix' => '',
        'suffix' => '',
    ],
    
    // Настройки форматирования текста
    'text' => [
        'bold' => false,
        'italic' => false,
        'size' => 'normal',
    ],
    
    // Настройки для стихов
  'poetry' => [
    'line_spacing' => 0,              // 0 - строки идут подряд
    'stanza_spacing' => 1,             // 1 пустая строка между строфами
    'align_center' => false,
],
    
    // Общие настройки
    'general' => [
        'show_cover' => true,
        'show_book_title' => true,
        'book_title_bold' => true,
        'book_title_size' => 'xlarge',
    ]
];
?>