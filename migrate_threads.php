<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

echo "Migrating threads...\n";

// CONFIG
$vb = new PDO("mysql:host=127.0.0.1;dbname=vforum;charset=utf8mb4", "root", "");
$vb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$fl = new PDO("mysql:host=127.0.0.1;dbname=flarum_db;charset=utf8mb4", "root", "");
$fl->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Prepare insert
$insert = $fl->prepare("
    INSERT INTO discussions
    (title, created_at, user_id)
    VALUES (?, ?, ?)
");

$stmt = $vb->query("
    SELECT threadid, title, postuserid, dateline
    FROM thread
    ORDER BY threadid ASC
");

$count = 0;

while ($t = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $title = trim($t['title']);
    $created = date("Y-m-d H:i:s", $t['dateline']);

    $userId = $t['postuserid'] ?: null;

	// kiểm tra user có tồn tại không
	$check = $fl->prepare("SELECT id FROM users WHERE id = ?");
	$check->execute([$userId]);

	if (!$check->fetch()) {
	    $userId = null; // để Flarum set NULL
	}

    try {
        $insert = $fl->prepare("
		    INSERT INTO discussions
		    (title, user_id, created_at, vb_thread_id)
		    VALUES (?, ?, ?, ?)
		");

		$insert->execute([
		    $t['title'],
		    $userId,
		    date('Y-m-d H:i:s', $t['dateline']),
		    $t['threadid']
		]);
        $count++;
    } catch (Exception $e) {
        echo "Thread {$t['threadid']} error: " . $e->getMessage() . "\n";
    }
}

echo "Done. Migrated $count discussions.\n";