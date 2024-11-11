<?php

use Carbon\Carbon;

if (! function_exists('formatTimestamp')) {
    function formatTimestamp($timestamp): string
    {
        $datetime = Carbon::parse($timestamp);

        $day = $datetime->format('d');

        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $month = $months[(int) $datetime->format('m')];

        $year = $datetime->format('Y');

        $hourMinute = $datetime->format('H:i');

        return "$day $month $year - $hourMinute";
    }
}
