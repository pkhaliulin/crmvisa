<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_model_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('version', 20)->unique();
            $table->jsonb('weights');
            $table->jsonb('thresholds');
            $table->jsonb('red_flag_rules');
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        // Вставляем начальную версию 1.0
        DB::table('scoring_model_versions')->insert([
            'id'            => \Illuminate\Support\Str::uuid()->toString(),
            'version'       => '1.0',
            'weights'       => json_encode([
                'finances'     => 0.25,
                'visa_history' => 0.30,
                'social_ties'  => 0.20,
                'destination'  => 0.15,
                'visa_type'    => 0.10,
            ]),
            'thresholds'    => json_encode([
                'high'   => 80,
                'medium' => 60,
                'low'    => 40,
            ]),
            'red_flag_rules'=> json_encode([
                ['condition' => 'refusals_3_in_3y', 'multiplier' => 0.6,  'description' => 'Более 2 отказов за 3 года'],
                ['condition' => 'had_overstay',     'multiplier' => 0.7,  'description' => 'Нарушение визового режима'],
                ['condition' => 'had_deportation',  'multiplier' => 0.5,  'description' => 'Депортация'],
            ]),
            'is_active'     => true,
            'activated_at'  => now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_model_versions');
    }
};
