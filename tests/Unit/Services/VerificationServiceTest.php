<?php

use App\Services\VerificationService;

describe('maskIdentityNumberForPublicDisplay', function () {
    it('returns null for empty input', function () {
        expect(VerificationService::maskIdentityNumberForPublicDisplay(null))->toBeNull();
        expect(VerificationService::maskIdentityNumberForPublicDisplay(''))->toBeNull();
        expect(VerificationService::maskIdentityNumberForPublicDisplay('   '))->toBeNull();
    });

    it('masks a 13-digit style value with first and last segments', function () {
        $masked = VerificationService::maskIdentityNumberForPublicDisplay('8507123455088');
        expect($masked)->toBe('8507*****5088');
    });

    it('strips spaces before masking', function () {
        $masked = VerificationService::maskIdentityNumberForPublicDisplay('8507 123455088');
        expect($masked)->toBe('8507*****5088');
    });
});
