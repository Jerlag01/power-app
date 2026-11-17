<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Visitor statistics (page views without cookies or IP addresses) and the SEO tags managed in the admin panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->char('visitor', 16);
            $table->string('path')->index();
            $table->string('locale', 5)->nullable();
            $table->string('source', 20)->index();
            $table->string('source_name', 100)->nullable();
            $table->string('referrer_host')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('device', 10)->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->index(['created_at', 'visitor']);
        });

        Schema::create('seo_tags', function (Blueprint $table) {
            $table->id();
            $table->string('label', 150);
            // title, description, meta, link or json_ld
            $table->string('type', 20);
            // The meta name / property ("keywords", "og:image") or the link rel ("preconnect").
            $table->string('key', 150)->nullable();
            $table->text('value');
            // Empty: every page. Otherwise a path such as /nl/projects or /*/news/* (* = anything).
            $table->string('path', 255)->nullable();
            $table->string('locale', 5)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Roles that manage posts (the default Editor role) also see the statistics and manage the SEO tags.
        foreach (DB::table('roles')->where('is_super', false)->get() as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

            if (! empty($permissions['posts'])) {
                $permissions['statistics'] = ['view'];
                $permissions['seo_tags'] = $permissions['posts'];
            }

            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            unset($permissions['statistics'], $permissions['seo_tags']);
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        }

        Schema::dropIfExists('seo_tags');
        Schema::dropIfExists('page_views');
    }
};
