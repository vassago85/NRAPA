@php
    /** @var string $qrCodeUrl */
    /** @var string $verifyUrl */
    $verifyStripTitle ??= 'Verify This Document';
    $verifyStripBlurb ??= 'Scan the QR code or visit the link below to confirm this is a genuine NRAPA document.';
@endphp
<div class="verify-strip">
    <table class="verify-strip-table">
        <tr>
            <td class="verify-strip-qr">
                <div class="qr-box">
                    <img src="{{ $qrCodeUrl }}" alt="QR Code"/>
                </div>
            </td>
            <td class="verify-strip-text">
                <div class="verify-strip-title">{{ $verifyStripTitle }}</div>
                <div class="verify-strip-blurb">{{ $verifyStripBlurb }}</div>
                <a href="{{ $verifyUrl }}" class="verify-strip-link">{{ $verifyUrl }}</a>
            </td>
        </tr>
    </table>
</div>
