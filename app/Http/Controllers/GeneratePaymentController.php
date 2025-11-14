<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Anisotton\Pagarme\Facades\Pagarme;
use App\Services\GeneratePaymentService;

class GeneratePaymentController extends Controller
{
    protected $generatePaymentService;

    public function __construct(GeneratePaymentService $generatePaymentService)
    {
        $this->generatePaymentService = $generatePaymentService;
    }

    /*public function createLinkPayment(Request $request)
    {
        $response = Pagarme::recipient()->post("recipients/{$recipient->recipient_id}/kyc_link");
        $json = Utils::jsonDecode($response->getBody(), true);

        $recipient->update([
            'kyc_url'        => $json['url'],
            'kyc_qrcode'     => $json['base64_qrcode'],
            'kyc_expires_at' => $json['expires_at'],
        ]);
    }*/

    public function getLinkPayment()
    {
        return $this->generatePaymentService->generateLinkPayment();
    }
}
