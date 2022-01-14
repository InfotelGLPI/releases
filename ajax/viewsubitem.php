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

if (strpos($_SERVER['PHP_SELF'], "viewsubitem.php")) {
   $AJAX_INCLUDE = 1;
   include('../../../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkCentralAccess();
global $CFG_GLPI;
$foreignKey = $_REQUEST['parenttype']::getForeignKeyField();
Html::header_nocache();
if (!isset($_REQUEST['type'])) {
   exit();
}
if (!isset($_REQUEST['parenttype'])) {
   exit();
}

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
   echo __('Access denied');
}

Html::ajaxFooter();

