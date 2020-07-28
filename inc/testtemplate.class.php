<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Template for task
 * @since 9.1
 **/
class PluginReleasesTesttemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'plugin_releases_tests';

   static function getTypeName($nb = 0) {
      return _n('Test template', 'Test templates', $nb,'releases');
   }


   function getAdditionalFields() {

      return [
         ['name'  => 'plugin_releases_typetests_id',
            'label' => _n('Test type','Test types',1, 'releases'),
            'type'  => 'dropdownTests',
         ],
         ['name'  => 'plugin_releases_risks_id',
            'label' => _n('Risk','Risks', 1,'releases'),
            'type'  => 'dropdownRisks',
         ],
         ['name'  => 'content',
            'label' => __('Description'),
            'type'  => 'textarea',
            'rows' => 10],

      ];
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '4',
         'name'               => __('Content'),
         'field'              => 'content',
         'table'              => $this->getTable(),
         'datatype'           => 'text',
         'htmltext'           => true
      ];

      $tab[] = [
         'id'                 => '3',
         'name'               => __('Deploy Task type'),
         'field'              => 'name',
         'table'              => getTableForItemType('PluginReleasesTypeDeployTask'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   /**
    * @see CommonDropdown::displaySpecificTypeField()
    **/
   function displaySpecificTypeField($ID, $field = []) {

      switch ($field['type']) {
         case 'dropdownTests' :
            PluginReleasesTypeTest::dropdown(["name"=>"plugin_releases_typetests_id"]);
            break;
         case 'dropdownRisks' :
            PluginReleasesRisktemplate::dropdown(["name"=>"plugin_releases_risks_id"]);
            break;

      }
   }


   static function canCreate() {
      return Session::haveRightsOr(static::$rightname, [UPDATE,CREATE]);
   }

   /**
    * Have I the global right to "view" the Object
    *
    * Default is true and check entity if the objet is entity assign
    *
    * May be overloaded if needed
    *
    * @return booleen
    **/
   static function canView() {
      return Session::haveRight(static::$rightname, READ);
   }

   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool|void
    */
   function showForm($ID, $options = []) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $rand_text       = mt_rand();
      $rand_name      = mt_rand();
      $rand_type      = mt_rand();
      $rand_risk      = mt_rand();

      echo "<tr class='tab_bg_1' hidden>";
      echo "<td colspan='4'>";
      $foreignKey = PluginReleasesReleasetemplate::getForeignKeyField();
      echo Html::hidden($foreignKey,["value"=>$this->fields[$foreignKey]]);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";

      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input("name",['id'=>'name'.$rand_name,"value"=>$this->getField('name')]);
      echo "</td>";

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



      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __("Associated risk",'releases');
      echo "</td>";

      echo "<td>";

      Dropdown::show(PluginReleasesRisktemplate::getType(), ['rand'=>$rand_risk,'name' => "plugin_releases_risks_id",
         'value' =>  $this->fields["plugin_releases_risks_id"],"condition"=>["plugin_releases_releasetemplates_id"=>$this->fields[$foreignKey]]]);
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
         'enable_fileupload' => false,
         'enable_richtext'   => true,
         'cols'              => $cols,
         'rows'              => $rows]);
      echo "</td>";
      echo "</tr>";
      $this->showFormButtons($options);
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
         ["plugin_releases_releasetemplates_id" => $item->getID()]);
   }
   /**
    *
    * @return css class
    */
   static function getCssClass() {
      return "test";
   }
   function post_addItem() {
      $_SESSION['releases']["template"][Session::getLoginUserID()] = 'test';
   }

   /**
    * @param $ID
    * @param $entity
    * @return ID|int|the
    */
   static function transfer($ID, $entity) {
      global $DB;

      if ($ID > 0) {
         $self = new self();
         $items = $self->find(["plugin_releases_releasetemplates_id"=>$ID]);
         foreach ($items as $id => $vals){
            $input = [];
            $input["id"] = $id;
            $input["entities_id"] = $entity;
            $self->update($input);
         }
         return true;

      }
      return 0;
   }
}
