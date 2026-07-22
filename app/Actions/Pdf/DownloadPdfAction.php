<?php

declare(strict_types=1);

namespace App\Actions\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class DownloadPdfAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(
        string $view,
        array $data,
        string $filename,
        string $paper = 'a4',
        string $orientation = 'portrait'
    ): Response {

        return Pdf::loadView($view, $data)
            ->setPaper($paper, $orientation)
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'sans-serif',
            ])
            ->download($filename);
    }
}
