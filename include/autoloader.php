<?php
/**
 * @file include/autoloader.php
 */

/**
 * @brief composer-derived autoloader init
 **/
class FriendicaAutoloaderInit
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/autoloader/ClassLoader.php';
        }
    }

    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('FriendicaAutoloaderInit', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader();
        spl_autoload_unregister(array('FriendicaAutoloaderInit', 'loadClassLoader'));

        // library
        $map = require __DIR__ . '/autoloader/autoload_namespaces.php';
        foreach ($map as $namespace => $path) {
            $loader->set($namespace, $path);
        }

        $map = require __DIR__ . '/autoloader/autoload_psr4.php';
        foreach ($map as $namespace => $path) {
            $loader->setPsr4($namespace, $path);
        }

        $classMap = require __DIR__ . '/autoloader/autoload_classmap.php';
        if ($classMap) {
            $loader->addClassMap($classMap);
        }
        
        $loader->register(true);
        
        $includeFiles = require __DIR__ . '/autoloader/autoload_files.php';
        foreach ($includeFiles as $fileIdentifier => $file) {
            friendicaRequire($fileIdentifier, $file);
        }
        

        return $loader;
    }
}

function friendicaRequire($fileIdentifier, $file)
{
    if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier])) {
        require $file;

        $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;
    }
}



return FriendicaAutoloaderInit::getLoader();
