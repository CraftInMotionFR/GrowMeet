<?php

class Env {
    private static array $vars = [];

    // Lit un fichier .env (lignes CLE=valeur) et garde les valeurs en mémoire
    public static function load(string $path): void {
        if (!file_exists($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            // On ignore les commentaires
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            self::$vars[trim($key)] = trim($value);
        }
    }

    // Une vraie variable d'environnement du serveur est prioritaire sur le .env
    public static function get(string $key, ?string $default = null): ?string {
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        return self::$vars[$key] ?? $default;
    }
}