<?php

namespace Railroad\Railcontent\Providers;

use Illuminate\Database\Events\StatementPrepared;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use PDO;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Events\ContentUpdated;
use Railroad\Railcontent\Listeners\VersionContentEventListener;

class RailcontentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // this makes all database calls return arrays rather than objects
        $this->listen = [
            StatementPrepared::class => [
                function ($event) {
                    $event->statement->setFetchMode(PDO::FETCH_ASSOC);
                }
            ],
            ContentUpdated::class => [VersionContentEventListener::class . '@handle']
        ];

        parent::boot();

        $this->setupConfig();

        $this->publishes(
            [
                __DIR__ . '/../../config/railcontent.php' => config_path('railcontent.php'),
            ]
        );

        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');

        //load package routes file
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }

    private function setupConfig()
    {
        // Caching
        ConfigService::$cacheTime = config('railcontent.cache_duration');

        // Database
        ConfigService::$databaseConnectionName = config('railcontent.database_connection_name');

        // Tables
        ConfigService::$tableContent = config('railcontent.tables.content');
        ConfigService::$tableVersions = config('railcontent.tables.versions');
        ConfigService::$tableFields = config('railcontent.tables.fields');
        ConfigService::$tableContentFields = config('railcontent.tables.content_fields');
        ConfigService::$tableData = config('railcontent.tables.data');
        ConfigService::$tableContentData = config('railcontent.tables.content_data');
        ConfigService::$tablePermissions = config('railcontent.tables.permissions');
        ConfigService::$tableContentPermissions = config('railcontent.tables.content_permissions');
        ConfigService::$tableUserContent = config('railcontent.tables.user_content');
        ConfigService::$tablePlaylists = config('railcontent.tables.playlists');
        ConfigService::$tableUserContentPlaylists = config('railcontent.tables.user_content_playlists');
}
}