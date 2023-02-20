<?php

namespace ScwWpRestRegistration;

class ValidationRule
{
    public string $definition;
    public array $arguments;


    public function __construct($ruleItem, $param, $value, $request)
    {
        $this->definition = $this->resolveDefinition($ruleItem);
        $this->arguments = $this->resolveArguments($ruleItem);

        if (method_exists($this, $this->definition)) {
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

    private function required($value, $request, $param)
    {
        if (empty($value)) return new \WP_Error('validation_failed', $this->formatMessage('required', $value, $request, $param));
    }

    private function string($value, $request, $param)
    {
        if (!is_string($value)) return new \WP_Error('validation_failed', $this->formatMessage('string', $value, $request, $param));
    }

    private function array($value, $request, $param)
    {
        if (!is_array($value)) return new \WP_Error('validation_failed', $this->formatMessage('array', $value, $request, $param));
    }
}
