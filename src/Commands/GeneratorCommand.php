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
    protected $signature = 'nw:make {generator} {model}';

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

        $model = ucfirst($model);
        $modelClass = "App\\{$model}";

        $modelExists = class_exists($modelClass);
        $modelInstance = ($modelExists) ? (new $modelClass()) : (null);

        $this->variables['model'] = $model;
        $this->variables['modelClass'] = $modelClass;
        $this->variables['modelExists'] = $modelExists;
        $this->variables['modelInstance'] = $modelInstance;

        $table = $this->variables['modelInstance']->getTable();
        $columns = \DB::select(\DB::raw('SHOW COLUMNS FROM '.$table));

        $modelColumns = [];
        foreach ($columns as $c) {
            $modelColumns[]['name'] = $c->Field;
        }

        $this->variables['modelColumns'] = $modelColumns;

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

    private function generate($stub, $saveTo, array $data = [])
    {
        $stubFile = __DIR__.'/../../stub/'.$stub;
        if (file_exists($stubFile)) {
            $template = file_get_contents($stubFile);

            $m = new \Mustache_Engine();
            $rendered = $m->render($template, array_merge($this->variables, $data));

            try {
                \File::makeDirectory($saveTo);
            } catch (\Exception $e) {
            }

            $filename = $this->variables['className'].'.php';
            $file = $saveTo.'/'.$filename;
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


}
