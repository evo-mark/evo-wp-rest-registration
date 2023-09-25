<?php

namespace EvoWpRestRegistration;

class RestApi
{
    public $base_url;
    public $version;
    public $namespace;
    public $directory;

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
    }

    public function prefix(): string
    {
        return $this->base_url . "/v" . $this->version;
    }

    public static function formatDir($dir): string
    {
        return rtrim($dir, '/') . '/';
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
        foreach (self::rsearch($this->directory, "/.*\.php$/") as $file) {
            $class = $this->namespace . self::convertPathToClass($file);

            if (class_exists($class)) {
                $endpoint = new $class;
                register_rest_route($this->prefix(), $endpoint->getPath(), [
                    'args'  => $endpoint->getArguments(),
                    'callback'  => $endpoint->getCallback(),
                    'methods'   => $endpoint->getMethods(),
                    'permission_callback' => $endpoint->getPermissionCallback(),
                ]);
            } else {
                dd($class);
            }
        }
    }
}
