<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TelegramUserController extends Controller
{
    private const PYTHON_COMMAND = 'python3';
    private const SCRIPT_PATH = '/home/b/blocksre/wbd-back.ru/public_html/public/index.py';
    private const ALL_MEMBERS_FILE = '/home/b/blocksre/wbd-back.ru/public_html/public/all_members.csv';
    private const NEW_MEMBERS_FILE = '/home/b/blocksre/wbd-back.ru/public_html/public/new_members.csv';

    public function index(): View
    {
        return view('admin.telegram-users.index');
    }

    public function refresh(): RedirectResponse
    {
        $process = $this->createPythonProcess(
            [self::PYTHON_COMMAND, self::SCRIPT_PATH],
            dirname(self::SCRIPT_PATH)
        );

        try {
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } catch (\Throwable $exception) {
            Log::error('Не удалось обновить список пользователей TG', [
                'exception' => $exception,
            ]);

            return redirect()
                ->route('admin.telegram-users.index')
                ->with('error', 'Не удалось обновить пользователей TG. Проверьте логи для подробностей.');
        }

        return redirect()
            ->route('admin.telegram-users.index')
            ->with('success', 'Список пользователей TG успешно обновлён.');
    }

    public function installDependencies(): RedirectResponse
    {
        $targetDirectory = $this->getPythonDependenciesPath();

        $this->ensureDirectoryExists($targetDirectory);

        $process = $this->createPythonProcess([
            self::PYTHON_COMMAND,
            '-m',
            'pip',
            'install',
            '--no-cache-dir',
            '--target',
            $targetDirectory,
            'telethon',
            'pandas',
        ], dirname(self::SCRIPT_PATH));

        try {
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        } catch (\Throwable $exception) {
            Log::error('Не удалось установить зависимости для TG', [
                'exception' => $exception,
            ]);

            return redirect()
                ->route('admin.telegram-users.index')
                ->with('error', 'Не удалось установить зависимости. Проверьте логи для подробностей.');
        }

        return redirect()
            ->route('admin.telegram-users.index')
            ->with('success', 'Зависимости для TG успешно установлены.');
    }

    private function createPythonProcess(array $command, ?string $workingDirectory = null): Process
    {
        $process = new Process($command, $workingDirectory);
        $process->setTimeout(300);

        $env = $this->buildPythonEnvironment($process->getEnv());
        $process->setEnv($env);

        return $process;
    }

    private function buildPythonEnvironment(array $baseEnv): array
    {
        $targetDirectory = $this->getPythonDependenciesPath();

        $pythonPath = $targetDirectory;

        if (! empty($baseEnv['PYTHONPATH'])) {
            $pythonPath .= PATH_SEPARATOR . $baseEnv['PYTHONPATH'];
        } elseif (($current = getenv('PYTHONPATH')) !== false && $current !== '') {
            $pythonPath .= PATH_SEPARATOR . $current;
        }

        return array_merge($baseEnv, [
            'PYTHONPATH' => $pythonPath,
        ]);
    }

    private function getPythonDependenciesPath(): string
    {
        return storage_path('app/python-dependencies');
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        File::makeDirectory($directory, 0755, true, true);
    }
  
    public function downloadAll(): RedirectResponse|BinaryFileResponse
    {
        return $this->downloadFile(self::ALL_MEMBERS_FILE, 'all_members.csv');
    }

    public function downloadNew(): RedirectResponse|BinaryFileResponse
    {
        return $this->downloadFile(self::NEW_MEMBERS_FILE, 'new_members.csv');
    }

    private function downloadFile(string $filePath, string $downloadName): RedirectResponse|BinaryFileResponse
    {
        if (! file_exists($filePath)) {
            return redirect()
                ->route('admin.telegram-users.index')
                ->with('error', 'Файл не найден: ' . $downloadName);
        }

        return response()->download($filePath, $downloadName);
    }
}

