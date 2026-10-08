<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Report;
use App\Models\TestSession;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

/** Library, detail, edit, delete (PRD.md §7.2–7.4, PROJECT.md M3.3 acceptance). */
class AssetLibraryTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $owner;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->owner = User::factory()->create(['name' => 'Owner Person']);
        $this->other = User::factory()->create();
        // The fixture assets in render_lab_verify must not influence counts in these tests.
        Asset::withTrashed()->update(['deleted_at' => now()->subYears(5)]);
    }

    private function makeAsset(array $attributes = [], string $format = 'glb'): Asset
    {
        $uuid = (string) Str::uuid();
        $disk = Storage::disk('local');
        if ($format === 'glb') {
            $disk->put("assets/web/{$uuid}/{$uuid}.glb", file_get_contents(base_path('fixtures/models/spline/buku.glb')));
        } else {
            $disk->put("assets/web/{$uuid}/{$uuid}.gltf", json_encode(['asset' => ['version' => '2.0'], 'buffers' => [['uri' => "{$uuid}-1.bin"]]]));
            $disk->put("assets/web/{$uuid}/{$uuid}-1.bin", str_repeat("\0", 36));
        }
        $disk->put("assets/thumbnails/{$uuid}.webp", 'RIFF....WEBP');

        $asset = Asset::create($attributes + [
            'name' => 'Asset '.Str::random(4),
            'tool_id' => Tool::where('name', 'Blender')->value('id'),
            'object_id' => $this->objectId(),
            'author' => 'Author',
            'uploaded_by' => $this->owner->id,
            'production_time_hours' => 1,
            'format' => $format,
            'web_file_path' => "assets/web/{$uuid}/{$uuid}.{$format}",
            'thumbnail_path' => "assets/thumbnails/{$uuid}.webp",
            'file_size' => 5800,
            'sha256' => hash('sha256', $uuid),
        ]);
        $asset->metrics()->create(['vertices' => 10, 'triangles' => $attributes['triangles'] ?? 5, 'meshes' => 1, 'materials' => 1, 'textures' => 0]);

        return $asset;
    }

    private function names(string $url): array
    {
        return $this->actingAs($this->owner)->get($url)->assertOk()->viewData('assets')->pluck('name')->all();
    }

    // ---------- Library: query parameters, no JavaScript needed ----------

    public function test_search_matches_name_author_and_description(): void
    {
        $this->makeAsset(['name' => 'Golden Apple']);
        $this->makeAsset(['name' => 'Chair', 'author' => 'Golden Studio']);
        $this->makeAsset(['name' => 'Lamp', 'description' => 'A golden lamp']);
        $this->makeAsset(['name' => 'Book']);

        $this->assertEqualsCanonicalizing(['Golden Apple', 'Chair', 'Lamp'], $this->names('/assets?q=golden'));
    }

    public function test_filters_by_tool_format_uploader_size_and_date(): void
    {
        $spline = Tool::where('name', 'Spline')->value('id');
        $this->makeAsset(['name' => 'A', 'tool_id' => $spline, 'file_size' => 3 * 1048576]);
        $this->makeAsset(['name' => 'B', 'uploaded_by' => $this->other->id], 'gltf');
        $old = $this->makeAsset(['name' => 'C']);
        DB::table('assets')->where('id', $old->id)->update(['created_at' => '2025-01-15 10:00:00']);

        $this->assertSame(['A'], $this->names("/assets?tool={$spline}"));
        $this->assertSame(['B'], $this->names('/assets?format=gltf'));
        $this->assertSame(['B'], $this->names("/assets?uploaded_by={$this->other->id}"));
        $this->assertSame(['A'], $this->names('/assets?size_min=2'));
        $this->assertEqualsCanonicalizing(['B', 'C'], $this->names('/assets?size_max=1'));
        $this->assertSame(['C'], $this->names('/assets?to=2025-12-31'));
        $this->assertEqualsCanonicalizing(['A', 'B'], $this->names('/assets?from=2026-01-01'));
    }

    public function test_filters_by_object_and_shows_it(): void
    {
        $bird = $this->objectId('Bird');
        $this->makeAsset(['name' => 'Bird A', 'object_id' => $bird]);
        $this->makeAsset(['name' => 'Bird B', 'object_id' => $bird]);
        $this->makeAsset(['name' => 'Chair', 'object_id' => $this->objectId('Chair')]);

        $this->assertEqualsCanonicalizing(['Bird A', 'Bird B'], $this->names("/assets?object={$bird}"));
        $this->get('/assets?view=table')->assertSee('Bird');
    }

    public function test_object_is_required_when_editing(): void
    {
        $asset = $this->makeAsset();

        $this->actingAs($this->owner)->put("/assets/{$asset->id}", ['name' => 'X', 'tool_id' => $asset->tool_id, 'object_id' => null, 'production_time_hours' => 1])
            ->assertSessionHasErrors('object_id');
    }

    public function test_sort_options(): void
    {
        $this->makeAsset(['name' => 'Zebra', 'triangles' => 50, 'file_size' => 100]);
        $this->makeAsset(['name' => 'Apple', 'triangles' => 900, 'file_size' => 900]);

        $this->assertSame(['Apple', 'Zebra'], $this->names('/assets?sort=name'));
        $this->assertSame(['Apple', 'Zebra'], $this->names('/assets?sort=triangles'));
        $this->assertSame(['Zebra', 'Apple'], $this->names('/assets?sort=size_asc'));
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        $this->makeAsset(['name' => 'Kept']);

        $this->assertSame(['Kept'], $this->names('/assets?tool=abc&format=obj&size_min=-5&from=yesterday&sort=drop&uploaded_by=1;DROP'));
    }

    public function test_the_form_keeps_the_filter_state_from_the_url(): void
    {
        $this->actingAs($this->owner)->get('/assets?q=apple&format=glb&view=table')
            ->assertSee('value="apple"', false)
            ->assertSee('<option value="glb" selected', false)
            ->assertSee('name="view" value="table"', false);
    }

    public function test_deleted_assets_are_hidden_and_listed_under_the_deleted_filter(): void
    {
        $this->makeAsset(['name' => 'Live']);
        $gone = $this->makeAsset(['name' => 'Gone']);
        $gone->delete();

        $this->assertSame(['Live'], $this->names('/assets'));
        $this->assertContains('Gone', $this->names('/assets?deleted=1'));
    }

    // ---------- Detail ----------

    public function test_detail_shows_metadata_metrics_and_the_viewer_source(): void
    {
        $asset = $this->makeAsset(['name' => 'Viewer Book']);

        $this->actingAs($this->other)->get("/assets/{$asset->id}")->assertOk()
            ->assertSee('Viewer Book')->assertSee('Owner Person')
            ->assertSee('data-src="'.route('assets.file', [$asset, basename($asset->web_file_path)]).'"', false)
            ->assertSee('Asset metrics (from the file)')
            ->assertDontSee('href="'.route('assets.edit', $asset).'"', false);
    }

    public function test_detail_lists_only_tests_the_viewer_may_see(): void
    {
        $asset = $this->makeAsset();
        $mine = TestSession::create(['name' => 'Owner test', 'method' => 'side_by_side', 'configuration_json' => [], 'target_json' => [], 'created_by' => $this->owner->id]);
        $theirs = TestSession::create(['name' => 'Other test', 'method' => 'crowd', 'configuration_json' => [], 'target_json' => [], 'created_by' => $this->other->id]);
        $asset->testSessions()->attach([$mine->id => ['position' => 1], $theirs->id => ['position' => 1]]);

        $this->actingAs($this->owner)->get("/assets/{$asset->id}")->assertSee('Owner test')->assertDontSee('Other test')->assertSee('by other users');
        $this->actingAs($this->admin)->get("/assets/{$asset->id}")->assertSee('Owner test')->assertSee('Other test');
    }

    // ---------- Ownership rules (PRD.md §4) ----------

    public function test_the_owner_can_edit_metadata_but_not_the_author(): void
    {
        $asset = $this->makeAsset(['author' => 'Original Author']);

        $this->actingAs($this->owner)->put("/assets/{$asset->id}", [
            'name' => 'Renamed', 'tool_id' => $asset->tool_id, 'object_id' => $asset->object_id, 'author' => 'Hacked Author', 'production_time_hours' => 4, 'description' => 'New text',
        ])->assertRedirect("/assets/{$asset->id}");

        $asset->refresh();
        $this->assertSame(['Renamed', 'Original Author', 'New text'], [$asset->name, $asset->author, $asset->description]);
    }

    public function test_an_admin_can_change_the_author(): void
    {
        $asset = $this->makeAsset();

        $this->actingAs($this->admin)->put("/assets/{$asset->id}", [
            'name' => $asset->name, 'tool_id' => $asset->tool_id, 'object_id' => $asset->object_id, 'author' => 'New Author', 'production_time_hours' => 1,
        ])->assertRedirect();

        $this->assertSame('New Author', $asset->fresh()->author);
    }

    public function test_another_user_cannot_edit_delete_or_restore(): void
    {
        $asset = $this->makeAsset();
        $this->actingAs($this->other);

        $this->get("/assets/{$asset->id}/edit")->assertForbidden();
        $this->put("/assets/{$asset->id}", ['name' => 'X', 'tool_id' => $asset->tool_id, 'object_id' => $asset->object_id, 'production_time_hours' => 1])->assertForbidden();
        $this->delete("/assets/{$asset->id}")->assertForbidden();
        $this->assertFalse($asset->fresh()->trashed());

        $asset->delete();
        $this->post("/assets/{$asset->id}/restore")->assertForbidden();
        $this->assertTrue($asset->fresh()->trashed());
    }

    public function test_owner_and_admin_can_soft_delete_and_restore(): void
    {
        $asset = $this->makeAsset();

        $this->actingAs($this->owner)->delete("/assets/{$asset->id}")->assertRedirect();
        $this->assertTrue($asset->fresh()->trashed());
        $this->get("/assets/{$asset->id}")->assertOk()->assertSee('Deleted on');

        $this->actingAs($this->admin)->post("/assets/{$asset->id}/restore")->assertRedirect();
        $this->assertFalse($asset->fresh()->trashed());
    }

    public function test_only_an_admin_can_delete_permanently(): void
    {
        $asset = $this->makeAsset();
        $asset->delete();

        $this->actingAs($this->owner)->delete("/assets/{$asset->id}/force")->assertForbidden();

        $this->actingAs($this->admin)->delete("/assets/{$asset->id}/force")->assertRedirect();
        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
        Storage::disk('local')->assertMissing($asset->web_file_path);
        Storage::disk('local')->assertMissing($asset->thumbnail_path);
    }

    public function test_permanent_delete_is_blocked_for_an_asset_used_in_a_published_report(): void
    {
        $asset = $this->makeAsset();
        $test = TestSession::create(['name' => 'Published test', 'method' => 'side_by_side', 'configuration_json' => [], 'target_json' => [], 'created_by' => $this->owner->id]);
        $asset->testSessions()->attach($test->id, ['position' => 1]);
        Report::create(['test_session_id' => $test->id, 'title' => 'Public', 'question' => 'Q?', 'status' => 'published', 'public_slug' => 'public-'.Str::random(6), 'published_at' => now(), 'created_by' => $this->owner->id]);
        $asset->delete();

        $this->actingAs($this->admin)->delete("/assets/{$asset->id}/force")->assertSessionHasErrors('item');

        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
        Storage::disk('local')->assertExists($asset->web_file_path);
    }

    public function test_permanent_delete_is_blocked_for_an_asset_used_by_any_test(): void
    {
        $asset = $this->makeAsset();
        $test = TestSession::create(['name' => 'Draft test', 'method' => 'side_by_side', 'configuration_json' => [], 'target_json' => [], 'created_by' => $this->owner->id]);
        $asset->testSessions()->attach($test->id, ['position' => 1]);

        $this->actingAs($this->admin)->delete("/assets/{$asset->id}/force")->assertSessionHasErrors('item');
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
    }

    // ---------- Files ----------

    public function test_download_streams_a_glb_and_zips_a_gltf(): void
    {
        $glb = $this->makeAsset(['name' => 'Single File']);
        $this->actingAs($this->other)->get("/assets/{$glb->id}/download")
            ->assertOk()->assertDownload('single-file.glb');

        $gltf = $this->makeAsset(['name' => 'Many Files'], 'gltf');
        $response = $this->get("/assets/{$gltf->id}/download")->assertOk()->assertDownload('many-files.zip');
        $zip = new ZipArchive;
        $zip->open($response->baseResponse->getFile()->getPathname());
        $this->assertSame(2, $zip->numFiles);
    }

    public function test_viewer_files_are_served_by_stored_name_only(): void
    {
        $asset = $this->makeAsset([], 'gltf');
        $uuid = basename(dirname($asset->web_file_path));
        $this->actingAs($this->other);

        $this->get("/assets/{$asset->id}/files/{$uuid}.gltf")->assertOk()->assertHeader('Content-Type', 'model/gltf+json');
        $this->get("/assets/{$asset->id}/files/{$uuid}-1.bin")->assertOk();
        $this->get("/assets/{$asset->id}/files/..%2F..%2Fthumbnails%2F{$uuid}.webp")->assertNotFound();
        $this->get("/assets/{$asset->id}/files/missing.bin")->assertNotFound();
    }

    public function test_guests_cannot_reach_asset_files(): void
    {
        $asset = $this->makeAsset();
        $name = basename($asset->web_file_path);

        $this->get("/assets/{$asset->id}/files/{$name}")->assertRedirect('/login');
        $this->get("/assets/{$asset->id}/download")->assertRedirect('/login');
        $this->get("/assets/{$asset->id}/thumbnail")->assertRedirect('/login');
    }

    public function test_the_thumbnail_is_served(): void
    {
        $asset = $this->makeAsset();

        $this->actingAs($this->other)->get("/assets/{$asset->id}/thumbnail")->assertOk();
    }

    // ---------- Bulk delete (decision D34) ----------

    public function test_bulk_mode_shows_checkboxes_and_marks_assets_used_in_tests(): void
    {
        $used = $this->makeAsset(['name' => 'Used one']);
        $test = TestSession::create(['name' => 'T', 'method' => 'side_by_side', 'configuration_json' => [], 'target_json' => [], 'created_by' => $this->owner->id]);
        $used->testSessions()->attach($test->id, ['position' => 1]);
        $this->makeAsset(['name' => 'Free one']);

        $this->actingAs($this->admin)->get('/assets?bulk=1')->assertOk()
            ->assertSee('name="ids[]"', false)
            ->assertSee('Used in 1 test')
            ->assertSee('Delete permanently');

        $this->actingAs($this->owner)->get('/assets?bulk=1')->assertOk()
            ->assertSee('name="ids[]"', false)
            ->assertDontSee('Delete permanently');
    }

    public function test_bulk_soft_delete_deletes_own_assets_and_skips_others(): void
    {
        $mine = $this->makeAsset();
        $theirs = $this->makeAsset(['uploaded_by' => $this->other->id]);

        $this->actingAs($this->owner)->from('/assets?bulk=1')
            ->delete('/assets/bulk', ['ids' => [$mine->id, $theirs->id]])
            ->assertRedirect('/assets?bulk=1')
            ->assertSessionHas('status', 'Deleted 1 asset. They can be restored. Skipped 1 that you do not own.');

        $this->assertTrue($mine->fresh()->trashed());
        $this->assertFalse($theirs->fresh()->trashed());
    }

    public function test_an_admin_bulk_soft_deletes_any_assets(): void
    {
        $a = $this->makeAsset();
        $b = $this->makeAsset(['uploaded_by' => $this->other->id]);

        $this->actingAs($this->admin)->delete('/assets/bulk', ['ids' => [$a->id, $b->id]])->assertSessionHasNoErrors();

        $this->assertTrue($a->fresh()->trashed());
        $this->assertTrue($b->fresh()->trashed());
    }

    public function test_bulk_permanent_delete_skips_assets_used_in_tests(): void
    {
        $free = $this->makeAsset(['name' => 'Free']);
        $used = $this->makeAsset(['name' => 'Used']);
        $test = TestSession::create(['name' => 'T', 'method' => 'side_by_side', 'configuration_json' => [], 'target_json' => [], 'created_by' => $this->owner->id]);
        $used->testSessions()->attach($test->id, ['position' => 1]);

        $this->actingAs($this->admin)->delete('/assets/bulk/force', ['ids' => [$free->id, $used->id]])
            ->assertSessionHas('status', 'Permanently deleted 1 asset and their files. Skipped 1 used in tests.');

        $this->assertDatabaseMissing('assets', ['id' => $free->id]);
        Storage::disk('local')->assertMissing($free->web_file_path);
        $this->assertDatabaseHas('assets', ['id' => $used->id]);
    }

    public function test_bulk_permanent_delete_also_works_on_deleted_assets(): void
    {
        $asset = $this->makeAsset();
        $asset->delete();

        $this->actingAs($this->admin)->delete('/assets/bulk/force', ['ids' => [$asset->id]])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    public function test_a_user_cannot_bulk_delete_permanently(): void
    {
        $asset = $this->makeAsset();

        $this->actingAs($this->owner)->delete('/assets/bulk/force', ['ids' => [$asset->id]])->assertForbidden();
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
    }

    public function test_bulk_delete_needs_a_selection(): void
    {
        $this->actingAs($this->owner)->delete('/assets/bulk', ['ids' => []])->assertSessionHasErrors('ids');
        $this->actingAs($this->owner)->delete('/assets/bulk', ['ids' => ['x']])->assertSessionHasErrors('ids.0');
    }
}
