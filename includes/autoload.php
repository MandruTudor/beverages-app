<?php

spl_autoload_register(function (string $class): void {
    $dirs = [
        __DIR__ . '/../controllers/',
        __DIR__ . '/../services/',
        __DIR__ . '/../repositories/',
        __DIR__ . '/../dto/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
