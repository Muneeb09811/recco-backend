<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get all settings
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        return response()->json($settings);
    }

    /**
     * Get settings by group
     */
    public function getByGroup($group)
    {
        $settings = Setting::getGroup($group);
        return response()->json($settings);
    }

    /**
     * Update settings (Admin)
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($validated['settings'] as $setting) {
            Setting::set($setting['key'], $setting['value']);
        }

        return response()->json([
            'message' => 'Settings updated successfully.',
        ]);
    }
}