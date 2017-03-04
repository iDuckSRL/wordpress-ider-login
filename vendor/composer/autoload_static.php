<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0d8f84c09c0c736676a7d8b64b1ea8f1
{
    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'phpseclib\\' => 10,
        ),
        'I' => 
        array (
            'IDERConnect\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'phpseclib\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib',
        ),
        'IDERConnect\\' => 
        array (
            0 => __DIR__ . '/..' . '/jlmsrl/ider-openid-client-php/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0d8f84c09c0c736676a7d8b64b1ea8f1::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0d8f84c09c0c736676a7d8b64b1ea8f1::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}