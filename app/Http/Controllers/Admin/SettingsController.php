<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Settings::whereIn('key', [
            'review_cashback_instructions',
            'cashback_review_message'
        ])->get()->keyBy('key'); // удобный доступ по ключу

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'review_cashback_instructions' => 'required|string',
            'cashback_review_message' => 'required|string',
        ]);

        foreach (['review_cashback_instructions', 'cashback_review_message'] as $key) {
            Settings::updateOrCreate(
                ['key' => $key],
                ['value' => $request->input($key)]
            );
        }

        return redirect()->route('admin.settings.index')->with('success', 'Настройки сохранены.');
    }
}
