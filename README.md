<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://southcoastweb.co.uk/images/new-scw-logo-dark.svg">
  <source media="(prefers-color-scheme: light)" srcset="https://southcoastweb.co.uk/images/new-scw-logo.svg">
  <img alt="southcoastweb company logo" src="https://southcoastweb.co.uk/images/new-scw-logo.svg">
</picture>

<a href="https://packagist.org/packages/southcoastweb/scw-wp-rest-registration"><img src="https://img.shields.io/packagist/v/southcoastweb/scw-wp-rest-registration?logo=packagist&logoColor=white" alt="Build status" /></a>
<a href="https://packagist.org/packages/southcoastweb/scw-wp-rest-registration"><img src="https://img.shields.io/packagist/dt/southcoastweb/scw-wp-rest-registration" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/southcoastweb/scw-wp-rest-registration"><img src="https://img.shields.io/packagist/l/southcoastweb/scw-wp-rest-registration" alt="License"></a>

# WP REST Registration

## by [southcoastweb](https://southcoastweb.co.uk)

Register your Wordpress REST API routes simply and easily.

### Features

-   Automatic registration of your routes
-   Base controller for easily creating object-orientated route files
-   Rule-based validation for common types

---

## Installation

1. Run `composer require southcoastweb/scw-wp-rest-registration` at the root of your plugin or theme folder

2. Call `new RestApi([]);` in your plugin PHP entry file or theme `functions.php` file with the arguments below:

## Arguments

`new RestApi()` accepts the following arguments as part of a **single associative array**

| Arg       | Required | Default | Description                               |
| --------- | -------- | ------- | ----------------------------------------- |
| base_url  | yes      | N/A     | The base URL used for your routes         |
| version   | no       | 1       | Your API version                          |
| namespace | yes      | N/A     | The namespace of your route classes       |
| directory | yes      | N/A     | The base directory for your route classes |

### Example

```php
use ScwWpRestRegistration\RestApi;

new RestApi([
    'namespace' => 'MyPlugin\\Admin\\RestApi\\',
    'version' => 1,
    'directory' => __DIR__ . '/src/Admin/RestApi',
    'base_url' => 'scw-rest-api'
]);
```

The above example will load route files from the directory given with the base URL of `https://southcoastweb.co.uk/wp-json/scw-rest-api/v1/`

---

## Route Files

Inside your routes directory, you can now create route classes that will be automatically registered:

```php
namespace MyPlugin\Admin\RestApi;

use WP_REST_Request;
use ScwWpRestRegistration\BaseRestController;

defined('ABSPATH') or exit;

class CheckSomevar extends BaseRestController
{
    protected $path = 'posts';
    protected $methods = 'GET';

    public function __construct()
    {
        // See https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#arguments
        $this->args = [
            'somevar' => [
                'required' => true,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                }
            ]
        ];
    }

    public function authorise()
    {
        return current_user_can('manage_options');
    }

    public function handler(WP_REST_Request $request)
    {
        global $wpdb;

        $somevar = $request->get_param('somevar');

        wp_send_json_success([
            'status'     => 'success',
            'response' => 'Yep, somevar was a number',
        ]);
    }
}
```

## Validation

You can add a `rules` property to your controller to automatically validate parameters.

```php
class CheckSomevar extends BaseRestController
{
    protected $rules = [
        'somevar' => ['required','numeric']
    ]

    public function handler()
    {
        $validated = $this->validated();

        // Only fields with rules that have been passed will be on your validated parameters.

        // Failure will result in a WP_Error('validation_failed') error being returned (HTTP status 400)
    }
}
```

### Available rules

-   Rules are passed as an array of strings. Strings may be either `rulename` format, `rulename:arg` format or `rulename:arg,arg,arg` format. Arguments' function will vary depending on the rule in question.

| Rule name | Arg                  | Arg 2    | Arg 3      | Description                                       |
| --------- | -------------------- | -------- | ---------- | ------------------------------------------------- |
| required  |                      |          |            | Param is required                                 |
| nullable  |                      |          |            | Remaining rules are skipped if null value         |
| sometimes |                      |          |            | Remaining rules are skipped if param not set      |
| boolean   |                      |          |            | Must be either true or false                      |
| string    |                      |          |            | Must be a string                                  |
| email     |                      |          |            | Must be a valid email address                     |
| url       | require (path,query) | as Arg 1 |            | Must be a valid URL                               |
| json      |                      |          |            | Must be a decodable JSON string                   |
| numeric   |                      |          |            | Must be a numeric value (int or float)            |
| array     |                      |          |            | Must be an array                                  |
| in        | val1                 | val2 etc |            | Value must be one of passed args                  |
| exists    | table (no prefix)    | column   |            | Value must exist on the given table/column        |
| unique    | table (no prefix)    | column   | exclude id | Value must _not_ exists on the given table/column |

### Note

This package does not autoload your route classes. They will need to be registered before running the `init` function
