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

include ('../../../inc/includes.php');

if (!isset($_GET["id"])) $_GET["id"] = "";

$mep = new PluginReleasesRelease();

if (isset($_POST["add"])) {
   $mep->check(-1,CREATE,$_POST);
   $newID = $mep->add($_POST);
   Event::log($newID, "change", 4, "maintain",
              //TRANS: %1$s is the user login, %2$s is the name of the item
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($mep->getFormURL()."?id=".$newID);
   } else {
      Html::back();
   }

} else if (isset($_POST["delete"])) {
   $mep->check($_POST['id'],DELETE);
   $mep->delete($_POST);

   $mep->redirectToList();

} else if (isset($_POST["update"])) {
   $mep->check($_POST['id'],UPDATE);
   $mep->update($_POST);

   Html::back();

} else if (isset($_POST["purge"])) {
   $mep-->check($_POST['id'],PURGE);
   $mep->delete($_POST,1);
   $mep->redirectToList();

} else if (isset($_POST["restore"])) {
   $mep->check($_POST['id'],PURGE);
   $mep->restore($_POST);
   $mep->redirectToList();

} else if (isset($_POST["add_item"])) {

   Html::back();

} else if (isset($_POST["update_item"])) {

   Html::back();

} else if (isset($_POST["delete_item"])) {


   Html::back();

} else {
   $mep->checkGlobal(READ);
   Html::header(PluginReleasesRelease::getTypeName(2),'',"helpdesk","pluginreleasesmenu");
   $mep->display($_GET);
   Html::footer();
}

?>