<?php

namespace EvoWpRestRegistration;

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
            'accepted' => 'You must accept the %param%',
            'numeric' => '%param% must be numeric',
            'array' => '%param% must be an array',
            'boolean' => '%param% must be true or false',
            'email' => 'Valid email address is required',
            'unique' => '%value% already exists as a %param%',
            'exists' => '%param% must exist on %args%',
            'in' => '%param% must be one of %args%',
            'json' => '%param% must be a valid JSON string',
            'url' => '%param% must be a valid URL',
            'min' => '%param% must be at least %args%',
            'max' => '%param% must be no more than %args%',
        ];
    }
}
