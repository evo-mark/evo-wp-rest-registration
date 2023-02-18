<?php

namespace ScwWpRestRegistration;

class RestApi
{
    public static $base_url;
    public static $version;
    public static $namespace;
    public static $directory;

    public static function prefix(): string
    {
        return self::$base_url . "/v" . self::$version;
    }

    public static function init(array $args = [])
    {
        $required = ["base_url", "namespace", "directory"];
        foreach ($required as $item) {
            if (!isset($args[$item]) || empty($args[$item])) {
                throw new \Exception("SCW WP Rest Registration: no " . $item . " provided");
            }
        }

        self::$base_url = $args['base_url'];
        self::$version = $args['version'] ?? 1;
        self::$namespace = self::formatNamespace($args['namespace']);
        self::$directory = self::formatDir($args['directory']);

        add_action('rest_api_init', [__CLASS__, 'registerRoutes']);
    }

    public static function formatDir($dir)
    {
        return rtrim($dir, '/') . '/';
    }

    public static function formatNamespace($ns)
    {
        return rtrim($ns, "\\") . "\\";
    }

    public static function rsearch($folder, $pattern)
    {
        $dir = new \RecursiveDirectoryIterator($folder);
        $ite = new \RecursiveIteratorIterator($dir);
        $files = new \RegexIterator($ite, $pattern, \RegexIterator::GET_MATCH);

        $fileList = array();
        foreach ($files as $file) {
            $file = str_replace($folder, "", $file);
            $fileList = array_merge($fileList, $file);
        }
        return $fileList;
    }

    public static function convertPathToClass($path, $ext = ".php")
    {
        $path = str_replace($ext, "", $path);
        return str_replace(DIRECTORY_SEPARATOR, "\\", $path);
    }

    public static function registerRoutes()
    {
        foreach (self::rsearch(self::$directory, "/.*\.php$/") as $file) {
            $class = self::$namespace . self::convertPathToClass($file);

            if (class_exists($class)) {
                $endpoint = new $class;
                register_rest_route(self::prefix(), $endpoint->getPath(), [
                    'args'  => $endpoint->getArguments(),
                    'callback'  => $endpoint->getCallback(),
                    'methods'   => $endpoint->getMethods(),
                    'permission_callback' => $endpoint->getPermissionCallback(),
                ]);
            }
        }
    }
}
