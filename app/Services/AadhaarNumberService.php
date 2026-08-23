<?php

namespace App\Services;

class AadhaarNumberService
{
    public static function mask(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';

        return 'XXXX-XXXX-'.substr($digits, -4);
    }

    public static function isValid(string $number): bool
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';

        if (strlen($digits) !== 12 || str_starts_with($digits, '0') || str_starts_with($digits, '1')) {
            return false;
        }

        $multiplicationTable = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
            [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
            [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
            [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
            [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
            [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
            [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
        ];
        $permutationTable = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 2, 8, 6, 3, 7, 9, 4, 0],
            [5, 8, 0, 3, 7, 9, 1, 6, 4, 2],
            [8, 9, 1, 5, 0, 3, 7, 4, 2, 6],
            [9, 4, 5, 3, 1, 2, 6, 8, 0, 7],
            [4, 2, 8, 6, 5, 7, 3, 9, 1, 0],
            [2, 7, 9, 4, 8, 1, 5, 3, 0, 6],
            [7, 0, 4, 6, 9, 3, 1, 2, 8, 5],
        ];

        $checksum = 0;
        $reversed = array_reverse(array_map('intval', str_split($digits)));
        foreach ($reversed as $position => $digit) {
            $checksum = $multiplicationTable[$checksum][$permutationTable[$position % 8][$digit]];
        }

        return $checksum === 0;
    }
}
