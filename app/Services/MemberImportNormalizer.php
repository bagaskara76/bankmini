<?php

namespace App\Services;

use App\Models\Customer;

class MemberImportNormalizer
{
    public function normalizeGrade(string $grade): string
    {
        if (preg_match('/(?<![A-Z0-9])(XII|XI|X|12|11|10)(?![A-Z0-9])/i', trim($grade), $match)) {
            return match (strtoupper($match[1])) {
                'X', '10' => '10',
                'XI', '11' => '11',
                'XII', '12' => '12',
            };
        }

        return trim($grade);
    }

    public function normalizeClass(string $class, ?string $grade = null): string
    {
        $class = trim($class);
        $class = preg_replace('/^(?:(?:kelas|tingkat)\s*)?(?:XII|XI|X|12|11|10)\s*[-:]?\s*/i', '', $class) ?? $class;
        $class = preg_replace('/^AKL(?=\s*[-_]?\s*\d)/i', 'AK', $class) ?? $class;
        $normalized = strtolower(preg_replace('/[^a-z0-9]/i', '', $class) ?? $class);

        if ($normalized === 'lps' && $grade === '12') {
            return 'LPS 1';
        }

        foreach (Customer::availableKelas() as $availableClass) {
            $availableNormalized = strtolower(preg_replace('/[^a-z0-9]/i', '', $availableClass) ?? $availableClass);
            if ($normalized === $availableNormalized) {
                return $availableClass;
            }
        }

        return trim($class);
    }
}
