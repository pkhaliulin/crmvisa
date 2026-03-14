<?php

namespace App\Modules\Document\Services;

class TransliterationService
{
    /**
     * Кириллица -> Латиница (ICAO 9303 + узбекские особенности).
     */
    private const CYR_TO_LAT = [
        // Двухсимвольные сочетания (приоритет)
        'Ш'  => 'SH',  'ш'  => 'sh',
        'Щ'  => 'SHCH','щ'  => 'shch',
        'Ч'  => 'CH',  'ч'  => 'ch',
        'Ж'  => 'ZH',  'ж'  => 'zh',
        'Х'  => 'KH',  'х'  => 'kh',
        'Ц'  => 'TS',  'ц'  => 'ts',
        'Ё'  => 'YO',  'ё'  => 'yo',
        'Ю'  => 'YU',  'ю'  => 'yu',
        'Я'  => 'YA',  'я'  => 'ya',
        'Й'  => 'Y',   'й'  => 'y',
        'Ў'  => 'O',   'ў'  => 'o',     // узб. O' (ICAO упрощение)
        'Ғ'  => 'G',   'ғ'  => 'g',     // узб. G' (ICAO)
        'Ҳ'  => 'KH',  'ҳ'  => 'kh',    // узб. H (ICAO -> KH)
        'Қ'  => 'Q',   'қ'  => 'q',     // узб.
        'Ъ'  => '',    'ъ'  => '',
        'Ь'  => '',    'ь'  => '',
        // Односимвольные
        'А'  => 'A',   'а'  => 'a',
        'Б'  => 'B',   'б'  => 'b',
        'В'  => 'V',   'в'  => 'v',
        'Г'  => 'G',   'г'  => 'g',
        'Д'  => 'D',   'д'  => 'd',
        'Е'  => 'E',   'е'  => 'e',
        'З'  => 'Z',   'з'  => 'z',
        'И'  => 'I',   'и'  => 'i',
        'К'  => 'K',   'к'  => 'k',
        'Л'  => 'L',   'л'  => 'l',
        'М'  => 'M',   'м'  => 'm',
        'Н'  => 'N',   'н'  => 'n',
        'О'  => 'O',   'о'  => 'o',
        'П'  => 'P',   'п'  => 'p',
        'Р'  => 'R',   'р'  => 'r',
        'С'  => 'S',   'с'  => 's',
        'Т'  => 'T',   'т'  => 't',
        'У'  => 'U',   'у'  => 'u',
        'Ф'  => 'F',   'ф'  => 'f',
        'Э'  => 'E',   'э'  => 'e',
    ];

    /**
     * Варианты транслитерации для нечёткого сравнения.
     * Ключ — нормализованный вариант, значения — допустимые альтернативы.
     */
    private const TRANSLIT_VARIANTS = [
        'KH' => ['X', 'H'],
        'SH' => ['SH'],
        'CH' => ['CH'],
        'ZH' => ['J', 'ZH'],
        'TS' => ['C', 'TS'],
        'YU' => ['IU', 'YU', 'JU'],
        'YA' => ['IA', 'YA', 'JA'],
        'YO' => ['IO', 'YO', 'JO'],
        'YE' => ['IE', 'YE', 'JE'],
        'Y'  => ['I', 'Y', 'J'],
        'E'  => ['E', 'YE'],
        "O'" => ['O', 'OO', "O'", 'OU'],
        "G'" => ['G', "G'", 'GH'],
        'Q'  => ['Q', 'K'],
    ];

    public static function cyrToLat(string $text): string
    {
        return strtr($text, self::CYR_TO_LAT);
    }

    public static function detectScript(string $text): string
    {
        $hasCyr = (bool) preg_match('/[\p{Cyrillic}]/u', $text);
        $hasLat = (bool) preg_match('/[A-Za-z]/', $text);

        if ($hasCyr && $hasLat) return 'mixed';
        if ($hasCyr) return 'cyrillic';
        return 'latin';
    }

    /**
     * Нормализовать имя для сравнения.
     * Переводит в латиницу (если кириллица), UPPERCASE, убирает спецсимволы.
     */
    public static function normalize(string $name): string
    {
        $script = self::detectScript($name);
        if ($script === 'cyrillic' || $script === 'mixed') {
            $name = self::cyrToLat($name);
        }

        // Убираем апострофы, дефисы, пробелы, приводим к uppercase
        $name = mb_strtoupper($name);
        $name = preg_replace("/['\x{2018}\x{2019}\x{02BB}\x{02BC}]/u", '', $name);
        $name = preg_replace('/[\-\s]+/', '', $name);
        $name = preg_replace('/[^A-Z]/', '', $name);

        return $name;
    }

