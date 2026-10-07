<?php

namespace App\Providers;

use App\Models\DeviceClass;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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

        // $date->display(): a copy in the display zone (config lab.display_timezone). Storage stays UTC.
        Carbon::macro('display', fn () => $this->copy()->setTimezone(config('lab.display_timezone')));

        // Option lists have no single model, so they use a gate instead of a policy (PRD.md §11).
        Gate::define('manage-option-lists', fn (User $user) => $user->isAdmin());

        // The header chip shows the device class confirmed in this session (DESIGN.md §8.1).
        View::composer('components.performance.device-chip', function ($view) {
            $id = session('device_class_id');
            $view->with('confirmedClass', $id ? DeviceClass::find($id)?->name : null);
        });
    }
}
