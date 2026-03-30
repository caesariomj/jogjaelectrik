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

        return "$day $month $year - $hourMinute WIB";
    }
}

if (! function_exists('formatDate')) {
    function formatDate($date): string
    {
        $datetime = Carbon::parse($date);

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

        return "$day $month $year";
    }
}

if (! function_exists('formatPrice')) {
    function formatPrice(string $price): string
    {
        return number_format($price, 0, ',', '.');
    }
}

if (! function_exists('extractCourierCode')) {
    function extractCourierCode(string $courier): string
    {
        $courierCodes = explode(':', config('services.rajaongkir.courier_codes'));

        if (in_array('jnt', $courierCodes) && str_contains($courier, 'j&t')) {
            $courier = 'jnt';
        } elseif (in_array('ide', $courierCodes) && str_contains($courier, 'id express')) {
            $courier = 'ide';
        }

        $result = null;

        foreach ($courierCodes as $code) {
            if (stripos($courier, $code) !== false) {
                $result = $code;
            }
        }

        return $result;
    }
}
