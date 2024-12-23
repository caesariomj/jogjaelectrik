<?php

namespace App\Exceptions;

use Exception;

class ApiRequestException extends Exception
{
    protected string $logMessage;

    protected string $userMessage;

    protected int $statusCode;

    public function __construct($logMessage = '', $userMessage = '', $statusCode = 500)
    {
        parent::__construct($logMessage);
        $this->logMessage = (string) $logMessage;
        $this->userMessage = (string) $userMessage;
        $this->statusCode = (int) $statusCode;
    }

    public function getLogMessage()
    {
        return $this->logMessage;
    }

    public function getUserMessage()
    {
        return $this->userMessage;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
