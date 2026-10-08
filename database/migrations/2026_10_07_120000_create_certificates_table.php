<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('issuer')->nullable();
            $table->string('number')->nullable();
            $table->date('valid_until')->nullable()->index();
            $table->json('description')->nullable();
            $table->string('logo')->nullable();
            $table->json('images')->nullable();
            $table->string('document')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        // Roles that manage clients also get to manage certificates (the default Editor role).
        foreach (DB::table('roles')->where('is_super', false)->get() as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

            if (! empty($permissions['clients'])) {
                $permissions['certificates'] = $permissions['clients'];
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

            if (array_key_exists('certificates', $permissions)) {
                unset($permissions['certificates']);
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
            }
        }

        Schema::dropIfExists('certificates');
    }
};
