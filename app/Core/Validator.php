<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $errors = [];

    public function required(string $field, string $label, ?array $source = null): self
    {
        $source = $source ?? $_POST;
        $value = trim((string) ($source[$field] ?? ''));
        if ($value === '') {
            $this->errors[] = $label . ' is required.';
        }
        return $this;
    }

    public function email(string $field, string $label, ?array $source = null): self
    {
        $source = $source ?? $_POST;
        $value = trim((string) ($source[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = $label . ' must be a valid email address.';
        }
        return $this;
    }

    public function max(string $field, string $label, int $max, ?array $source = null): self
    {
        $source = $source ?? $_POST;
        $value = (string) ($source[$field] ?? '');
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ($length > $max) {
            $this->errors[] = $label . ' must not exceed ' . $max . ' characters.';
        }
        return $this;
    }

    public function in(string $field, string $label, array $allowed, ?array $source = null): self
    {
        $source = $source ?? $_POST;
        $value = (string) ($source[$field] ?? '');
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[] = $label . ' has an invalid value.';
        }
        return $this;
    }

    public function numeric(string $field, string $label, ?array $source = null): self
    {
        $source = $source ?? $_POST;
        $value = (string) ($source[$field] ?? '');
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[] = $label . ' must be a valid number.';
        }
        return $this;
    }

    public function min(string $field, string $label, int $min, ?array $source = null): self
    {
        $source = $source ?? $_POST;
        $value = (string) ($source[$field] ?? '');
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ($value !== '' && $length < $min) {
            $this->errors[] = $label . ' must be at least ' . $min . ' characters.';
        }
        return $this;
    }

    public function matches(string $field, string $otherField, string $label, ?array $source = null): self
    {
        $source = $source ?? $_POST;
        if ((string) ($source[$field] ?? '') !== (string) ($source[$otherField] ?? '')) {
            $this->errors[] = $label . ' does not match ' . str_replace('_', ' ', $otherField) . '.';
        }
        return $this;
    }

    public function url(string $field, string $label, ?array $source = null): self
    {
        $source = $source ?? $_POST;
        $value = trim((string) ($source[$field] ?? ''));
        if ($value !== '' && !preg_match('#^https?://[^\s]+$#i', $value)) {
            $this->errors[] = $label . ' must be a valid http(s) URL.';
        }
        return $this;
    }

    public function add(string $message): self
    {
        if ($message !== '') {
            $this->errors[] = $message;
        }
        return $this;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }
}
