<?php

if (!function_exists('convertEnglishToMyanmarNumber')) {
    function convertEnglishToMyanmarNumber($number)
    {
        $englishNumbers = range(0, 9);
        $myanmarNumbers = ['၀', '၁', '၂', '၃', '၄', '၅', '၆', '၇', '၈', '၉'];

        return str_replace($englishNumbers, $myanmarNumbers, $number);
    }
}