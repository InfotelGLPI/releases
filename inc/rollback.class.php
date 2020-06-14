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

   public $dohistory = true;
   static $rightname = 'plugin_releases_rollbacks';
   protected $usenotepad = true;
   static $types = [];


   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Rollback', 'Rollbacks', $nb, 'release');
   }
   /**
    *
    * @return css class
    */
   static function getCssClass() {

      return "rollback";
   }


   //TODO
   /**
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];
      return $tab;

   }

//   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
//
//      if ($item->getType() == self::getType()) {
//        return self::getTypeName(2);
//      } else if ($item->getType() == PluginReleasesRelease::getType()){
//         return self::createTabEntry(self::getTypeName(2), self::countForItem($item));
//      }
//
//      return '';
//   }
   static function countForItem(CommonDBTM $item) {
      $dbu = new DbUtils();
      $table = CommonDBTM::getTable(PluginReleasesRollback::class);
      return $dbu->countElementsInTable($table,
         ["plugin_releases_releases_id" => $item->getID()]);
   }

//
//   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
//      global $CFG_GLPI;
//      if ($item->getType() == PluginReleasesRelease::getType()) {
//         $self = new self();
//         if(self::canView()){
//            $self->showScripts($item);
//         }else{
//            echo "<div class='center'><br><br>";
//            echo Html::image($CFG_GLPI["root_doc"] . "/pics/warning.png", ['alt' => __('Warning')]);
//            echo "<br><br><span class='b'>".__("You don't have permission to perform this action.")."</span></div>";
//         }
////         if(self::canCreate()) {
////            $self->showForm("", ['plugin_release_releases_id' => $item->getField('id'),
////               'target' => $CFG_GLPI['root_doc'] . "/plugins/release/front/rollback.form.php"]);
////         }
//      }
//
////   }
//   function defineTabs($options = []) {
//
//      $ong = [];
//      $this->addDefaultFormTab($ong);
//      return $ong;
//   }
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


      echo "<tr class='tab_bg_1'>";
      echo "<input type='hidden' name='plugin_releases_releases_id' value='" . $options["plugin_releases_releases_id"] . "'>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo _n('Rollback template', 'Rollback templates', 2);
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
               $("#content'.$rand_text.'").html(data.content);
               // set name
               $("#name'.$rand_name.'").val(data.name);
               // set also tinmyce (if enabled)
               if (tasktinymce = tinymce.get("content'.$rand_text.'")) {
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
      echo Html::input("name",["id"=>"name".$rand_name,"value"=>$this->getField('name'),  'rand'      => $rand_name,]);
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

      return true;
   }

   function showScripts(PluginReleasesRelease $release) {

      echo "<div class='timeline_box'>";
      $rand = mt_rand();
//      $release->showTimelineForm($rand,self::class);
//      $release->showTimeLine($rand,self::class);
      $release->showStateItem("rollback_state",__("All rollbacks are defined ?","release"),PluginReleasesRelease::ROLLBACKDEFINITION);
      echo "</div>";

   }
   function prepareInputForAdd($input) {

      $input =  parent::prepareInputForAdd($input);

      $input["users_id"] = Session::getLoginUserID();

      return $input;
   }
   function prepareInputForUpdate($input) {
      // update last editor if content change
      if (isset($input['update'])
         && ($uid = Session::getLoginUserID())) { // Change from task form
         $input["users_id_editor"] = $uid;
      }
      return $input;
   }

}

