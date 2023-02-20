<?php

namespace ScwWpRestRegistration;

use Dotenv\Validator;
use ScwWpRestRegistration\Validation;

defined('ABSPATH') or exit;

abstract class BaseRestController
{
    protected $path;
    protected $methods;
    protected array $args = [];
    protected $rules = [];
    protected array $messages = [];
    public $validator;

    public function getPath()
    {
        return rtrim($this->path, '/') . '/';
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function getCallback()
    {
        return [$this, 'handler'];
    }

    public function getPermissionCallback()
    {
        return [$this, 'authorise'];
    }

    public function getArguments()
    {
        if (!empty($this->rules)) {
            $this->validator = new Validation($this->rules, $this->args, new ValidationMessages($this->messages));
            return $this->validator->createValidationCallback();
        } else return $this->args ?? [];
    }

    public function authorise()
    {
        return false;
    }

    public function validated(): array
    {
        return $this->validator->validated ?? [];
    }
}
