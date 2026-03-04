<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->string('case_number', 10)->nullable()->unique()->after('id');
        });

        // Присвоить номера существующим заявкам
        $cases = DB::table('cases')->whereNull('case_number')->pluck('id');
        foreach ($cases as $id) {
            $number = $this->generateUnique();
            DB::table('cases')->where('id', $id)->update(['case_number' => $number]);
        }
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn('case_number');
        });
    }

    private function generateUnique(): string
    {
        do {
            $number = 'VB-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (DB::table('cases')->where('case_number', $number)->exists());

        return $number;
    }
};
