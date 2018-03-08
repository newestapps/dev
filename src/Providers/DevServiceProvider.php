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
use Newestapps\Dev\Faker\BrazilianPersonalDocumentsFakerProvider;
use Newestapps\Dev\Faker\GeolocationFakerProvider;

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
    }

}