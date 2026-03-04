<?php

$vb = new PDO("mysql:host=localhost;dbname=vforum;charset=utf8mb4","root","");
$fl = new PDO("mysql:host=localhost;dbname=lysoviet_forum;charset=utf8mb4","root","");

$vb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$fl->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Start migrating posts...\n";

$sql = "
SELECT postid, threadid, userid, pagetext, dateline
FROM post
ORDER BY postid ASC
";

foreach ($vb->query($sql) as $p) {

    // Map discussion
    $map = $fl->prepare("SELECT id FROM discussions WHERE vb_thread_id = ?");
    $map->execute([$p['threadid']]);
    $discussion = $map->fetch(PDO::FETCH_ASSOC);

    if (!$discussion) {
        continue; // bỏ post nếu không tìm thấy thread
    }

    $discussionId = $discussion['id'];

    // Map user
    $userId = $p['userid'] ?: null;

    $check = $fl->prepare("SELECT id FROM users WHERE id = ?");
    $check->execute([$userId]);

    if (!$check->fetch()) {
        $userId = null;
    }

    // Convert content sang JSON dạng lysoviet_forum
    // $content = json_encode($p['pagetext'], JSON_UNESCAPED_UNICODE);
    $content = json_encode([
        'content' => $p['pagetext'],
        'type' => 'comment'
    ], JSON_UNESCAPED_UNICODE);

    $insert = $fl->prepare("
        INSERT INTO posts
        (discussion_id, user_id, type, content, created_at)
        VALUES (?, ?, 'comment', ?, ?)
    ");

    $insert->execute([
        $discussionId,
        $userId,
        $content,
        date('Y-m-d H:i:s', $p['dateline'])
    ]);

}

echo "Done.\n";