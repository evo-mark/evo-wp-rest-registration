<?php

namespace ScwWpRestRegistration;

defined('ABSPATH') or exit;

abstract class BaseRestController
{
    protected $path;
    protected $methods;
    protected $args;

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
        return $this->args ?? [];
    }

    public function authorise()
    {
        return false;
    }
}
