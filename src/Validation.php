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

    public function createValidationCallback()
    {
        // Loop through rules and create a `validate_callback` callable
        foreach ($this->rules as $key => $ruleSet) {
            $args[$key]['validate_callback'] = $this->validationCallback($ruleSet);
        }
        return $args;
    }

    /** 
     * Generates a callback that is used as the `validate_callback` property on the arg
     */
    public function validationCallback(array $ruleSet): callable
    {
        return function ($value, $request, $param) use ($ruleSet) {
            foreach ($ruleSet as $ruleItem) {
                // $ruleItem = required | string | array
                $ruleResolver = new ValidationRule($ruleItem, $param, $value, $request);
                if (method_exists($this, $ruleItem)) {
                    $result = $this->{$ruleItem}($value, $request, $param);
                    if ($result instanceof \WP_Error) return $result;
                }
            }
            // Set the value on the validated array for use in the controller
            $this->validated[$param] = $value;
        };
    }

    public function formatMessage($ruleName, $value, $request, $param)
    {
        $message = $this->messageCentre->messages[$ruleName];
        $message = str_replace("%param%", $param, $message);
        $message = str_replace("%value%", $value, $message);
        return $message;
    }
}
