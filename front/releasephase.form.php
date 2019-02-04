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

$phase = new PluginReleasesReleasePhase();

// autoload include in objectphase.form (ticketphase, problemphase,...)
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}
Session::checkCentralAccess();

if (isset($_POST["add"])) {
   //$phase->check(-1, CREATE, $_POST);
   $phase->add($_POST);

//   Event::log($phase->getField($fk), strtolower($itemtype), 4, "tracking",
//              //TRANS: %s is the user login
//              sprintf(__('%s adds a phase'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["purge"])) {
   $phase->check($_POST['id'], PURGE);
   $phase->delete($_POST, 1);

//   Event::log($phase->getField($fk), strtolower($itemtype), 4, "tracking",
//              //TRANS: %s is the user login
//              sprintf(__('%s purges a phase'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["update"])) {
   //$phase->check($_POST["id"], UPDATE);
   $phase->update($_POST);

//   Event::log($phase->getField($fk), strtolower($itemtype), 4, "tracking",
//              //TRANS: %s is the user login
//              sprintf(__('%s updates a phase'), $_SESSION["glpiname"]));
   Html::back();

}

Html::displayErrorAndDie('Lost');