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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Releases\Deploytasktemplate;
use GlpiPlugin\Releases\ReleaseTemplate;
use GlpiPlugin\Releases\Risktemplate;
use GlpiPlugin\Releases\Rollbacktemplate;
use GlpiPlugin\Releases\Testtemplate;

if (strpos($_SERVER['PHP_SELF'], "viewsubitemtemplate.php")) {
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}
Session::checkRight('plugin_releases_releases', UPDATE);

Session::checkCentralAccess();
global $CFG_GLPI;
Html::header_nocache();

if (!isset($_REQUEST['type'])) {
    throw new NotFoundHttpException();
}
if (!isset($_REQUEST['parenttype'])) {
    throw new NotFoundHttpException();
}

// Restrict handled itemtypes to the plugin's own *template classes
// (avoid arbitrary itemtype instantiation).
$allowed_types   = [Deploytasktemplate::class, Risktemplate::class, Rollbacktemplate::class, Testtemplate::class];
$allowed_parents = [ReleaseTemplate::class];
if (!in_array($_REQUEST['type'], $allowed_types, true)) {
    throw new NotFoundHttpException();
}
if (!in_array($_REQUEST['parenttype'], $allowed_parents, true)) {
    throw new NotFoundHttpException();
}

$item   = getItemForItemtype($_REQUEST['type']);
$parent = getItemForItemtype($_REQUEST['parenttype']);

$foreignKey = $parent->getForeignKeyField();

// Enforce right + entity + object access on the parent template, never trust the raw id.
if (isset($_REQUEST[$foreignKey])
    && isset($_REQUEST["id"])
    && $parent->can((int) $_REQUEST[$foreignKey], READ)) {

   $id = (int) $_REQUEST['id'] > 0 ? (int) $_REQUEST['id'] : null;
   if ($id) {
      $item->getFromDB($id);
   } else {
      // New subitem: inject the parent id so the form keeps the relationship.
      $item->getEmpty();
      $item->fields[$foreignKey] = $parent->getID();
   }
   $item->showForm($id ?? -1);

} else {
    throw new AccessDeniedHttpException();
}
