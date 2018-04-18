<?php

namespace Newestapps\Dev\Commands;

use App\Boleto;
use App\Event;
use ColorThief\ColorThief;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use sngrl\StringBladeCompiler\Facades\StringView;

class GeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nw:make {generator} {model} {--namespace=} {--tableName=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Newestapps Generator';

    private $variables = [];

    /**
     * Create a new command instance.
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
        $generator = $this->argument('generator');
        $model = $this->argument('model');

        if (in_array($generator, [
            'domain',
        ])) {
            $fullGenerator = "__make_{$generator}";
            if (method_exists($this, $fullGenerator)) {
                $this->$fullGenerator($model);
            }
        } else {
            $this->runStand($generator, $model);
        }
    }

    public function runStand($generator, $model)
    {
        $model = ucfirst($model);
        $modelClassBaseName = $model;
        $modelClass = "App\\{$model}";

        $this->variables['model'] = $model;
        $this->variables['modelClassBaseName'] = $modelClassBaseName;
        $this->variables['modelClass'] = $modelClass;
        $this->variables['varName'] = camel_case(snake_case($model));
        $this->variables['pluralVarName'] = str_plural($this->variables['varName']);
        $this->variables['tableName'] = (empty($this->option('tableName'))) ? (snake_case(str_plural($model))) : ($this->option('tableName'));
        $this->variables['usingCustomTableName'] = !empty($this->option('tableName'));
        $this->variables['tableExists'] = false;

        $modelExists = class_exists($modelClass);
        $columns = [];
        $modelInstance = null;
        if ($modelExists) {
            $modelInstance = ($modelExists) ? (new $modelClass()) : (null);

            if ($modelInstance !== null) {
                $table = $modelInstance->getTable();
                try {
                    $columns = \DB::select(\DB::raw('SHOW COLUMNS FROM '.$table));
                    $this->variables['tableExists'] = true;
                } catch (\Exception $e) {
                }
            }

        } else {
            try {
                $columns = \DB::select(\DB::raw('SHOW COLUMNS FROM '.$this->variables['tableName']));
                $this->variables['tableExists'] = true;
            } catch (\Exception $e) {
            }
        }

        $modelColumns = [];
        $modelColumnsFillable = [];
        $modelColumnsHidden = [];
        foreach ($columns as $c) {
            $modelColumns[]['name'] = $c->Field;

            if (!in_array($c->Field, ['id', 'own_id', 'uuid', 'created_at', 'updated_at', 'deleted_at'])) {
                $modelColumnsFillable[]['name'] = $c->Field;
            }

            if (in_array($c->Field, ['password', 'access_token', 'refresh_token', 'client_secret'])) {
                $modelColumnsHidden[]['name'] = $c->Field;
            }
        }

        $this->variables['modelExists'] = $modelExists;
        $this->variables['modelColumns'] = $modelColumns;
        $this->variables['modelColumnsFillable'] = $modelColumnsFillable;
        $this->variables['modelColumnsHidden'] = $modelColumnsHidden;
        $this->variables['modelInstance'] = $modelInstance;

        // Run generators
        $genChars = preg_split('//', $generator, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($genChars as $gen) {
            $met = "_{$gen}";
            if (method_exists($this, $met)) {
                $this->$met($model, $modelClass, $modelExists, $modelInstance);
            }
        }
    }

    private function className($model, $suffix = '')
    {
        $className = $model;
        if (strlen($suffix) > 0 && substr($model, -(strlen($suffix)), strlen($suffix)) !== $suffix) {
            $className = "{$model}{$suffix}";
        }

        $this->variables['className'] = $className;

        return $className;
    }

    private function namespace($namespace = 'App\\')
    {
        $this->variables['namespace'] = $namespace;
    }

    private function getNamespaceOption($prefix = '\\', $suffix = '')
    {
        $namespaceOption = $this->option('namespace');
        $namespaceOption = (!empty(trim($namespaceOption))) ? ($prefix.ucfirst($namespaceOption).$suffix) : ('');

        return $namespaceOption;
    }

    private function checkExistentFile($file, $hint)
    {
        if (file_exists($file)) {
            $override = $this->confirm("The file you're about to generate already exists! Override existent file? ({$hint})",
                false);

            return $override;
        }

        return true;
    }

    private function generate($stub, $saveTo, array $data = [])
    {
        $filename = $this->variables['className'].'.php';
        $file = $saveTo.'/'.$filename;

        if (!$this->checkExistentFile($file, $filename)) {
            return;
        }

        $stubFile = __DIR__.'/../../stub/'.$stub;
        if (file_exists($stubFile)) {
            $template = file_get_contents($stubFile);

            $m = new \Mustache_Engine();
            $rendered = $m->render($template, array_merge($this->variables, $data));

            try {
                \File::makeDirectory($saveTo);
            } catch (\Exception $e) {
            }

            file_put_contents($file, $rendered);

            if (file_exists($file)) {
                $this->info('File created -> '.$filename);
            } else {
                $this->error('Error creating file -> '.$filename);
            }
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////

    /** TRANSFORMER */
    private function _t($model, $modelClass, $modelExists, $modelInstance)
    {
        $this->namespace('App\\Transformers');
        $this->className($model, 'Transformer');

        $this->generate('Transformer.stub', app_path('Transformers'));
    }

