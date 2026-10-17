<?php

namespace App\Services;

use App\Models\Report;
use App\Models\TestSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Report drafts, public asset approval, publish and unpublish (PRD.md §10.4). Reports are private by default.
 * Publishing needs a question, at least one approved asset, and a completed run. Tokens change on every publish and
 * unpublish, so an old public file URL stops working at once; the slug stays the same on republish.
 */
class ReportService
{
    /** The test's report, or an unsaved draft with defaults. */
    public function forTest(TestSession $test, User $user): Report
    {
        return $test->report ?? new Report(['test_session_id' => $test->id, 'title' => $test->name, 'status' => 'draft', 'created_by' => $user->id]);
    }

    /** @param array{title: string, question?: ?string, limitations?: ?string, conclusion?: ?string, approved?: list<int>} $data */
    public function save(TestSession $test, User $user, array $data): Report
    {
        return DB::transaction(function () use ($test, $user, $data) {
            $report = $this->forTest($test, $user);
            $report->fill([
                'title' => $data['title'],
                'question' => $data['question'] ?? null,
                'limitations' => $data['limitations'] ?? null,
                'conclusion' => $data['conclusion'] ?? null,
            ])->save();

            // Approval: rows for approved assets only (default: not approved). New rows get a new token.
            $approved = array_map('intval', $data['approved'] ?? []);
            $current = $report->publicAssets()->pluck('assets.id')->all();
            $report->publicAssets()->detach(array_diff($current, $approved));
            foreach (array_diff($approved, $current) as $assetId) {
                $report->publicAssets()->attach($assetId, ['token' => self::token(), 'created_at' => now()]);
            }

            return $report->fresh();
        });
    }

    /** @return list<string> what still blocks publishing (empty when it can be published) */
    public function blockers(Report $report): array
    {
        $blockers = [];
        if (! $report->exists) {
            return ['Save the draft first.'];
        }
        if (trim((string) $report->question) === '') {
            $blockers[] = 'Write the question the report answers.';
        }
        if ($report->publicAssets()->count() === 0) {
            $blockers[] = 'Approve at least one asset for public delivery.';
        }
        if ($report->test->latestCompletedRun() === null) {
            $blockers[] = 'The test needs a completed run.';
        }

        return $blockers;
    }

    public function publish(Report $report, User $user): Report
    {
        return DB::transaction(function () use ($report, $user) {
            $report->public_slug ??= $this->slug($report->title);
            $report->fill(['status' => 'published', 'published_at' => now(), 'published_by' => $user->id])->save();
            $this->rotateTokens($report);

            return $report;
        });
    }

    /** Public access ends at once: the page returns 404 and every old token is replaced. */
    public function unpublish(Report $report): Report
    {
        return DB::transaction(function () use ($report) {
            $report->update(['status' => 'draft']);
            $this->rotateTokens($report);

            return $report;
        });
    }

    private function rotateTokens(Report $report): void
    {
        foreach ($report->publicAssets()->pluck('assets.id') as $assetId) {
            $report->publicAssets()->updateExistingPivot($assetId, ['token' => self::token(), 'created_at' => now()]);
        }
    }

    /** Stable slug: title + short random suffix (PRD.md §10.4). */
    private function slug(string $title): string
    {
        do {
            $slug = trim(Str::limit(Str::slug($title) ?: 'report', 60, ''), '-').'-'.Str::lower(Str::random(6));
        } while (Report::where('public_slug', $slug)->exists());

        return $slug;
    }

    private static function token(): string
    {
        return Str::random(48);
    }
}
