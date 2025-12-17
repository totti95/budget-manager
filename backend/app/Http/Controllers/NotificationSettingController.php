<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    /**
     * Get user's notification settings
     */
    public function show(Request $request)
    {
        $settings = $request->user()->notificationSettings;

        // If no settings exist, create defaults
        if (!$settings) {
            $settings = $request->user()->notificationSettings()->create([
                'budget_exceeded_enabled' => true,
                'budget_exceeded_threshold_percent' => 100,
                'savings_goal_enabled' => true,
            ]);
        }

        return response()->json($settings);
    }

    /**
     * Update user's notification settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'budget_exceeded_enabled' => 'sometimes|boolean',
            'budget_exceeded_threshold_percent' => 'sometimes|integer|min:50|max:150',
            'savings_goal_enabled' => 'sometimes|boolean',
        ]);

        $settings = $request->user()->notificationSettings;

        if (!$settings) {
            $settings = $request->user()->notificationSettings()->create($validated);
        } else {
            $settings->update($validated);
        }

        return response()->json($settings);
    }
}
