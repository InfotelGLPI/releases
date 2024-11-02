<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.

 https://github.com/InfotelGLPI/releases
 -------------------------------------------------------------------------

 LICENSE

 This file is part of releases.

 releases is free software; you can redistribute it and/or modify
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginReleasesReview
 */
class PluginReleasesReview extends CommonDBTM {

   static $rightname = 'plugin_releases_releases';

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Review', 'Reviews', $nb, 'releases');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == PluginReleasesRelease::getType()) {
         return self::getTypeName(1);
      }

      return '';
   }

   static function countForItem(CommonDBTM $item) {
      $dbu   = new DbUtils();
      $table = CommonDBTM::getTable(PluginReleasesReview::class);
      return $dbu->countElementsInTable($table,
                                        ["plugin_releases_releases_id" => $item->getID()]);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $CFG_GLPI;
      if ($item->getType() == PluginReleasesRelease::getType()) {
         $self = new self();
         if (self::canCreate()) {
            $review = new PluginReleasesReview();
            if ($review->getFromDBByCrit(["plugin_releases_releases_id" => $item->getField('id')])) {
               $ID = $review->getID();
            } else {
               $ID = 0;
            }
            $self->showForm($ID, ['plugin_releases_releases_id' => $item->getField('id'),
                                  'target'                      => PLUGIN_RELEASES_WEBDIR . "/front/review.form.php"]);
         }
      }
   }


   function post_addItem() {
      // Add document if needed, without notification
      $this->input = $this->addFiles($this->input, ['force_update' => true]);

      $release = new PluginReleasesRelease();
      $release->getFromDB($this->input['plugin_releases_releases_id']);
      if ($release->getField('status') < PluginReleasesRelease::REVIEW) {
         $val           = [];
         $val['id']     = $release->getID();
         $val['status'] = PluginReleasesRelease::REVIEW;
         $release->update($val);
      }


   }

   function post_updateItem($history = 1) {
      // Add document if needed, without notification
      $this->input = $this->addFiles($this->input, ['force_update' => true]);

   }

   /**
    * Actions done after the PURGE of the item in the database
    *
    * @return void
    **/
   function post_purgeItem() {
      $release = new PluginReleasesRelease();
      $release->getFromDB($this->getField("plugin_releases_releases_id"));
      $val           = [];
      $val['id']     = $this->getField("plugin_releases_releases_id");
      $val['status'] = PluginReleasesRelease::FINALIZE;
      $release->update($val);
   }

   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $this->getFromDB($ID);

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

       $plugin_releases_releases_id = $this->getField("plugin_releases_releases_id");
      if (isset($options["plugin_releases_releases_id"])) {
          $plugin_releases_releases_id = $options["plugin_releases_releases_id"];
      }

      echo Html::hidden('plugin_releases_releases_id', ['value' => $plugin_releases_releases_id]);
      $rand = mt_rand();
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __("Real production run date", 'releases');
      echo "</td>";

      echo "<td>";
      $canedit = true;
      if ($this->getField("date_lock") == 1) {
         $canedit = false;
      }
      Html::showDateTimeField('real_date_release', ["value" => $this->getField('real_date_release'), 'canedit' => $canedit]);
      echo "</td>";
      echo "<td>" . __('Conforming realization', 'releases') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("conforming_realization", $this->getField("conforming_realization"));
      echo "</td>";

      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input("name", ['id' => 'name', "value" => $this->getField('name')]);

      echo "</td>";
      echo "<td>" . __('Incidents during process', 'releases') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("incident", $this->getField("incident"));
      echo "</td>";

      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description') . "</td>";
      echo "<td colspan='3'>";
      Html::textarea(["name" => "incident_description", "enable_richtext" => true, "value" => $this->getField('incident_description')]);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Technical Support Document", "releases") . "</td>";

      echo "<td colspan='3'>";
      $document = new Document_Item();
      $type     = PluginReleasesReview::getType();


      $content_id = "content$rand";
      Html::file(['filecontainer' => 'fileupload_info_ticket',
                  'editor_id'     => $content_id,
                  'showtitle'     => false,
                  'multiple'      => true]);
      if ($document->find(["itemtype" => $type, "items_id" => $this->getID()])) {
         $d       = new Document();
         $items_i = $document->find(["itemtype" => $type, "items_id" => $this->getID()]);
         //         $item_i = reset($items_i);
         foreach ($items_i as $item) {
            $items_i    = $d->find(["id" => $item["documents_id"]]);
            $item_i     = reset($items_i);
            $foreignKey = "plugin_releases_reviews_id";
            $pics_url   = $CFG_GLPI['root_doc'] . "/pics/timeline";

            if ($item_i['filename']) {
               $filename = $item_i['filename'];
               $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
               echo "<img src='";
               if (empty($filename)) {
                  $filename = $item_i['name'];
               }
               if (file_exists(GLPI_ROOT . "/pics/icones/$ext-dist.png")) {
                  echo $CFG_GLPI['root_doc'] . "/pics/icones/$ext-dist.png";
               } else {
                  echo "$pics_url/file.png";
               }
               echo "'/>&nbsp;";

               echo "<a href='" . $CFG_GLPI['root_doc'] . "/front/document.send.php?docid=" . $item_i['id']
                    . "&$foreignKey=" . $this->getID() . "' target='_blank'>$filename";
               if (Document::isImage(GLPI_DOC_DIR . '/' . $item_i['filepath'])) {
                  echo "<div class='timeline_img_preview'>";
                  echo "<img src='" . $CFG_GLPI['root_doc'] . "/front/document.send.php?docid=" . $item_i['id']
                       . "&$foreignKey=" . $this->getID() . "&context=timeline'/>";
                  echo "</div>";
               }
               echo "</a>";
            }
            if ($item_i['link']) {
               echo "<a href='{$item_i['link']}' target='_blank'><i class='fa fa-external-link'></i>{$item_i['name']}</a>";
            }
            if (!empty($item_i['mime'])) {
               echo "&nbsp;(" . $item_i['mime'] . ")";
            }
            echo "<span class='buttons'>";
            echo "<a href='" . Document::getFormURLWithID($item_i['id']) . "' class='edit_document fa fa-eye pointer' title='" .
                 _sx("button", "Show") . "'>";
            echo "<span class='sr-only'>" . _sx('button', 'Show') . "</span></a>";

            $doc = new Document();
            $doc->getFromDB($item_i['id']);
            if ($doc->can($item_i['id'], UPDATE)) {
               echo "<a href='" . static::getFormURL() .
                    "?delete_document&documents_id=" . $item_i['id'] .
                    "&$foreignKey=" . $this->getID() . "' class='delete_document fas fa-trash-alt pointer' title='" .
                    _sx("button", "Delete permanently") . "'>";
               echo "<span class='sr-only'>" . _sx('button', 'Delete permanently') . "</span></a>";
            }
            echo "</span>";
         }
      }

      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      $release = new PluginReleasesRelease();
      $release->getFromDB($plugin_releases_releases_id);
      if ($release->getField("status") == PluginReleasesRelease::REVIEW) {
         echo "<form method='post' action='" . $this->getFormURL() . "'>";
         echo "<br><table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'>";
         echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
         echo Html::hidden('plugin_releases_releases_id', ['value' => $plugin_releases_releases_id]);
         echo "<td>";
         echo Html::submit(_sx('button', 'Conclude the review', 'releases'), ['name' => 'conclude', 'class' => 'btn btn-primary']);
         echo "</td></tr>";
         echo "</table>";
      }


      return true;
   }

   function prepareInputForAdd($input) {

      $release = new PluginReleasesRelease();
      $release->getFromDB($input["plugin_releases_releases_id"]);
      $input["entities_id"] = $release->getField("entities_id");

      if (empty($input["real_date_release"])) {
          $input["real_date_release"] = NULL;
      }
      return $input;
   }

//   /**
//    * @param $ID
//    * @param $entity
//    *
//    * @return ID|int|the
//    */
//   static function transfer($ID, $entity) {
//      global $DB;
//
//      if ($ID > 0) {
//         $self  = new self();
//         $items = $self->find(["plugin_releases_releases_id" => $ID]);
//         foreach ($items as $id => $vals) {
//            $input                = [];
//            $input["id"]          = $id;
//            $input["entities_id"] = $entity;
//            $self->update($input);
//            self::transferDocument($id, $entity);
//         }
//         return true;
//
//      }
//      return 0;
//   }
//
//   static function transferDocument($ID, $entity) {
//      global $DB;
//
//      if ($ID > 0) {
//         $self      = new self();
//         $documents = new Document_Item();
//         $items     = $documents->find(["items_id" => $ID, "itemtype" => self::getType()]);
//         foreach ($items as $id => $vals) {
//            $input                = [];
//            $input["id"]          = $id;
//            $input["entities_id"] = $entity;
//            $documents->update($input);
//         }
//         return true;
//
//      }
//      return 0;
//   }
}

