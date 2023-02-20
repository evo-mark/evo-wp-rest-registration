<?php

namespace ScwWpRestRegistration;

class Validation
{
    protected $rules;
    protected $args;
    protected $messageCentre;
    public $validated;

    public function __construct($rules, $args, $messages)
    {
        $this->validated = [];
        $this->rules = $rules;
        $this->args = $args;
        $this->messageCentre = $messages;
    }

    public function make()
    {
        foreach ($this->rules as $key => $rule) {
            $args[$key]['validate_callback'] = $this->validationCallback($rule);
        }
        return $args;
    }

    /** 
     * Generates a callback that is used as the `validate_callback` property on the arg
     */
    public function validationCallback(array $rule): callable
    {
        return function ($value, $request, $param) use ($rule) {
            foreach ($rule as $ruleItem) {
                if (method_exists($this, $ruleItem)) {
                    $result = $this->{$ruleItem}($value, $request, $param);
                    if ($result instanceof \WP_Error) return $result;
                }
            }
            $this->validated[$param] = $value;
        };
    }

    public function required($value, $request, $param)
    {
        if (empty($value)) return new \WP_Error('validation_failed', $this->formatMessage('required', $value, $request, $param));
    }

    public function string($value, $request, $param)
    {
        if (!is_string($value)) return new \WP_Error('validation_failed', $this->formatMessage('string', $value, $request, $param));
    }

    public function array($value, $request, $param)
    {
        if (!is_array($value)) return new \WP_Error('validation_failed', $this->formatMessage('array', $value, $request, $param));
    }

    public function formatMessage($ruleName, $value, $request, $param)
    {
        $message = $this->messageCentre->messages[$ruleName];
        $message = str_replace("%param%", $param, $message);
        $message = str_replace("%value%", $value, $message);
        return $message;
    }
}
