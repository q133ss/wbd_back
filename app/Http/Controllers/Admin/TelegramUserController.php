<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
        $process = new Process([
            self::PYTHON_COMMAND,
            basename(self::SCRIPT_PATH),
        ]);

        $process->setTimeout(300);
        $process->setWorkingDirectory(dirname(self::SCRIPT_PATH));

        try {
            $process->run();
        } catch (\Throwable $exception) {
            Log::error('Не удалось запустить процесс обновления пользователей TG', [
                'exception' => $exception,
            ]);

            return redirect()
                ->route('admin.telegram-users.index')
                ->with('error', 'Не удалось запустить обновление пользователей TG. Проверьте логи для подробностей.');
        }

        if ($process->isSuccessful()) {
            return redirect()
                ->route('admin.telegram-users.index')
                ->with('success', 'Список пользователей TG успешно обновлён.');
        }

        $errorMessage = $this->resolveProcessErrorMessage($process);

        Log::error('Процесс обновления пользователей TG завершился с ошибкой', [
            'exit_code' => $process->getExitCode(),
            'output' => $process->getOutput(),
            'error_output' => $process->getErrorOutput(),
        ]);

        return redirect()
            ->route('admin.telegram-users.index')
            ->with('error', $errorMessage);
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

    private function resolveProcessErrorMessage(Process $process): string
    {
        $errorOutput = $process->getErrorOutput();

        if (preg_match("/ModuleNotFoundError: No module named '([^']+)'/", $errorOutput, $matches)) {
            $missingModule = $matches[1];

            return sprintf(
                'Python не нашёл модуль "%s". Установите его для пользователя веб-сервера: pip3 install --user %s',
                $missingModule,
                $missingModule
            );
        }

        if ($errorOutput !== '') {
            return 'Команда завершилась с ошибкой: ' . trim($errorOutput);
        }

        if ($process->getExitCode() !== null) {
            return 'Команда завершилась с кодом ' . $process->getExitCode() . '. Проверьте логи для подробностей.';
        }

        return 'Не удалось обновить пользователей TG. Проверьте логи для подробностей.';
    }
}

