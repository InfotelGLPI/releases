<?php

/*
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.
 -------------------------------------------------------------------------
 */

// Chemin vers la racine GLPI (3 niveaux au-dessus de marketplace/releases/tests/)
$glpi_root = dirname(__DIR__, 3);

if (!file_exists($glpi_root . '/tests/bootstrap.php')) {
    echo "\nGLPI test bootstrap introuvable. Assurez-vous de lancer les tests depuis la racine GLPI.\n\n";
    exit(1);
}

// Bootstrap GLPI : initialise le kernel, la connexion DB et les fixtures de test
require_once $glpi_root . '/tests/bootstrap.php';

// Enregistre l'autoloader PSR-4 du plugin (si le plugin n'est pas encore actif en DB)
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'GlpiPlugin\\Releases\\')) {
        return;
    }
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen('GlpiPlugin\\Releases\\')));
    $file     = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $relative . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}, prepend: true);
