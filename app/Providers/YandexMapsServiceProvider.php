<?php

namespace App\Providers;

use App\Services\YandexMaps\ApiClient;
use App\Services\YandexMaps\HtmlParser;
use App\Services\YandexMaps\ParserOrchestrator;
use App\Services\YandexMaps\YandexMapsConfig;
use Illuminate\Support\ServiceProvider;

class YandexMapsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HtmlParser::class);

        $this->app->singleton(YandexMapsConfig::class, function ($app) {
            return YandexMapsConfig::fromConfig();
        });

        $this->app->scoped(ApiClient::class);

        $this->app->bind(ParserOrchestrator::class);
    }

    public function boot(): void
    {
        //
    }
}
