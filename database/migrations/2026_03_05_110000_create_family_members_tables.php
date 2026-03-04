<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Семья пользователя (сохраняется в профиле, переиспользуется)
        Schema::create('public_user_family_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_user_id');
            $table->string('name', 255);
            $table->string('relationship', 20); // child, spouse, parent, sibling, other
            $table->date('dob')->nullable();
            $table->char('gender', 1)->nullable(); // M, F
            $table->string('citizenship', 2)->nullable();
            $table->string('passport_number', 20)->nullable();
            $table->date('passport_expires_at')->nullable();
            $table->timestamps();

            $table->foreign('public_user_id')->references('id')->on('public_users')->cascadeOnDelete();
            $table->index('public_user_id');
        });

        // Привязка членов семьи к конкретной заявке
        Schema::create('case_family_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('case_id');
            $table->uuid('family_member_id');
            $table->timestamps();

            $table->foreign('case_id')->references('id')->on('cases')->cascadeOnDelete();
            $table->foreign('family_member_id')->references('id')->on('public_user_family_members')->cascadeOnDelete();
            $table->unique(['case_id', 'family_member_id']);
        });

        // Привязка документов чеклиста к конкретному члену семьи
        Schema::table('case_checklist', function (Blueprint $table) {
            $table->uuid('family_member_id')->nullable()->after('case_id');
            $table->index('family_member_id');
        });
    }

    public function down(): void
    {
        Schema::table('case_checklist', function (Blueprint $table) {
            $table->dropIndex(['family_member_id']);
            $table->dropColumn('family_member_id');
        });
        Schema::dropIfExists('case_family_members');
        Schema::dropIfExists('public_user_family_members');
    }
};
