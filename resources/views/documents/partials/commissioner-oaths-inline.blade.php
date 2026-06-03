@php
    $commissionerSignatureHtml = \App\Helpers\DocumentDataHelper::getCommissionerSignatureHtml();
@endphp
<div class="card commissioner-inline">
    <div class="card-title">Commissioner of Oaths</div>
    <div class="commissioner-text">
        I certify that this is a true and correct copy of the original document which has been presented to me, and that according to my observations no alterations have been made to either the original document nor the copy.
    </div>
    <table class="commissioner-sign-table">
        <tr>
            <td class="commissioner-sig-label-cell">Signature:</td>
            <td class="commissioner-sig-line-cell">
                @if($commissionerSignatureHtml)
                    <span class="commissioner-sig-img">{!! $commissionerSignatureHtml !!}</span>
                @endif
            </td>
        </tr>
    </table>
    <div class="commissioner-details">
        <div class="commissioner-role">COMMISSIONER OF OATHS</div>
        <div>L van Rooyen</div>
        <div>SAIPA PR15741</div>
        <div>1152 Meyer Street, Waverley, Pretoria</div>
    </div>
</div>
