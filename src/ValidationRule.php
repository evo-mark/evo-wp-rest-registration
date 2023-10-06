<?php

namespace EvoWpRestRegistration;

use \WP_Error;

class ValidationRule
{
    private string $definition;
    private array $arguments;
    private $messageCentre;
    private $param;
    private $value;
    private $request;
    public $error;
    public bool $skip = false;

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
        if ($this->request->has_param($this->param) === false || empty($this->value)) $this->createError('required');
    }

    private function nullable()
    {
        if (empty($this->value)) $this->skip = true;
    }

    private function sometimes()
    {
        if ($this->request->has_param($this->param) === false) $this->skip = true;
    }

    private function accepted()
    {
        $acceptedValues = ['yes', 'on', '1', 'true'];

        if (!in_array(strtolower($this->value), $acceptedValues, true)) $this->createError('accepted');
    }

    private function string()
    {
        if (!is_string($this->value)) $this->createError('string');
    }

    private function numeric()
    {
        if (!is_numeric($this->value)) $this->createError('numeric');
    }

    private function array()
    {
        if (!is_array($this->value)) $this->createError('array');
    }

    private function in()
    {
        if (!in_array($this->value, $this->arguments)) $this->createError('in');
    }

    private function boolean()
    {
        if (!is_bool($this->value)) $this->createError('boolean');
    }

    private function email()
    {
        if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) $this->createError('email');
    }

    private function json()
    {
        if (is_null(json_decode($this->value))) $this->createError('json');
    }

    private function url()
    {
        $filters = [FILTER_VALIDATE_URL];
        if (in_array('path', $this->arguments)) $filters[] = FILTER_FLAG_PATH_REQUIRED;
        if (in_array('query', $this->arguments)) $filters[] = FILTER_FLAG_QUERY_REQUIRED;
        if (!filter_var($this->value, ...$filters)) $this->createError('url');
    }

    private function exists()
    {
        global $wpdb;

        $column = $this->arguments[1] ?? $this->param;
        $table = $wpdb->prefix . $this->arguments[0];

        $prepared = $wpdb->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = %s", [$this->value]);

        $count = intval($wpdb->get_var($prepared));
        if ($count === 0) {
            $this->createError('exists');
        }
    }

    private function unique()
    {
        global $wpdb;

        $table = $wpdb->prefix . $this->arguments[0];
        $column = $this->arguments[1] ?? $this->param;
        $ignore = $this->arguments[2] ?? null;

        $prepared = $wpdb->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = %s", [$this->value]);
        if ($ignore) {
            $prepared .= " AND `id` != " . $this->request->get_param('id');
        }

        $count = intval($wpdb->get_var($prepared));
        if ($count > 0) {
            $this->createError('unique');
        }
    }

    private function createError($ruleName)
    {
        $this->error = new \WP_Error('validation_failed', $this->formatMessage($ruleName));
    }

    private function formatMessage(string $ruleName, array $replacers = []): string
    {
        $message = $this->messageCentre->messages[$ruleName];
        $replacers = array_merge($replacers, ['param' => ucwords(str_replace('_', ' ', $this->param)), 'value' => $this->value, 'args' => implode(", ", $this->arguments)]);
        foreach ($replacers as $key => $replacer) {
            if (is_string($replacer) === false) {
                if (is_array($replacer) && empty($replacer)) {
                    $replacer = "";
                } else if (is_array($replacer) || is_object($replacer)) {
                    $replacer = json_encode($replacer);
                }
            }
            $message = str_replace("%" . $key . "%", $replacer, $message);
        }
        return $message;
    }
}
