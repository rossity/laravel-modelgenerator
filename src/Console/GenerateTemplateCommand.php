<?php

namespace Rossity\ModelGenerator\Console;

use Illuminate\Console\Command;

class GenerateTemplateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:template
        {name : Class name of model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a template used to scaffold a model';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function getStub($type)
    {
        return file_get_contents(__DIR__ . "/../../stubs/$type.stub");
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $modelName = $this->argument('name');

        $template = str_replace(
            ['{{modelName}}'],
            [$modelName],
            $this->getStub('template')
        );

        $path = app_path('Http/Templates');

        if (!is_dir($path)) {
            mkdir($path);
        }

        file_put_contents("$path/{$modelName}Template.php", $template);
    }
}
