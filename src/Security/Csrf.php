<?php

namespace App\Security;

use RuntimeException;

class Csrf
{
    public static function generate(&$data, $name)
    {
        $data['CSRF'][$name] = $csrf = bin2hex(random_bytes(16));

        return "$name:$csrf";
    }

    public static function validate(&$data, $name)
    {
        $val = filter_input(INPUT_POST, 'csrf', FILTER_UNSAFE_RAW);
        list($which, $hash) = explode(':', $val, 2);

        if ($which != $name) {
            throw new RuntimeException('Failed CSRF Check');
        }

        if ($data['CSRF'][$name] != $hash) {
            throw new RuntimeException('Failed CSRF Check');
        }

        self::generate($data, $name);

        return true;
    }
}