    /**
     * Сравнение двух имен с учётом транслитерации.
     *
     * @return array{match: bool, level: string, similarity: float}
     *   level: 'exact', 'translit', 'close', 'different'
     */
    public static function compareNames(string $name1, string $name2): array
    {
        $n1 = self::normalize($name1);
        $n2 = self::normalize($name2);

        if ($n1 === '' || $n2 === '') {
            return ['match' => false, 'level' => 'empty', 'similarity' => 0.0];
        }

        // Точное совпадение после нормализации
        if ($n1 === $n2) {
            return ['match' => true, 'level' => 'exact', 'similarity' => 1.0];
        }

        // Проверка с учётом вариантов транслитерации
        $expanded1 = self::expandVariants($n1);
        $expanded2 = self::expandVariants($n2);

        foreach ($expanded1 as $v1) {
            foreach ($expanded2 as $v2) {
                if ($v1 === $v2) {
                    return ['match' => true, 'level' => 'translit', 'similarity' => 0.95];
                }
            }
        }

        // Нечёткое сравнение (Levenshtein)
        $maxLen = max(strlen($n1), strlen($n2));
        $distance = levenshtein($n1, $n2);
        $similarity = $maxLen > 0 ? 1.0 - ($distance / $maxLen) : 0.0;

        if ($similarity >= 0.85) {
            return ['match' => true, 'level' => 'close', 'similarity' => round($similarity, 2)];
        }

        return ['match' => false, 'level' => 'different', 'similarity' => round($similarity, 2)];
    }

    /**
     * Полное сравнение ФИО из двух документов.
     *
     * @return array{match: bool, level: string, details: array}
     */
    public static function compareFullNames(
        ?string $firstName1, ?string $lastName1,
        ?string $firstName2, ?string $lastName2,
        ?string $middleName1 = null, ?string $middleName2 = null,
    ): array {
        $firstResult  = self::compareNames($firstName1 ?? '', $firstName2 ?? '');
        $lastResult   = self::compareNames($lastName1 ?? '', $lastName2 ?? '');
        $middleResult = ($middleName1 || $middleName2)
            ? self::compareNames($middleName1 ?? '', $middleName2 ?? '')
            : ['match' => true, 'level' => 'exact', 'similarity' => 1.0];

        $allMatch = $firstResult['match'] && $lastResult['match'] && $middleResult['match'];

        // Определяем общий уровень совпадения
        $levels = [$firstResult['level'], $lastResult['level']];
        if ($middleName1 || $middleName2) $levels[] = $middleResult['level'];

        $level = 'exact';
        if (in_array('different', $levels)) $level = 'different';
        elseif (in_array('close', $levels)) $level = 'close';
        elseif (in_array('translit', $levels)) $level = 'translit';

        return [
            'match'   => $allMatch,
            'level'   => $level,
            'details' => [
                'first_name'  => $firstResult,
                'last_name'   => $lastResult,
                'middle_name' => $middleResult,
            ],
        ];
    }

    /**
     * Развернуть варианты транслитерации для нечёткого сравнения.
     */
    private static function expandVariants(string $normalized): array
    {
        $results = [$normalized];

        foreach (self::TRANSLIT_VARIANTS as $standard => $variants) {
            $newResults = [];
            foreach ($results as $current) {
                if (str_contains($current, $standard)) {
                    foreach ($variants as $alt) {
                        $newResults[] = str_replace($standard, $alt, $current);
                    }
                }
                $newResults[] = $current;
            }
            $results = array_unique($newResults);
        }

        // Обратное: альтернативы -> стандарт
        foreach (self::TRANSLIT_VARIANTS as $standard => $variants) {
            foreach ($variants as $alt) {
                $newResults = [];
                foreach ($results as $current) {
                    if ($alt !== $standard && str_contains($current, $alt)) {
                        $newResults[] = str_replace($alt, $standard, $current);
                    }
                    $newResults[] = $current;
                }
                $results = array_unique($newResults);
            }
        }

        return array_values(array_unique($results));
    }
}
