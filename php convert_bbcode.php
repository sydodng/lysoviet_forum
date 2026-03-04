<?php

$pdo = new PDO(
    "mysql:host=localhost;dbname=flarum;charset=utf8mb4",
    "root",
    "",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Cleaning BBCode...\n";

function cleanBBCode($text)
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Xoá tag rác nhưng giữ BBCode chuẩn
    $allowed = [
        'b','i','u','url','img','quote','code','list','*'
    ];

    // Xoá color
    $text = preg_replace('/\[color=.*?\](.*?)\[\/color\]/is', '$1', $text);

    // Xoá size
    $text = preg_replace('/\[size=.*?\](.*?)\[\/size\]/is', '$1', $text);

    // Xoá font
    $text = preg_replace('/\[font=.*?\](.*?)\[\/font\]/is', '$1', $text);

    // Xoá align nhưng giữ nội dung
    $text = preg_replace('/\[(right|left|center)\](.*?)\[\/\\1\]/is', '$2', $text);

    // Xoá tag lạ còn sót
    $text = preg_replace('/\[(?!\/?(b|i|u|url|img|quote|code|list|\*)\b)[^\]]+\]/i', '', $text);

    return $text;
}


// Backup
$pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS content_backup2 LONGTEXT");
$pdo->exec("UPDATE posts SET content_backup2 = content WHERE content_backup2 IS NULL");

$stmt = $pdo->query("SELECT id, content FROM posts");
$update = $pdo->prepare("UPDATE posts SET content = ? WHERE id = ?");

$count = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $new = cleanBBCode($row['content']);

    $update->execute([$new, $row['id']]);

    $count++;

    if ($count % 100 == 0) {
        echo "Processed $count posts\n";
    }
}

echo "Done. Total: $count posts\n";