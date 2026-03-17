<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class DuplicateReviewException extends HttpException
{
    public function __construct(string $message = 'You have already submitted a review for this user. You can edit your existing review instead.')
    {
        parent::__construct(409, $message);
    }
}
