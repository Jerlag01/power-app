<?php

use App\Support\MailTemplates;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Editable e-mail templates (System → E-mail templates): every mail of the website in Dutch, French and English.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50);
            $table->string('locale', 5);
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['key', 'locale']);
        });

        MailTemplates::sync();

        // Roles that manage the mail settings also edit the templates.
        foreach (DB::table('roles')->where('is_super', false)->get() as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];

            if (in_array('mail', (array) ($permissions['settings'] ?? []), true)) {
                $permissions['mail_templates'] = ['view', 'update'];
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->get() as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            unset($permissions['mail_templates']);
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        }

        Schema::dropIfExists('mail_templates');
    }
};
