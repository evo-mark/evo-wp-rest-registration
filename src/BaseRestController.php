<?php

namespace EvoWpRestRegistration;

use EvoWpRestRegistration\Validation;
use WP_REST_Server;

defined('ABSPATH') or exit;

abstract class BaseRestController
{
    public $validator;
    protected $path;
    protected $methods;
    protected bool $indexed = false;
    protected $rules = [];
    protected array $args = [];
    protected array $messages = [];

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
    public function getMethods(): string|array
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

    /**
     * Return either the full validated array, or a field if given
     * @param string $field The optional field to pick from the validated array
     * @return mixed Either the whole validated array or a single field of it
     */
    public function validated(?string $field = null): mixed
    {
        if (!empty($field)) {
            return $this->validator->validated[$field] ?? null;
        }

        return $this->validator->validated ?? [];
    }

    public function showInIndex(): bool
    {
        return $this->indexed === true;
    }
}
