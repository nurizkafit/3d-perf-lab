<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Library search, filters, and sort from query parameters (PRD.md §7.2).
 * Invalid values are ignored, so any shared URL renders.
 */
class AssetFilters
{
    public const SORTS = [
        'date' => ['created_at', 'desc', 'Newest first'],
        'date_asc' => ['created_at', 'asc', 'Oldest first'],
        'name' => ['name', 'asc', 'Name A–Z'],
        'size' => ['file_size', 'desc', 'Largest file'],
        'size_asc' => ['file_size', 'asc', 'Smallest file'],
        'triangles' => ['asset_metrics.triangles', 'desc', 'Most triangles'],
        'triangles_asc' => ['asset_metrics.triangles', 'asc', 'Fewest triangles'],
    ];

    public array $values;

    public function __construct(Request $request)
    {
        $date = fn ($value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
        $number = fn ($value) => is_numeric($value) && $value >= 0 ? (float) $value : null;
        $id = fn ($value) => ctype_digit((string) $value) ? (int) $value : null;

        $this->values = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 100),
            'tool' => $id($request->query('tool')),
            'author' => mb_substr(trim((string) $request->query('author', '')), 0, 100),
            'format' => in_array($request->query('format'), ['glb', 'gltf'], true) ? $request->query('format') : null,
            'uploaded_by' => $id($request->query('uploaded_by')),
            'size_min' => $number($request->query('size_min')),
            'size_max' => $number($request->query('size_max')),
            'from' => $date($request->query('from')),
            'to' => $date($request->query('to')),
            'sort' => array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'date',
            'deleted' => $request->boolean('deleted'),
            // Bulk delete works on the table view (decision D34).
            'bulk' => $request->boolean('bulk'),
            'view' => $request->query('view') === 'table' || $request->boolean('bulk') ? 'table' : 'grid',
        ];
    }

    public function apply(Builder $query): Builder
    {
        $v = $this->values;
        [$column, $direction] = self::SORTS[$v['sort']];

        return $query
            ->select('assets.*')
            ->leftJoin('asset_metrics', 'asset_metrics.asset_id', '=', 'assets.id')
            ->when($v['deleted'], fn ($q) => $q->onlyTrashed())
            ->when($v['q'] !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('assets.name', 'like', $this->like($v['q']))
                ->orWhere('assets.author', 'like', $this->like($v['q']))
                ->orWhere('assets.description', 'like', $this->like($v['q']))))
            ->when($v['tool'], fn ($q) => $q->where('assets.tool_id', $v['tool']))
            ->when($v['author'] !== '', fn ($q) => $q->where('assets.author', 'like', $this->like($v['author'])))
            ->when($v['format'], fn ($q) => $q->where('assets.format', $v['format']))
            ->when($v['uploaded_by'], fn ($q) => $q->where('assets.uploaded_by', $v['uploaded_by']))
            ->when($v['size_min'] !== null, fn ($q) => $q->where('assets.file_size', '>=', $v['size_min'] * 1048576))
            ->when($v['size_max'] !== null, fn ($q) => $q->where('assets.file_size', '<=', $v['size_max'] * 1048576))
            ->when($v['from'], fn ($q) => $q->where('assets.created_at', '>=', Carbon::parse($v['from'])->startOfDay()))
            ->when($v['to'], fn ($q) => $q->where('assets.created_at', '<=', Carbon::parse($v['to'])->endOfDay()))
            ->orderBy($column, $direction)
            ->orderBy('assets.id', 'desc');
    }

    /** True when any filter narrows the list (sort and view do not). */
    public function active(): bool
    {
        $v = $this->values;

        return $v['q'] !== '' || $v['author'] !== '' || $v['deleted']
            || collect(['tool', 'format', 'uploaded_by', 'size_min', 'size_max', 'from', 'to'])->contains(fn ($key) => $v[$key] !== null);
    }

    private function like(string $value): string
    {
        return '%'.addcslashes($value, '%_\\').'%';
    }
}
