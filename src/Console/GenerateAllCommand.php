<?php

namespace Rossity\ModelGenerator\Console;

use Illuminate\Console\Command;

class GenerateAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates scaffolding for all models in the models folder';

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
        //
    }
}
