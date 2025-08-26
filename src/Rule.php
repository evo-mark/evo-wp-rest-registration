<?php

namespace EvoWpRestRegistration;

class Rule
{
    protected string $type;
    protected array $args;

    public function __construct(string $type, array $args)
    {
        $this->type = $type;
        $this->args = $args;
    }

    public function validate(mixed $value): ?string
    {
        switch ($this->type) {
            case "enum":
                if (enum_exists($this->args['class']) === false) {
                    return "enum_invalid";
                }
                $match = $this->args['class']::tryFrom($value);
                return empty($match) ? "enum_invalid_value" : null;
            default:
                return null;
        };
    }

    public static function enum(string $class)
    {
        return new static(type: 'enum', args: ['class' => $class]);
    }
}
