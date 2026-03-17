<?php

namespace App\Http\Controllers;

class PublicCatalogController extends Controller
{
    /**
     * Renderiza a aplicação SPA para uso público (sem exigir autenticação),
     * usada pelo catálogo de coleções/imagens.
     */
    public function __invoke()
    {
        $scriptVariables = [
            'appName' => config('app.name'),
            'user' => null,
            'appUrl' => config('app.url'),
            'baseUrl' => url(''),
            'assetUrl' => asset(''),
        ];

        return view('application', [
            'scriptVariables' => $scriptVariables,
        ]);
    }
}

