<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.

 https://github.com/InfotelGLPI/Releases
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

use Glpi\Event;

if (!isset($_GET["id"])) {
   $_GET["id"] = 0;
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

// as _actors virtual field stores json, bypass automatic escaping
if (isset($_UPOST['_actors'])) {
    $_POST['_actors'] = json_decode($_UPOST['_actors'], true);
    $_REQUEST['_actors'] = $_POST['_actors'];
}

$release = new PluginReleasesRelease();

if (isset($_POST["add"])) {

   $release->check(-1, CREATE, $_POST);

   $newID = $release->add($_POST);
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($release->getFormURL() . "?id=" . $newID);
   }
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

} else if (isset($_POST["createRelease"])) {

   $change = new Change();
   $change->getFromDB($_POST["changes_id"]);
   $input                = [];
   $input["name"]        = $change->getField("name");
   $input["content"]     = $change->getField("content");
   $input["entities_id"] = $change->getField("entities_id");

   $newID                                = $release->add($input);
   $change_release                       = new PluginReleasesChange_Release();
   $input                                = [];
   $input["changes_id"]                  = $change->getID();
   $input["plugin_releases_releases_id"] = $newID;
   $change_release->add($input);
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($release->getFormURL() . "?id=" . $newID);
   }
   Html::back();

} else if (isset($_POST['addme_observer'])) {
   $release->check($_POST['plugin_releases_releases_id'], READ);
   $input = array_merge(Toolbox::addslashes_deep($release->fields), [
      'plugin_releases_releases_id' => $_POST['plugin_releases_releases_id'],
      '_itil_observer'              => [
         '_type'            => "user",
         'users_id'         => Session::getLoginUserID(),
         'use_notification' => 1,
      ]
   ]);
   $release->update($input);
   Event::log($_POST['plugin_releases_releases_id'], "plugin_releases", 4, "maintain",
      //TRANS: %s is the user login
              sprintf(__('%s adds an actor'), $_SESSION["glpiname"]));
   Html::redirect(PluginReleasesRelease::getFormURLWithID($_POST['plugin_releases_releases_id']));

} else if (isset($_POST['addme_assign'])) {
   $release_user = new PluginReleasesRelease_User();

   $release->check($_POST['plugin_releases_releases_id'], READ);
   $input = ['plugin_releases_releases_id' => $_POST['plugin_releases_releases_id'],
             'users_id'                    => Session::getLoginUserID(),
             'use_notification'            => 1,
             'type'                        => CommonITILActor::ASSIGN];
   $release_user->add($input);
   \Glpi\Event::log($_POST['plugin_releases_releases_id'], "plugin_releases", 4, "maintain",
      //TRANS: %s is the user login
                    sprintf(__('%s adds an actor'), $_SESSION["glpiname"]));
   Html::redirect(PluginReleasesRelease::getFormURLWithID($_POST['plugin_releases_releases_id']));

} else if (isset($_REQUEST['delete_document'])) {

   $doc = new Document();
   $doc->getFromDB(intval($_REQUEST['documents_id']));
   if ($doc->can($doc->getID(), UPDATE)) {
      $document_item        = new Document_Item;
      $found_document_items = $document_item->find([
                                                      'itemtype'     => 'PluginReleasesRelease',
                                                      'items_id'     => (int)$_REQUEST['PluginReleasesRelease'],
                                                      'documents_id' => $doc->getID()
                                                   ]);
      foreach ($found_document_items as $item) {
         $document_item->delete(Toolbox::addslashes_deep($item), true);
      }
   }
   Html::back();

} else {

   $release->checkGlobal(READ);

   $menus = ["helpdesk", PluginReleasesRelease::getType()];
   PluginReleasesRelease::displayFullPageForItem($_REQUEST['id'] ?? 0, $menus, $_REQUEST);
}
