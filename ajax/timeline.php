<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include('../../../inc/includes.php');

Session::checkLoginUser();

if ( $_POST['action'] == 'done_fail') {
   header("Content-Type: application/json; charset=UTF-8");

   if (
      !isset($_POST['items_id'])
      || !isset($_POST['parenttype']) || ($parent = getItemForItemtype($_POST['parenttype'])) === false
   ) {
      exit();
   }

   $taskClass = $_POST['itemtype'];
   $task      = new $taskClass();
   $task->getFromDB(intval($_POST['items_id']));

   if ($_POST["newStatus"] == $task->fields['state']) {
      $new_state = PluginReleasesTest::TODO;
   } else {
      $new_state = $_POST["newStatus"];
   }

   $new_label = Planning::getState($new_state);
   echo json_encode([
                       'state' => $new_state,
                       'label' => $new_label
                    ]);

   $foreignKey = $parent->getForeignKeyField();
   $task->update([
                    'id'        => intval($_POST['items_id']),
                    $foreignKey => intval($_POST[$foreignKey]),
                    'state'     => $new_state
                 ]);
   $release = new PluginReleasesRelease();
   $release->getFromDB($task->fields["plugin_releases_releases_id"]);
   if (PluginReleasesTest::countDoneForItem($release) != 0) {
      $release->update(['id' => $release->getID(),
                        'status' => PluginReleasesRelease::TESTDEFINITION]);
   } else if (PluginReleasesDeploytask::countDoneForItem($release) != 0) {
      $release->update(['id' => $release->getID(),
                        'status' => PluginReleasesRelease::TASKDEFINITION]);
   } else if (PluginReleasesRollback::countDoneForItem($release) != 0) {
      $release->update(['id' => $release->getID(),
                        'status' => PluginReleasesRelease::ROLLBACKDEFINITION]);
   } else {
      $release->update(['id' => $release->getID(),
                        'status' => PluginReleasesRelease::RISKDEFINITION]);
   }
} else if (($_POST['action'] ?? null) === 'change_release_subitem_state') {
   header("Content-Type: application/json; charset=UTF-8");

   if (
      !isset($_POST['items_id'])
      || !isset($_POST['parenttype']) || ($parent = getItemForItemtype($_POST['parenttype'])) === false
   ) {
      exit();
   }

   $taskClass = $_POST['itemtype'];
   $task      = new $taskClass();
   $task->getFromDB(intval($_POST['items_id']));

      $new_state = ($task->fields['state'] == Planning::DONE)
         ? Planning::TODO
         : Planning::DONE;
      $new_label = Planning::getState($new_state);
      echo json_encode([
                          'state' => $new_state,
                          'label' => $new_label
                       ]);

      $foreignKey = $parent->getForeignKeyField();
      $task->update([
                       'id'        => intval($_POST['items_id']),
                       $foreignKey => intval($_POST[$foreignKey]),
                       'state'     => $new_state
                    ]);

      $release = new PluginReleasesRelease();
      $release->getFromDB($task->fields["plugin_releases_releases_id"]);
      if (PluginReleasesTest::countDoneForItem($release) != 0) {
         $release->update(['id' => $release->getID(),
                           'status' => PluginReleasesRelease::TESTDEFINITION]);
      } else if (PluginReleasesDeploytask::countDoneForItem($release) != 0) {
         $release->update(['id' => $release->getID(),
                           'status' => PluginReleasesRelease::TASKDEFINITION]);
      } else if (PluginReleasesRollback::countDoneForItem($release) != 0) {
         $release->update(['id' => $release->getID(),
                           'status' => PluginReleasesRelease::ROLLBACKDEFINITION]);
      } else {
         $release->update(['id' => $release->getID(),
                           'status' => PluginReleasesRelease::RISKDEFINITION]);
      }
} else {

   if (!isset($_REQUEST['action'])) {
      exit;
   }

   header("Content-Type: text/html; charset=UTF-8");

   $objType    = $_REQUEST['parenttype']::getType();
   $foreignKey = $_REQUEST['parenttype']::getForeignKeyField();


   switch ($_REQUEST['action']) {

      case "change_task_state":
         header("Content-Type: application/json; charset=UTF-8");
         if (!isset($_REQUEST['items_id'])) {
            exit();
         }
         $objClass = $_REQUEST['itemtype'];
         $obj      = new $objClass;
         $obj->getFromDB(intval($_REQUEST['items_id']));

         if (!in_array($obj->fields['state'], [0, Planning::INFO])) {
            $new_state = ($obj->fields['state'] == Planning::DONE)
               ? Planning::TODO
               : Planning::DONE;
            $new_label = Planning::getState($new_state);
            echo json_encode([
                                'state' => $new_state,
                                'label' => $new_label
                             ]);
            $obj->update([
                            'id'        => intval($_REQUEST['items_id']),
                            $foreignKey => intval($_REQUEST[$foreignKey]),
                            'state'     => $new_state
                         ]);
         }
         break;

      case "viewsubitem":
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
            if ($item->getType() == "ITILFollowup") {
               $item->fields["itemtype"] = $parent->getType();
               $item->fields["items_id"] = $_REQUEST["id"];
            }
            $parent::showSubForm($item, $_REQUEST["id"], ['parent'    => $parent,
                                                          "itemtype"  => $parent->getType(),
                                                          "items_id"  => $parent->getID(),
                                                          $foreignKey => $_REQUEST[$foreignKey]]);
         } else {
            echo __('Access denied');
         }

         Html::ajaxFooter();
         break;

   }
}
