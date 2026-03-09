<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearTmpImages extends Command
{
    /**
     * 例:
     *  - php artisan tmp:clear-images
     *  - php artisan tmp:clear-images --hours=24
     */
    protected $signature = 'tmp:clear-images {--hours=24 : Delete files older than N hours}';

    protected $description = 'Delete old temporary uploaded images in storage/app/public/tmp';

    public function handle(): int
    {
        $hours = (int)$this->option('hours');
        if ($hours <= 0) {
            $this->error('--hours must be a positive integer.');
            return Command::FAILURE;
        }

        $disk = Storage::disk('public');
        $dir  = 'tmp';

        if (!$disk->exists($dir)) {
            $this->info("Directory '{$dir}' does not exist. Nothing to delete.");
            return Command::SUCCESS;
        }

        $threshold = now()->subHours($hours)->timestamp;

        $files = $disk->allFiles($dir); // tmp配下を再帰的に取得
        $deleted = 0;
        $skipped = 0;

        foreach ($files as $file) {
            // 念のため tmp 配下以外を触らない
            if (!str_starts_with($file, 'tmp/')) {
                $skipped++;
                continue;
            }

            $absolutePath = $disk->path($file);

            // ローカルディスク前提（publicは通常local）
            if (!is_file($absolutePath)) {
                $skipped++;
                continue;
            }

            $mtime = @filemtime($absolutePath);
            if ($mtime === false) {
                $skipped++;
                continue;
            }

            if ($mtime < $threshold) {
                $disk->delete($file);
                $deleted++;
            } else {
                $skipped++;
            }
        }

        $this->info("tmp cleanup done. deleted={$deleted}, skipped={$skipped}, older_than_hours={$hours}");
        return Command::SUCCESS;
    }
}
