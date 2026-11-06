<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class FolderPickerService
{
    /**
     * Launch the native Windows folder browser dialog and return the selected absolute path.
     */
    public function openFolderDialog(?string $initialPath = null): ?string
    {
        if (! $this->isWindows()) {
            return null;
        }

        $resolvedInitial = '';
        if (! empty($initialPath)) {
            $cleaned = str_replace(["\r", "\n", '"', "'", '`'], '', trim($initialPath));
            if ($cleaned !== '') {
                $resolvedInitial = str_starts_with($cleaned, 'storage')
                    ? base_path($cleaned)
                    : $cleaned;
            }
        }

        // PowerShell script to launch native Windows FolderBrowserDialog (100% visible, no transparent owner)
        $script = <<<'POWERSHELL'
$ProgressPreference = 'SilentlyContinue';
Add-Type -AssemblyName System.Windows.Forms;

$dialog = New-Object System.Windows.Forms.FolderBrowserDialog;
$dialog.Description = 'Seleccione la carpeta institucional donde se guardaran las copias de seguridad:';
$dialog.ShowNewFolderButton = $true;
$dialog.RootFolder = [System.Environment+SpecialFolder]::MyComputer;

$initial = '__INITIAL_PATH__';
if ($initial -ne '' -and (Test-Path -Path $initial)) {
    $dialog.SelectedPath = $initial;
}

if ($dialog.ShowDialog() -eq [System.Windows.Forms.DialogResult]::OK) {
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8;
    Write-Output $dialog.SelectedPath;
}
POWERSHELL;

        $script = str_replace('__INITIAL_PATH__', addslashes($resolvedInitial), $script);
        $encoded = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));

        $process = new Process([
            'powershell',
            '-NoProfile',
            '-STA',
            '-EncodedCommand',
            $encoded,
        ]);

        $process->setTimeout(120);

        try {
            $process->run();
        } catch (\Throwable) {
            return null;
        }

        if (! $process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());

        return $output !== '' ? $output : null;
    }

    public function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows' || strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * List available drive roots on the host machine.
     *
     * @return list<array{drive: string, path: string, label: string, free_gb: float|null}>
     */
    public function getSystemDrives(): array
    {
        $drives = [];

        if ($this->isWindows()) {
            foreach (range('C', 'Z') as $letter) {
                $root = $letter.':\\';
                if (is_dir($root)) {
                    $freeBytes = @disk_free_space($root);
                    $drives[] = [
                        'drive' => $letter.':',
                        'path' => $root,
                        'label' => "Disco ({$letter}:)",
                        'free_gb' => $freeBytes !== false ? round($freeBytes / (1024 * 1024 * 1024), 1) : null,
                    ];
                }
            }
        } else {
            $freeBytes = @disk_free_space('/');
            $drives[] = [
                'drive' => '/',
                'path' => '/',
                'label' => 'Raíz (/)',
                'free_gb' => $freeBytes !== false ? round($freeBytes / (1024 * 1024 * 1024), 1) : null,
            ];
        }

        return $drives;
    }

    /**
     * Common preset backup paths for easy 1-click selection, detected dynamically per host.
     *
     * @return array<int, array{label: string, path: string}>
     */
    public function getQuickPresets(): array
    {
        $presets = [
            ['label' => 'storage/app/backups (Portátil / Proyecto)', 'path' => 'storage/app/backups'],
        ];

        if ($this->isWindows()) {
            if (is_dir('C:\\laragon\\backups')) {
                $presets[] = ['label' => 'C:\laragon\backups', 'path' => 'C:\laragon\backups'];
            }
            if (is_dir('D:\\')) {
                $presets[] = ['label' => 'D:\Respaldos_TI', 'path' => 'D:\Respaldos_TI'];
            }
            if (is_dir('C:\\')) {
                $presets[] = ['label' => 'C:\Respaldos_TI', 'path' => 'C:\Respaldos_TI'];
            }
        }

        $userProfile = getenv('USERPROFILE');
        if ($userProfile && is_dir($userProfile)) {
            $presets[] = [
                'label' => 'Documentos\Respaldos',
                'path' => $userProfile.'\Documents\Respaldos_TI',
            ];
        }

        return $presets;
    }
}
