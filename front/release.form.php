<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018 by the Releases Development Team.

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
if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$release = New PluginReleasesRelease();

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
}else if (isset($_POST["createRelease"])){
   $change = new Change();
   $change->getFromDB($_POST["changes_id"]);
   $input = [];
   $input["name"] = $change->getField("name");
   $input["release_area"] = $change->getField("content");
   $input["entities_id"] = $change->getField("entities_id");

   $newID = $release->add($input);
   $change_release = new PluginReleasesChange_Release();
   $input = [];
   $input["changes_id"] = $change->getID();
   $input["plugin_releases_releases_id"] = $newID;
   $change_release->add($input);
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($release->getFormURL() . "?id=" . $newID);
   }
   Html::back();

} else if (isset($_REQUEST['delete_document'])) {
   $doc = new Document();
   $doc->getFromDB(intval($_REQUEST['documents_id']));
   if ($doc->can($doc->getID(), UPDATE)) {
      $document_item = new Document_Item;
      $found_document_items = $document_item->find([
         'itemtype'     => 'PluginReleasesRelease',
         'items_id'     => (int)$_REQUEST['PluginReleasesRelease'],
         'documents_id' => $doc->getID()
      ]);
      foreach ($found_document_items  as $item) {
         $document_item->delete(Toolbox::addslashes_deep($item), true);
      }
   }
   Html::back();
} else {

   $release->checkGlobal(READ);

   Html::header(PluginReleasesRelease::getTypeName(2), '', "helpdesk", PluginReleasesRelease::getType());

   $release->display($_GET);

   Html::footer();
}
