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

    protected function setPath($path)
    {
        $array_folder = explode('/', $path);
        $mk_folder = "";

        foreach ($array_folder as $folder) {
            $mk_folder = $mk_folder . $folder . "/";
            if (!is_dir($mk_folder)) {
                mkdir($mk_folder);
            }
        }

        return $path;
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
        $fields = $this->fields
            ->map(function ($item, $key) {
                $validation = '';
                if ($item['required']) {
                    $validation = $validation . 'required|';
                }

                if ($item['nullable']) {
                    $validation = $validation . 'nullable|';
                }

                if ($item['extra_rules']) {
                    $validation = $validation . $item['extra_rules'] . '|';
                }
                $type = explode(',', $item['type'], 2)[0];
                switch ($type) {
                    case 'float':
                        $type = 'numeric';
                        break;
                    case 'dateTime':
                    case 'date':
                        $type = 'date';
                        break;
                    case 'text':
                        $type = 'string';
                        break;
                }
                $validation = $validation . $type;
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

        $path = $this->setPath(app_path('Http/Requests/Api/'));

        file_put_contents(
            $path . "{$this->name}Request.php",
            $requestTemplate
        );

        $this->info('Request created');
    }

    protected function createFactory()
    {
        $snaked_variable = snake_case($this->pluralNameVariable);

        $fields = $this->fields
            ->map(function ($item, $key) {
                switch (explode(',', $item['type'])[0]) {
                    case 'dateTime':
                        $seed = '$faker->dateTimeBetween($startDate = \'-6 months\', $endDate = \'now\', $timezone = null)->format(\'Y-m-d H:i:s\')';
                        break;
                    case 'date':
                        $seed = '$faker->dateTimeBetween($startDate = \'-6 months\', $endDate = \'now\', $timezone = null)->format(\'Y-m-d\')';
                        break;
                    case 'boolean':
                        $seed = '$bool = rand(0,1)';
                        break;
                    case 'integer':
                        $seed = '$integer = rand($low = 1, $high = 10)';
                        break;
                    case 'float':
                        $seed = '$float = rand($low = 1, $high = 10) / 10';
                        break;
                    case 'string':
                        $seed = '$faker->sentence($nbWords = 6, $variableNbWords = true)';
                        break;
                    case 'text':
                        $seed = '$faker->text($maxNbChars = 200)';
                        break;
                    default:
                        $seed = '$faker->foo()';
                }
                return "'{$key}' => {$seed},";
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

        $this->info('Factory created');

    }

    protected function createMigration()
    {
        $snaked_variable = snake_case($this->pluralNameVariable);

        $fields = $this->fields
            ->map(function ($item, $key) {
                $fieldArray = explode(',', $item['type'], 2);
                $name = $key;
                $constraints = count($fieldArray) > 1 ? ",{$fieldArray[1]}" : '';
                $field = '$table->' . "{$fieldArray[0]}('{$name}'{$constraints})";
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

        $this->info('Migration created');

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

        $this->info('Resources created');

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
            $relation_content = preg_replace("/\n}(?>.|\n)*/", '', file_get_contents($relation_file));
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
            $modelTemplate = str_replace(
                [
                    '{{relation}}',
                    '{{guardedAttributes}}'
                ],
                [
                    $relationTemplate,
                    ", '" . snake_case($related['name']) . "_id'"
                ],
                $modelTemplate
            );

            $modelTemplate = $this->removeTag($modelTemplate, 'belongsTo');
        } else {
            $modelTemplate = $this->removeBlock($modelTemplate, 'belongsTo');
            $modelTemplate = str_replace(['{{guardedAttributes}}'], '', $modelTemplate);
        }

        $modelTemplate = $this->logActivity ? $this->removeTag($modelTemplate, 'logsActivity') : $this->removeBlock($modelTemplate, 'logsActivity');
        $modelTemplate = $this->addMedia ? $this->removeTag($modelTemplate, 'hasMedia') : $this->removeBlock($modelTemplate, 'hasMedia');
        $modelTemplate = str_replace(['{{modelName}}'], $this->name, $modelTemplate);

        file_put_contents(app_path("{$this->name}.php"), $modelTemplate);

        $this->info('Model created');
    }

    protected function createPolicy()
    {
        $file = base_path('/app/Providers/AuthServiceProvider.php');

        $contents = preg_replace(
            '/(\[.*)(\,).*]/ms',
            "$1,\n" . $this->addSpaces(8) . "'App\\{$this->name}' => 'App\Policies\\{$this->name}Policy',\n" . $this->addSpaces(4) . "]",
            file_get_contents($file)
        );
        file_put_contents(
            $file,
            $contents
        );

        $path = app_path('Policies');

        if (!is_dir($path)) {
            mkdir($path);
        }

        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{nameVariable}}',
            ],
            [
                $this->name,
                $this->nameVariable,
            ],
            $this->getStub('policy')
        );

        file_put_contents("{$path}\\{$this->name}Policy.php", $controllerTemplate);

        $this->info('Policy created');

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
                '{{relatedModel}}',
                '{{relatedVariable}}',
                '{{relation}}'
            ],
            [
                $this->name,
                $this->pluralNameVariable,
                $this->nameVariable,
                $allowedFilters,
                $this->related['name'],
                camel_case($this->related['name']),
                snake_case($this->related['type'] === 'hasMany' ? str_plural($this->name) : $this->name)
            ],
            $this->getStub('controller')
        );

        if ($this->related) {
            $controllerTemplate = $this->removeBlock($controllerTemplate, 'notRelated');
            $controllerTemplate = $this->removeTag($controllerTemplate, 'hasRelated');
        } else {
            $controllerTemplate = $this->removeBlock($controllerTemplate, 'hasRelated');
            $controllerTemplate = $this->removeTag($controllerTemplate, 'notRelated');
        }

        if ($this->assets['policy']) {
            $controllerTemplate = $this->removeTag($controllerTemplate, 'hasPolicy');
        } else {
            $controllerTemplate = $this->removeBlock($controllerTemplate, 'hasPolicy');
        }

        $path = app_path('Http/Controllers/Api');

        if (!is_dir($path)) {
            mkdir($path);
        }

        file_put_contents("{$path}\\{$this->name}Controller.php", $controllerTemplate);

        $this->info('Controller created');
    }

    private function addRoutes()
    {
        $file = base_path('routes/api.php');

        $routeName = snake_case($this->pluralName);

        if ($this->related) {
            $routeName = snake_case(str_plural($this->related['name'])) . '.' . $routeName;
        }

        file_put_contents(
            $file,
            "\nRoute::apiResource('" . $routeName . "', '{$this->name}Controller');",
            FILE_APPEND
        );
        $this->info('API route appended to routes file, please move if necessary.');
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
        $this->nameVariable = camel_case($this->name);
        $this->pluralName = str_plural($this->name);
        $this->pluralNameVariable = camel_case($this->pluralName);

        $this->fields = collect($template->fields);
        $this->related = $template->related;
        if ($this->related) {
            $relation_name = snake_case($this->related['name']);
            $this->fields->prepend(
                [
                    'type' => 'integer',
                    'default' => null,
                    'required' => true,
                    'nullable' => false,
                    'extra_rules' => '',
                    'filterable' => 'exact',
                ],
                "{$relation_name}_id"
            );
        }

        $this->logActivity = $template->logActivity;
        $this->addMedia = $template->addMedia;

        $this->assets = collect($template->assets);

        if ($this->assets['model']) {
            $this->createModel();
        }
        if ($this->assets['migration']) {
            $this->createMigration();
        }
        if ($this->assets['controller']) {
            $this->createController();
        }
        if ($this->assets['policy']) {
            $this->createPolicy();
        }
        if ($this->assets['resources']) {
            $this->createResources();
        }
        if ($this->assets['request']) {
            $this->createRequest();
        }
        if ($this->assets['factory']) {
            $this->createFactory();
        }
        if ($this->assets['routes']) {
            $this->addRoutes();
        }

    }

}
