<?php

namespace App\Exceptions;

use Exception;

class DuplicateNikException extends Exception
{
    public function __construct(string $nik = '')
    {
        $message = $nik
            ? "NIK '{$nik}' sudah terdaftar dalam sistem."
            : 'NIK sudah terdaftar dalam sistem.';

        parent::__construct($message);
    }
}
