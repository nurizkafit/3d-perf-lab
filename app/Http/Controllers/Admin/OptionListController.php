<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OptionActionRequest;
use App\Http\Requests\Admin\OptionBulkRequest;
use App\Http\Requests\Admin\OptionItemRequest;
use App\Models\DeviceClass;
use App\Support\OptionLists;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Admin management of the option lists (PRD.md §11, SITEMAP.md §3.5). */
class OptionListController extends Controller
{
    public function index(string $list): View
    {
        $items = OptionLists::query($list)
            ->when($list === 'targets', fn ($query) => $query->with('deviceClasses'))
            ->get();

        return view('pages.admin.options.index', [
            'list' => $list,
            'definition' => OptionLists::definition($list),
            'items' => $items->map(fn (Model $item) => [
                'model' => $item,
                'summary' => OptionLists::summary($list, $item),
                'used' => OptionLists::isUsed($list, $item),
            ]),
        ]);
    }

    public function create(string $list): View
    {
        return $this->form($list, null);
    }

    public function edit(string $list, int $item): View
    {
        return $this->form($list, OptionLists::find($list, $item));
    }

    public function store(OptionItemRequest $request, string $list): RedirectResponse
    {
        $definition = OptionLists::definition($list);

        $item = DB::transaction(function () use ($request, $list, $definition) {
            $item = new $definition['model'](OptionLists::attributesFromInput($list, $request->validated()));
            $item->sort_order = (int) OptionLists::query($list)->max('sort_order') + 1;
            $item->save();
            $this->syncDeviceClasses($list, $item, $request->validated());

            return $item;
        });

        return redirect()->route('admin.options.index', $list)->with('status', "Added {$item->name}.");
    }

    public function bulkCreate(string $list): View
    {
        abort_unless(in_array($list, OptionLists::BULK_LISTS, true), 404);

        return view('pages.admin.options.bulk', ['list' => $list, 'definition' => OptionLists::definition($list)]);
    }

    /** One name per line. Blank lines and names that already exist (any case) are skipped and reported. */
    public function bulkStore(OptionBulkRequest $request, string $list): RedirectResponse
    {
        $definition = OptionLists::definition($list);
        $existing = OptionLists::query($list)->pluck('name')->map(fn ($name) => mb_strtolower($name))->all();
        $added = [];
        $skipped = [];

        DB::transaction(function () use ($request, $list, $definition, &$existing, &$added, &$skipped) {
            $order = (int) OptionLists::query($list)->max('sort_order');

            foreach ($request->names() as $name) {
                if (in_array(mb_strtolower($name), $existing, true)) {
                    $skipped[] = $name;

                    continue;
                }

                $item = new $definition['model'](OptionLists::attributesFromInput($list, ['name' => $name]));
                $item->sort_order = ++$order;
                $item->save();
                $existing[] = mb_strtolower($name);
                $added[] = $name;
            }
        });

        $message = 'Added '.count($added).' '.($definition['singular']).(count($added) === 1 ? '' : 's').'.';
        if ($skipped) {
            $message .= ' Skipped '.count($skipped).' that already exist: '.implode(', ', $skipped).'.';
        }

        return redirect()->route('admin.options.index', $list)->with('status', $message);
    }

    public function update(OptionItemRequest $request, string $list, int $item): RedirectResponse
    {
        $model = OptionLists::find($list, $item);

        DB::transaction(function () use ($request, $list, $model) {
            $model->update(OptionLists::attributesFromInput($list, $request->validated()));
            $this->syncDeviceClasses($list, $model, $request->validated());
        });

        return redirect()->route('admin.options.index', $list)->with('status', "Saved {$model->name}.");
    }

    public function deactivate(OptionActionRequest $request, string $list, int $item): RedirectResponse
    {
        $model = OptionLists::find($list, $item);

        if ($model->is_default ?? false) {
            return back()->withErrors(['item' => "{$model->name} is the default. Choose another default first."]);
        }

        $model->update(['is_active' => false]);

        return back()->with('status', "Deactivated {$model->name}. It no longer appears in new selections.");
    }

    public function activate(OptionActionRequest $request, string $list, int $item): RedirectResponse
    {
        $model = OptionLists::find($list, $item);
        $model->update(['is_active' => true]);

        return back()->with('status', "Activated {$model->name}.");
    }

    public function makeDefault(OptionActionRequest $request, string $list, int $item): RedirectResponse
    {
        abort_unless(OptionLists::definition($list)['default'], 404);
        $model = OptionLists::find($list, $item);

        if (! $model->is_active) {
            return back()->withErrors(['item' => "Activate {$model->name} before making it the default."]);
        }

        DB::transaction(function () use ($list, $model) {
            OptionLists::query($list)->where('is_default', true)->update(['is_default' => false]);
            $model->update(['is_default' => true]);
        });

        return back()->with('status', "{$model->name} is now the default.");
    }

    /** Move an item one place up or down, then store positions 0..n in display order. */
    public function reorder(OptionActionRequest $request, string $list, int $item): RedirectResponse
    {
        $items = OptionLists::query($list)->get()->all();
        $index = collect($items)->search(fn ($candidate) => $candidate->id === $item);
        abort_if($index === false, 404);
        $target = $request->validated('direction') === 'up' ? $index - 1 : $index + 1;

        if (isset($items[$target])) {
            [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
            DB::transaction(function () use ($items) {
                foreach ($items as $position => $model) {
                    $model->update(['sort_order' => $position]);
                }
            });
        }

        return back();
    }

    /** Only an item that no record uses can be deleted (PRD.md §11). */
    public function destroy(OptionActionRequest $request, string $list, int $item): RedirectResponse
    {
        $model = OptionLists::find($list, $item);

        if (OptionLists::isUsed($list, $model)) {
            return back()->withErrors(['item' => "{$model->name} is in use, so it cannot be deleted. Deactivate it instead."]);
        }
        if ($model->is_default ?? false) {
            return back()->withErrors(['item' => "{$model->name} is the default. Choose another default first."]);
        }

        $model->delete();

        return back()->with('status', "Deleted {$model->name}.");
    }

    private function form(string $list, ?Model $item): View
    {
        $item?->loadMissing($list === 'targets' ? ['deviceClasses'] : []);

        return view('pages.admin.options.form', [
            'list' => $list,
            'definition' => OptionLists::definition($list),
            'item' => $item,
            'values' => OptionLists::formValues($list, $item),
            'deviceClasses' => $list === 'targets' ? DeviceClass::orderBy('sort_order')->get() : collect(),
        ]);
    }

    private function syncDeviceClasses(string $list, Model $item, array $validated): void
    {
        if ($list === 'targets') {
            $item->deviceClasses()->sync($validated['device_class_ids'] ?? []);
        }
    }
}
