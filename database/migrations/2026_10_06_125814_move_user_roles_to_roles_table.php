<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the fixed admin/editor role column with editable roles.
 * Existing users keep their access: admins get the "Administrator" role
 * (all permissions), editors the "Editor" role (content and messages).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $adminId = DB::table('roles')->insertGetId([
            'name' => 'Administrator',
            'description' => 'Full access to everything, including users, roles and settings.',
            'is_super' => true,
            'permissions' => json_encode([]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $editorId = DB::table('roles')->insertGetId([
            'name' => 'Editor',
            'description' => 'Manages news, projects, expertise, clients and contact messages.',
            'is_super' => false,
            'permissions' => json_encode([
                'posts' => ['view', 'create', 'update', 'delete'],
                'projects' => ['view', 'create', 'update', 'delete'],
                'expertises' => ['view', 'create', 'update', 'delete'],
                'clients' => ['view', 'create', 'update', 'delete'],
                'contact_messages' => ['view', 'update', 'delete'],
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained()->nullOnDelete();
        });

        DB::table('users')->where('role', 'admin')->update(['role_id' => $adminId]);
        DB::table('users')->where('role', '!=', 'admin')->update(['role_id' => $editorId]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('editor')->after('password');
        });

        $superRoleIds = DB::table('roles')->where('is_super', true)->pluck('id');
        DB::table('users')->whereIn('role_id', $superRoleIds)->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        DB::table('roles')->delete();
    }
};
