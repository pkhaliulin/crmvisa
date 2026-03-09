<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->jsonb('value')->default('{}');
            $table->string('group', 50)->default('general');
            $table->timestamps();
        });

        $defaults = [
            ['key' => 'site.enabled', 'value' => json_encode(true), 'group' => 'site'],
            ['key' => 'site.maintenance_banner', 'value' => json_encode(false), 'group' => 'site'],
            ['key' => 'site.maintenance_text', 'value' => json_encode('Сайт временно работает в ограниченном режиме.'), 'group' => 'site'],
            ['key' => 'seo.title', 'value' => json_encode('visabor.uz — AI Visa Platform для граждан Узбекистана'), 'group' => 'seo'],
            ['key' => 'seo.description', 'value' => json_encode('AI-платформа для граждан Узбекистана. Проверьте вероятность получения визы, сравните направления и получите персональные рекомендации.'), 'group' => 'seo'],
            ['key' => 'seo.keywords', 'value' => json_encode('виза Узбекистан, AI visa, шансы на визу, visabor, визовый помощник'), 'group' => 'seo'],
            ['key' => 'hero.title', 'value' => json_encode('Узнайте шансы до подачи визы'), 'group' => 'content'],
            ['key' => 'hero.subtitle', 'value' => json_encode('AI-платформа нового поколения — сравните направления, получите персональный скоринг и выберите страну, куда вам проще всего поехать.'), 'group' => 'content'],
            ['key' => 'hero.cta_text', 'value' => json_encode('Проверить шансы'), 'group' => 'content'],
            ['key' => 'contact.phone', 'value' => json_encode('+998 71 200-00-00'), 'group' => 'contact'],
            ['key' => 'contact.email', 'value' => json_encode('info@visabor.uz'), 'group' => 'contact'],
            ['key' => 'contact.telegram', 'value' => json_encode('https://t.me/visaborbot'), 'group' => 'contact'],
            ['key' => 'contact.address', 'value' => json_encode('Ташкент, Узбекистан'), 'group' => 'contact'],
            ['key' => 'sections.hero', 'value' => json_encode(true), 'group' => 'sections'],
            ['key' => 'sections.scoring', 'value' => json_encode(true), 'group' => 'sections'],
            ['key' => 'sections.destinations', 'value' => json_encode(true), 'group' => 'sections'],
            ['key' => 'sections.agencies', 'value' => json_encode(true), 'group' => 'sections'],
            ['key' => 'sections.trust', 'value' => json_encode(true), 'group' => 'sections'],
            ['key' => 'sections.faq', 'value' => json_encode(true), 'group' => 'sections'],
            ['key' => 'sections.compare', 'value' => json_encode(true), 'group' => 'sections'],
            ['key' => 'sections.app', 'value' => json_encode(true), 'group' => 'sections'],
            ['key' => 'sections.telegram', 'value' => json_encode(true), 'group' => 'sections'],
            ['key' => 'sections.cabinet', 'value' => json_encode(true), 'group' => 'sections'],
        ];

        foreach ($defaults as $d) {
            $d['created_at'] = now();
            $d['updated_at'] = now();
            DB::table('site_settings')->insert($d);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
