<?php

namespace App\Exceptions;

use Exception;

class ApiRequestException extends Exception
{
    protected $statusCode;

    public function __construct($message = '', $statusCode = 500)
    {
        parent::__construct();
        $this->statusCode = $statusCode;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
