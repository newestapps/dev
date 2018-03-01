<?php
/**
 * Created by rodrigobrun
 *   with PhpStorm
 */

namespace Newestapps\Dev\Providers;

use Illuminate\Support\ServiceProvider;
use Faker\Generator as FakerGenerator;
use Faker\Factory as FakerFactory;
use Newestapps\Dev\Faker\BrazilianPersonalDocumentsFakerProviderProvider;
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
            $faker->addProvider(new EmanueleMinotto\Faker\PlaceholdItProvider($faker));
            $faker->addProvider(new NewAgeIpsum\NewAgeProvider($faker));
            $faker->addProvider(new CronExpressionGenerator\FakerProvider($faker));
            $faker->addProvider(new BrazilianPersonalDocumentsFakerProviderProvider($faker));
            $faker->addProvider(new GeolocationFakerProvider($faker));

            return $faker;
        });
    }

}