<?php

namespace App\Services\Billing;

/**
 * Raised by InvoiceIssuer when the source record isn't in a state the
 * ledger will invoice (not billable, not paid, missing user, etc.).
 */
class InvoiceNotIssuableException extends \DomainException
{
}
