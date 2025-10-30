<?php

use Illuminate\Support\Str;

if (! function_exists('str_contains')) {

    function str_contains($haystack, $needles)
    {
        return Str::contains($haystack, $needles);
    }
}

if (! function_exists('str_caracter')) {

    function str_caracter($text)
    {
        return Str::of($text)->replaceMatches('/[^A-Za-z0-9]++/', ' ');
    }
}
