<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Client names used to be a list of tags in the general settings.
 * Move them to the clients table so each can have a logo.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $general = DB::table('settings')->where('key', 'general')->value('value');

        if ($general === null) {
            return;
        }

        $general = json_decode($general, true);
        $now = now();

        foreach (array_values($general['clients'] ?? []) as $order => $name) {
            DB::table('clients')->insert([
                'name' => $name,
                'sort_order' => $order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        unset($general['clients']);
        DB::table('settings')->where('key', 'general')->update(['value' => json_encode($general, JSON_UNESCAPED_UNICODE)]);
        Cache::forget('site-settings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Logos cannot be stored as tags; the clients table is dropped by its own migration.
    }
};
