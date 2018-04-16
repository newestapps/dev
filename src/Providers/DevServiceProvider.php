<?php
/**
 * Created by rodrigobrun
 *   with PhpStorm
 */

namespace Newestapps\Dev\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Faker\Generator as FakerGenerator;
use Faker\Factory as FakerFactory;
use Newestapps\Dev\Commands\GeneratorCommand;
use Newestapps\Dev\Facades\StringView;
use Newestapps\Dev\Faker\BrazilianPersonalDocumentsFakerProvider;
use Newestapps\Dev\Faker\GeolocationFakerProvider;
use sngrl\StringBladeCompiler\StringBladeCompilerServiceProvider;

class DevServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FakerGenerator::class, function () {
            $faker = FakerFactory::create('pt_BR');

            $faker->addProvider(new \EmanueleMinotto\Faker\PlaceholdItProvider($faker));
            $faker->addProvider(new \NewAgeIpsum\NewAgeProvider($faker));
            $faker->addProvider(new \CronExpressionGenerator\FakerProvider($faker));
            $faker->addProvider(new BrazilianPersonalDocumentsFakerProvider($faker));
            $faker->addProvider(new GeolocationFakerProvider($faker));

            return $faker;
        });

        $this->commands([
            GeneratorCommand::class,
        ]);


        $this->app->bind('stringview', 'sngrl\StringBladeCompiler\StringView');

        /*
       * This removes the need to add a facade in the config\app
       */
        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('StringView', StringView::class);
        });
    }

}