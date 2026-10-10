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
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('intro')->nullable();
            $table->json('description')->nullable();
            $table->json('requirements')->nullable();
            $table->json('offer')->nullable();
            $table->string('location')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->json('notify_emails')->nullable();
            $table->date('closes_at')->nullable()->index();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vacancy_title');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message');
            $table->string('cv_path');
            $table->string('cv_name');
            $table->string('locale', 5)->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        // Roles that manage clients also manage vacancies (the default Editor role);
        // roles that read contact messages also read applications.
        foreach (DB::table('roles')->where('is_super', false)->get() as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

            if (! empty($permissions['clients'])) {
                $permissions['vacancies'] = $permissions['clients'];
            }

            if (! empty($permissions['contact_messages'])) {
                $permissions['job_applications'] = $permissions['contact_messages'];
            }

            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            unset($permissions['vacancies'], $permissions['job_applications']);
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        }

        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('vacancies');
    }
};
