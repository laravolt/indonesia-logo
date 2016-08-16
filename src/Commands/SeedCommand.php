<?php

namespace Laravolt\IndonesiaLogo\Commands;

use Illuminate\Console\Command;

class SeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravolt:indonesialogo:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Artisan::call('db:seed', ['--class' => 'Laravolt\IndonesiaLogo\Seeds\DatabaseSeeder']);
        $this->info('Seeded: Laravolt\IndonesiaLogo\Seeds\DatabaseSeeder');
    }
}
