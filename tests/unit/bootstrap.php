<?php

/*
 -------------------------------------------------------------------------
 releases plugin for GLPI
 Copyright (C) 2020-2026 by the releases Development Team.

 https://github.com/InfotelGLPI/releases
 -------------------------------------------------------------------------

 LICENSE

 This file is part of releases.

 releases is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 releases is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with releases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
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
