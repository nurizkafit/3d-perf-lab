<?php

namespace Tests\Feature;

use App\Jobs\CleanupStagedUploads;
use App\Models\Asset;
use App\Models\Tool;
use App\Models\UploadItem;
use App\Models\UploadSession;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Bulk upload (PRD.md §7.1, PROJECT.md M3.2 acceptance). Storage is faked; nothing touches real files. */
class BulkUploadTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Person']);
        $this->user = User::factory()->create(['name' => 'Plain User']);
    }

    // ---------- Test files ----------

    private function fixture(string $path): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(basename($path), file_get_contents(base_path("fixtures/models/{$path}")));
    }

    /** One triangle as glTF JSON + .bin, optionally with a 1×1 PNG texture. */
    private function gltfFiles(string $name, bool $textured = false): array
    {
        $bin = pack('g*', 0, 0, 0, 1, 0, 0, 0, 1, 0).($textured ? pack('g*', 0, 0, 1, 0, 0, 1) : '');
        $gltf = [
            'asset' => ['version' => '2.0'],
            'scene' => 0,
            'scenes' => [['nodes' => [0]]],
            'nodes' => [['mesh' => 0]],
            'buffers' => [['uri' => "{$name}.bin", 'byteLength' => strlen($bin)]],
            'bufferViews' => [['buffer' => 0, 'byteOffset' => 0, 'byteLength' => 36]],
            'accessors' => [['bufferView' => 0, 'componentType' => 5126, 'count' => 3, 'type' => 'VEC3', 'min' => [0, 0, 0], 'max' => [1, 1, 0]]],
            'meshes' => [['primitives' => [['attributes' => ['POSITION' => 0]]]]],
        ];
        $files = [];
        if ($textured) {
            $gltf['bufferViews'][] = ['buffer' => 0, 'byteOffset' => 36, 'byteLength' => 24];
            $gltf['accessors'][] = ['bufferView' => 1, 'componentType' => 5126, 'count' => 3, 'type' => 'VEC2'];
            $gltf['meshes'][0]['primitives'][0]['attributes']['TEXCOORD_0'] = 1;
            $gltf['meshes'][0]['primitives'][0]['material'] = 0;
            $gltf['materials'] = [['pbrMetallicRoughness' => ['baseColorTexture' => ['index' => 0]]]];
            $gltf['textures'] = [['source' => 0]];
            $gltf['images'] = [['uri' => "{$name}.png"]];
            $files[] = UploadedFile::fake()->createWithContent("{$name}.png", base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        }
        array_unshift($files, UploadedFile::fake()->createWithContent("{$name}.bin", $bin));

        return [UploadedFile::fake()->createWithContent("{$name}.gltf", json_encode($gltf)), $files];
    }

    private function newSession(User $as): UploadSession
    {
        $response = $this->actingAs($as)->postJson('/api/uploads/sessions')->assertCreated();

        return UploadSession::findOrFail($response->json('id'));
    }

    private function upload(UploadSession $session, UploadedFile $file, array $dependents = [], array $extra = []): array
    {
        return $this->post("/api/uploads/sessions/{$session->id}/files", ['file' => $file, 'dependents' => $dependents] + $extra, ['Accept' => 'application/json'])
            ->assertCreated()
            ->json();
    }

    private function metrics(): array
    {
        return ['vertices' => 216, 'triangles' => 112, 'meshes' => 2, 'materials' => 2, 'textures' => 0, 'texture_dimensions' => [], 'texture_memory_estimate' => 0];
    }

    // ---------- Acceptance ----------

    public function test_five_models_in_one_session_become_five_assets(): void
    {
        $session = $this->newSession($this->admin);

        $items = [
            $this->upload($session, $this->fixture('blender/apel.glb'), [], ['metrics' => json_encode($this->metrics())]),
            $this->upload($session, $this->fixture('prisma3d/ayam.glb')),
            $this->upload($session, $this->fixture('spline/buku.glb')),
            $this->upload($session, ...$this->gltfFiles('triangle')),
            $this->upload($session, ...$this->gltfFiles('textured-triangle', textured: true)),
        ];

        $this->assertSame(['staged', 'staged', 'staged', 'staged', 'staged'], array_column($items, 'status'));
        $this->assertSame(['GLB', 'GLB', 'GLB', 'GLTF', 'GLTF'], array_column($items, 'format'));
        $this->assertSame('Textured Triangle', $items[4]['name'], 'Default name from the file name');

        // Bulk apply happens in the browser; the server receives the completed rows.
        $tool = Tool::where('name', 'Blender')->firstOrFail();
        $rows = collect($items)->mapWithKeys(fn ($item) => [$item['id'] => [
            'name' => $item['name'], 'author' => 'Studio Author', 'tool_id' => $tool->id, 'object_id' => $this->objectId(), 'production_time_minutes' => 2.5,
        ]])->all();

        $result = $this->postJson("/api/uploads/sessions/{$session->id}/register", ['rows' => $rows])->assertOk()->json();

        $this->assertCount(5, $result['registered']);
        $this->assertSame([], $result['errors']);
        $this->assertSame('completed', $result['session_status']);

        $assets = Asset::whereIn('id', array_values($result['registered']))->get();
        $this->assertCount(5, $assets);
        foreach ($assets as $asset) {
            $this->assertSame('Studio Author', $asset->author);
            $this->assertSame($this->admin->id, $asset->uploaded_by);
            Storage::disk('local')->assertExists($asset->web_file_path);
            $this->assertStringStartsWith('assets/web/', $asset->web_file_path);
        }
        $this->assertSame(216, Asset::find($result['registered'][$items[0]['id']])->metrics->vertices);
        Storage::disk('local')->assertDirectoryEmpty("staging/{$session->id}");
    }

    public function test_an_invalid_file_fails_with_a_clear_reason_and_never_becomes_an_asset(): void
    {
        $session = $this->newSession($this->admin);
        $item = $this->upload($session, UploadedFile::fake()->createWithContent('notes.glb', 'just some text'));

        $this->assertSame('invalid', $item['status']);
        $this->assertStringContainsString('Not a GLB or GLTF file', $item['error']);

        $result = $this->postJson("/api/uploads/sessions/{$session->id}/register", ['rows' => [$item['id'] => ['name' => 'Notes', 'author' => 'A', 'tool_id' => Tool::first()->id, 'object_id' => $this->objectId(), 'production_time_minutes' => 1]]])->json();
        $this->assertSame([], $result['registered']);
        $this->assertDatabaseMissing('assets', ['name' => 'Notes']);
    }

    public function test_a_user_cannot_set_the_author(): void
    {
        $session = $this->newSession($this->user);
        $item = $this->upload($session, $this->fixture('spline/buku.glb'));

        $result = $this->postJson("/api/uploads/sessions/{$session->id}/register", ['rows' => [$item['id'] => [
            'name' => 'Book', 'author' => 'Someone Else', 'tool_id' => Tool::first()->id, 'object_id' => $this->objectId(), 'production_time_minutes' => 1,
        ]]])->json();

        $this->assertSame('Plain User', Asset::find($result['registered'][$item['id']])->author);
    }

    public function test_an_admin_can_set_the_author(): void
    {
        $session = $this->newSession($this->admin);
        $item = $this->upload($session, $this->fixture('spline/buku.glb'));

        $result = $this->postJson("/api/uploads/sessions/{$session->id}/register", ['rows' => [$item['id'] => [
            'name' => 'Book', 'author' => 'Someone Else', 'tool_id' => Tool::first()->id, 'object_id' => $this->objectId(), 'production_time_minutes' => 1,
        ]]])->json();

        $this->assertSame('Someone Else', Asset::find($result['registered'][$item['id']])->author);
    }

    public function test_a_staged_file_is_not_visible_outside_its_session(): void
    {
        $session = $this->newSession($this->user);
        $item = $this->upload($session, $this->fixture('spline/buku.glb'), [], [
            'thumbnail' => UploadedFile::fake()->image('thumbnail.jpg', 64, 64),
        ]);
        $this->assertNotNull($item['thumbnail_url']);

        foreach ([User::factory()->create(), $this->admin] as $other) {
            $this->actingAs($other);
            $this->get("/assets/upload/{$session->id}")->assertForbidden();
            $this->get($item['thumbnail_url'])->assertForbidden();
            $this->post("/api/uploads/sessions/{$session->id}/files", ['file' => $this->fixture('spline/buku.glb')], ['Accept' => 'application/json'])->assertForbidden();
            $this->patchJson("/api/uploads/items/{$item['id']}", ['name' => 'X'])->assertForbidden();
            $this->deleteJson("/api/uploads/items/{$item['id']}")->assertForbidden();
            $this->postJson("/api/uploads/sessions/{$session->id}/register", ['rows' => [$item['id'] => ['name' => 'X']]])->assertForbidden();
        }

        $this->actingAs($this->user)->get("/assets/upload/{$session->id}")->assertOk()->assertSee('buku.glb');
        $this->get($item['thumbnail_url'])->assertOk();
    }

    // ---------- Validation and grouping ----------

    public function test_a_gltf_without_its_bin_fails_and_names_the_missing_file(): void
    {
        $session = $this->newSession($this->admin);
        [$gltf] = $this->gltfFiles('lonely');

        $item = $this->upload($session, $gltf);

        $this->assertSame('invalid', $item['status']);
        $this->assertStringContainsString('lonely.bin', $item['error']);
    }

    public function test_gltf_dependents_are_stored_under_generated_names(): void
    {
        $session = $this->newSession($this->admin);
        $this->upload($session, ...$this->gltfFiles('my model', textured: true));

        $staged = UploadItem::where('upload_session_id', $session->id)->firstOrFail();
        $gltf = json_decode(Storage::disk('local')->get($staged->staged_path), true);

        $this->assertDoesNotMatchRegularExpression('/my model/', $staged->staged_path);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}-1\.bin$/', $gltf['buffers'][0]['uri']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}-2\.png$/', $gltf['images'][0]['uri']);
    }

    public function test_a_gltf_pointing_outside_its_folder_is_rejected(): void
    {
        $session = $this->newSession($this->admin);
        $gltf = UploadedFile::fake()->createWithContent('evil.gltf', json_encode(['asset' => ['version' => '2.0'], 'buffers' => [['uri' => '../../.env']]]));

        $item = $this->upload($session, $gltf);

        $this->assertSame('invalid', $item['status']);
    }

    public function test_a_texture_that_is_not_an_image_is_rejected(): void
    {
        $session = $this->newSession($this->admin);
        [$gltf, $files] = $this->gltfFiles('fake-texture', textured: true);
        $files[1] = UploadedFile::fake()->createWithContent('fake-texture.png', 'not an image');

        $this->assertSame('invalid', $this->upload($session, $gltf, $files)['status']);
    }

    public function test_a_truncated_glb_is_rejected(): void
    {
        $session = $this->newSession($this->admin);
        $bytes = file_get_contents(base_path('fixtures/models/spline/buku.glb'));

        $item = $this->upload($session, UploadedFile::fake()->createWithContent('cut.glb', substr($bytes, 0, 200)));

        $this->assertSame('invalid', $item['status']);
        $this->assertStringContainsString('truncated', $item['error']);
    }

    public function test_a_file_over_the_size_limit_is_rejected(): void
    {
        config(['lab.asset_max_upload_mb' => 0.001]); // about 1 KB
        $session = $this->newSession($this->admin);

        $item = $this->upload($session, $this->fixture('spline/buku.glb'));

        $this->assertSame('invalid', $item['status']);
        $this->assertStringContainsString('larger than the maximum', $item['error']);
    }

    public function test_registration_is_per_row_with_partial_success(): void
    {
        $session = $this->newSession($this->admin);
        $good = $this->upload($session, $this->fixture('spline/buku.glb'));
        $bad = $this->upload($session, $this->fixture('prisma3d/ayam.glb'));

        $result = $this->postJson("/api/uploads/sessions/{$session->id}/register", ['rows' => [
            $good['id'] => ['name' => 'Book', 'author' => 'A', 'tool_id' => Tool::first()->id, 'object_id' => $this->objectId(), 'production_time_minutes' => 1],
            $bad['id'] => ['name' => 'Chicken', 'author' => 'A', 'tool_id' => null, 'object_id' => null, 'production_time_minutes' => -2],
        ]])->assertOk()->json();

        $this->assertArrayHasKey($good['id'], $result['registered']);
        $this->assertArrayHasKey('tool_id', $result['errors'][$bad['id']]);
        $this->assertArrayHasKey('object_id', $result['errors'][$bad['id']], 'Object is required');
        $this->assertArrayHasKey('production_time_minutes', $result['errors'][$bad['id']]);
        $this->assertSame('staged', UploadItem::find($bad['id'])->status);
        $this->assertSame('open', $result['session_status']);
    }

    public function test_an_inactive_tool_cannot_be_used(): void
    {
        $session = $this->newSession($this->admin);
        $item = $this->upload($session, $this->fixture('spline/buku.glb'));
        $tool = Tool::create(['name' => 'Retired tool', 'is_active' => false]);

        $result = $this->postJson("/api/uploads/sessions/{$session->id}/register", ['rows' => [$item['id'] => [
            'name' => 'Book', 'author' => 'A', 'tool_id' => $tool->id, 'object_id' => $this->objectId(), 'production_time_minutes' => 1,
        ]]])->json();

        $this->assertArrayHasKey('tool_id', $result['errors'][$item['id']]);
    }

    public function test_a_duplicate_of_an_own_asset_shows_a_warning_but_does_not_block(): void
    {
        $session = $this->newSession($this->admin);
        $first = $this->upload($session, $this->fixture('spline/buku.glb'));
        $this->postJson("/api/uploads/sessions/{$session->id}/register", ['rows' => [$first['id'] => ['name' => 'Book', 'author' => 'A', 'tool_id' => Tool::first()->id, 'object_id' => $this->objectId(), 'production_time_minutes' => 1]]]);

        $second = $this->upload($this->newSession($this->admin), $this->fixture('spline/buku.glb'));

        $this->assertSame('staged', $second['status']);
        $this->assertStringContainsString('already registered', $second['warning']);
    }

    public function test_metadata_is_saved_for_resume(): void
    {
        $session = $this->newSession($this->admin);
        $item = $this->upload($session, $this->fixture('spline/buku.glb'));

        $this->patchJson("/api/uploads/items/{$item['id']}", ['name' => 'Saved name', 'production_time_minutes' => 3])->assertOk();

        $this->get("/assets/upload/{$session->id}")->assertOk()->assertSee('Saved name');
    }

    public function test_remove_deletes_the_staged_files_and_keeps_the_record(): void
    {
        $session = $this->newSession($this->admin);
        $item = $this->upload($session, $this->fixture('spline/buku.glb'));
        $path = UploadItem::find($item['id'])->staged_path;

        $this->deleteJson("/api/uploads/items/{$item['id']}")->assertOk()->assertJson(['status' => 'removed']);

        Storage::disk('local')->assertMissing($path);
        $this->assertSame('removed', UploadItem::find($item['id'])->status);
    }

    public function test_cleanup_removes_abandoned_staged_files_after_the_retention_period(): void
    {
        $session = $this->newSession($this->admin);
        $item = $this->upload($session, $this->fixture('spline/buku.glb'));
        $path = UploadItem::find($item['id'])->staged_path;

        $old = now()->subDays(config('lab.staging_retention_days') + 1);
        UploadItem::whereKey($item['id'])->update(['updated_at' => $old]);
        UploadSession::whereKey($session->id)->update(['updated_at' => $old]);

        (new CleanupStagedUploads)->handle();

        Storage::disk('local')->assertMissing($path);
        $this->assertSame('removed', UploadItem::find($item['id'])->status);
        $this->assertSame('expired', $session->fresh()->status);
    }

    public function test_cleanup_keeps_recent_sessions(): void
    {
        $session = $this->newSession($this->admin);
        $item = $this->upload($session, $this->fixture('spline/buku.glb'));

        (new CleanupStagedUploads)->handle();

        Storage::disk('local')->assertExists(UploadItem::find($item['id'])->staged_path);
    }

    public function test_the_upload_page_shows_the_effective_maximum(): void
    {
        $this->actingAs($this->user)->get('/assets/upload')->assertOk()->assertSee('Maximum');
    }
}
