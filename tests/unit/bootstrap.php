<?php

/*
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.
 -------------------------------------------------------------------------
 */

// Bootstrap léger pour les tests unitaires : charge uniquement les autoloaders PHP.
// Aucune connexion à la base de données, aucun kernel GLPI.
$glpi_root = dirname(__DIR__, 4);

if (!file_exists($glpi_root . '/vendor/autoload.php')) {
    echo "\nvendor/autoload.php introuvable. Exécutez composer install depuis la racine GLPI.\n\n";
    exit(1);
}

require_once $glpi_root . '/vendor/autoload.php';

// Autoloader PSR-4 du plugin
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'GlpiPlugin\\Releases\\')) {
        return;
    }
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen('GlpiPlugin\\Releases\\')));
    $file     = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}, prepend: true);
