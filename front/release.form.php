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

use Glpi\Event;
use GlpiPlugin\Releases\Change_Release;
use GlpiPlugin\Releases\Release;
use GlpiPlugin\Releases\Release_User;

if (!isset($_GET["id"])) {
    $_GET["id"] = 0;
}
if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

// as _actors virtual field stores json, bypass automatic escaping
if (isset($_POST['_actors'])) {
    $_POST['_actors'] = json_decode($_POST['_actors'], true);
    $_REQUEST['_actors'] = $_POST['_actors'];
}

$release = new Release();

if (isset($_POST["add"])) {

    $release->check(-1, CREATE, $_POST);

    $newID = $release->add($_POST);
    if ($_SESSION['glpibackcreated']) {
        Html::redirect($release->getFormURL() . "?id=" . $newID);
    }
    Html::back();
} elseif (isset($_POST["delete"])) {

    $release->check($_POST['id'], DELETE);
    $release->delete($_POST);
    $release->redirectToList();

} elseif (isset($_POST["restore"])) {

    $release->check($_POST['id'], PURGE);
    $release->restore($_POST);
    $release->redirectToList();

} elseif (isset($_POST["purge"])) {
    $release->check($_POST['id'], PURGE);
    $release->delete($_POST, 1);
    $release->redirectToList();

} elseif (isset($_POST["update"])) {

    $release->check($_POST['id'], UPDATE);
    $release->update($_POST);
    Html::back();

} elseif (isset($_POST["createRelease"])) {

    // Enforce creation right and access to the source change (global right + entity + item access)
    $release->check(-1, CREATE);
    $change = new Change();
    $change->check($_POST["changes_id"], READ);
    $input                = [];
    $input["name"]        = $change->getField("name");
    $input["content"]     = $change->getField("content");
    $input["entities_id"] = $change->getField("entities_id");

    $newID                                = $release->add($input);
    $change_release                       = new Change_Release();
    $input                                = [];
    $input["changes_id"]                  = $change->getID();
    $input["plugin_releases_releases_id"] = $newID;
    $change_release->add($input);
    if ($_SESSION['glpibackcreated']) {
        Html::redirect($release->getFormURL() . "?id=" . $newID);
    }
    Html::back();

} elseif (isset($_POST['addme_observer'])) {
    $release->check($_POST['plugin_releases_releases_id'], READ);
    $input = array_merge($release->fields, [
        'plugin_releases_releases_id' => $_POST['plugin_releases_releases_id'],
        '_itil_observer'              => [
            '_type'            => "user",
            'users_id'         => Session::getLoginUserID(),
            'use_notification' => 1,
        ],
    ]);
    $release->update($input);
    Event::log(
        $_POST['plugin_releases_releases_id'],
        "plugin_releases",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s adds an actor'), $_SESSION["glpiname"]),
    );
    Html::redirect(Release::getFormURLWithID($_POST['plugin_releases_releases_id']));

} elseif (isset($_POST['addme_assign'])) {
    $release_user = new Release_User();

    // Self-adding as an ASSIGN actor (technician) is an operator action: require UPDATE,
    // not just READ (READ stays enough for addme_observer). Mirrors core ITIL assignment.
    $release->check($_POST['plugin_releases_releases_id'], UPDATE);
    $input = ['plugin_releases_releases_id' => $_POST['plugin_releases_releases_id'],
        'users_id'                    => Session::getLoginUserID(),
        'use_notification'            => 1,
        'type'                        => CommonITILActor::ASSIGN];
    $release_user->add($input);
    Event::log(
        $_POST['plugin_releases_releases_id'],
        "plugin_releases",
        4,
        "maintain",
        //TRANS: %s is the user login
        sprintf(__('%s adds an actor'), $_SESSION["glpiname"]),
    );
    Html::redirect(Release::getFormURLWithID($_POST['plugin_releases_releases_id']));

} elseif (isset($_POST['delete_document'])) {

    // Detaching a document mutates state: read it from POST only so the
    // CheckCsrfListener enforces the CSRF token (it validates non-GET requests
    // only). A GET-triggered detach would otherwise be forgeable.
    $document_item = new Document_Item();
    $document_item->getFromDBByCrit([
        'itemtype'     => Release::class,
        'items_id'     => (int) ($_POST[Release::class] ?? 0),
        'documents_id' => (int) ($_POST['documents_id'] ?? 0),
    ]);
    // check() on the linkage carries the DELETE right, the entity and the
    // access to the target Release, so a document cannot be detached from a
    // release the user has no access to (aligned with review.form.php). The
    // Document right alone was not enough: it left the release access unchecked.
    $document_item->check($document_item->getID(), DELETE);
    $document_item->delete([
        'id'           => $document_item->getID(),
        'itemtype'     => Release::class,
        'items_id'     => (int) ($_POST[Release::class] ?? 0),
        'documents_id' => (int) ($_POST['documents_id'] ?? 0),
    ]);
    Html::back();

} else {

    $release->checkGlobal(READ);

    $menus = ["helpdesk", Release::class];
    Release::displayFullPageForItem($_REQUEST['id'] ?? 0, $menus, $_REQUEST);
}
