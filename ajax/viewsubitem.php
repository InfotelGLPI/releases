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
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (!isset($_POST['type'])) {
   exit();
}
if (!isset($_POST['parenttype'])) {
   exit();
}

if (($item = getItemForItemtype($_POST['type']))
    && ($parent = getItemForItemtype($_POST['parenttype']))) {
   if (isset($_POST[$parent->getForeignKeyField()])
       && isset($_POST["id"])
       && $parent->getFromDB($_POST[$parent->getForeignKeyField()])) {
      $item->showForm($_POST["id"], array('parent' => $parent));

   } else {
      _e('Access denied');
   }
}

Html::ajaxFooter();
?>