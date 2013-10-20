<?php
spl_autoload_register(
    function ($class) {
        $file = __DIR__ . '/' . strtr($class, '\\', '/') . '.php';
        if (is_readable($file)) {
            @include($file);
        }
    }, false
);
