<?php

use Illuminate\Support\Carbon;

if (! function_exists('date_db')) {

    /**
     * Convert date for DB format (YYYY-MM-DD).
     *
     * @param  string  $date
     * @return string
     */
    function date_db($date)
    {
        return implode('-', array_reverse(explode('/', $date)));
    }
}

if (! function_exists('date_view')) {

    function date_view($date)
    {
        return implode('/', array_reverse(explode('-', $date)));
    }
}

if (! function_exists('datetime_db')) {

    /**
     * Convert date for DB format (YYYY-MM-DD H:I:S).
     *
     * @param  string  $datetime
     * @return string
     */
    function datetime_db($datetime = null)
    {
        if ($datetime) {
            return null;
        }

        [$date, $time] = explode(' ', $datetime);

        return date_db($date).' '.$time;
    }
}

if (! function_exists('start_datetime_db')) {

    function start_datetime_db($date)
    {
        return date_db($date).' 00:00:00';
    }
}

if (! function_exists('end_datetime_db')) {

    function final_datetime_db($date)
    {
        return date_db($date).' 23:59:59';
    }
}

if (! function_exists('is_date_null')) {

    function is_date_null($date)
    {
        return $date = (! is_null($date)) ? $date : Carbon::now()->toDateString();
    }
}

if (! function_exists('date_range_start')) {

    function date_range_start($dateRange)
    {
        if (! $dateRange) {
            return null;
        }

        [$start, $space, $end] = explode(' ', $dateRange);

        return implode('-', array_reverse(explode('/', $start)));
    }
}

if (! function_exists('date_range_end')) {

    function date_range_end($dateRange)
    {
        if (! $dateRange) {
            return null;
        }

        [$start, $space, $end] = explode(' ', $dateRange);

        return implode('-', array_reverse(explode('/', $end)));
    }
}

if (! function_exists('is_date_null')) {

    function is_date_null($date)
    {
        return $date = (! is_null($date)) ? $date : Carbon::now()->toDateString();
    }
}

if (! function_exists('date_in_full')) {

    function date_in_full($data)
    {

        $date = date('D', strtotime($data));
        $monthSet = date('M', strtotime($data));
        $day = date('d', strtotime($data));
        $year = date('Y', strtotime($data));

        $week = [
            'Sun' => 'Domingo',
            'Mon' => 'Segunda-Feira',
            'Tue' => 'Terca-Feira',
            'Wed' => 'Quarta-Feira',
            'Thu' => 'Quinta-Feira',
            'Fri' => 'Sexta-Feira',
            'Sat' => 'Sábado',
        ];

        $month = [
            'Jan' => 'Janeiro',
            'Feb' => 'Fevereiro',
            'Mar' => 'Marco',
            'Apr' => 'Abril',
            'May' => 'Maio',
            'Jun' => 'Junho',
            'Jul' => 'Julho',
            'Aug' => 'Agosto',
            'Nov' => 'Novembro',
            'Sep' => 'Setembro',
            'Oct' => 'Outubro',
            'Dec' => 'Dezembro',
        ];

        return $week["$date"].", {$day} de ".$month["$monthSet"]." de {$year}";
    }
}

if (! function_exists('date_month')) {

    function date_month($data)
    {

        $date = date('D', strtotime($data));
        $monthSet = date('M', strtotime($data));

        $month = [
            'Jan' => 'Janeiro',
            'Feb' => 'Fevereiro',
            'Mar' => 'Marco',
            'Apr' => 'Abril',
            'May' => 'Maio',
            'Jun' => 'Junho',
            'Jul' => 'Julho',
            'Aug' => 'Agosto',
            'Sep' => 'Setembro',
            'Oct' => 'Outubro',
            'Nov' => 'Novembro',
            'Dec' => 'Dezembro',
        ];

        return $month["$monthSet"];
    }
}

if (! function_exists('get_month')) {
    function get_month($month = null)
    {
        $months = [
            1 => 'JANEIRO',
            2 => 'FEVEREIRO',
            3 => 'MARÇO',
            4 => 'ABRIL',
            5 => 'MAIO',
            6 => 'JUNHO',
            7 => 'JULHO',
            8 => 'AGOSTO',
            9 => 'SETEMBRO',
            10 => 'OUTUBRO',
            11 => 'NOVEMBRO',
            12 => 'DEZEMBRO',
        ];

        return ($month) ? $months[$month] : $months;
    }
}
