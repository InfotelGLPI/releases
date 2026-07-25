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
use GlpiPlugin\Releases\Deploytask;
use GlpiPlugin\Releases\Release;
use GlpiPlugin\Releases\Risk;
use GlpiPlugin\Releases\Rollback;
use GlpiPlugin\Releases\Test;

if (strpos($_SERVER['PHP_SELF'], "viewsubitem.php")) {
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

// Restrict handled itemtypes to the plugin's own classes (avoid arbitrary itemtype instantiation)
$allowed_types   = [Deploytask::class, Risk::class, Rollback::class, Test::class];
$allowed_parents = [Release::class];
if (!in_array($_REQUEST['type'], $allowed_types, true)) {
    throw new NotFoundHttpException();
}
if (!in_array($_REQUEST['parenttype'], $allowed_parents, true)) {
    throw new NotFoundHttpException();
}

$foreignKey = $_REQUEST['parenttype']::getForeignKeyField();

$item   = getItemForItemtype($_REQUEST['type']);
$parent = getItemForItemtype($_REQUEST['parenttype']);

if (isset($_REQUEST[$parent->getForeignKeyField()])
    && isset($_REQUEST["id"])
    && $parent->getFromDB($_REQUEST[$parent->getForeignKeyField()])) {

   $ol = ObjectLock::isLocked($_REQUEST['parenttype'], $parent->getID());
   if ($ol && (Session::getLoginUserID() != $ol->fields['users_id'])) {
      ObjectLock::setReadOnlyProfile();
   }
   $id = isset($_REQUEST['id']) && (int)$_REQUEST['id'] > 0 ? $_REQUEST['id'] : null;
   if ($id) {
      $item->getFromDB($id);
   }
   $url = $_REQUEST['type']::getFormURL();
   $item->showForm($id);

} else {
    throw new AccessDeniedHttpException();
}



