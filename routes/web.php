<?php

use App\Http\Controllers\Admin\OptionListController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Public\ReportController;
use App\Support\MetricDefinitions;
use App\Support\OptionLists;
use App\Support\SampleReports;
use App\Support\SampleScreens;
use Illuminate\Support\Facades\Route;

// Public site (SITEMAP.md §2).
Route::view('/', 'pages.public.landing')->name('home');
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/{slug}', [ReportController::class, 'show'])->name('reports.show');
Route::view('/about', 'pages.public.about')->name('about');
Route::view('/contact', 'pages.public.contact')->name('contact');

// Authentication (SITEMAP.md §2, §3.1). No registration route.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Application: signed-in, active accounts only. Screens still show sample data until their milestones.
Route::middleware(['auth', 'active'])->group(function () {
    Route::view('/dashboard', 'pages.dashboard.index', ['tests' => SampleScreens::tests(), 'assets' => SampleScreens::assets()])->name('dashboard');
    Route::view('/device', 'pages.device.show')->name('device.show');

    Route::view('/assets', 'pages.assets.index', ['assets' => SampleScreens::assets()])->name('assets.index');
    Route::view('/assets/upload', 'pages.assets.upload', ['rows' => SampleScreens::uploadRows()])->name('assets.upload');
    Route::view('/assets/{asset}', 'pages.assets.show', ['item' => SampleScreens::asset()])->name('assets.show');
    Route::view('/assets/{asset}/edit', 'pages.assets.edit', ['item' => SampleScreens::asset()])->name('assets.edit');

    Route::view('/lab', 'pages.lab.index')->name('lab.index');
    Route::view('/lab/comparison', 'pages.lab.comparison', ['assets' => array_slice(SampleScreens::assets(), 0, 3), 'budgetItems' => MetricDefinitions::budgetItemsFor('comparison')])->name('lab.comparison');
    Route::view('/lab/runs/{run}', 'pages.lab.run')->name('lab.run');

    Route::view('/results', 'pages.results.index', ['tests' => SampleScreens::tests()])->name('results.index');
    Route::view('/results/{test}', 'pages.results.show', ['report' => SampleReports::all()[0], 'assets' => array_slice(SampleScreens::assets(), 0, 3), 'budgetItems' => MetricDefinitions::BUDGET_ITEMS, 'metrics' => MetricDefinitions::METRICS])->name('results.show');
    Route::view('/results/{test}/report', 'pages.results.report', ['report' => SampleReports::all()[0], 'assets' => array_slice(SampleScreens::assets(), 0, 3), 'standardLimitations' => SampleReports::STANDARD_LIMITATIONS])->name('results.report');

    // Administration: admins only (SITEMAP.md §3.5).
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::prefix('options/{list}')->name('options.')->whereIn('list', array_keys(OptionLists::LISTS))->group(function () {
            Route::get('/', [OptionListController::class, 'index'])->name('index');
            Route::get('/create', [OptionListController::class, 'create'])->name('create');
            Route::post('/', [OptionListController::class, 'store'])->name('store');
            Route::get('/{item}/edit', [OptionListController::class, 'edit'])->whereNumber('item')->name('edit');
            Route::put('/{item}', [OptionListController::class, 'update'])->whereNumber('item')->name('update');
            Route::post('/{item}/deactivate', [OptionListController::class, 'deactivate'])->whereNumber('item')->name('deactivate');
            Route::post('/{item}/activate', [OptionListController::class, 'activate'])->whereNumber('item')->name('activate');
            Route::post('/{item}/default', [OptionListController::class, 'makeDefault'])->whereNumber('item')->name('default');
            Route::post('/{item}/reorder', [OptionListController::class, 'reorder'])->whereNumber('item')->name('reorder');
            Route::delete('/{item}', [OptionListController::class, 'destroy'])->whereNumber('item')->name('destroy');
        });
    });
});

// Kitchen-sink page for the UI kit (M1.1). Local only.
if (app()->environment('local')) {
    Route::view('/_ui', 'dev.ui');
}
