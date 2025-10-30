<?php

if (! function_exists('calc_percentage')) {

    function calc_percentage($percentage, $total = 0)
    {
        return $total == 0 ?: round(($total * $percentage) / 100, 2);
    }
}

if (! function_exists('porcentagem_xn')) {
    function porcentagem_xn($porcentagem, $total)
    {
        return ($porcentagem / 100) * $total;
    }
}

if (! function_exists('percentageTwoValues')) {

    /**
     * Calculo para saber a porcentagem de dois valores.
     *
     * (Valor Maior - Valor Menor ) * 100 / Valor Menor
     */
    function percentageTwoValues($larger, $smaller)
    {
        $difference = money_db($larger) - money_db($smaller);

        return money_decimal_db(
            ($difference * 100) / money_db($smaller)
        );
    }
}

if (! function_exists('setting')) {

    function setting($key, $default = null)
    {
        if (is_null($key)) {
            return new \App\Models\Setting;
        }

        if (is_array($key)) {
            return \App\Models\Setting::set($key[0], $key[1]);
        }

        $value = \App\Models\Setting::get($key);

        return is_null($value) ? value($default) : $value;
    }
}
