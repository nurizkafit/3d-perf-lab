<?php

use App\Http\Controllers\Admin\OptionListController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\AssetSearchController;
use App\Http\Controllers\Api\TestRunController;
use App\Http\Controllers\Api\UploadController as ApiUploadController;
use App\Http\Controllers\Assets\AssetController;
use App\Http\Controllers\Assets\UploadController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Lab\DeviceController;
use App\Http\Controllers\Public\ReportController;
use App\Http\Controllers\Results\ResultController;
use App\Models\DeviceClass;
use App\Support\OptionLists;
use App\Support\SampleReports;
use App\Support\SampleScreens;
use App\Support\TargetStep;
use App\Support\ViewerConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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
    Route::get('/device', [DeviceController::class, 'show'])->name('device.show');
    Route::post('/device', [DeviceController::class, 'store'])->name('device.store');

    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');
    Route::delete('/assets/bulk', [AssetController::class, 'bulkDestroy'])->name('assets.bulk-destroy');
    Route::delete('/assets/bulk/force', [AssetController::class, 'bulkForceDestroy'])->name('assets.bulk-force-destroy');
    // Bulk upload (PRD.md §7.1). Session pages are owner-only (UploadSessionPolicy).
    Route::get('/assets/upload', [UploadController::class, 'create'])->name('assets.upload');
    Route::get('/assets/upload/{session}', [UploadController::class, 'show'])->name('assets.upload.show');
    Route::get('/assets/upload/{session}/items/{item}/thumbnail', [UploadController::class, 'thumbnail'])->name('assets.upload.thumbnail');

    // JSON endpoints: session auth + CSRF (PRD.md §13).
    Route::prefix('api/uploads')->name('api.uploads.')->group(function () {
        Route::post('/sessions', [ApiUploadController::class, 'storeSession'])->name('sessions.store');
        Route::post('/sessions/{session}/files', [ApiUploadController::class, 'storeFile'])->name('files.store');
        Route::post('/sessions/{session}/register', [ApiUploadController::class, 'register'])->name('register');
        Route::patch('/items/{item}', [ApiUploadController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{item}', [ApiUploadController::class, 'destroyItem'])->name('items.destroy');
    });
    // Asset detail and actions (SITEMAP.md §3.2). Deleted assets stay reachable for detail, restore, and files.
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->withTrashed()->whereNumber('asset')->name('assets.show');
    Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->whereNumber('asset')->name('assets.edit');
    Route::put('/assets/{asset}', [AssetController::class, 'update'])->whereNumber('asset')->name('assets.update');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->whereNumber('asset')->name('assets.destroy');
    Route::post('/assets/{asset}/restore', [AssetController::class, 'restore'])->withTrashed()->whereNumber('asset')->name('assets.restore');
    Route::delete('/assets/{asset}/force', [AssetController::class, 'forceDestroy'])->withTrashed()->whereNumber('asset')->name('assets.force-destroy');
    Route::get('/assets/{asset}/download', [AssetController::class, 'download'])->withTrashed()->whereNumber('asset')->name('assets.download');
    Route::get('/assets/{asset}/files/{name}', [AssetController::class, 'file'])->withTrashed()->whereNumber('asset')->name('assets.file');
    Route::get('/assets/{asset}/thumbnail', [AssetController::class, 'thumbnail'])->withTrashed()->whereNumber('asset')->name('assets.thumbnail');

    Route::view('/lab', 'pages.lab.index')->name('lab.index');
    // Side-by-Side (M4.3) and Crowd (M4.4) pages and the benchmark page /lab/runs/{run} return with those milestones.

    // Lab JSON endpoints: session auth + CSRF (PRD.md §13).
    Route::get('/api/assets', AssetSearchController::class)->name('api.assets.index');
    Route::post('/api/tests/{test}/runs', [TestRunController::class, 'startRun'])->name('api.tests.runs.store');
    Route::post('/api/runs/{run}/invalidate', [TestRunController::class, 'invalidate'])->name('api.runs.invalidate');
    Route::post('/api/runs/{run}/cancel', [TestRunController::class, 'cancel'])->name('api.runs.cancel');

    Route::view('/results', 'pages.results.index', ['tests' => SampleScreens::tests()])->name('results.index');
    Route::get('/results/{test}', [ResultController::class, 'show'])->whereNumber('test')->name('results.show');
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

// Local-only development pages: UI kit (M1.1) and fixture viewer (M3.1).
if (app()->environment('local')) {
    Route::view('/_ui', 'dev.ui');

    Route::get('/_viewer', fn () => view('dev.viewer', [
        'fixtures' => collect(glob(base_path('fixtures/models/*/*.{glb,gltf}'), GLOB_BRACE))
            ->map(fn ($path) => Str::after($path, base_path('fixtures/models/')))
            ->values(),
        'config' => ViewerConfig::preview(),
    ]));

    Route::get('/_engine', fn (Request $request) => view('dev.engine', [
        'fixture' => 'blender/apel.glb',
        'target' => TargetStep::data($request),
        'config' => [
            'url' => route('dev.fixture', 'blender/apel.glb'),
            'classes' => DeviceClass::where('is_active', true)->get(['id', 'name', 'category', 'platform']),
        ],
    ]));

    // Streams development fixtures only; the resolved path must stay inside fixtures/models/.
    Route::get('/_fixtures/{path}', function (string $path) {
        $root = realpath(base_path('fixtures/models'));
        $file = realpath($root.'/'.$path);
        abort_unless($file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file), 404);

        return response()->file($file, [
            'Content-Type' => str_ends_with($file, '.gltf') ? 'model/gltf+json' : 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600', // lets the warm load hit the browser cache (D35)
        ]);
    })->where('path', '.*')->name('dev.fixture');
}
