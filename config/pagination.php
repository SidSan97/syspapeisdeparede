<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default items per page
    |--------------------------------------------------------------------------
    |
    | Valor global de itens por página para listagens paginadas.
    | Altere via variável de ambiente PAGINATION_PER_PAGE para afetar
    | todos os pontos que utilizam este config.
    |
    */
    'per_page' => env('PAGINATION_PER_PAGE', 15),
];


