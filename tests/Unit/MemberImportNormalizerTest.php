<?php

namespace Tests\Unit;

use App\Services\MemberImportNormalizer;
use PHPUnit\Framework\TestCase;

class MemberImportNormalizerTest extends TestCase
{
    public function test_normalizes_numeric_and_roman_numeral_grades(): void
    {
        $normalizer = new MemberImportNormalizer;

        $this->assertSame('10', $normalizer->normalizeGrade('X'));
        $this->assertSame('11', $normalizer->normalizeGrade('XI'));
        $this->assertSame('12', $normalizer->normalizeGrade('Kelas XII'));
        $this->assertSame('11', $normalizer->normalizeGrade('11.0'));
    }

    public function test_normalizes_supported_class_names_and_removes_grade_prefixes(): void
    {
        $normalizer = new MemberImportNormalizer;

        $this->assertSame('RPL 1', $normalizer->normalizeClass('rpl1'));
        $this->assertSame('RPL 2', $normalizer->normalizeClass('XI RPL 2'));
        $this->assertSame('AK 3', $normalizer->normalizeClass('Kelas 11 - akl-3'));
        $this->assertSame('DKV 2', $normalizer->normalizeClass('XII dkv-2'));
        $this->assertSame('LPS 1', $normalizer->normalizeClass('LPS', '12'));
        $this->assertSame('LPS 1', $normalizer->normalizeClass('XII LPS', '12'));
        $this->assertSame('LPS 1', $normalizer->normalizeClass('LPS 1', '10'));
        $this->assertSame('LPS 2', $normalizer->normalizeClass('LPS 2', '11'));
        $this->assertSame('LPS 2', $normalizer->normalizeClass('LPS 2', '12'));
        $this->assertSame('LPS 1', $normalizer->normalizeClass('LPS', '10'));
        $this->assertSame('LPS 1', $normalizer->normalizeClass('LPS', '12'));
        $this->assertSame('IPA 1', $normalizer->normalizeClass('XI IPA 1'));
        $this->assertNotContains('IPA 1', \App\Models\Customer::availableKelas());
        $this->assertNotContains('IPS 2', \App\Models\Customer::availableKelas());
    }

    public function test_available_classes_match_the_school_jurisdictions(): void
    {
        $this->assertSame([
            'RPL 1', 'RPL 2',
            'TKJ 1', 'TKJ 2',
            'DKV 1', 'DKV 2',
            'AK 1', 'AK 2', 'AK 3', 'AK 4',
            'MP 1', 'MP 2',
            'LPS 1', 'LPS 2',
            'BR 1', 'BR 2',
            'BD',
        ], \App\Models\Customer::availableKelas());
        $this->assertContains('LPS 2', \App\Models\Customer::availableKelas('10'));
        $this->assertContains('LPS 2', \App\Models\Customer::availableKelas('11'));
        foreach (['10', '11', '12'] as $grade) {
            $this->assertContains('LPS 1', \App\Models\Customer::availableKelas($grade));
            $this->assertContains('LPS 2', \App\Models\Customer::availableKelas($grade));
        }
    }
}
