<?php

class Autoloader {
    private string $baseDir;

    public function __construct(string $baseDir) {
        $this->baseDir = rtrim($baseDir, '/') . '/';
    }

    public function register(): void {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass(string $className): void {
        // Cherche dans Controllers, Core, Models, Views
        $dirs = [
            $this->baseDir . 'Controllers/',
            $this->baseDir . 'Core/',
            $this->baseDir . 'Models/',
            $this->baseDir . 'Views/',
        ];

        foreach ($dirs as $dir) {
            $file = $dir . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
}
