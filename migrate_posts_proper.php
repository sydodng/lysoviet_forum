<?php

use Flarum\Foundation\Site;
use Flarum\Post\CommentPost;
use Flarum\User\User;
use Flarum\Discussion\Discussion;

require __DIR__.'/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Boot Flarum Site (HTTP container)
|--------------------------------------------------------------------------
*/

$site = Site::fromPaths([
    'base' => __DIR__,
    'public' => __DIR__.'/public',
    'storage' => __DIR__.'/storage',
]);

$container = $site->getContainer();

/*
|--------------------------------------------------------------------------
| Kết nối vBulletin
|--------------------------------------------------------------------------
*/

$vb = new PDO(
    "mysql:host=localhost;dbname=vforum;charset=utf8mb4",
    "root",
    ""
);

$vb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Start migrating posts...\n";

$count = 0;

foreach ($vb->query("SELECT * FROM post ORDER BY postid ASC") as $p) {

    $discussion = Discussion::where('vb_thread_id', $p['threadid'])->first();
    if (!$discussion) continue;

    $user = User::find($p['userid']);
    $actor = null;

    if ($user && $user->exists) {
        $actor = $user;
    } else {
        $actor = User::find(1); // admin fallback
    }

    try {

        $post = CommentPost::reply(
            $discussion->id,
            $p['pagetext'],
            $actor->id
        );

        $post->created_at = date('Y-m-d H:i:s', $p['dateline']);
        $post->save();

        $count++;

        if ($count % 100 == 0) {
            echo "Inserted $count posts...\n";
        }

    } catch (\Throwable $e) {
        echo "Error at post {$p['postid']}: ".$e->getMessage()."\n";
    }
}

echo "Done. Total: $count posts\n";