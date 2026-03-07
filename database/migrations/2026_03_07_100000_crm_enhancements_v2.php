<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. lead_source в cases — откуда пришёл лид
        if (! Schema::hasColumn('cases', 'lead_source')) {
            Schema::table('cases', function (Blueprint $table) {
                $table->string('lead_source', 50)->nullable()->after('notes')
                    ->comment('instagram, facebook, telegram, visabor, website, referral, direct, other');
            });
        }

        // 2. is_mandatory в global_services — обязательная услуга
        if (! Schema::hasColumn('global_services', 'is_mandatory')) {
            Schema::table('global_services', function (Blueprint $table) {
                $table->boolean('is_mandatory')->default(false)->after('is_active');
            });
        }

        // 3. country_suggestions — предложения агентств по исправлению данных стран
        if (! Schema::hasTable('country_suggestions')) {
            Schema::create('country_suggestions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('agency_id');
                $table->uuid('user_id');
                $table->string('country_code', 3);
                $table->string('field', 100)->comment('embassy_address, visa_center_phone, etc.');
                $table->text('current_value')->nullable();
                $table->text('suggested_value');
                $table->text('comment')->nullable();
                $table->string('status', 20)->default('pending')->comment('pending, approved, rejected');
                $table->uuid('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['country_code', 'status']);
            });
        }

        // 4. Дополнительные поля агентства для настроек
        if (! Schema::hasColumn('agencies', 'public_phone')) {
            Schema::table('agencies', function (Blueprint $table) {
                $table->string('public_phone', 20)->nullable()->after('phone');
                $table->string('public_email', 100)->nullable()->after('public_phone');
            });
        }

        // 5. Поля для верификации контактов пользователя
        if (! Schema::hasColumn('users', 'phone_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (Schema::hasColumn('cases', 'lead_source')) {
                $table->dropColumn('lead_source');
            }
        });

        Schema::table('global_services', function (Blueprint $table) {
            if (Schema::hasColumn('global_services', 'is_mandatory')) {
                $table->dropColumn('is_mandatory');
            }
        });

        Schema::dropIfExists('country_suggestions');

        Schema::table('agencies', function (Blueprint $table) {
            if (Schema::hasColumn('agencies', 'public_phone')) {
                $table->dropColumn(['public_phone', 'public_email']);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone_verified_at')) {
                $table->dropColumn('phone_verified_at');
            }
        });
    }
};
