<?php

namespace App\Http\Controllers\Sustainability;

use App\Http\Controllers\Controller;
use App\Models\RecyclingCertificate;
use App\Services\CertificateService;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    public function download(RecyclingCertificate $certificate, CertificateService $certificates)
    {
        $certificate->load('activity');

        $pdf = Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'activity' => $certificate->activity,
            'qrCode' => $certificates->qrSvgDataUri($certificate->qr_payload),
        ])->setPaper('a4');

        return $pdf->download($certificate->certificate_number.'.pdf');
    }

    public function verify(string $token, CertificateService $certificates)
    {
        $certificate = RecyclingCertificate::query()
            ->where('verification_token', $token)
            ->with('activity')
            ->firstOrFail();

        return view('certificates.verify', [
            'certificate' => $certificate,
            'activity' => $certificate->activity,
            'qrCode' => $certificates->qrSvgDataUri($certificate->qr_payload, 160),
        ]);
    }
}
