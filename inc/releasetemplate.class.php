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
      return _n('Release template', 'Release templates', $nb,'releases');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (static::canView()) {
         switch ($item->getType()) {
            case __CLASS__ :
               $timeline    = $item->getTimelineItems();
               $nb_elements = count($timeline);
//               $nb_elements = 0;

               $ong = [
                  1 => __("Processing release", 'releases') . " <sup class='tab_nb'>$nb_elements</sup>",
               ];

               return $ong;

         }
      }
      return '';
   }
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case __CLASS__ :
            switch ($tabnum) {
               case 1 :
                  if(!$withtemplate){
                     echo "<div class='timeline_box'>";
                     $rand = mt_rand();
                     $item->showTimelineForm($rand);
                     $item->showTimeline($rand);
                     echo "</div>";
                  }else{
                     echo "<div class='timeline_box'>";
                     $rand = mt_rand();
                     $item->showTimeline($rand);
                     echo "</div>";
                  }

                  break;

            }
            break;

      }
      return true;
   }
   function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab(self::getType(), $ong, $options);
      $this->addDefaultFormTab($ong);
//      $this->defineDefaultObjectTabs($ong, $options);
      $this->addStandardTab('PluginReleasesReleasetemplate_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options); // todo hide in template
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);


      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }
   function getAdditionalFields() {

      return [
         ['name'  => 'content',
            'label' => __('Description', 'release'),
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
            'label' => __('Non-working hour', 'release'),
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
            'label' => _n('Deploy task','Deploy tasks',2, 'release'),
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
         case 'status' :
            PluginReleasesRelease::dropdownStateItem("status", $this->fields["status"]);
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
     $input =  parent::prepareInputForAdd($input);
      $input["tests"] = isset($input["tests"])?json_encode($input["tests"]):json_encode([]);
      $input["tasks"] = isset($input["tasks"])?json_encode($input["tasks"]):json_encode([]);
      $input["rollbacks"] =isset($input["rollbacks"])? json_encode($input["rollbacks"]):json_encode([]);
      return $input;
   }

   function prepareInputForUpdate($input) {
      $input = parent::prepareInputForUpdate($input);
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
      Html::textarea(["name"=>"content",
                      "enable_richtext"=>true,
                      "value"=>$this->getField('content')]);
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
      echo "<tr class='tab_bg_1'>";
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
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo  _n('Deploy task','Deploy tasks',2, 'release');
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

   function displayMenu($ID, $options = []) {
      echo "<div class='center'>";
      echo "<table class='tab_cadre'>";
      echo "<tr  class='tab_bg_1'>";
      echo "<th>" . __("Release","releases") . "</th>";
      echo "</tr>";
      echo "<tr  class='tab_bg_1'>";
      echo "<td class='center b' >";
      $dbu = new DbUtils();
      $condition = $dbu->getEntitiesRestrictCriteria($this->getTable());
      self::dropdown(["name"=>"releasetemplates_id"]+$condition);
      $url = PluginReleasesRelease::getFormURL();
      echo "<a  id='link' href='$url'>";
      $url = $url."?template_id=";
      $script = "
      var link = function (id,linkurl) {
         var link = linkurl+id;
         $(\"a#link\").attr(\"href\", link);
      };
      $(\"select[name='releasetemplates_id']\").change(function() {
         link($(\"select[name='releasetemplates_id']\").val(),'$url');
         });";

      echo Html::scriptBlock('$(document).ready(function() {'.$script.'});');
      echo "<br/><br/>";
      echo __("Create a release", 'releases');
      echo "</a>";
      echo "</table>";
      echo "</div>";
   }

   function getTimelineItems() {

      $objType    = self::getType();
      $foreignKey = self::getForeignKeyField();
//      $foreignKey =  "plugin_releases_releases_id";

      $timeline = [];

      $riskClass     = 'PluginReleasesRisktemplate';
      $risk_obj      = new $riskClass;
      $rollbackClass = 'PluginReleasesRollbacktemplate';
      $rollback_obj  = new $rollbackClass;
      $taskClass     = 'PluginReleasesDeploytasktemplate';
      $task_obj      = new $taskClass;
      $testClass     = 'PluginReleasesTesttemplate';
      $test_obj      = new $testClass;

      //checks rights
      $restrict_risk = $restrict_rollback = $restrict_task = $restrict_test = [];
      //      $restrict_risk['itemtype'] = static::getType();
      //      $restrict_risk['items_id'] = $this->getID();
      $user = new User();





      //checks rights

      //add risks to timeline
      if ($risk_obj->canview()) {
         $risks = $risk_obj->find([$foreignKey => $this->getID()] + $restrict_risk, ['date_mod DESC', 'id DESC']);
         foreach ($risks as $risks_id => $risk) {
            $risk_obj->getFromDB($risks_id);
            $risk['can_edit']                                   = $risk_obj->canUpdateItem();
            $timeline[$risk['date_mod'] . "_risk_" . $risks_id] = ['type'     => $riskClass,
               'item'     => $risk,
               'itiltype' => 'Risk'];
         }
      }

      if ($rollback_obj->canview()) {
         $rollbacks = $rollback_obj->find([$foreignKey => $this->getID()] + $restrict_rollback, ['date_mod DESC', 'id DESC']);
         foreach ($rollbacks as $rollbacks_id => $rollback) {
            $rollback_obj->getFromDB($rollbacks_id);
            $rollback['can_edit']                                       = $rollback_obj->canUpdateItem();
            $timeline[$risk['date_mod'] . "_rollback_" . $rollbacks_id] = ['type'     => $rollbackClass,
               'item'     => $rollback,
               'itiltype' => 'Rollback'];
         }
      }

      if ($task_obj->canview()) {
         //         $tasks = $task_obj->find([$foreignKey => $this->getID()] + $restrict_task);
         $tasks = $task_obj->find([$foreignKey => $this->getID()] + $restrict_task, ['level DESC']);
         foreach ($tasks as $tasks_id => $task) {
            $task_obj->getFromDB($tasks_id);
            $task['can_edit']                                                      = $task_obj->canUpdateItem();
            $rand                                                                  = mt_rand();
            $timeline["task" . $task_obj->getField('level') . "$tasks_id" . $rand] = ['type'     => $taskClass,
               'item'     => $task,
               'itiltype' => 'Task'];
         }
      }

      if ($test_obj->canview()) {
         $tests = $test_obj->find([$foreignKey => $this->getID()] + $restrict_test, ['date_mod DESC', 'id DESC']);
         foreach ($tests as $tests_id => $test) {
            $test_obj->getFromDB($tests_id);
            $test['can_edit']                                   = $test_obj->canUpdateItem();
            $timeline[$risk['date_mod'] . "_test_" . $tests_id] = ['type'     => $testClass,
               'item'     => $test,
               'itiltype' => 'test'];
         }
      }

      //reverse sort timeline items by key (date)
      ksort($timeline);

      return $timeline;
   }

   function showTimelineForm($rand) {
      global $CFG_GLPI;

      $objType    = static::getType();
      $foreignKey = static::getForeignKeyField();

      //check sub-items rights
      $tmp       = [$foreignKey => $this->getID()];
      $riskClass = "PluginReleasesRisktemplate";
      $risk      = new $riskClass;
      $risk->getEmpty();
      $risk->fields['itemtype'] = $objType;
      $risk->fields['items_id'] = $this->getID();


      $rollbackClass = "PluginReleasesRollbacktemplate";
      $rollback      = new $rollbackClass;
      $rollback->getEmpty();
      $rollback->fields['itemtype'] = $objType;
      $rollback->fields['items_id'] = $this->getID();

      $taskClass = "PluginReleasesDeploytasktemplate";
      $task      = new $taskClass;
      $task->getEmpty();
      $task->fields['itemtype'] = $objType;
      $task->fields['items_id'] = $this->getID();

      $testClass = "PluginReleasesTesttemplate";
      $test      = new $testClass;
      $test->getEmpty();
      $test->fields['itemtype'] = $objType;
      $test->fields['items_id'] = $this->getID();

      $canadd_risk = $risk->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
            $this->getClosedStatusArray());

      $canadd_rollback = $rollback->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
            $this->getClosedStatusArray());

      $canadd_task = $task->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
            $this->getClosedStatusArray());

      $canadd_test = $test->can(-1, CREATE, $tmp) && !in_array($this->fields["status"], $this->getClosedStatusArray());

      // javascript function for add and edit items
      $objType    = self::getType();
      $foreignKey = self::getForeignKeyField();

      echo "<script type='text/javascript' >
     
     

      function viewEditSubitem" . $this->fields['id'] . "$rand(e, itemtype, items_id, o, domid) {
               domid = (typeof domid === 'undefined')
                         ? 'viewitem" . $this->fields['id'] . $rand . "'
                         : domid;
               var target = e.target || window.event.srcElement;
               if (target.nodeName == 'a') return;
               if (target.className == 'read_more_button') return;

               var _eltsel = '[data-uid='+domid+']';
               var _elt = $(_eltsel);
               _elt.addClass('edited');
               $(_eltsel + ' .displayed_content').hide();
               $(_eltsel + ' .cancel_edit_item_content').show()
                                                        .click(function() {
                                                            $(this).hide();
                                                            _elt.removeClass('edited');
                                                            $(_eltsel + ' .edit_item_content').empty().hide();
                                                            $(_eltsel + ' .displayed_content').show();
                                                        });
               $(_eltsel + ' .edit_item_content').show()
                                                 .load('" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/timeline.php',
                                                       {'action'    : 'viewsubitem',
                                                        'type'      : itemtype,
                                                        'parenttype': '$objType',
                                                        '$foreignKey': " . $this->fields['id'] . ",
                                                        'id'        : items_id
                                                       });
      };
      </script>";

      if (!$canadd_risk && !$canadd_rollback && !$canadd_task && !$canadd_test && !$this->canReopen()) {
         return false;
      }

      echo "<script type='text/javascript' >\n
//      $(document).ready(function() {
//                $('.ajax_box').show();
//      });
      function viewAddSubitem" . $this->fields['id'] . "$rand(itemtype) {\n";

      $params = ['action'     => 'viewsubitem',
         'type'       => 'itemtype',
         'parenttype' => $objType,
         $foreignKey  => $this->fields['id'],
         'id'         => -1];
      $out    = Ajax::updateItemJsCode("viewitem" . $this->fields['id'] . "$rand",
         $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/timeline.php",
         $params, "", false);
      echo str_replace("\"itemtype\"", "itemtype", $out);
      echo "};
      ";

      echo "</script>\n";
      //show choices
      echo "<div class='timeline_form'>";
      echo "<div class='filter_timeline_release'>";
      echo "<ul class='timeline_choices'>";

      $release = new $objType();
      $release->getFromDB($this->getID());

      echo "<li class='risk'>";
      echo "<a href='#' data-type='risk' title='" . $riskClass::getTypeName(2) .
         "'><i class='fas fa-bug'></i>" . $riskClass::getTypeName(2) . " (" . $riskClass::countForItem($release) . ")</a></li>";
      if ($canadd_risk) {
         echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$riskClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }



      echo "<li class='rollback'>";
      echo "<a href='#'  data-type='rollback' title='" . $rollbackClass::getTypeName(2) .
         "'><i class='fas fa-undo-alt'></i>" . $rollbackClass::getTypeName(2) . " (" . $rollbackClass::countForItem($release) . ")</a></li>";
      if ($canadd_rollback) {
         echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$rollbackClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }





      echo "<li class='task'>";
      echo "<a href='#'   data-type='task' title='" . _n('Deploy task', 'Deploy tasks', 2, 'releases') .
         "'><i class='fas fa-check-square'></i>" . _n('Deploy task', 'Deploy tasks', 2, 'releases') . " (" . $taskClass::countForItem($release) . ")</a></li>";
      if ($canadd_task) {
         echo "<i class='fas fa-plus-circle pointer'  onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$taskClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }



      echo "<li class='test'>";
      echo "<a href='#'  data-type='test' title='" . $testClass::getTypeName(2) .
         "'><i class='fas fa-check'></i>" . $testClass::getTypeName(2) . " (" . $testClass::countForItem($release) . ")</a></li>";
      if ($canadd_test) {
         echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$testClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }



      echo "</ul>"; // timeline_choices
      echo "</div>";

      echo "<div class='clear'>&nbsp;</div>";

      echo "</div>"; //end timeline_form

      echo "<div class='ajax_box' id='viewitem" . $this->fields['id'] . "$rand'></div>\n";
   }

   static function isAllowedStatus($old,$new){
      if($old != self::CLOSED && $old != self::REVIEW){
         return true;
      }
      return false;
   }

   /**
    * is the current user could reopen the current change
    *
    * @since 9.4.0
    *
    * @return boolean
    */
   function canReopen() {
      return Session::haveRight('plugin_releases_releases', CREATE)
         && in_array($this->fields["status"], $this->getClosedStatusArray());
   }

   /**
    * Get the ITIL object closed status list
    *
    * @since 0.83
    *
    * @return array
    **/
   static function getClosedStatusArray() {


      $tab = [PluginReleasesRelease::CLOSED,PluginReleasesRelease::REVIEW];
      return $tab;
   }

   /**
    * Get the ITIL object closed, solved or waiting status list
    *
    * @since 9.4.0
    *
    * @return array
    */
   static function getReopenableStatusArray() {
      return [PluginReleasesRelease::CLOSED, PluginReleasesRelease::WAITING];
   }

   static function countForItem($ID, $class, $state = 0) {
      $dbu   = new DbUtils();
      $table = CommonDBTM::getTable($class);
      if ($state) {
         return $dbu->countElementsInTable($table,
            ["plugin_releases_releasetemplates_id" => $ID, "state" => 2]);
      }
      return $dbu->countElementsInTable($table,
         ["plugin_releases_releasetemplates_id" => $ID]);
   }

   /**
    * @since 9.4.0
    *
    * @param CommonDBTM $item The item whose form should be shown
    * @param integer $id ID of the item
    * @param mixed[] $params Array of extra parameters
    *
    * @return void
    */
   static function showSubForm(CommonDBTM $item, $id, $params) {

      if ($item instanceof Document_Item) {
         Document_Item::showAddFormForItem($params['parent'], '');

      } else if (method_exists($item, "showForm")
         && $item->can(-1, CREATE, $params)) {
         $item->showForm($id, $params);
      }
   }

   /**
    * Displays the timeline filter buttons
    *
    * @return void
    * @since 9.4.0
    *
    */
   function filterTimeline() {

      echo "<div class='filter_timeline'>";
      echo "<h3>" . __("Timeline filter") . " : </h3>";
      echo "<ul>";

      $riskClass = "PluginReleasesRisk";
      echo "<li><a href='#' class='filterEle fas fa-bug pointer' data-type='risk' title='" . $riskClass::getTypeName(2) .
         "'><span class='sr-only'>" . $riskClass::getTypeName(2) . "</span></a></li>";
      $rollbackClass = "PluginReleasesRollback";
      echo "<li><a href='#' class='filterEle fas fa-undo-alt pointer' data-type='rollback' title='" . $rollbackClass::getTypeName(2) .
         "'><span class='sr-only'>" . $rollbackClass::getTypeName(2) . "</span></a></li>";
      $taskClass = "PluginReleasesDeploytask";
      echo "<li><a href='#' class=' filterEle fas fa-check-square pointer' data-type='task' title='" . _n('Deploy task', 'Deploy tasks', 2, 'releases') .
         "'><span class='sr-only'>" . _n('Deploy task', 'Deploy tasks', 2, 'releases') . "</span></a></li>";
      $testClass = "PluginReleasesTest";
      echo "<li><a href='#' class=' filterEle fas fa-check pointer' data-type='test' title='" . $testClass::getTypeName(2) .
         "'><span class='sr-only'>" . $testClass::getTypeName(2) . "</span></a></li>";
      echo "<li><a href='#' class=' filterEle fas fa-comment pointer' data-type='ITILFollowup' title='" . __('Followup') .
         "'><span class='sr-only'>" . __('Followup') . "</span></a></li>";
      echo "<li><a href='#' class=' filterEle fa fa-ban pointer' data-type='reset' title=\"" . __s("Reset display options") .
         "\"><span class='sr-only'>" . __('Reset display options') . "</span></a></li>";
      echo "</ul>";
      echo "</div>";

      echo "<script type='text/javascript'>$(function() {filter_timeline();});</script>";
      echo "<script type='text/javascript'>$(function() {filter_timeline_release();});</script>";
   }

   /**
    * Displays the timeline of items for this ITILObject
    *
    * @param integer $rand random value used by div
    *
    * @return void
    * @since 9.4.0
    *
    */
   function showTimeLine($rand) {
      global $CFG_GLPI, $autolink_options;

      $user     = new User();
      $pics_url = $CFG_GLPI['root_doc'] . "/pics/timeline";
      $timeline = $this->getTimelineItems();

      $autolink_options['strip_protocols'] = false;

      $objType    = static::getType();
      $foreignKey = static::getForeignKeyField();

      //display timeline
      echo "<div class='timeline_history'>";

      static::showTimelineHeader();

      $timeline_index = 0;

      foreach ($timeline as $item) {

         if ($obj = getItemForItemtype($item['type'])) {
            $obj->fields = $item['item'];
         } else {
            $obj = $item;
         }

         if (is_array($obj)) {
            $item_i = $obj['item'];
         } else {
            $item_i = $obj->fields;
         }

         $date = "";
         if (isset($item_i['date'])) {
            $date = $item_i['date'];
         } else if (isset($item_i['date_mod'])) {
            $date = $item_i['date_mod'];
         }

         // set item position depending on field timeline_position
         if($item['itiltype'] == "Followup"){
            $user_position = 'right';
         }else{
            $user_position = 'left'; // default position
         }



         echo "<div class='h_item $user_position'>";

         echo "<div class='h_info'>";

         echo "<div class='h_date'><i class='far fa-clock'></i>" . Html::convDateTime($date) . "</div>";
         if ($item_i['users_id'] !== false) {
            echo "<div class='h_user'>";
            if (isset($item_i['users_id']) && ($item_i['users_id'] != 0)) {
               $user->getFromDB($item_i['users_id']);

               echo "<div class='tooltip_picture_border'>";
               echo "<img class='user_picture' alt=\"" . __s('Picture') . "\" src='" .
                  User::getThumbnailURLForPicture($user->fields['picture']) . "'>";
               echo "</div>";

               echo "<span class='h_user_name'>";
               $userdata = getUserName($item_i['users_id'], 2);
               echo $user->getLink() . "&nbsp;";
               echo Html::showToolTip(
                  $userdata["comment"],
                  ['link' => $userdata['link']]
               );
               echo "</span>";
            } else {
               echo __("Requester");
            }
            echo "</div>"; // h_user
         }

         echo "</div>"; //h_info

         $domid     = "viewitem{$item['type']}{$item_i['id']}";
         $randdomid = $domid . $rand;
         $domid     = Toolbox::slugify($domid);

         $fa    = null;
         $class = "h_content";
         if($item['itiltype'] == "Followup"){
            if (isset($item['itiltype'])) {
               $class .= " ITIL{$item['itiltype']}";
            } else {
               $class .= " {$item['type']}";
            }
         }else{
            $class .= " {$item['type']::getCssClass()}";
         }



         //         $class .= " {$item_i['state']}";


         echo "<div class='$class' id='$domid' data-uid='$randdomid'>";
         if ($fa !== null) {
            echo "<i class='solimg fa fa-$fa fa-5x'></i>";
         }
         if (isset($item_i['can_edit']) && $item_i['can_edit']) {
            echo "<div class='edit_item_content'></div>";
            echo "<span class='cancel_edit_item_content'></span>";
         }
         echo "<div class='displayed_content'>";
         echo "<div class='h_controls'>";
         if ($item_i['can_edit']
            && !in_array($this->fields['status'], $this->getClosedStatusArray())
         ) {
            // merge/split icon

            // edit item
            echo "<span class='far fa-edit control_item' title='" . __('Edit') . "'";
            echo "onclick='javascript:viewEditSubitem" . $this->fields['id'] . "$rand(event, \"" . $item['type'] . "\", " . $item_i['id'] . ", this, \"$randdomid\")'";
            echo "></span>";
         }

         echo "</div>";
         if (isset($item_i['content'])) {
            if (isset($item_i["name"])){
               $content = "<h2>" . $item_i['name'] . "  </h2>" . $item_i['content'];
            }else{
               $content =  $item_i['content'];
            }

            $content = Toolbox::getHtmlToDisplay($content);
            $content = autolink($content, false);

            $long_text = "";
            if ((substr_count($content, "<br") > 30) || (strlen($content) > 2000)) {
               $long_text = "long_text";
            }

            echo "<div class='item_content $long_text'>";

            echo "<div class='rich_text_container'>";
            $richtext = Html::setRichTextContent('', $content, '', true);
            $richtext = Html::replaceImagesByGallery($richtext);
            echo $richtext;
            echo "</div>";

            if (!empty($long_text)) {
               echo "<p class='read_more'>";
               echo "<a class='read_more_button'>.....</a>";
               echo "</p>";
            }
            echo "</div>";
         }

         echo "<div class='b_right'>";

         if (isset($item_i['plugin_releases_typedeploytasks_id'])
            && !empty($item_i['plugin_releases_typedeploytasks_id'])) {
            echo Dropdown::getDropdownName("glpi_plugin_releases_typedeploytasks", $item_i['plugin_releases_typedeploytasks_id']) . "<br>";
         }
         if (isset($item_i['plugin_releases_typerisks_id'])
            && !empty($item_i['plugin_releases_typerisks_id'])) {
            echo Dropdown::getDropdownName("glpi_plugin_releases_typerisks", $item_i['plugin_releases_typerisks_id']) . "<br>";
         }
         if (isset($item_i['plugin_releases_typetests_id'])
            && !empty($item_i['plugin_releases_typetests_id'])) {
            echo Dropdown::getDropdownName("glpi_plugin_releases_typetests", $item_i['plugin_releases_typetests_id']) . "<br>";
         }
         if (isset($item_i['plugin_releases_risks_id'])
            && !empty($item_i['plugin_releases_risks_id'])) {
            echo __("Associated with") . " ";
            echo Dropdown::getDropdownName("glpi_plugin_releases_risks", $item_i['plugin_releases_risks_id']) . "<br>";
         }

         if (isset($item_i['actiontime'])
            && !empty($item_i['actiontime'])) {
            echo "<span class='actiontime'>";
            echo Html::timestampToString($item_i['actiontime'], false);
            echo "</span>";
         }
         if (isset($item_i['begin'])) {
            echo "<span class='planification'>";
            echo Html::convDateTime($item_i["begin"]);
            echo " &rArr; ";
            echo Html::convDateTime($item_i["end"]);
            echo "</span>";
         }

         if (isset($item_i['users_id_editor'])
            && $item_i['users_id_editor'] > 0) {
            echo "<div class='users_id_editor' id='users_id_editor_" . $item_i['users_id_editor'] . "'>";
            $user->getFromDB($item_i['users_id_editor']);
            $userdata = getUserName($item_i['users_id_editor'], 2);
            if (isset($item_i['date_mod']))
               echo sprintf(
                  __('Last edited on %1$s by %2$s'),
                  Html::convDateTime($item_i['date_mod']),
                  $user->getLink()
               );
            echo Html::showToolTip($userdata["comment"],
               ['link' => $userdata['link']]);
            echo "</div>";
         }

         echo "</div>"; // b_right

         echo "</div>"; // displayed_content
         echo "</div>"; //end h_content

         echo "</div>"; //end  h_info

         $timeline_index++;
      }
      echo Html::scriptBlock("$(document).ready(function (){
                                        $('.filter_timeline_release li a').removeClass('h_active');
                                        $('.h_item').removeClass('h_hidden');
                                       $('.h_item').addClass('h_hidden');
                                      $(\"a[data-type='risk']\").addClass('h_active');
                                       $('.ajax_box').empty();
                                       //activate clicked element
                                       //find active classname
                                       $(\"a[data-type='risk'].filterEle\").addClass('h_active');
                                       $(\".h_content.risk\").parent().removeClass('h_hidden');

                                    });");
      // end timeline
      echo "</div>"; // h_item $user_position
   }
   /**
    * Displays the timeline header (filters)
    *
    * @since 9.4.0
    *
    * @return void
    */
   function showTimelineHeader() {

      echo "<h2>".__("Release actions details",'releases')." : </h2>";
      $this->filterTimeline();
   }
   function canAddFollowups() {
    return Session::haveRightsOr("plugin_releases_releases",[CREATE,UPDATE]);
   }


}
