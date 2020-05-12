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

   public $dohistory = true;
   static $rightname = 'ticket';
   protected $usenotepad = true;
   static $types = [];


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


   //TODO
   /**
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];

   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == self::getType()) {
        return self::getTypeName(2);
      } else if ($item->getType() == PluginReleasesRelease::getType()){
         return self::createTabEntry(self::getTypeName(2), self::countForItem($item));
      }

      return '';
   }
   static function countForItem(CommonDBTM $item) {
      $dbu = new DbUtils();
      $table = CommonDBTM::getTable(PluginReleasesTest::class);
      return $dbu->countElementsInTable($table,
         ["plugin_releases_releases_id" => $item->getID()]);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $CFG_GLPI;
      if ($item->getType() == PluginReleasesRelease::getType()) {
         $self = new self();
         if(self::canView()){
            $self->showScripts($item);
         }
//         if(self::canCreate()) {
//            $self->showForm("", ['plugin_release_releases_id' => $item->getField('id'),
//               'target' => $CFG_GLPI['root_doc'] . "/plugins/release/front/test.form.php"]);
//         }
      }

   }
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      return $ong;
   }
/**
* Type than could be linked to a Rack
*
* @param $all boolean, all type, or only allowed ones
*
* @return array of types
* */
   static function getTypes($all = false) {

      if ($all) {
         return self::$types;
      }

      // Only allowed types
      $types = self::$types;

      foreach ($types as $key => $type) {
         if (!class_exists($type)) {
            continue;
         }

         $item = new $type();
         if (!$item->canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }

   function initShowForm($ID, $options = []){


      $this->initForm($ID, $options);
      $this->showFormHeader($options);

   }

   function closeShowForm($options){
      $this->showFormButtons($options);
   }

   function showForm($ID, $options = []) {

      $this->initShowForm($ID,$options);

      $this->coreShowForm($ID,$options);
      $this->closeShowForm($options);

      return true;
   }

   function coreShowForm($ID, $options = []) {
      global $CFG_GLPI, $DB;
      $rand_template   = mt_rand();
      $rand_text       = mt_rand();
      $rand_name      = mt_rand();
      $rand_type      = mt_rand();
      $rand_risk      = mt_rand();

      echo "<tr class='tab_bg_1'>";
      if (isset($options['plugin_releases_releases_id'])) {


         echo "<td hidden>" . _n('Release', 'Releases', 1, 'releases') . "</td>";
         $rand = mt_rand();

         echo "<td hidden>";
         Dropdown::show(PluginReleasesRelease::getType(),
            ['name' => "plugin_releases_releases_id", 'id' => "plugin_releases_releases_id",
               'value' => $options["plugins_release_releases_id"],
               'rand' => $rand]);
         echo "</td>";
      } else {
         echo "<td>" . _n('Release', 'Releases', 1, 'releases') . "</td>";
         $rand = mt_rand();

         echo "<td>";
         Dropdown::show(PluginReleasesRelease::getType(), ['name' => "plugin_releases_releases_id", 'id' => "plugin_releases_releases_id",
            'value' => $this->fields["plugin_releases_releases_id"]]);
         echo "</td>";
      }
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td style='vertical-align: middle' colspan='4'>";
      echo "<div class='fa-label'>
            <i class='fas fa-reply fa-fw'
               title='"._n('Task template', 'Task templates', 2)."'></i>";
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
               $("#content'.$rand_text.'").html(data.content);
               // set name
               $("#name'.$rand_name.'").val(data.name);
               $("#dropdown_plugin_releases_typetests_id'.$rand_type.'").trigger("setValue", plugin_releases_typetests_id);
              
               // set also tinmyce (if enabled)
               if (tasktinymce = tinymce.get("content'.$rand_text.'")) {
                  tasktinymce.setContent(data.content.replace(/\r?\n/g, "<br />"));
               }
               
            });
         }
      ');
      echo "</td>";
//      echo "<td>";
//      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __("Test type",'releases');
      echo "</td>";

      echo "<td>";
      if (isset($_GET["typetestid"])) {
         $value = $_GET["typetestid"];
      } else {
         $value = $this->fields["plugin_releases_typetests_id"];
      }
      Dropdown::show(PluginReleasesTypeTest::getType(), ['rand'=>$rand_type,'name' => "plugin_releases_typetests_id",
         'value' => $value]);
      echo "</td>";

      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input("name",['id'=>'name'.$rand_name,"value"=>$this->getField('name')]);
      echo "</td>";

      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __("Associated risk",'releases');
      echo "</td>";

      echo "<td>";

      Dropdown::show(PluginReleasesRisk::getType(), ['rand'=>$rand_risk,'name' => "plugin_releases_risks_id", "condition"=>["plugin_releases_releases_id"=>$options['plugin_releases_releases_id']],
         'value' =>  $this->fields["plugin_releases_risks_id"]]);
      echo "</td>";
      echo "<td colspan='2'></td>";
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
         'enable_fileupload' => true,
         'enable_richtext'   => true,
         'cols'              => $cols,
         'rows'              => $rows]);
      echo "</td>";
      echo "</tr>";

      return true;
   }

   function prepareInputForUpdate($input) {
      // update last editor if content change
      if (isset($input['update'])
         && ($uid = Session::getLoginUserID())) { // Change from task form
         $input["users_id_editor"] = $uid;
      }
      return $input;
   }

   function showScripts(PluginReleasesRelease $release) {

      echo "<div class='timeline_box'>";
      $rand = mt_rand();
      $release->showTimelineForm($rand,self::class);
      $release->showTimeLine($rand,self::class);
      $release->showStateItem("test_state",__("All tests are defined ?","releases"),PluginReleasesRelease::TESTDEFINITION);

      echo "</div>";

   }
   function prepareInputForAdd($input) {

      $input =  parent::prepareInputForAdd($input);

      $input["users_id"] = Session::getLoginUserID();

      return $input;
   }

   /**
    * @return array
    */
   static function getMenuContent() {

      $menu['title'] = self::getMenuName(2);
      $menu['page'] = self::getSearchURL(false);
      $menu['links']['search'] = self::getSearchURL(false);

      $menu['links']['template'] = "/plugins/releases/front/testtemplate.php";
      if (self::canCreate()) {
         $menu['links']['add'] = self::getFormURL(false);
      }


      return $menu;
   }

}

