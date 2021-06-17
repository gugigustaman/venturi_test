<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    protected $customCode;
    protected $customData;

    public function __construct($message, $customCode, $customData = [])
    {
        $this->customCode = $customCode;
        $this->customData = $customData;
        parent::__construct($message, 15151);
    }

    public function getCustomCode()
    {
        return $this->customCode;
    }

    public function render($request)
    {
        $payload = [
            'message' => $this->getMessage()
        ];

        $payload = array_merge($payload, $this->customData);

        return response()->json($payload, $this->customCode);
    }
}
