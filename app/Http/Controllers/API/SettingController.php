<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * List all settings grouped by group
     */
    public function index(Request $request)
    {
        try {
            // Check if settings table exists
            if (!\Schema::hasTable('settings')) {
                return response()->json(['data' => [], 'message' => 'Settings table not found'], 200);
            }

            $query = Setting::query();

            if ($request->has('group')) {
                $query->where('group', $request->group);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $settings = $query->orderBy('group')->orderBy('label')->get();

            // If no settings, return empty grouped structure
            if ($settings->isEmpty()) {
                return response()->json(['data' => []]);
            }

            // Group by group
            $grouped = $settings->groupBy('group')->map(function ($items) {
                return $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'key' => $item->key,
                        'value' => $this->castValue($item->value, $item->type),
                        'type' => $item->type,
                        'label' => $item->label,
                        'description' => $item->description,
                        'is_active' => $item->is_active,
                    ];
                });
            });

            return response()->json(['data' => $grouped]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get setting by key
     */
    public function show(string $key)
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $this->castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'group' => $setting->group,
                'label' => $setting->label,
                'description' => $setting->description,
                'is_active' => $setting->is_active,
            ]
        ]);
    }

    /**
     * Get public settings (for frontend)
     */
    public function public(Request $request)
    {
        $keys = $request->input('keys', []);
        
        $query = Setting::where('is_active', true);
        
        if (!empty($keys)) {
            $query->whereIn('key', $keys);
        }

        $settings = $query->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $this->castValue($setting->value, $setting->type);
        }

        return response()->json(['data' => $result]);
    }

    /**
     * Update multiple settings (batch update)
     */
    public function updateBatch(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:settings,key',
            'settings.*.value' => 'nullable',
        ]);

        $updated = [];
        foreach ($validated['settings'] as $item) {
            $setting = Setting::where('key', $item['key'])->first();
            if ($setting) {
                $setting->value = $this->prepareValue($item['value'] ?? '', $setting->type);
                $setting->save();
                $updated[] = $setting->key;
            }
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'updated' => $updated,
        ]);
    }

    /**
     * Update single setting
     */
    public function update(Request $request, string $key)
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        $validated = $request->validate([
            'value' => 'nullable',
            'is_active' => 'boolean',
        ]);

        // Preserve is_active if not provided in request
        if (array_key_exists('is_active', $validated)) {
            $setting->is_active = $validated['is_active'];
        }

        // Always update value (even if null/empty) as long as the key exists in request
        if ($request->has('value')) {
            $setting->value = $this->prepareValue($validated['value'] ?? '', $setting->type);
        }

        $setting->save();

        return response()->json([
            'message' => 'Setting updated successfully',
            'data' => [
                'key' => $setting->key,
                'value' => $this->castValue($setting->value, $setting->type),
            ]
        ]);
    }

    /**
     * Get VCF related settings
     */
    public function vcfSettings()
    {
        try {
            if (!\Schema::hasTable('settings')) {
                return response()->json(['data' => []]);
            }
            $settings = Setting::getByGroup('vcf');
            return response()->json(['data' => $settings]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    }

    /**
     * Get print related settings
     */
    public function printSettings()
    {
        try {
            if (!\Schema::hasTable('settings')) {
                return response()->json(['data' => []]);
            }
            $settings = Setting::getByGroup('print');
            return response()->json(['data' => $settings]);
        } catch (\Exception $e) {
            return response()->json(['data' => []]);
        }
    }

    /**
     * Cast value based on type
     */
    private function castValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Prepare value for storage
     */
    private function prepareValue($value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }
}
