<?php

namespace Tests\Feature;

use App\Enums\BackupStatus;
use App\Models\DemoBackup;
use App\Models\User;
use App\Services\BackupScheduleManager;
use App\Services\EnvironmentDetector;
use App\Services\FolderPickerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_runbook_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('backups.runbook'));

        $response->assertOk();
        $response->assertSee('Manual Paso a Paso de Restauración');
        $response->assertSee('SOP-TI-01');
        $response->assertSee('Fase 1: Verificación de Autenticidad');
    }

    public function test_guest_cannot_view_runbook_page(): void
    {
        $response = $this->get(route('backups.runbook'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_update_backup_schedule(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('backups.schedule'), [
            'enabled' => '1',
            'frequency' => 'daily',
            'time' => '02:00',
            'database' => 'iesp_continuidad_demo',
            'retention_days' => 45,
            'replicate_secondary' => '1',
            'secondary_path' => 'backups/secondary',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $manager = app(BackupScheduleManager::class);
        $config = $manager->getConfig();

        $this->assertTrue($config['enabled']);
        $this->assertSame('daily', $config['frequency']);
        $this->assertSame('02:00', $config['time']);
        $this->assertSame('iesp_continuidad_demo', $config['database']);
        $this->assertSame(45, $config['retention_days']);
        $this->assertTrue($config['replicate_secondary']);
    }

    public function test_authenticated_user_can_update_storage_destination_and_format(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('backups.schedule'), [
            'enabled' => '1',
            'frequency' => 'hourly',
            'time' => '03:30',
            'database' => 'iesp_continuidad_demo',
            'retention_days' => 60,
            'replicate_secondary' => '1',
            'secondary_path' => 'backups/secondary',
            'storage_destination' => 'D:\Respaldos_TI',
            'folder_structure' => 'date_subfolders',
            'save_format' => 'both',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $manager = app(BackupScheduleManager::class);
        $config = $manager->getConfig();

        $this->assertSame('D:\Respaldos_TI', $config['storage_destination']);
        $this->assertSame('date_subfolders', $config['folder_structure']);
        $this->assertSame('both', $config['save_format']);
        $this->assertSame('hourly', $config['frequency']);
    }

    public function test_authenticated_user_can_trigger_scheduled_backup_now(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('backups.run-now'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_backup_schedule_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('backups.schedule'), [
            'frequency' => 'invalid_frequency',
            'time' => 'invalid_time',
            'retention_days' => 'not_a_number',
        ]);

        $response->assertSessionHasErrors(['frequency', 'time', 'database', 'retention_days', 'secondary_path']);
    }

    public function test_authenticated_user_can_leave_retention_days_blank_for_unlimited(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('backups.schedule'), [
            'enabled' => '1',
            'frequency' => 'hourly',
            'database' => 'iesp_continuidad_demo',
            'retention_days' => '',
            'replicate_secondary' => '1',
            'secondary_path' => 'backups/secondary',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $manager = app(BackupScheduleManager::class);
        $config = $manager->getConfig();

        $this->assertNull($config['retention_days']);
    }

    public function test_automated_backup_command_runs_successfully(): void
    {
        Storage::fake('local');

        $exitCode = Artisan::call('backup:auto-run', [
            '--database' => 'demo-institucional',
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('demo_backups', [
            'is_automated' => true,
            'status' => 'success',
        ]);
    }

    public function test_environment_detector_returns_valid_structure(): void
    {
        $detector = app(EnvironmentDetector::class);
        $detection = $detector->detect();

        $this->assertArrayHasKey('stack', $detection);
        $this->assertArrayHasKey('mysql_available', $detection);
        $this->assertArrayHasKey('databases', $detection);
        $this->assertArrayHasKey('sqlite_available', $detection);
        $this->assertIsArray($detection['databases']);

        // System schemas must be strictly excluded
        $this->assertNotContains('information_schema', $detection['databases']);
        $this->assertNotContains('performance_schema', $detection['databases']);
        $this->assertNotContains('mysql', $detection['databases']);
        $this->assertNotContains('sys', $detection['databases']);
    }

    public function test_guest_cannot_access_folder_picker(): void
    {
        $response = $this->postJson(route('backups.browse-folder'));

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_browse_folder_successfully(): void
    {
        $user = User::factory()->create();

        $mock = $this->mock(FolderPickerService::class);
        $mock->shouldReceive('openFolderDialog')
            ->once()
            ->with('D:\MiRuta')
            ->andReturn('D:\Respaldos_TI');

        $response = $this->actingAs($user)->postJson(route('backups.browse-folder'), [
            'current_path' => 'D:\MiRuta',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'path' => 'D:\Respaldos_TI',
        ]);
    }

    public function test_folder_picker_returns_cancelled_when_dialog_closed_without_selection(): void
    {
        $user = User::factory()->create();

        $mock = $this->mock(FolderPickerService::class);
        $mock->shouldReceive('openFolderDialog')
            ->once()
            ->andReturn(null);

        $response = $this->actingAs($user)->postJson(route('backups.browse-folder'), [
            'current_path' => '',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'cancelled' => true,
        ]);
    }

    public function test_backup_index_renders_folder_presets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('backups.index'));

        $response->assertOk();
        $response->assertSee('Examinar...');
        $response->assertSee('Rutas rápidas:');
        $response->assertSee('storage/app/backups');
    }

    public function test_authenticated_user_can_query_restore_preflight_endpoint(): void
    {
        $user = User::factory()->create();
        $backup = DemoBackup::factory()->create([
            'database_name' => 'iesp_continuidad_demo',
            'status' => BackupStatus::Success,
        ]);

        $response = $this->actingAs($user)->getJson(route('backups.preflight', $backup));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'backup_id',
            'database',
            'is_available',
            'stack',
            'flavor',
            'version',
            'label',
            'exists',
            'table_count',
            'backup_size',
            'backup_date',
        ]);
        $this->assertSame('iesp_continuidad_demo', $response->json('database'));
    }

    public function test_guest_cannot_query_restore_preflight_endpoint(): void
    {
        $backup = DemoBackup::factory()->create([
            'database_name' => 'iesp_continuidad_demo',
            'status' => BackupStatus::Success,
        ]);

        $response = $this->getJson(route('backups.preflight', $backup));

        $response->assertUnauthorized();
    }
}
