<?php

namespace EvoWpRestRegistration;

class RestApi
{
    public $base_url;
    public $version;
    public $namespace;
    public $directory;
    public $cors;

    public function __construct(array $args = [])
    {
        $required = ["base_url", "namespace", "directory"];
        foreach ($required as $item) {
            if (!isset($args[$item]) || empty($args[$item])) {
                throw new \Exception("Evo WP Rest Registration: no " . $item . " provided");
            }
        }

        $this->base_url = $args['base_url'];
        $this->version = $args['version'] ?? 1;
        $this->namespace = self::formatNamespace($args['namespace']);
        $this->directory = self::formatDir($args['directory']);

        add_action('rest_api_init', [$this, 'registerRoutes']);
        add_filter('rest_pre_dispatch', [$this, '_processRequest'], 10, 3);

        $this->cors = $args['cors'] ?? [];
        add_action('rest_api_init', [$this, 'setupCors'], 15);
    }

    /**
     * Check that the incoming request is for a route matching this prefix
     */
    private function requestMatchesPrefix($request): bool
    {
        $requestRoute = strtolower(trim($request->get_route(), "/ "));
        $prefix = strtolower($this->prefix());
        return strpos($requestRoute, $prefix) === 0;
    }

    /**
     * Move files to the main parameter array to allow their validation
     */
    public function _processRequest($result, $server, $request)
    {
        if ($this->requestMatchesPrefix($request)) {
            if (has_action(Hooks::PROCESS_REQUEST_FILES)) {
                do_action(Hooks::PROCESS_REQUEST_FILES, $request, $server);
            } else {
                $files = $request->get_file_params();
                if (!empty($files)) {
                    foreach ($files as $key => $file) {
                        $request->set_param($key, $file);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get the REST route prefix
     */
    public function prefix(): string
    {
        return $this->base_url . "/v" . $this->version;
    }

    public static function formatDir($dir): string
    {
        return rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
    }

    public static function formatNamespace($ns): string
    {
        return rtrim($ns, "\\") . "\\";
    }

    public static function rsearch($folder, $pattern): array
    {
        $dir = new \RecursiveDirectoryIterator($folder);
        $ite = new \RecursiveIteratorIterator($dir);
        $files = new \RegexIterator($ite, $pattern, \RegexIterator::GET_MATCH);

        $fileList = [];
        foreach ($files as $file) {
            $file = str_replace($folder, "", $file);
            $fileList = array_merge($fileList, $file);
        }
        return $fileList;
    }

    public static function convertPathToClass($path, $ext = ".php"): string
    {
        $path = str_replace($ext, "", $path);
        return str_replace(DIRECTORY_SEPARATOR, "\\", $path);
    }

    public function registerRoutes(): void
    {
        $routeFiles = self::rsearch($this->directory, "/.*\.php$/");
        $routeFiles = array_reverse($routeFiles);
        $count = 0;
        foreach ($routeFiles as $file) {
            $class = $this->namespace . self::convertPathToClass($file);

            if (class_exists($class)) {
                $endpoint = new $class;

                register_rest_route($this->prefix(), $this->strStart($endpoint->getPath(), "/"), [
                    'args'  => $endpoint->getArguments(),
                    'callback'  => $endpoint->getCallback(),
                    'methods'   => $endpoint->getMethods(),
                    'permission_callback' => $endpoint->getPermissionCallback(),
                    'show_in_index' => $endpoint->showInIndex()
                ]);
            } else {
                wp_die("Couldn't find the class $class to register the REST route");
            }
            $count++;
        }
    }

    private function strStart($subject, $cap = "/"): string
    {
        return $cap . ltrim($subject, $cap);
    }

    /**
     * Allow users to override the default CORS behaviour for REST endpoints
     */
    public function setupCors()
    {
        // Remove the default Wordpress CORS headers
        remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');

        // Add custom allowed headers
        add_filter('rest_allowed_cors_headers', function ($allowHeaders) {
            if (!empty($this->cors['allow_headers'])) {
                $headers = is_array($this->cors['allow_headers']) ? $this->cors['allow_headers'] : [$this->cors['allow_headers']];
                return array_merge($allowHeaders, $headers);
            } else {
                return $allowHeaders;
            }
        });

        // Add custom exposed headers
        add_filter('rest_exposed_cors_headers', function ($exposeHeaders) {
            if (!empty($this->cors['expose_headers'])) {
                $headers = is_array($this->cors['expose_headers']) ? $this->cors['expose_headers'] : [$this->cors['expose_headers']];
                return array_merge($exposeHeaders, $headers);
            } else {
                return $exposeHeaders;
            }
        });

        // Replace the default CORS headers with version that can be overridden globally and per-route
        add_filter('rest_pre_serve_request', function ($served, $result, $request, $server) {
            $isThis = $this->requestMatchesPrefix($request);
            if (!$isThis) {
                return \rest_send_cors_headers($served);
            }

            // ORIGIN
            $origin = get_http_origin();

            /**
             * Allows overriding the allow-origin CORS header
             *
             * @since 4.3.0
             *
             * @param string $allowOrigin       The string to pass to allow-origin header
             * @param \WP_HTTP_Response $result The Wordpress REST response instance.
             * @param \WP_REST_Request $request The Wordpress REST request instance
             * @param \WP_REST_Server $server   The Wordpress REST server instance
             */
            $allowOrigin = apply_filters('evo_wp_rest_allow_origin', $this->cors['allow_origin'] ?? null, $result, $request, $server);

            if (!empty($allowOrigin)) {
                header('Access-Control-Allow-Origin: ' . $allowOrigin);
            } else if ($origin) {
                if ('null' !== $origin) {
                    $origin = sanitize_url($origin);
                }
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Vary: Origin', false);
            } elseif (! headers_sent() && 'GET' === $_SERVER['REQUEST_METHOD'] && ! is_user_logged_in()) {
                header('Vary: Origin', false);
            }

            // METHODS

             /**
             * Allows overriding the allow-methods CORS header
             *
             * @since 4.3.0
             *
             * @param string|array $allow_methods The string to pass to allow-origin header
             * @param \WP_HTTP_Response $result   The Wordpress REST response instance.
             * @param \WP_REST_Request $request   The Wordpress REST request instance
             * @param \WP_REST_Server $server     The Wordpress REST server instance
             */
            $allowMethods = apply_filters('evo_wp_rest_allow_methods', $this->cors['allow_methods'] ?? null, $result, $request, $server);

            if (!empty($allowHeaders)) {
                $string = is_array($allowMethods) ? implode(", ", $allowMethods) : $allowMethods;
                header('Access-Control-Allow-Methods: ' . $string);
            } else {
                header('Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE');
            }

            // CREDENTIALS

             /**
             * Allows overriding the allow-credentials CORS header
             *
             * @since 4.3.0
             *
             * @param bool $allowCredentials  Whether to allow credentials or not
             * @param \WP_HTTP_Response $result The Wordpress REST response instance.
             * @param \WP_REST_Request $request The Wordpress REST request instance
             * @param \WP_REST_Server $server   The Wordpress REST server instance
             */
            $allowCredentials = apply_filters('evo_wp_rest_allow_credentials', $this->cors['allow_credentials'] ?? true, $result, $request, $server);
            
            header('Access-Control-Allow-Credentials: ' . ($allowCredentials ? 'true' : 'false'));

            return $served;
        }, 10, 4);
    }
}
