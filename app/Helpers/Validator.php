<?php
namespace App\Helpers;

/**
 * Input Validator
 */
class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "{$label} is required.";
        }
        return $this;
    }

    public function email(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} must be a valid email.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $min) {
            $this->errors[$field] = "{$label} must be at least {$min} characters.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $max) {
            $this->errors[$field] = "{$label} must be at most {$max} characters.";
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "{$label} must be a number.";
        }
        return $this;
    }

    public function in(string $field, array $values, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = "{$label} is not a valid option.";
        }
        return $this;
    }

    public function match(string $field1, string $field2, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field1));
        if (($this->data[$field1] ?? '') !== ($this->data[$field2] ?? '')) {
            $this->errors[$field1] = "{$label} does not match.";
        }
        return $this;
    }

    public function file(string $field, array $allowedTypes = [], int $maxSize = 0, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return $this;

        $file = $_FILES[$field];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$field] = "{$label} upload failed.";
            return $this;
        }
        if ($maxSize > 0 && $file['size'] > $maxSize) {
            $this->errors[$field] = "{$label} exceeds maximum size.";
        }
        if (!empty($allowedTypes)) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedTypes)) {
                $this->errors[$field] = "{$label} type is not allowed.";
            }
        }
        return $this;
    }

    public function passes(): bool { return empty($this->errors); }
    public function fails(): bool { return !empty($this->errors); }
    public function errors(): array { return $this->errors; }
    public function firstError(): string { return reset($this->errors) ?: ''; }
}
