<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->default(10.00)->after('is_active');
            }
            if (! Schema::hasColumn('agencies', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('commission_rate');
            }
            if (! Schema::hasColumn('agencies', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('is_verified');
            }
            if (! Schema::hasColumn('agencies', 'block_reason')) {
                $table->text('block_reason')->nullable()->after('blocked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'is_verified', 'blocked_at', 'block_reason']);
        });
    }
};
