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
 * Class PluginReleasesTest
 */
class PluginReleasesTest extends CommonDBTM {

   static $rightname = 'plugin_releases_tests';
   const TODO = 1; // todo
   const DONE = 2; // done
   const FAIL = 3; // fail

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Test', 'Tests', $nb, 'releases');
   }

   /**
    *
    * @return css class
    */
   static function getCssClass() {
      return "test";
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
    * @param \CommonDBTM $item
    *
    * @return int
    */
   static function countFailForItem(CommonDBTM $item) {
      $dbu   = new DbUtils();
      $table = CommonDBTM::getTable(self::class);
      return $dbu->countElementsInTable($table,
                                        ["plugin_releases_releases_id" => $item->getID(),
                                         "state"                       => self::FAIL]);
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
      $release           = new PluginReleasesRelease();
      $release->getFromDB($input["plugin_releases_releases_id"]);
      $input["entities_id"] = $release->getField("entities_id");

      return $input;
   }

   /**
    *
    */
   function post_addItem() {
      parent::post_addItem();

      //      $input                                = [];
      //      $input["name"]                        = $this->fields["name"];
      //      $input["plugin_releases_risks_id"]    = $this->fields["plugin_releases_risks_id"];
      //      $input["content"]                     = $this->fields["content"];
      //      $input["plugin_releases_releases_id"] = $this->fields["plugin_releases_releases_id"];
      //      $input["users_id_tech"]               = $_SESSION['glpiID'];
      //      $task                                 = new PluginReleasesDeploytask();
      //      $task->add($input);

      $release            = new PluginReleasesRelease();
      $inputRelease       = [];
      $inputRelease["id"] = $this->fields["plugin_releases_releases_id"];
      if (self::countForItem($release) == self::countDoneForItem($release)) {
         $inputRelease               = [];
         $inputRelease["id"]         = $release->getID();
         $inputRelease["test_state"] = 1;
         $release->update($inputRelease);
      } else {
         $inputRelease               = [];
         $inputRelease["id"]         = $release->getID();
         $inputRelease["test_state"] = 0;
         $release->update($inputRelease);
      }


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
      $release = new PluginReleasesRelease();
      $release->getFromDB($this->getField("plugin_releases_releases_id"));
      if (self::countForItem($release) == self::countDoneForItem($release)) {
         $inputRelease               = [];
         $inputRelease["id"]         = $release->getID();
         $inputRelease["test_state"] = 1;
         $release->update($inputRelease);
      } else {
         $inputRelease               = [];
         $inputRelease["id"]         = $release->getID();
         $inputRelease["test_state"] = 0;
         $release->update($inputRelease);
      }
      parent::post_updateItem($history);
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
      $rand_type     = mt_rand();
      $rand_risk     = mt_rand();
      $rand_state    = mt_rand();

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<input type='hidden' name='plugin_releases_releases_id' value='" . $options["plugin_releases_releases_id"] . "'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo _n('Test template', 'Test templates', 1, 'releases');
      echo "</td>";
      echo "<td style='vertical-align: middle' >";
      //      echo "<div class='fa-label'>
      //            <i class='fas fa-reply fa-fw'
      //               title='"._n('Task template', 'Task templates', 2)."'></i>";
      PluginReleasesTesttemplate::dropdown(['value'     => '',
                                            'entity'    => $this->getEntityID(),
                                            'rand'      => $rand_template,
                                            'on_change' => 'tasktemplate_update(this.value)']);
      echo "</div>";
      echo Html::scriptBlock('
         function tasktemplate_update(value) {
            $.ajax({
               url: "' . $CFG_GLPI["root_doc"] . '/plugins/releases/ajax/test.php",
               type: "POST",
               data: {
                  templates_id: value
               }
            }).done(function(data) {
               console.log(data);
               var plugin_releases_typetests_id = isNaN(parseInt(data.plugin_releases_typetests_id))
                  ? 0
                  : parseInt(data.plugin_releases_typetests_id);
                  
                  

               // set textarea content
               $("#content' . $rand_text . '").html(data.content);
               // set name
               $("#name' . $rand_name . '").val(data.name);
               $("#dropdown_plugin_releases_typetests_id' . $rand_type . '").trigger("setValue", plugin_releases_typetests_id);
              
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
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __("Test type", 'releases');
      echo "</td>";

      echo "<td>";
      if (isset($_GET["typetestid"])) {
         $value = $_GET["typetestid"];
      } else {
         $value = $this->fields["plugin_releases_typetests_id"];
      }
      Dropdown::show(PluginReleasesTypeTest::getType(), ['rand'  => $rand_type, 'name' => "plugin_releases_typetests_id",
                                                         'value' => $value]);
      echo "</td>";

      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input("name", ['id' => 'name' . $rand_name, "value" => $this->getField('name')]);
      echo "</td>";

      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __("Associated risk", 'releases');
      echo "</td>";
      echo "<td>";
      Dropdown::show(PluginReleasesRisk::getType(), ['rand'  => $rand_risk, 'name' => "plugin_releases_risks_id", "condition" => ["plugin_releases_releases_id" => $options['plugin_releases_releases_id']],
                                                     'value' => $this->fields["plugin_releases_risks_id"]]);
      echo "</td>";
      echo "<td>";
      echo __('Status');
      echo "</td>";

      echo "<td>";
      if (isset($this->fields["state"])) {
         echo "<div class='fa-label'>
            <i class='fas fa-tasks fa-fw'
               title='" . __('Status') . "'></i>";
         PluginReleasesDeploytask::dropdownStateTask("state", $this->fields["state"], true, ['rand' => $rand_state]);
         echo "</div>";
      }
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description') . "</td>";
      echo "<td colspan='3'>";
      //       Html::textarea(["name"=>"content","enable_richtext"=>true,"value"=>$this->getField('content')]);
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