    /** MODEL */
    private function _m($model, $modelClass, $modelExists, $modelInstance)
    {
        $this->namespace('App');
        $this->className(str_singular($model), '');

        $this->generate('Model.stub', app_path(''));

        if ($this->variables['tableExists']) {
            \Artisan::call('ide-helper:models', [
                '-W' => true,
                '-n' => true,
            ]);
        }
    }

    /** CONTROLLER */
    private function _c($model, $modelClass, $modelExists, $modelInstance)
    {
        $this->namespace('App\\Http\\Controllers'.$this->getNamespaceOption('\\'));
        $this->className($model, 'Controller');

        $this->generate('Controller.stub', app_path('Http/Controllers'.$this->getNamespaceOption('/')), [
            'repositoryBaseClassName' => "{$model}Repository",
            'transformerBaseClassName' => "{$model}Transformer",
            'requestBaseNameClass' => "{$model}Request",
            'responseBaseNameClass' => "{$model}Response",
        ]);

        $this->output->block("Controller Routes Setup Instructions ---------------------------------------------------".
            "\n  - Add this line to your routes file");
        $this->warn("  Route::resource('{$this->variables['pluralVarName']}', '{$this->getNamespaceOption('', '\\\\')}{$this->variables['className']}');");
        $this->line('');
    }

    /** REPOSITORY */
    private function _r($model, $modelClass, $modelExists, $modelInstance)
    {
        $this->namespace('App\\Repositories');
        $this->className($model, 'Repository');

        $repositoryModelName = $this->variables['className'];
        $repositoryClassName = $this->variables['namespace'].'\\'.$this->variables['className'];

        $this->generate('Repository.stub', app_path('Repositories'));

        ////////

        $this->namespace('App\\Repositories\\DataSource');
        $this->className($model, 'RepositoryEloquent');

        $this->generate('RepositoryEloquent.stub', app_path('Repositories/DataSource'), [
            'repositoryModelName' => $repositoryModelName,
            'repositoryClassName' => $repositoryClassName,
        ]);

        $this->output->block("Repository Setup Instructions----------------------------------------------------------".
            "\n - Add this line to your AppServiceProvider(register method)");
        $this->warn("  \$this->app->bind(\\{$repositoryClassName}::class, \\{$this->variables['namespace']}\\{$this->variables['className']}::class);");
        $this->line('');
    }

    ////////////////////////////////////////////////////////////////////////////////
    /// PRESETS
    ////////////////////////////////////////////////////////////////////////////////

    private function __make_domain($model)
    {
        $this->runStand('t', $model);
        $this->runStand('r', $model);
        $this->runStand('c', $model);
    }
}
