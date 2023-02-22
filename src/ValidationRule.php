<?php

namespace ScwWpRestRegistration;

use \WP_Error;

class ValidationRule
{
    private string $definition;
    private array $arguments;
    private $messageCentre;
    private $param;
    private $value;
    private $request;
    public WP_Error $error;

    public function __construct($ruleItem, $param, $value, $messageCentre, $request)
    {
        $this->definition = $this->resolveDefinition($ruleItem);
        $this->arguments = $this->resolveArguments($ruleItem);
        $this->messageCentre = $messageCentre;
        $this->param = $param;
        $this->value = $value;
        $this->request = $request;

        if (method_exists($this, $this->definition)) {
            $this->{$this->definition}();
        }
    }

    public function resolveDefinition(string $ruleItem): string
    {
        $exploded = explode(":", $ruleItem);
        return array_shift($exploded);
    }

    public function resolveArguments(string $ruleItem): array
    {
        $exploded = explode(":", $ruleItem, 2);
        $argString = array_pop($exploded);
        return explode(",", $argString);
    }

    private function required()
    {
        if (empty($this->value)) $this->error($this->formatMessage('required'));
    }

    private function string()
    {
        if (!is_string($this->value)) $this->error($this->formatMessage('string'));
    }

    private function array()
    {
        if (!is_array($this->value)) $this->error($this->formatMessage('array'));
    }

    private function error($message)
    {
        return new \WP_Error('validation_failed', $message);
    }

    private function formatMessage(string $ruleName, array $replacers = []): string
    {
        $message = $this->messageCentre->messages[$ruleName];
        $replacers = array_merge($replacers, ['param' => $this->param, 'value' => $this->value]);
        foreach ($replacers as $key => $replacer) {
            $message = str_replace("%" . $key . "%", $replacer, $message);
        }
        return $message;
    }
}
