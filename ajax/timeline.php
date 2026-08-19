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
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Releases\Deploytask;
use GlpiPlugin\Releases\Release;
use GlpiPlugin\Releases\Risk;
use GlpiPlugin\Releases\Rollback;
use GlpiPlugin\Releases\Test;

Session::checkLoginUser();
Session::checkRight('plugin_releases_releases', UPDATE);

if ($_POST['action'] == 'done_fail') {
    header("Content-Type: application/json; charset=UTF-8");

    $_POST['parenttype'] = Release::class;

    if (!isset($_POST['items_id'])
      || !isset($_POST['parenttype']) || ($parent = getItemForItemtype($_POST['parenttype'])) === false
    ) {
        throw new NotFoundHttpException();
    }

    $allowed_task_classes = [Deploytask::class, Risk::class, Rollback::class, Test::class];
    if (!in_array($_POST['itemtype'], $allowed_task_classes, true)) {
        throw new NotFoundHttpException();
    }
    $taskClass = $_POST['itemtype'];
    $task      = new $taskClass();
    $task->getFromDB(intval($_POST['items_id']));

    // Forbid any state change once the parent release reached a terminal status
    $release = new Release();
    $release->getFromDB($task->fields["plugin_releases_releases_id"]);
    // Enforce entity + item access on the parent release, not only the global right
    if (!$release->can($release->getID(), UPDATE)) {
        throw new AccessDeniedHttpException();
    }
    if (in_array($release->getField('status'), Release::getClosedStatusArray(), true)) {
        throw new NotFoundHttpException();
    }

    if ($_POST["newStatus"] == $task->fields['state']) {
        $new_state = Test::TODO;
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
    if (Test::countDoneForItem($release) != 0) {
        $release->update(['id' => $release->getID(),
                        'status' => Release::TESTDEFINITION]);
    } elseif (Deploytask::countDoneForItem($release) != 0) {
        $release->update(['id' => $release->getID(),
                        'status' => Release::TASKDEFINITION]);
    } elseif (Rollback::countDoneForItem($release) != 0) {
        $release->update(['id' => $release->getID(),
                        'status' => Release::ROLLBACKDEFINITION]);
    } else {
        $release->update(['id' => $release->getID(),
                        'status' => Release::RISKDEFINITION]);
    }
} elseif (($_POST['action'] ?? null) === 'change_release_subitem_state') {
    header("Content-Type: application/json; charset=UTF-8");

    if (!isset($_POST['items_id'])
      || !isset($_POST['parenttype']) || ($parent = getItemForItemtype($_POST['parenttype'])) === false
    ) {
        throw new NotFoundHttpException();
    }

    $allowed_task_classes = [Deploytask::class, Risk::class, Rollback::class, Test::class];
    if (!in_array($_POST['itemtype'], $allowed_task_classes, true)) {
        throw new NotFoundHttpException();
    }
    $taskClass = $_POST['itemtype'];
    $task      = new $taskClass();
    $task->getFromDB(intval($_POST['items_id']));

    // Forbid any state change once the parent release reached a terminal status
    $release = new Release();
    $release->getFromDB($task->fields["plugin_releases_releases_id"]);
    // Enforce entity + item access on the parent release, not only the global right
    if (!$release->can($release->getID(), UPDATE)) {
        throw new AccessDeniedHttpException();
    }
    if (in_array($release->getField('status'), Release::getClosedStatusArray(), true)) {
        throw new NotFoundHttpException();
    }

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

    if (Test::countDoneForItem($release) != 0) {
        $release->update(['id' => $release->getID(),
                         'status' => Release::TESTDEFINITION]);
    } elseif (Deploytask::countDoneForItem($release) != 0) {
        $release->update(['id' => $release->getID(),
                         'status' => Release::TASKDEFINITION]);
    } elseif (Rollback::countDoneForItem($release) != 0) {
        $release->update(['id' => $release->getID(),
                         'status' => Release::ROLLBACKDEFINITION]);
    } else {
        $release->update(['id' => $release->getID(),
                         'status' => Release::RISKDEFINITION]);
    }
} else {
    if (!isset($_REQUEST['action'])) {
        exit;
    }

    $_REQUEST['parenttype'] = Release::class;
    header("Content-Type: text/html; charset=UTF-8");

    $objType    = $_REQUEST['parenttype']::getType();
    $foreignKey = $_REQUEST['parenttype']::getForeignKeyField();


    switch ($_REQUEST['action']) {
        case "change_task_state":
            header("Content-Type: application/json; charset=UTF-8");
            // Toggling a task state mutates data; require POST so the
            // CheckCsrfListener enforces the CSRF token (it only validates
            // non-GET requests, so a GET-routed switch action bypasses it).
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new BadRequestHttpException();
            }
            if (!isset($_REQUEST['items_id'])) {
                throw new NotFoundHttpException();
            }
            if ($_REQUEST['itemtype'] == 'Rollback') {
                $_REQUEST['itemtype'] = Rollback::class;
            } else if ($_REQUEST['itemtype'] == 'Risk') {
                $_REQUEST['itemtype'] = Risk::class;
            }
            $allowed_task_classes = [Deploytask::class, Risk::class, Rollback::class, Test::class];
            if (!in_array($_REQUEST['itemtype'], $allowed_task_classes, true)) {
                throw new NotFoundHttpException();
            }
            $objClass = $_REQUEST['itemtype'];

            $obj      = new $objClass();
            $obj->getFromDB(intval($_REQUEST['items_id']));

            // Forbid any state change once the parent release reached a terminal status
            $release = new Release();
            $release->getFromDB($obj->fields["plugin_releases_releases_id"]);
            // Enforce entity + item access on the parent release, not only the global right
            if (!$release->can($release->getID(), UPDATE)) {
                throw new AccessDeniedHttpException();
            }
            if (in_array($release->getField('status'), Release::getClosedStatusArray(), true)) {
                throw new NotFoundHttpException();
            }

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
                throw new NotFoundHttpException();
            }
            if (!isset($_REQUEST['parenttype'])) {
                throw new NotFoundHttpException();
            }

            // Restrict the rendered itemtype to the plugin's own subitem classes
            $allowed_subitem_classes = [Deploytask::class, Risk::class, Rollback::class, Test::class];
            if (!in_array($_REQUEST['type'], $allowed_subitem_classes, true)) {
                throw new NotFoundHttpException();
            }

            $item   = getItemForItemtype($_REQUEST['type']);
            $parent = getItemForItemtype($_REQUEST['parenttype']);

            // Enforce right + entity + object access on the parent Release, never trust the raw id.
            // Mirrors the write branches (change_task_state); the display path was leaking cross-entity subitems.
            if (isset($_REQUEST[$parent->getForeignKeyField()])
             && isset($_REQUEST["id"])
             && $parent->can((int) $_REQUEST[$parent->getForeignKeyField()], READ)) {
                $ol = ObjectLock::isLocked($_REQUEST['parenttype'], $parent->getID());
                if ($ol && (Session::getLoginUserID() != $ol->fields['users_id'])) {
                    ObjectLock::setReadOnlyProfile();
                }
                if ($item->getType() == "ITILFollowup") {
                    $item->getFromDB($_REQUEST["id"]);
                }

                $parent::showSubForm($item, $_REQUEST["id"], ['parent'    => $parent,
                                                          "itemtype"  => $parent->getType(),
                                                          "items_id"  => $parent->getID(),
                                                          $foreignKey => $_REQUEST[$foreignKey]]);
            } else {
                throw new AccessDeniedHttpException();
            }


            break;
    }
}
