<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);

echo "Starting migration...\n";

// ===== CONFIG =====
$vb_host = "127.0.0.1";
$vb_db   = "vforum";
$vb_user = "root";
$vb_pass = "";

$fl_host = "127.0.0.1";
$fl_db   = "flarum_db";
$fl_user = "root";
$fl_pass = "";

// ===== CONNECT VB (QUAN TRỌNG: utf8mb4) =====
$vb = new PDO("mysql:host=$vb_host;dbname=$vb_db;charset=utf8mb4", $vb_user, $vb_pass);
$vb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ===== CONNECT FLARUM =====
$fl = new PDO("mysql:host=$fl_host;dbname=$fl_db;charset=utf8mb4", $fl_user, $fl_pass);
$fl->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ===== PREPARE INSERT =====
$insert = $fl->prepare("
    INSERT INTO users
    (username, email, password, joined_at, last_seen_at, is_email_confirmed)
    VALUES (?, ?, ?, ?, ?, 1)
");

// ===== MIGRATE =====
$stmt = $vb->query("SELECT userid, username, email, joindate, lastvisit FROM user");

$count = 0;

while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $username = trim($u['username']);
    $email    = $u['email'] ?: "user{$u['userid']}@example.com";

    $joined = $u['joindate'] ? date("Y-m-d H:i:s", $u['joindate']) : null;
    $last   = $u['lastvisit'] ? date("Y-m-d H:i:s", $u['lastvisit']) : null;

    $password = password_hash("reset123", PASSWORD_BCRYPT);

    try {
	    $insert->execute([
	        $username,
	        $email,
	        $password,
	        $joined,
	        $last
	    ]);

	    $flarumId = $fl->lastInsertId();

	    $fl->prepare("
	        INSERT INTO vb_user_map (vb_user_id, flarum_user_id)
	        VALUES (?, ?)
	    ")->execute([
	        $u['userid'],
	        $flarumId
	    ]);

	    $count++;

	} catch (Exception $e) {
	    echo "Error user {$u['userid']}: " . $e->getMessage() . "\n";
	}
}

echo "Done. Migrated $count users.\n";