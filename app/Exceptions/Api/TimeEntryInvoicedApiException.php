<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

class TimeEntryInvoicedApiException extends ApiException
{
    public const string KEY = 'time_entry_invoiced';
}
