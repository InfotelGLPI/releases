<?php

/**
 * -------------------------------------------------------------------------
 * releases plugin for GLPI
 * Copyright (C) 2020-2026 by the releases Development Team.
 *
 * https://github.com/InfotelGLPI/releases
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of releases.
 *
 * releases is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * releases is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with releases. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Releases\Deploytask;
use GlpiPlugin\Releases\Release;
use GlpiPlugin\Releases\Risk;
use GlpiPlugin\Releases\Rollback;
use GlpiPlugin\Releases\Test;

Session::checkRight('plugin_releases_releases', UPDATE);

if (($_POST['action'] ?? null) === 'done_fail') {
    header("Content-Type: application/json; charset=UTF-8");

    $_POST['parenttype'] = Release::class;

    if (!isset($_POST['items_id'])
      || ($parent = getItemForItemtype($_POST['parenttype'])) === false
    ) {
        throw new NotFoundHttpException();
    }

    $allowed_task_classes = [Deploytask::class, Risk::class, Rollback::class, Test::class];
    if (!in_array($_POST['itemtype'], $allowed_task_classes, true)) {
        throw new NotFoundHttpException();
    }
    $taskClass = $_POST['itemtype'];
    $task      = new $taskClass();
    if (!$task->getFromDB(intval($_POST['items_id']))) {
        throw new NotFoundHttpException();
    }

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

    // Each subitem class declares its own rightname: the release right alone must not grant the write
    if (!$task->can($task->getID(), UPDATE)) {
        throw new AccessDeniedHttpException();
    }

    if ($_POST["newStatus"] == $task->fields['state']) {
        $new_state = Test::TODO;
    } else {
        // Only accept a known task state (TODO/DONE/FAIL are shared by all task classes),
        // never a raw out-of-range value from the POST, so the state column stays in domain.
        $new_state = in_array((int) $_POST["newStatus"], [Test::TODO, Test::DONE, Test::FAIL], true)
            ? (int) $_POST["newStatus"]
            : Test::TODO;
    }

    $new_label = Planning::getState($new_state);
    echo json_encode([
        'state' => $new_state,
        'label' => $new_label,
    ]);

    $foreignKey = $parent->getForeignKeyField();
    $task->update([
        'id'        => intval($_POST['items_id']),
        // Keep the parent resolved from the row itself: a posted id would re-parent the subitem
        $foreignKey => $release->getID(),
        'state'     => $new_state,
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
    // Release is the only legitimate parent here: never derive the written foreign key from the POST
    $_POST['parenttype'] = Release::class;

    if (!isset($_POST['items_id'])
      || ($parent = getItemForItemtype($_POST['parenttype'])) === false
    ) {
        throw new NotFoundHttpException();
    }

    $allowed_task_classes = [Deploytask::class, Risk::class, Rollback::class, Test::class];
    if (!in_array($_POST['itemtype'], $allowed_task_classes, true)) {
        throw new NotFoundHttpException();
    }
    $taskClass = $_POST['itemtype'];
    $task      = new $taskClass();
    if (!$task->getFromDB(intval($_POST['items_id']))) {
        throw new NotFoundHttpException();
    }

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

    // Each subitem class declares its own rightname: the release right alone must not grant the write
    if (!$task->can($task->getID(), UPDATE)) {
        throw new AccessDeniedHttpException();
    }

    $new_state = ($task->fields['state'] == Planning::DONE)
       ? Planning::TODO
       : Planning::DONE;
    $new_label = Planning::getState($new_state);
    echo json_encode([
        'state' => $new_state,
        'label' => $new_label,
    ]);

    $foreignKey = $parent->getForeignKeyField();
    $task->update([
        'id'        => intval($_POST['items_id']),
        // Keep the parent resolved from the row itself: a posted id would re-parent the subitem
        $foreignKey => $release->getID(),
        'state'     => $new_state,
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
            } elseif ($_REQUEST['itemtype'] == 'Risk') {
                $_REQUEST['itemtype'] = Risk::class;
            }
            $allowed_task_classes = [Deploytask::class, Risk::class, Rollback::class, Test::class];
            if (!in_array($_REQUEST['itemtype'], $allowed_task_classes, true)) {
                throw new NotFoundHttpException();
            }
            $objClass = $_REQUEST['itemtype'];

            $obj      = new $objClass();
            if (!$obj->getFromDB(intval($_REQUEST['items_id']))) {
                throw new NotFoundHttpException();
            }

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

            // Each subitem class declares its own rightname: the release right alone must not grant the write
            if (!$obj->can($obj->getID(), UPDATE)) {
                throw new AccessDeniedHttpException();
            }

            if (!in_array($obj->fields['state'], [0, Planning::INFO])) {
                $new_state = ($obj->fields['state'] == Planning::DONE)
                ? Planning::TODO
                : Planning::DONE;
                $new_label = Planning::getState($new_state);
                echo json_encode([
                    'state' => $new_state,
                    'label' => $new_label,
                ]);
                $obj->update([
                    'id'        => intval($_REQUEST['items_id']),
                    // Keep the parent resolved from the row itself: a posted id would re-parent the subitem
                    $foreignKey => $release->getID(),
                    'state'     => $new_state,
                ]);
            }
            break;

        case "viewsubitem":
            Html::header_nocache();
            if (!isset($_REQUEST['type'])) {
                throw new NotFoundHttpException();
            }

            // The timeline addresses its subitems by short name — viewAddSubitem("Risk"),
            // viewEditSubitem(..., "Risk", ...) — because a fully qualified name cannot
            // survive a JS string literal: the backslashes of GlpiPlugin\Releases\Risk are
            // consumed as escape sequences. Resolving the short name against a fixed map
            // keeps the allow-list closed while accepting what the UI actually sends.
            // ITILFollowup belongs here too: showTimelineForm() offers an add button for it
            // and getTimelineItems() lists the existing ones.
            $allowed_subitem_classes = [
                'Deploytask'   => Deploytask::class,
                'Risk'         => Risk::class,
                'Rollback'     => Rollback::class,
                'Test'         => Test::class,
                'ITILFollowup' => ITILFollowup::class,
            ];
            if (!is_string($_REQUEST['type']) || !isset($allowed_subitem_classes[$_REQUEST['type']])) {
                throw new NotFoundHttpException();
            }
            $_REQUEST['type'] = $allowed_subitem_classes[$_REQUEST['type']];

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
                // Each subitem class declares its own rightname: the release right alone must not expose it
                $subitem_id = (int) ($_REQUEST["id"] ?? 0);
                if ($subitem_id > 0) {
                    if (!$item->can($subitem_id, READ)) {
                        throw new AccessDeniedHttpException();
                    }

                    // Same guard as ajax/viewsubitem.php and ajax/viewsubitemtemplate.php:
                    // both ends were checked, nothing tied them together, so a subitem
                    // belonging to another release could still be rendered under the
                    // controlled parent. ITILFollowup is polymorphic: it carries
                    // itemtype/items_id rather than the release foreign key.
                    if ($item instanceof ITILFollowup) {
                        if ($item->fields['itemtype'] !== $parent->getType()
                            || (int) $item->fields['items_id'] !== $parent->getID()) {
                            throw new AccessDeniedHttpException();
                        }
                    } elseif ((int) $item->fields[$foreignKey] !== $parent->getID()) {
                        throw new AccessDeniedHttpException();
                    }
                } elseif (!$item::canCreate()) {
                    throw new AccessDeniedHttpException();
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
