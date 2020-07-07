<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018 by the Releases Development Team.

 https://github.com/InfotelGLPI/releases
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginReleasesRollback
 */
class PluginReleasesRollback extends CommonDBTM {

   static $rightname = 'plugin_releases_rollbacks';
   const TODO = 1; // todo
   const DONE = 2; // done

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Rollback', 'Rollbacks', $nb, 'releases');
   }

   /**
    *
    * @return css class
    */
   static function getCssClass() {
      return "rollback";
   }


   /**
    * @param \CommonDBTM $item
    *
    * @return int
    */
   static function countForItem(CommonDBTM $item) {
      $dbu   = new DbUtils();
      $table = CommonDBTM::getTable(self::class);
      return $dbu->countElementsInTable($table,
                                        ["plugin_releases_releases_id" => $item->getID()]);
   }

   /**
    * @param \CommonDBTM $item
    *
    * @return int
    */
   static function countDoneForItem(CommonDBTM $item) {
      $dbu   = new DbUtils();
      $table = CommonDBTM::getTable(self::class);
      return $dbu->countElementsInTable($table,
                                        ["plugin_releases_releases_id" => $item->getID(),
                                         "state"                       => self::DONE]);
   }

   /**
    * Prepare input datas for adding the item
    *
    * @param array $input datas used to add the item
    *
    * @return array the modified $input array
    **/
   function prepareInputForAdd($input) {

      $input = parent::prepareInputForAdd($input);

      $input["users_id"] = Session::getLoginUserID();
      $release = new PluginReleasesRelease();
      $release->getFromDB($input["plugin_releases_releases_id"]);
      $input["entities_id"] = $release->getField("entities_id");

      return $input;
   }

   function post_addItem() {
      parent::post_addItem();


   }

   /**
    * Prepare input datas for updating the item
    *
    * @param array $input data used to update the item
    *
    * @return array the modified $input array
    **/
   function prepareInputForUpdate($input) {
      // update last editor if content change
      if (isset($input['update'])
          && ($uid = Session::getLoginUserID())) { // Change from task form
         $input["users_id_editor"] = $uid;
      }
      $input = parent::prepareInputForUpdate($input);
      return $input;
   }


   function post_updateItem($history = 1) {

//      parent::post_updateItem($history);
   }
   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    */
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $rand_template = mt_rand();
      $rand_text     = mt_rand();
      $rand_name     = mt_rand();

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<input type='hidden' name='plugin_releases_releases_id' value='" . $options["plugin_releases_releases_id"] . "'>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo _n('Rollback template', 'Rollback templates', 1,'releases');
      echo "</td>";
      echo "<td style='vertical-align: middle' >";
      //      echo "<div class='fa-label'>
      //            <i class='fas fa-reply fa-fw'
      //               title='".."'></i>";
      PluginReleasesRollbacktemplate::dropdown(['value'     => $this->fields['plugin_releases_rollbacktemplates_id'],
                                                'entity'    => $this->getEntityID(),
                                                'rand'      => $rand_template,
                                                'on_change' => 'tasktemplate_update(this.value)']);
      echo "</div>";
      echo Html::scriptBlock('
         function tasktemplate_update(value) {
            $.ajax({
               url: "' . $CFG_GLPI["root_doc"] . '/plugins/release/ajax/rollback.php",
               type: "POST",
               data: {
                  templates_id: value
               }
            }).done(function(data) {
               console.log(data);
               

               // set textarea content
               $("#content' . $rand_text . '").html(data.content);
               // set name
               $("#name' . $rand_name . '").val(data.name);
               // set also tinmyce (if enabled)
               if (tasktinymce = tinymce.get("content' . $rand_text . '")) {
                  tasktinymce.setContent(data.content.replace(/\r?\n/g, "<br />"));
               }
               
            });
         }
      ');
      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td>";
      //      echo "<td>";
      //      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";


      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input("name", ["id" => "name" . $rand_name, "value" => $this->getField('name'), 'rand' => $rand_name,]);
      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description') . "</td>";
      echo "<td colspan='3'>";
      //       Html::textarea(["id"=>"content".$rand_content, "name"=>"content","enable_richtext"=>true,"value"=>$this->getField('content'),  'rand'      => $rand_content,]);
      $content_id = "content$rand_text";
      $cols       = 100;
      $rows       = 10;
      Html::textarea(['name'              => 'content',
                      'value'             => $this->fields["content"],
                      'rand'              => $rand_text,
                      'editor_id'         => $content_id,
                      'enable_fileupload' => false,
                      'enable_richtext'   => true,
                      'cols'              => $cols,
                      'rows'              => $rows]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }
}

