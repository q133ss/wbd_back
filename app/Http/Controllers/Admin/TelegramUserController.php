<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
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
        $process = new Process([self::PYTHON_COMMAND, self::SCRIPT_PATH]);

        try {
            $process->setTimeout(300);
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
        $process = new Process([
            self::PYTHON_COMMAND,
            '-m',
            'pip',
            'install',
            '--no-cache-dir',
            'telethon',
            'pandas',
        ]);

        try {
            $process->setTimeout(300);
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

