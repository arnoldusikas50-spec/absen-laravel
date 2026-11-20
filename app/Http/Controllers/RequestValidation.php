<?php

namespace App\Http\Controllers;

class RequestValidation
{
    public function __construct(
        protected $validator = null,
        protected array $errors = [],
    ) {}

    public function getError(string $field): string
    {
        if (isset($this->errors[$field])) {
            return (string) $this->errors[$field];
        }
        if ($this->validator) {
            return (string) $this->validator->errors()->first($field);
        }

        return '';
    }

    public function getErrors(): array
    {
        if (! empty($this->errors)) {
            return $this->errors;
        }
        if ($this->validator) {
            $out = [];
            foreach ($this->validator->errors()->messages() as $field => $msgs) {
                $out[$field] = $msgs[0] ?? '';
            }

            return $out;
        }

        return [];
    }

    public function hasError(string $field): bool
    {
        return $this->getError($field) !== '';
    }

    public function listErrors(): string
    {
        return implode("\n", $this->getErrors());
    }
}
