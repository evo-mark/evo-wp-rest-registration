<?php

namespace EvoWpRestRegistration;

use EvoWpRestRegistration\Validation;
use WP_REST_Server;

defined('ABSPATH') or exit;

abstract class BaseRestController
{
    protected $path;
    protected $methods;
    protected array $args = [];
    protected $rules = [];
    protected array $messages = [];
    public $validator;

    /**
     * Registers the REST endpoint's sanitised path
     */
    public function getPath(): string
    {
        return rtrim($this->path, '/') . '/';
    }

    /**
     * Registers the REST endpoint's `methods` property
     */
    public function getMethods(): string
    {
        return $this->methods ?? WP_REST_Server::READABLE;
    }

    /**
     * Registers the REST endpoint's `callback` property
     */
    public function getCallback(): callable
    {
        return [$this, 'handler'];
    }

    public function getPermissionCallback(): callable
    {
        return [$this, 'authorise'];
    }

    public function getArguments(): array
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
