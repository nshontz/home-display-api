<?php

namespace App\Console\Commands;

use App\Models\Dinner;
use Illuminate\Console\Command;

class ProteinCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dinner:protein-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Dinner::whereNull('protein_id')->get()->map(function ($dinner) {
            $dinner->guessProtien();
        });
    }
}
