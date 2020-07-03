<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018 by the Releases Development Team.

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

include('../../../inc/includes.php');
Session::checkLoginUser();
if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$release = New PluginReleasesRollback();

if (isset($_POST["add"])) {
   $release->check(-1, CREATE, $_POST);

   $newID = $release->add($_POST);
   $_SESSION['releases'][Session::getLoginUserID()] = 'rollback';
   Html::back();
} else if (isset($_POST["delete"])) {
   $release->check($_POST['id'], DELETE);
   $release->delete($_POST);
   $release->redirectToList();

} else if (isset($_POST["restore"])) {
   $release->check($_POST['id'], PURGE);
   $release->restore($_POST);
   $release->redirectToList();

} else if (isset($_POST["purge"])) {
   $release->check($_POST['id'], PURGE);
   $release->delete($_POST, 1);
   $release->redirectToList();

} else if (isset($_POST["update"])) {
   $release->check($_POST['id'], UPDATE);
   $release->update($_POST);
   Html::back();

} else {

   $release->checkGlobal(READ);

   Html::header(PluginReleasesRelease::getTypeName(2), '', "help", PluginReleasesRelease::getType());

   $release->display($_GET);

   Html::footer();
}
