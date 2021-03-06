<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit555eac2be6304e201001ec17b2028db4
{
    public static $files = array (
        'f255b2695f755667cfcdad73757aa298' => __DIR__ . '/../..' . '/src/cvs-auth.php',
    );

    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
        'M' => 
        array (
            'Michelf\\' => 8,
        ),
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
        'Michelf\\' => 
        array (
            0 => __DIR__ . '/..' . '/michelf/php-markdown/Michelf',
        ),
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit555eac2be6304e201001ec17b2028db4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit555eac2be6304e201001ec17b2028db4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit555eac2be6304e201001ec17b2028db4::$classMap;

        }, null, ClassLoader::class);
    }
}
