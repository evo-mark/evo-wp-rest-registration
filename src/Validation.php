<?php

namespace ScwWpRestRegistration;


class Validation
{
    protected $rules;
    protected $args;
    public $validated;
    public $request;
    protected $messageCentre;

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
            $this->args[$key]['required'] = in_array('required', $ruleSet);
            $this->args[$key]['validate_callback'] = $this->validationCallback($ruleSet);
        }
        return $this->args;
    }

    /** 
     * Generates a callback that is used as the `validate_callback` property on the arg
     * 
     * Seems to only be called when a value is set on the request
     */
    public function validationCallback(array $ruleSet): callable
    {
        return function ($value, $request, $param) use ($ruleSet) {
            foreach ($ruleSet as $ruleItem) {
                $ruleResolver = new ValidationRule($ruleItem, $param, $value, $this->messageCentre, $request);
                if ($ruleResolver->error) {
                    return $ruleResolver->error;
                } else if ($ruleResolver->skip === true) {
                    break;
                }
            }
            // Set the value on the validated array for use in the controller
            $this->validated[$param] = $value;
        };
    }
}
