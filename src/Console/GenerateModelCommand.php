<?php

namespace Rossity\ModelGenerator\Console;

use Illuminate\Console\Command;

class GenerateModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:model
        {name : Class name of model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates scaffolding for a specific model';

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

    protected function addSpaces($number)
    {
        $space = '';
        for ($i = 1; $i <= $number; $i++) {
            $space = $space . ' ';
        }
        return $space;
    }

    protected function removeBlock($string, $block_to_remove)
    {
        return preg_replace("/[\r\n]*{{" . $block_to_remove . '}}.*?{{.?' . $block_to_remove . "}}[\r\n]?/s", '', $string);
    }

    protected function removeTag($string, $tag_to_remove)
    {
        return preg_replace('/{{.?' . $tag_to_remove . "}}[\r\n]?/s", '', $string);
    }

    protected function createRequest()
    {
        $path = app_path('Http/Requests');

        if (!is_dir($path)) {
            mkdir($path);
        }

        $fields = $this->fields
            ->map(function ($item, $key) {
                $validation = '';
                if ($item['required']) {
                    $validation = $validation . 'required|';
                }
                $validation = $validation . $item['type'];
                if ($item['extra_rules']) {
                    $validation = $validation . '|' . $item['extra_rules'];
                }
                return "'{$key}' => '$validation'";
            })
            ->all();

        $requestTemplate = str_replace(
            [
                '{{modelName}}',
                '{{fields}}',
            ],
            [
                $this->name,
                implode($fields, ",\n" . $this->addSpaces(12))
            ],
            $this->getStub('request')
        );

        file_put_contents(
            app_path("Http/Requests/{$this->name}Request.php"),
            $requestTemplate
        );
    }

    protected function createFactory()
    {
        $snaked_variable = snake_case($this->pluralNameVariable);

        $fields = $this->fields
            ->map(function ($item, $key) {
                return "'{$key}' => " . '$faker->foo(),';
            })
            ->all();

        $factoryTemplate = str_replace(
            [
                '{{modelName}}',
                '{{fields}}',
            ],
            [
                $this->name,
                implode($fields, "\n" . $this->addSpaces(6))
            ],
            $this->getStub('factory')
        );

        file_put_contents(
            base_path("database/factories/{$this->name}Factory.php"),
            $factoryTemplate
        );
    }

    protected function createMigration()
    {
        $snaked_variable = snake_case($this->pluralNameVariable);

        $fields = $this->fields
            ->map(function ($item, $key) {
                $field = '$table->' . "{$item['type']}('{$key}')";
                if ($item['nullable']) {
                    $field = $field . '->nullable()';
                }
                return $item['default'] !== null ? $field . "->default({$item['default']});" : $field . ";";
            })
            ->all();

        $migrationTemplate = str_replace(
            [
                '{{pluralName}}',
                '{{pluralVariableName}}',
                '{{fields}}',
            ],
            [
                $this->pluralName,
                $snaked_variable,
                implode($fields, "\n" . $this->addSpaces(12))
            ],
            $this->getStub('migration')
        );

        file_put_contents(
            base_path("database/migrations/" . date('Y_m_d_His') . "_create_{$snaked_variable}_table.php"),
            $migrationTemplate
        );
    }

    protected function createResources()
    {
        $path = app_path('Http/Resources');

        if (!is_dir($path)) {
            mkdir($path);
        }

        $fields = $this->fields
            ->map(function ($item, $key) {
                return "'$key' => " . '$this' . "->$key";
            })
            ->all();
        $resourceTemplate = str_replace(
            [
                '{{modelName}}',
                '{{fields}}'
            ],
            [
                $this->name,
                implode($fields, ",\n" . $this->addSpaces(12))
            ],
            $this->getStub('resource')
        );
        file_put_contents(app_path("Http/Resources/{$this->name}Resource.php"), $resourceTemplate);
        $fields = $this->fields
            ->map(function ($item, $key) {
                return "'$key' => $" . $this->nameVariable . "->$key";
            })
            ->all();
        $resourceTemplate = str_replace(
            [
                '{{modelName}}',
                '{{nameVariable}}',
                '{{fields}}'
            ],
            [
                $this->name,
                $this->nameVariable,
                implode($fields, ",\n" . $this->addSpaces(20))
            ],
            $this->getStub('collection')
        );
        file_put_contents(app_path("Http/Resources/{$this->name}Collection.php"), $resourceTemplate);
    }

    protected function createModel()
    {
        $modelTemplate = $this->getStub('model');

        $related = $this->related;

        if ($related) {
            $relationTemplate = str_replace(
                [
                    '{{belongingRelation}}',
                    '{{belongingModel}}',
                    '{{relation}}'
                ],
                [
                    snake_case($related['type'] === 'hasMany' ? str_plural($this->name) : $this->name),
                    "App\\" . $this->name,
                    $related['type']
                ],
                $this->getStub('relation')
            );
            $relation_file = base_path('/app/' . $related['name'] . '.php');
            $relation_content = rtrim(file_get_contents($relation_file), "}");
            file_put_contents(
                $relation_file,
                $relation_content . "\n" . $relationTemplate . "}"
            );
            $relationTemplate = str_replace(
                [
                    '{{belongingRelation}}',
                    '{{belongingModel}}',
                    '{{relation}}'
                ],
                [
                    snake_case($related['name']),
                    "App\\" . $related['name'],
                    'belongsTo'
                ],
                $this->getStub('relation')
            );
            $modelTemplate = str_replace(['{{relation}}'], [$relationTemplate], $modelTemplate);
            $modelTemplate = $this->removeTag($modelTemplate, 'belongsTo');
        } else {
            $modelTemplate = $this->removeBlock($modelTemplate, 'belongsTo');
        }

        $modelTemplate = $this->logActivity ? $this->removeTag($modelTemplate, 'logsActivity') : $this->removeBlock($modelTemplate, 'logsActivity');
        $modelTemplate = $this->addMedia ? $this->removeTag($modelTemplate, 'hasMedia') : $this->removeBlock($modelTemplate, 'hasMedia');

        $modelTemplate = str_replace(['{{modelName}}'], $this->name, $modelTemplate);
        file_put_contents(app_path("{$this->name}.php"), $modelTemplate);
    }

    protected function createController()
    {
        $allowedFilters = $this->fields
            ->whereIn('filterable', [true, 'exact'])
            ->map(function ($item, $key) {
                if ($item['filterable'] === true) {
                    return "'$key'";
                }
                return "Filter::exact('$key')";
            })
            ->all();

        if ($allowedFilters) {
            $allowedFilters =
                "[\n" . $this->addSpaces(16)
                . implode($allowedFilters, ",\n" . $this->addSpaces(16))
                . "\n" . $this->addSpaces(12) . "]";
        } else {
            $allowedFilters = "";
        }

        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{pluralNameVariable}}',
                '{{nameVariable}}',
                '{{allowedFilters}}',
            ],
            [
                $this->name,
                $this->pluralNameVariable,
                $this->nameVariable,
                $allowedFilters,
            ],
            $this->getStub('Controller')
        );

        file_put_contents(app_path("/Http/Controllers/{$this->name}Controller.php"), $controllerTemplate);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        include_once app_path('Http\Templates') . "\\{$name}Template.php";
        $class = "{$name}Template";
        $template = new $class();

        $this->name = $name;
        $this->nameVariable = lcfirst($this->name);
        $this->pluralName = str_plural($this->name);
        $this->pluralNameVariable = lcfirst($this->pluralName);

        $this->fields = collect($template->fields);
        $this->related = $template->related;
        $this->logActivity = $template->logActivity;
        $this->addMedia = $template->addMedia;


        $this->createModel();
        $this->info('Model created');

        $this->createMigration();
        $this->info('Migration created');

        $this->createController();
        $this->info('Controller created');

        $this->createResources();
        $this->info('Resources created');

        $this->createRequest();
        $this->info('Request created');

        $this->createFactory();
        $this->info('Factory created');

        file_put_contents(
            base_path('routes/api.php'),
            "Route::resource('" . snake_case($this->pluralName) . "', '{$this->name}Controller');",
            FILE_APPEND
        );
        $this->info('API route created, please move if necessary.');
    }

}
