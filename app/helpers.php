<?php

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Carbon;

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
if (!function_exists('convertToMyanmarDate')) {
    function convertToMyanmarDate($englishDate)
    {
        try {
            // Create a DateTime object from the English date
            $carbonDate = Carbon::parse($englishDate);

        $burmeseMonths = ['ဇန်နဝါရီ', 'ဖေဖော်ဝါရီ', 'မတ်', 'ဧပြီ', 'မေ', 'ဇွန်', 'ဇူလိုင်', 'ဩဂုတ်', 'စက်တင်ဘာ', 'အောက်တိုဘာ', 'နိုဝင်ဘာ' ,'ဒီဇင်ဘာ'];
        $burmeseDays = ['တနင်္ဂနွေ', 'တနင်္လာ', 'အင်္ဂါ', 'ဗုဒ္ဓဟူး', 'ကြာသပတေး', 'သောကြာ', 'စနေ'];
        // Get the year, month, and day from the Carbon instance
        $formattedDate = $burmeseDays[$carbonDate->dayOfWeek]."နေ့၊ {$burmeseMonths[$carbonDate->month - 1]} ". convertEnglishToMyanmarNumber($carbonDate->day)." ရက်၊ ". convertEnglishToMyanmarNumber($carbonDate->year)." ခုနှစ်";

        return $formattedDate;
        } catch (Exception $e) {
            // Log any conversion errors
            info("Myanmar Date Conversion Error: " . $e->getMessage());
            return null;
        }
    }
}
