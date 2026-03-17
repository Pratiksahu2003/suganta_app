<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class DuplicateReviewException extends HttpException
{
    public function __construct(string $message = 'You have already reviewed this entity.')
    {
        parent::__construct(409, $message);
    }
}
