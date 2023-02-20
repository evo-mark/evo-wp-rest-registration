<?php

namespace ScwWpRestRegistration;

class ValidationMessages
{
    public $messages = [];

    public function __construct(array $userMessages = [])
    {
        $this->messages = array_merge($this->defaults(), $userMessages);
    }

    private function defaults()
    {
        return [
            'required' => '%param% is required',
            'string' => '%param% must be a string',
            'array' => '%param% must be an array'
        ];
    }
}
