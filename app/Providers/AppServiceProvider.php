<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        if(config('app.env') == "production" || config('app.env') == "staging")
        {
            $this->app->bind('path.public', function() {
                return base_path().'/../public_html';
              });
        }
    }

      /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Force HTTPS for all URLs in production or staging
        if (config('app.env') == 'production' || config('app.env') == 'staging') {
            URL::forceScheme('https');
        }
    }
}
