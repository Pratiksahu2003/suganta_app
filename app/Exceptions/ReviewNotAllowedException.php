<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ReviewNotAllowedException extends HttpException
{
    public function __construct(string $message = 'You are not allowed to perform this action.')
    {
        parent::__construct(403, $message);
    }
}
