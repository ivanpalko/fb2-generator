<?php
// Простейший FB2 файл для теста
$xml = '<?xml version="1.0" encoding="utf-8"?>
<FictionBook xmlns="http://www.gribuser.ru/xml/fictionbook/2.0">
  <description>
    <title-info>
      <book-title>Тест</book-title>
      <author>
        <first-name>Тест</first-name>
        <last-name>Тестов</last-name>
      </author>
    </title-info>
  </description>
  <body>
    <section>
      <title><p>Глава 1</p></title>
      <p>Простой текст</p>
    </section>
  </body>
</FictionBook>';

header('Content-Type: application/xml');
echo $xml;
?>
