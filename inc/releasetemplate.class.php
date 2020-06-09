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
class PluginReleasesReleasetemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'plugin_releases_releases';



   static function getTypeName($nb = 0) {
      return _n('Release template', 'Release templates', $nb,'release');
   }


   function getAdditionalFields() {

      return [
         ['name'  => 'release_area',
            'label' => __('Release Area', 'release'),
            'type'  => 'textarea',
            'rows' => 10],
         ['name'  => 'date_preproduction',
            'label' => __('Pre-production run date', 'release'),
            'type'  => 'date',
            ],
         ['name'  => 'date_production',
            'label' => __('Production run date', 'release'),
            'type'  => 'date',
            ],
         ['name'  => 'service_shutdown',
            'label' => __('Service shutdown', 'release'),
            'type'  => 'bool',
            ],
         ['name'  => 'service_shutdown_details',
            'label' => __('Service shutdown details', 'release'),
            'type'  => 'textarea',
            'rows' => 10],
         ['name'  => 'hour_type',
            'label' => __('Non working hour', 'release'),
            'type'  => 'bool',
         ],
         ['name'  => 'tests',
            'label' => _n('Test','Tests', 2,'release'),
            'type'  => 'dropdownTests',
         ],
         ['name'  => 'rollbacks',
            'label' => _n('Rollback','Rollbacks',2, 'release'),
            'type'  => 'dropdownRollbacks',
         ],
         ['name'  => 'tasks',
            'label' => _n('Deploy Task','Deploy Tasks',2, 'release'),
            'type'  => 'dropdownTasks',
         ],
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
      $dbu = new DbUtils();

      switch ($field['type']) {
         case 'state' :
            PluginReleasesRelease::dropdownStateItem("state", $this->fields["state"]);
            break;
         case 'dropdownRollbacks' :
            $item = new PluginReleasesRollbacktemplate();
            $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
           $rolltemp = new PluginReleasesRollbacktemplate();
           $alltemps = $rolltemp->find($condition);
           $rolls = [];
           foreach ($alltemps as $roll){
              $rolls[$roll["id"]] = $roll["name"];
           }

           $val = $this->getField("rollbacks");
           $val = json_decode($val);
           if($val == ""){
              $val = [];
           }
           Dropdown::showFromArray("rollbacks", $rolls, array('id' => 'rollbacks', 'multiple' => true, 'values' => $val, "display" => true));

            break;
         case 'dropdownTests' :
            $item = new PluginReleasesTesttemplate();
            $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
            $testtemp = new PluginReleasesTesttemplate();
            $alltemps = $testtemp->find($condition);
            $tests = [];
            foreach ($alltemps as $test){
               $tests[$test["id"]] = $test["name"];
            }

            $val = $this->getField("tests");
            $val = json_decode($val);
            if($val == ""){
               $val = [];
            }
            Dropdown::showFromArray("tests", $tests, array('id' => 'tests', 'multiple' => true, 'values' => $val, "display" => true));
            break;
         case 'dropdownTasks' :
            $item = new PluginReleasesDeploytasktemplate();
            $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
            $tasktemp = new PluginReleasesDeploytasktemplate();
            $alltemps = $tasktemp->find($condition);
            $tasks = [];
            foreach ($alltemps as $task){
               $tasks[$task["id"]] = $task["name"];
            }

            $val = $this->getField("tasks");
            $val = json_decode($val);
            if($val == ""){
               $val = [];
            }
            Dropdown::showFromArray("tasks", $tasks, array('id' => 'tasks', 'multiple' => true, 'values' => $val, "display" => true));
            break;
         case 'actiontime' :
            $toadd = [];
            for ($i=9; $i<=100; $i++) {
               $toadd[] = $i*HOUR_TIMESTAMP;
            }
            Dropdown::showTimeStamp(
               "actiontime", [
                  'min'             => 0,
                  'max'             => 8*HOUR_TIMESTAMP,
                  'value'           => $this->fields["actiontime"],
                  'addfirstminutes' => true,
                  'inhours'         => true,
                  'toadd'           => $toadd
               ]
            );
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

   function prepareInputForAdd($input) {
      $input["tests"] = isset($input["tests"])?json_encode($input["tests"]):json_encode([]);
      $input["tasks"] = isset($input["tasks"])?json_encode($input["tasks"]):json_encode([]);
      $input["rollbacks"] =isset($input["rollbacks"])? json_encode($input["rollbacks"]):json_encode([]);
      return $input;
   }

   function prepareInputForUpdate($input) {

      $input["tests"] = isset($input["tests"])?json_encode($input["tests"]):json_encode([]);
      $input["tasks"] = isset($input["tasks"])?json_encode($input["tasks"]):json_encode([]);
      $input["rollbacks"] =isset($input["rollbacks"])? json_encode($input["rollbacks"]):json_encode([]);
      return $input;
   }

   function ShowForm($ID, $options = []) {
      global $CFG_GLPI, $DB;
      $this->initForm($ID, $options);
      $this->showFormHeader($options);


      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input("name",["value"=>$this->getField('name')]);

      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td >";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Release area','releases') . "</td>";
      echo "<td colspan='3'>";
      Html::textarea(["name"=>"release_area","enable_richtext"=>true,"value"=>$this->getField('release_area')]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Pre-production planned run date','releases') . "</td>";
      echo "<td >";
      $date_preprod =  Html::convDateTime($this->getField('date_preproduction'));
      Html::showDateField("date_preproduction",["value"=>$date_preprod]);
      echo "</td>";
      echo "<td>" . __('Production planned run date','releases') . "</td>";
      echo "<td >";
      $date_prod =  Html::convDateTime($this->getField('date_production'));
      Html::showDateField("date_production",["value"=>$date_prod]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Location') . "</td>";
      echo "<td >";
      Dropdown::show(Location::getType(),["name"=>"locations_id","value"=>$this->getField('locations_id')]);
      echo "</td>";
      echo "<td>" . __('Service shutdown','releases') . "</td>";
      echo "<td >";
      Dropdown::showYesNo("service_shutdown",$this->getField('service_shutdown'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Service shutdown details','releases') . "</td>";
      echo "<td colspan='3'>";
      Html::textarea(["name"=>"service_shutdown_details","enable_richtext"=>true,"value"=>$this->getField('service_shutdown_details')]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Non-working hour','releases') . "</td>";
      echo "<td >";
      Dropdown::showYesNo("hour_type",$this->getField('hour_type'));
      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td >";
//      echo "<td>" . __('Communication','releases') . "</td>";
//      echo "<td >";
//      Dropdown::showYesNo("communication",$this->getField('communication'));
//      echo "</td>";
      echo "</tr>";
      $dbu = new DbUtils();
      echo "<tr>";
      echo "<td>";
      echo _n('Test','Tests', 2,'release');


      echo "</td>";

      echo "<td>";
            $item = new PluginReleasesRollbacktemplate();
            $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
           $rolltemp = new PluginReleasesRollbacktemplate();
           $alltemps = $rolltemp->find($condition);
           $rolls = [];
           foreach ($alltemps as $roll){
              $rolls[$roll["id"]] = $roll["name"];
           }

           $val = $this->getField("rollbacks");
           $val = json_decode($val);
           if($val == ""){
              $val = [];
           }
           Dropdown::showFromArray("rollbacks", $rolls, array('id' => 'rollbacks', 'multiple' => true, 'values' => $val, "display" => true));

      echo "</td>";
      echo "<td>";
      echo _n('Rollback','Rollbacks',2, 'release');


      echo "</td>";
      echo "<td>";

            $item = new PluginReleasesTesttemplate();
            $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
            $testtemp = new PluginReleasesTesttemplate();
            $alltemps = $testtemp->find($condition);
            $tests = [];
            foreach ($alltemps as $test){
               $tests[$test["id"]] = $test["name"];
            }

            $val = $this->getField("tests");
            $val = json_decode($val);
            if($val == ""){
               $val = [];
            }
            Dropdown::showFromArray("tests", $tests, array('id' => 'tests', 'multiple' => true, 'values' => $val, "display" => true));
      echo "</td>";
      echo "</tr>";
      echo "<tr>";
      echo "<td>";
      echo  _n('Deploy Task','Deploy Tasks',2, 'release');
      echo "</td>";
      echo "<td>";
            $item = new PluginReleasesDeploytasktemplate();
            $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
            $tasktemp = new PluginReleasesDeploytasktemplate();
            $alltemps = $tasktemp->find($condition);
            $tasks = [];
            foreach ($alltemps as $task){
               $tasks[$task["id"]] = $task["name"];
            }

            $val = $this->getField("tasks");
            $val = json_decode($val);
            if($val == ""){
               $val = [];
            }
            Dropdown::showFromArray("tasks", $tasks, array('id' => 'tasks', 'multiple' => true, 'values' => $val, "display" => true));
      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td>";
      echo "</tr>";

//      echo "<tr class='tab_bg_1'>";
//      echo "<td>" . __('Communication type','releases') . "</td>";
//      echo "<td >";
//      $types   = ['Entity'=>'Entity', 'Group'=>'Group', 'Profile'=>'Profile', 'User'=>'User','Location'=>'Location'];
//      $addrand = Dropdown::showItemTypes('communication_type', $types,["id"=>"communication_type","value"=>$this->getField('communication_type')]);
//      echo "</td>";
//      $targets = [];
//      $targets = json_decode($this->getField('target'));
////      $targets = $this->getField('target');
//      echo "<td>" ._n('Target', 'Targets',
//            Session::getPluralNumber()) . "</td>";
//
//
//      echo "<td id='targets'>";
//
//
//      echo "</td>";
//      Ajax::updateItem( "targets",
//         $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/changeTarget.php",
//         ['type' => $this->getField('communication_type'),'current_type'=>$this->getField('communication_type'),'values'=>$targets], true);
//      Ajax::updateItemOnSelectEvent("dropdown_communication_type".$addrand, "targets",
//         $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/changeTarget.php",
//         ['type' => '__VALUE__','current_type'=>$this->getField('communication_type'),'values'=>$targets], true);
//      echo "</tr>";
      $this->showFormButtons($options);
      return true;
   }
}
