<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Shells live in resources/views/layouts (SITEMAP.md §4): <x-layouts::public>.
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');

        // Option lists have no single model, so they use a gate instead of a policy (PRD.md §11).
        Gate::define('manage-option-lists', fn (User $user) => $user->isAdmin());
    }
}
