<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.

 https://github.com/InfotelGLPI/releases
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Releases.

 Releases is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 releases is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with releases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (strpos($_SERVER['PHP_SELF'], "changeitemstate.php")) {

   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkCentralAccess();
Session::checkRight('plugin_releases_releases', UPDATE);


if (isset($_POST["value"]) && isset($_POST["plugin_releases_releases_id"]) && isset($_POST["field"]) && isset($_POST["status"])) {
   global $DB;
   $item = new PluginReleasesRelease();
   $item->getFromDB($_POST["plugin_releases_releases_id"]);
   if ($_POST["status"] > $item->getField('status')) {
      $update = [$_POST["field"] => $_POST["value"], "id" => $item->getID(), 'status' => $_POST["status"]];
   } else {
      $update = [$_POST["field"] => $_POST["value"], "id" => $item->getID()];
   }
   $item->update($update);
}
