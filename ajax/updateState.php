<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 databases plugin for GLPI
 Copyright (C) 2009-2016 by the databases Development Team.

 https://github.com/InfotelGLPI/databases
 -------------------------------------------------------------------------

 LICENSE

 This file is part of databases.

 databases is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 databases is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with databases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (strpos($_SERVER['PHP_SELF'], "updateState.php")) {
   $AJAX_INCLUDE = 1;
   include('../../../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkCentralAccess();

$foreignKey = $_REQUEST['parenttype']::getForeignKeyField();
if (isset($_POST["tasks_id"]) && isset($_POST["type"]) && isset($_POST[$foreignKey])) {
   header("Content-Type: application/json; charset=UTF-8");

   $task = new $_POST["type"];
   $task->getFromDB(intval($_REQUEST['tasks_id']));
   if (!in_array($task->fields['state'], [0, Planning::INFO])) {
      $new_state = ($task->fields['state'] == Planning::DONE)
         ? Planning::TODO
         : Planning::DONE;
      $new_label = Planning::getState($new_state);
      echo json_encode([
         'state'  => $new_state,
         'label'  => $new_label
      ]);

      $task->update([
         'id'         => intval($_POST['tasks_id']),
         $foreignKey => intval($_POST[$foreignKey]),
         'state'      => $new_state
      ]);
   }




}
