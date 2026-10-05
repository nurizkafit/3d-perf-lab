<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\ConfirmDeviceRequest;
use App\Models\DeviceClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Device class confirmation (BENCHMARK.md §7.1). The browser suggests a class; the suggestion is never
 * stored. The user confirms one from the admin-managed list; it is kept in the session.
 */
class DeviceController extends Controller
{
    public function show(Request $request): View
    {
        return view('pages.device.show', [
            'classes' => DeviceClass::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'category', 'platform']),
            'currentId' => $request->session()->get('device_class_id'),
        ]);
    }

    public function store(ConfirmDeviceRequest $request): RedirectResponse
    {
        $request->session()->put('device_class_id', (int) $request->validated('device_class_id'));
        $class = DeviceClass::find($request->validated('device_class_id'));

        return redirect()->to($request->validated('return_to') ?: route('dashboard'))
            ->with('status', "This device is now recorded as {$class->name}.");
    }
}
