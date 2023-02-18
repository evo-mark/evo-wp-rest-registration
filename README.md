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

---

## Installation

1. Run `composer require southcoastweb/scw-wp-rest-registration` at the root of your plugin or theme folder

2. Call `RestApi::init();` in your plugin PHP entry file or theme `functions.php` file with the arguments below:

## Arguments

`RestApi::init()` accepts the following arguments as part of a **single associative array**

| Arg       | Required | Default | Description                               |
| --------- | -------- | ------- | ----------------------------------------- |
| base_url  | yes      | N/A     | The base URL used for your routes         |
| version   | no       | 1       | Your API version                          |
| namespace | yes      | N/A     | The namespace of your route classes       |
| directory | yes      | N/A     | The base directory for your route classes |

### Example

```php
RestApi::init([
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

### Note

This package does not autoload your route classes. They will need to be registered before running the `init` function
