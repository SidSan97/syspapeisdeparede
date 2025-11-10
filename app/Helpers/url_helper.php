<?php

if (! function_exists('route_is')) {

    /**
     * Verifica se a rota atual está contida em uma lista de rotas.
     *
     * @param  mixed  $names
     * @return bool
     */
    function route_is($names)
    {
        return in_array(\Route::current()->getName(), \Illuminate\Support\Arr::wrap($names));
    }
}

if (! function_exists('uploads_path')) {

    function uploads_path(string $path = '')
    {
        return asset('storage/'.$path);
    }
}

if (! function_exists('public_storage_path')) {

    /**
     * Retorna url do diretório público do storage.
     *
     * @return string
     */
    function public_storage_path(string $path = '')
    {
        return public_path('storage/'.$path);
    }
}
