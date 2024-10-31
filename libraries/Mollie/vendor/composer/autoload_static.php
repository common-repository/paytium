<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita342939a18ebf0d33415df2cd1ae8ff3
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Mollie\\Api\\' => 11,
        ),
        'C' => 
        array (
            'Composer\\CaBundle\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Mollie\\Api\\' => 
        array (
            0 => __DIR__ . '/..' . '/mollie/mollie-api-php/src',
        ),
        'Composer\\CaBundle\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/ca-bundle/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita342939a18ebf0d33415df2cd1ae8ff3::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita342939a18ebf0d33415df2cd1ae8ff3::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
