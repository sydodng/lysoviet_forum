<?php

return [
    (new Flarum\Extend\Console())
        ->command(Local\VbMigrate\MigrateVbPostsCommand::class),
];