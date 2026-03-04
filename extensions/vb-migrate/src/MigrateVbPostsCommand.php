<?php

namespace Local\VbMigrate;

use Illuminate\Console\Command;
use Flarum\Post\CommentPost;
use Flarum\User\User;
use Flarum\Discussion\Discussion;
use Illuminate\Support\Carbon;
use PDO;

class MigrateVbPostsCommand extends Command
{
    protected $signature = 'migrate:vbposts';
    protected $description = 'Migrate vBulletin posts properly';

    public function handle()
    {
        $vb = new PDO(
            "mysql:host=localhost;dbname=vforum;charset=utf8mb4",
            "root",
            ""
        );

        $vb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->info("Start migrating posts...");

        $count = 0;

        foreach ($vb->query("SELECT * FROM post ORDER BY postid ASC") as $p) {

            $discussion = Discussion::where('vb_thread_id', $p['threadid'])->first();
            if (!$discussion) continue;

            $user = User::find($p['userid']);
            $actor = User::find($p['userid']);

            if (!$actor) {
                $actor = User::find(1);
            }

            try {
                

                // Tính số thứ tự trong discussion
                $lastNumber = CommentPost::where('discussion_id', $discussion->id)->max('number');
                $number = $lastNumber ? $lastNumber + 1 : 1;

                $post = new CommentPost();

                $post->discussion_id = $discussion->id;
                $post->user_id = $user->id;
                $post->content = $p['pagetext']; // RAW BBCode
                $post->created_at = Carbon::createFromTimestamp($p['dateline']);
                $post->ip_address = null;
                $post->is_private = 0;
                $post->is_approved = 1;
                $post->number = $number;

                $post->created_at = date('Y-m-d H:i:s', $p['dateline']);
                $post->save();

                $count++;

                if ($count % 100 == 0) {
                    $this->info("Inserted $count posts...");
                }

            } catch (\Throwable $e) {
                $this->error("Error at post {$p['postid']}: ".$e->getMessage());
            }
        }

        $this->info("Done. Total: $count posts");
    }
}