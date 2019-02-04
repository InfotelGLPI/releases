<?php
/*
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2015 by the Releases Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Releases.

 Releases is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Releases is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Releases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

$test = new PluginReleasesReleaseTest();

// autoload include in objecttask.form (tickettask, problemtask,...)
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}
Session::checkCentralAccess();

if (!$test->canCreate()) {
   Html::displayRightError();
}

$itemtype = "PluginReleasesReleaseTest";
$fk       = getForeignKeyFieldForItemType($itemtype);

if (isset($_POST["add"])) {
   //$task->check(-1, CREATE, $_POST);
   $test->add($_POST);

   //   Event::log($test->getField($fk), strtolower($itemtype), 4, "tracking",
   //              //TRANS: %s is the user login
   //              sprintf(__('%s adds a test'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["purge"])) {
   $test->check($_POST['id'], PURGE);
   $test->delete($_POST, 1);

   //   Event::log($test->getField($fk), strtolower($itemtype), 4, "tracking",
   //              //TRANS: %s is the user login
   //              sprintf(__('%s purges a test'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["update"])) {
   //$task->check($_POST["id"], UPDATE);
   $test->update($_POST);
   //
   //   Event::log($test->getField($fk), strtolower($itemtype), 4, "tracking",
   //              //TRANS: %s is the user login
   //              sprintf(__('%s updates a test'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_GET['_in_modal'])) {
   Html::popHeader($test->getTypeName(1), $_SERVER['PHP_SELF']);
   $test->showForm(-1, array('plugin_releases_releases_id' => $_GET['plugin_releases_releases_id']));
   Html::popFooter();
} else {
   Html::displayErrorAndDie('Lost');
}

