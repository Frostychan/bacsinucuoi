<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:export-static-site')]
#[Description('Export a static GitHub Pages copy of the website')]
class ExportStaticSite extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $html = view('static-site')->render();
        $publicPath = public_path();

        file_put_contents($publicPath.'/index.html', $html);
        file_put_contents($publicPath.'/404.html', $html);
        file_put_contents($publicPath.'/.nojekyll', '');

        $this->components->info('Đã xuất trang tĩnh vào public/index.html.');

        return self::SUCCESS;
    }
}
