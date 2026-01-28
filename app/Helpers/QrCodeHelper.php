<?php

namespace App\Helpers;

class QrCodeHelper
{
    /**
     * Generate a QR code image URL for a given verification URL.
     * 
     * @param string $verificationUrl The full URL to encode in the QR code
     * @param int $size The size of the QR code in pixels (default: 200)
     * @return string The QR code image URL
     */
    public static function generateUrl(string $verificationUrl, int $size = 200): string
    {
        // Use qrserver.com API (free, no API key required)
        $encoded = urlencode($verificationUrl);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded}&format=png&margin=1";
    }
}
