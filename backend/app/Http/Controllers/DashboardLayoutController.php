<?php

namespace App\Http\Controllers;

use App\Models\UserDashboardLayout;
use Illuminate\Http\Request;

class DashboardLayoutController extends Controller
{
    /**
     * Get the user's dashboard layout or return default layout
     */
    public function show(Request $request)
    {
        $layout = UserDashboardLayout::where('user_id', $request->user()->id)->first();

        if (! $layout) {
            return response()->json([
                'layoutConfig' => UserDashboardLayout::getDefaultLayout(),
                'widgetSettings' => UserDashboardLayout::getDefaultWidgetSettings(),
            ]);
        }

        return response()->json($layout);
    }

    /**
     * Save or update the user's dashboard layout
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'layout_config' => 'required|array',
            'layout_config.*.i' => 'required|string',
            'layout_config.*.x' => 'required|integer|min:0',
            'layout_config.*.y' => 'required|integer|min:0',
            'layout_config.*.w' => 'required|integer|min:1|max:12',
            'layout_config.*.h' => 'required|integer|min:1',
            'widget_settings' => 'sometimes|array',
        ]);

        $layout = UserDashboardLayout::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json($layout);
    }

    /**
     * Reset the user's dashboard layout to default
     */
    public function destroy(Request $request)
    {
        UserDashboardLayout::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Layout réinitialisé']);
    }
}
