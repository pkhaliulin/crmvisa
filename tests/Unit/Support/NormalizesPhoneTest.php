<?php

namespace Tests\Unit\Support;

use App\Support\Traits\NormalizesPhone;
use PHPUnit\Framework\TestCase;

class NormalizesPhoneTest extends TestCase
{
    use NormalizesPhone {
        normalizePhone as public;
    }

    public function test_strips_spaces_and_dashes(): void
    {
        $this->assertSame('+998901234567', self::normalizePhone('+998 90 123 45 67'));
        $this->assertSame('+998901234567', self::normalizePhone('+998-90-123-45-67'));
        $this->assertSame('+998901234567', self::normalizePhone('+ 998 (90) 123-45-67'));
    }

    public function test_adds_plus_prefix(): void
    {
        $this->assertSame('+998901234567', self::normalizePhone('998901234567'));
    }

    public function test_preserves_existing_plus(): void
    {
        $this->assertSame('+998901234567', self::normalizePhone('+998901234567'));
    }

    public function test_handles_9_digit_local(): void
    {
        $this->assertSame('+901234567', self::normalizePhone('901234567'));
    }

    public function test_result_has_no_serialize_wrapper(): void
    {
        $result = self::normalizePhone('+998999999999');
        $this->assertStringNotContainsString('s:', $result);
        $this->assertStringStartsWith('+', $result);
        $this->assertMatchesRegularExpression('/^\+\d+$/', $result);
    }

    public function test_output_contains_only_digits_and_plus(): void
    {
        $phones = [
            '+998 (90) 123-45-67',
            '998901234567',
            '+1 (555) 123-4567',
            '90 123 45 67',
        ];

        foreach ($phones as $phone) {
            $result = self::normalizePhone($phone);
            $this->assertMatchesRegularExpression(
                '/^\+?\d+$/',
                $result,
                "normalizePhone('$phone') = '$result' contains invalid chars"
            );
        }
    }
}
