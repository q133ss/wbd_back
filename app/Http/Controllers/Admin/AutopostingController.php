<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Autoposting\UpdateRequest;
use App\Models\AutopostLog;
use App\Models\AutopostSetting;

class AutopostingController extends Controller
{
    public function index()
    {
        $settings = AutopostSetting::query()->first();

        if (! $settings) {
            $settings = AutopostSetting::create();
        }

        $logs = AutopostLog::with('ad')->latest()->paginate(20);

        return view('admin.autoposting.index', compact('settings', 'logs'));
    }

    public function update(UpdateRequest $request)
    {
        $settings = AutopostSetting::query()->first();

        $data = $request->validated();

        if (! $settings) {
            AutopostSetting::create($data);
        } else {
            $settings->update($data);
        }

        return redirect()->route('admin.autoposting.index')->with('status', 'Настройки обновлены');
    }
}
