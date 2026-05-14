<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class CertificateService
{
    public function qrSvgDataUri(string $payload, int $size = 220): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd(),
        );

        $svg = (new Writer($renderer))->writeString($payload);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
