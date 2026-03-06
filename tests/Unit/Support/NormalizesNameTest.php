<?php

namespace Tests\Unit\Support;

use App\Support\Traits\NormalizesName;
use PHPUnit\Framework\TestCase;

class NormalizesNameTest extends TestCase
{
    use NormalizesName {
        normalizeName as public;
    }

    public function test_title_case_latin(): void
    {
        $this->assertSame('John Doe', self::normalizeName('john doe'));
        $this->assertSame('John Doe', self::normalizeName('JOHN DOE'));
        $this->assertSame('John Doe', self::normalizeName('jOHN dOE'));
    }

    public function test_title_case_cyrillic(): void
    {
        $this->assertSame('Иван Петров', self::normalizeName('иван петров'));
        $this->assertSame('Иван Петров', self::normalizeName('ИВАН ПЕТРОВ'));
    }

    public function test_trims_extra_spaces(): void
    {
        $this->assertSame('John Doe', self::normalizeName('  john   doe  '));
    }

    public function test_single_name(): void
    {
        $this->assertSame('Islom', self::normalizeName('islom'));
    }

    public function test_hyphenated_name(): void
    {
        $result = self::normalizeName('anna-maria');
        $this->assertSame('Anna-Maria', $result);
    }

    public function test_uzbek_names(): void
    {
        $this->assertSame('Islom Karimov', self::normalizeName('islom karimov'));
        $this->assertSame('Anvar Ismoilov', self::normalizeName('ANVAR ISMOILOV'));
    }
}
