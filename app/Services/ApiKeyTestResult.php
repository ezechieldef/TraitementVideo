<?php

namespace App\Services;

class ApiKeyTestResult
{
    public function __construct(
        public bool $success,
        public string $message = ''
    ) {}
}
