<?php

namespace Tests\Feature;

use App\Enums\BackupStatus;
use App\Models\DemoBackup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\TestCase;

class DemoBackupWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_verified_encrypted_demo_backup(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $sourcePath = config('database.connections.backup_demo.database');
        $sourceChecksum = hash_file('sha256', $sourcePath);

        $this->actingAs($user)
            ->post(route('demo-backups.store'))
            ->assertRedirect(route('arquitectura'))
            ->assertSessionHas('backup_status');

        $backup = DemoBackup::query()->firstOrFail();
        $encryptedArtifact = Storage::disk('local')->get($backup->artifact_path);

        $this->assertSame(BackupStatus::Success, $backup->status);
        $this->assertSame($sourceChecksum, $backup->source_sha256);
        $this->assertSame(hash('sha256', $encryptedArtifact), $backup->artifact_sha256);
        $this->assertStringNotContainsString('SQLite format 3', $encryptedArtifact);
        Storage::disk('local')->assertExists($backup->artifact_path);
    }

    public function test_authenticated_user_can_restore_a_backup_to_an_isolated_sqlite_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('demo-backups.store'));
        $backup = DemoBackup::query()->firstOrFail();

        $this->post(route('demo-backups.restore', $backup))
            ->assertRedirect(route('arquitectura'))
            ->assertSessionHas('backup_status');

        $backup->refresh();
        $restoredContents = Storage::disk('local')->get($backup->restored_path);
        $restoredDatabase = new PDO('sqlite:'.Storage::disk('local')->path($backup->restored_path));

        $this->assertSame('ok', $restoredDatabase->query('PRAGMA integrity_check')->fetchColumn());
        $this->assertSame($backup->source_sha256, hash('sha256', $restoredContents));
        $this->assertNotSame($backup->artifact_path, $backup->restored_path);
        Storage::disk('local')->assertExists($backup->restored_path);
    }

    public function test_guest_cannot_run_backup_or_restore_operations(): void
    {
        $this->post(route('demo-backups.store'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('demo_backups', 0);
    }

    public function test_changed_backup_artifact_is_rejected_without_creating_a_restore(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('demo-backups.store'));
        $backup = DemoBackup::query()->firstOrFail();
        Storage::disk('local')->put($backup->artifact_path, 'tampered artifact');

        $this->post(route('demo-backups.restore', $backup))
            ->assertServerError();

        $this->assertDatabaseHas('demo_backups', [
            'id' => $backup->id,
            'restored_path' => null,
        ]);
        Storage::disk('local')->assertMissing('demo-restores');
    }

    public function test_missing_demo_source_is_recorded_as_a_failed_backup(): void
    {
        Storage::fake('local');
        config(['database.connections.backup_demo.database' => storage_path('missing-demo-source.sqlite')]);

        $this->actingAs(User::factory()->create())
            ->post(route('demo-backups.store'))
            ->assertServerError();

        $this->assertDatabaseHas('demo_backups', [
            'status' => 'failed',
        ]);
    }
}
