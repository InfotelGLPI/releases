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
class PluginReleasesRisktemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'plugin_releases_risks';



   static function getTypeName($nb = 0) {
      return _n('Risk template', 'Risk templates', $nb,'releases');
   }


   function getAdditionalFields() {

      return [
         ['name'  => 'plugin_releases_typerisks_id',
            'label' => __('Type risk','Type risks', 'releases'),
            'type'  => 'dropdownTests',
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
            PluginReleasesTypeRisk::dropdown(["name"=>"plugin_releases_typerisks_id"]);
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
   public function showForm($ID, $options = []) {
      global $CFG_GLPI, $DB;
      $rand_text       = mt_rand();
      $rand_name      = mt_rand();
      $rand_type      = mt_rand();
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __("Risk type",'releases');
      echo "</td>";

      echo "<td>";
      if (isset($_GET["typeriskid"])) {
         $value = $_GET["typeriskid"];
      } else {
         $value = $this->fields["plugin_releases_typerisks_id"];
      }
      Dropdown::show(PluginReleasesTypeRisk::getType(), ['name' => "plugin_releases_typerisks_id",
         'value' => $value,'rand'=>$rand_type]);
      echo "</td>";

      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input("name",['id'=>'name'.$rand_name,"value"=>$this->getField('name'),'rand'=>$rand_name]);
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description') . "</td>";
      echo "<td colspan='3'>";
//       Html::textarea(['id'=>'content'.$rand_content,"name"=>"content","enable_richtext"=>true,"value"=>$this->getField('content'),'rand'=>$rand_content]);
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

}
