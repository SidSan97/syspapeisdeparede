<?php

if (! function_exists('hashId')) {

    /**
     * Formata campo id para exibir nas views.
     *
     * @param  string  $number
     * @return string
     */
    function hashId($number)
    {
        return '#'.\Illuminate\Support\Str::of($number)->pad(6);
    }
}

if (! function_exists('text_status')) {

    /**
     * Exibe icone relativo ao status do registro.
     *
     * @return string
     */
    function text_status(int $status)
    {
        return $status ? 'Ativo' : 'Inativo';
    }
}

if (! function_exists('icon_status')) {

    function icon_status(int $status)
    {
        $icon = $status == 1
            ? ['icon' => 'check', 'color' => 'text-success']
            : ['icon' => 'times', 'color' => 'text-danger'];

        echo vsprintf('<i class="fa fa-%s %s"></i>', $icon);
    }
}

if (! function_exists('active_tab')) {

    function active_tab($name)
    {
        return request()->tab == $name ? 'active show' : '';
    }
}

if (! function_exists('set_selected')) {

    function set_selected($field, $value)
    {
        return $field == $value ? 'selected' : '';
    }
}

if (! function_exists('set_checked')) {

    function set_checked($field, $value)
    {
        return $field != $value ?: 'checked';
    }
}

if (! function_exists('limit_text')) {

    function limit_text($text, $limit = 40, $end = '...')
    {
        return \Illuminate\Support\Str::limit($text, $limit, $end);
    }
}
