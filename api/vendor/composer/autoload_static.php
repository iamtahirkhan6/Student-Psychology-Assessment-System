<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit16274978aa50b85f69dc972005705cd5
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit16274978aa50b85f69dc972005705cd5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit16274978aa50b85f69dc972005705cd5::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
