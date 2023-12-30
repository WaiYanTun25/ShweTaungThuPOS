<?php

if (!function_exists('convertEnglishToMyanmarNumber')) {
    function convertEnglishToMyanmarNumber($number)
    {
        $englishNumbers = range(0, 9);
        $myanmarNumbers = ['၀', '၁', '၂', '၃', '၄', '၅', '၆', '၇', '၈', '၉'];

        return str_replace($englishNumbers, $myanmarNumbers, $number);
    }
}

if (!function_exists('formatToCustomDate')) {
    function formatToCustomDate($inputDate)
    {
        $dateTime = new DateTime($inputDate);
        return $dateTime->format('d/m/y');
    }
}
