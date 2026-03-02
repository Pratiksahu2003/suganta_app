<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array sendTemplate(string $mobile, string $templateKey, array $variables = [])
 * @method static array sendRaw(string $mobile, string $message, string|null $dltTemplateId = null)
 *
 * @see \App\Services\SmsCountryService
 */
class Sms extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'smscountry';
    }
}
