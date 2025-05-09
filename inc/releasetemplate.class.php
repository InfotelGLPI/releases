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
   public $dohistory         = true;
   public $can_be_translated = true;
   public $userlinkclass     = 'PluginReleasesReleasetemplate_User'; //todo chnage after table create for template
   public $grouplinkclass    = 'PluginReleasesGroup_Releasetemplate';//todo chnage after table create for template
   public $supplierlinkclass = 'PluginReleasesReleasetemplate_Supplier';//todo chnage after table create for template

   static $rightname = 'plugin_releases_releases';

   /// Use user entity to select entity of the object
   protected $userentity_oncreate = false;
   protected $users               = [];
   /// Groups by type
   protected $groups = [];
   /// Suppliers by type
   protected $suppliers = [];

   static function getTypeName($nb = 0) {
      return _n('Release template', 'Release templates', $nb, 'releases');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (static::canView()) {
         switch ($item->getType()) {
            case __CLASS__ :
               $timeline    = $item->getTimelineItems();
               $nb_elements = count($timeline);
               //               $nb_elements = 0;

               $ong = [
                  1 => __("Processing release", 'releases') . " <span class='badge'>$nb_elements</span>",
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
                  if (!$withtemplate) {
                     echo "<div class='timeline_box'>";
                     $rand = mt_rand();
                     $item->showTimelineForm($rand);
                     $item->showTimeline($rand);
                     echo "</div>";
                  } else {
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
          'label' => __('Description', 'releases'),
          'type'  => 'textarea',
          'rows'  => 10],
         ['name'  => 'date_preproduction',
          'label' => __('Pre-production run date', 'releases'),
          'type'  => 'date',
         ],
         ['name'  => 'date_production',
          'label' => __('Production run date', 'releases'),
          'type'  => 'date',
         ],
         ['name'  => 'service_shutdown',
          'label' => __('Service shutdown', 'releases'),
          'type'  => 'bool',
         ],
         ['name'  => 'service_shutdown_details',
          'label' => __('Service shutdown details', 'releases'),
          'type'  => 'textarea',
          'rows'  => 10],
         ['name'  => 'hour_type',
          'label' => __('Non-working hours', 'releases'),
          'type'  => 'bool',
         ],
         ['name'  => 'tests',
          'label' => _n('Test', 'Tests', 2, 'releases'),
          'type'  => 'dropdownTests',
         ],
         ['name'  => 'rollbacks',
          'label' => _n('Rollback', 'Rollbacks', 2, 'releases'),
          'type'  => 'dropdownRollbacks',
         ],
         ['name'  => 'tasks',
          'label' => _n('Deploy task', 'Deploy tasks', 2, 'releases'),
          'type'  => 'dropdownTasks',
         ],
      ];
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'       => '4',
         'name'     => __('Content'),
         'field'    => 'content',
         'table'    => $this->getTable(),
         'datatype' => 'text',
         'htmltext' => true
      ];

      $tab[] = [
         'id'       => '3',
         'name'     => __('Deploy Task type','releases'),
         'field'    => 'name',
         'table'    => getTableForItemType('PluginReleasesTypeDeployTask'),
         'datatype' => 'dropdown'
      ];

      return $tab;
   }


    public static function getItemsTable()
    {
        return 'glpi_plugin_releases_releases_items';
    }

   /**
    * @see CommonDropdown::displaySpecificTypeField()
    **/
   function displaySpecificTypeField($ID, $field = [], array $options = []) {
      $dbu = new DbUtils();

      switch ($field['type']) {
         case 'dropdownRollbacks' :
            $item      = new PluginReleasesRollbacktemplate();
            $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
            $rolltemp  = new PluginReleasesRollbacktemplate();
            $alltemps  = $rolltemp->find($condition);
            $rolls     = [];
            foreach ($alltemps as $roll) {
               $rolls[$roll["id"]] = $roll["name"];
            }

            $val = $this->getField("rollbacks");
            $val = json_decode($val);
            if ($val == "") {
               $val = [];
            }
            Dropdown::showFromArray("rollbacks", $rolls, array('id' => 'rollbacks', 'multiple' => true, 'values' => $val, "display" => true));

            break;
         case 'dropdownTests' :
            $item      = new PluginReleasesTesttemplate();
            $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
            $testtemp  = new PluginReleasesTesttemplate();
            $alltemps  = $testtemp->find($condition);
            $tests     = [];
            foreach ($alltemps as $test) {
               $tests[$test["id"]] = $test["name"];
            }

            $val = $this->getField("tests");
            $val = json_decode($val);
            if ($val == "") {
               $val = [];
            }
            Dropdown::showFromArray("tests", $tests, array('id' => 'tests', 'multiple' => true, 'values' => $val, "display" => true));
            break;
         case 'dropdownTasks' :
            $item      = new PluginReleasesDeploytasktemplate();
            $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
            $tasktemp  = new PluginReleasesDeploytasktemplate();
            $alltemps  = $tasktemp->find($condition);
            $tasks     = [];
            foreach ($alltemps as $task) {
               $tasks[$task["id"]] = $task["name"];
            }

            $val = $this->getField("tasks");
            $val = json_decode($val);
            if ($val == "") {
               $val = [];
            }
            Dropdown::showFromArray("tasks", $tasks, array('id' => 'tasks', 'multiple' => true, 'values' => $val, "display" => true));
            break;
         case 'actiontime' :
            $toadd = [];
            for ($i = 9; $i <= 100; $i++) {
               $toadd[] = $i * HOUR_TIMESTAMP;
            }
            Dropdown::showTimeStamp(
               "actiontime", [
                              'min'             => 0,
                              'max'             => 8 * HOUR_TIMESTAMP,
                              'value'           => $this->fields["actiontime"],
                              'addfirstminutes' => true,
                              'inhours'         => true,
                              'toadd'           => $toadd
                           ]
            );
            break;
      }
   }

   static function canCreate(): bool
   {
      return Session::haveRightsOr(static::$rightname, [UPDATE, CREATE]);
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
   static function canView(): bool
   {
      return Session::haveRight(static::$rightname, READ);
   }

   function prepareInputForAdd($input) {
      $input = parent::prepareInputForAdd($input);
      //      $input = parent::prepareInputForUpdate($input);
      if ((isset($input['target']) && empty($input['target'])) || !isset($input['target'])) {
         $input['target'] = [];
      }
      $input['target'] = json_encode($input['target']);
      if (!isset($input['_auto_import'])) {
         if (!isset($input["_users_id_requester"])) {
            if ($uid = Session::getLoginUserID()) {
               $input["_users_id_requester"] = $uid;
            }
         }
      }
      if (($uid = Session::getLoginUserID())
          && !isset($input['_auto_import'])) {
         $input["users_id_recipient"] = $uid;
      } else if (isset($input["_users_id_requester"]) && $input["_users_id_requester"]
                 && !isset($input["users_id_recipient"])) {
         if (!is_array($input['_users_id_requester'])) {
            $input["users_id_recipient"] = $input["_users_id_requester"];
         }
      }
      return $input;
   }

   function prepareInputForUpdate($input) {
      $input = parent::prepareInputForUpdate($input);
      //      $input = parent::prepareInputForUpdate($input);
      if ((isset($input['target']) && empty($input['target'])) || (!isset($input['target']) && isset($input["communication_type"]) && $input["communication_type"] != $this->fields["communication_type"])) {
         $input['target'] = [];
      }
      if (isset($input["communication_type"])) {
         if (isset($input['target'])) {
            $input['target'] = json_encode($input['target']);
         } else {
            $input['target'] = json_encode([]);
         }

      }

      $release_user     = new PluginReleasesReleasetemplate_User();
      $release_supplier = new PluginReleasesReleasetemplate_Supplier();
      $group_release    = new PluginReleasesGroup_Releasetemplate();

      $release_user->deleteByCriteria(["plugin_releases_releasetemplates_id" => $this->getID()]);
      $release_supplier->deleteByCriteria(["plugin_releases_releasetemplates_id" => $this->getID()]);
      $group_release->deleteByCriteria(["plugin_releases_releasetemplates_id" => $this->getID()]);
      $useractors = null;
      // Add user groups linked to ITIL objects
      if (!empty($this->userlinkclass)) {
         $useractors = new $this->userlinkclass();
      }
      $groupactors = null;
      if (!empty($this->grouplinkclass)) {
         $groupactors = new $this->grouplinkclass();
      }
      $supplieractors = null;
      if (!empty($this->supplierlinkclass)) {
         $supplieractors = new $this->supplierlinkclass();
      }

      // "do not compute" flag set by business rules for "takeintoaccount_delay_stat" field
      $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($this->input);

      if (!is_null($useractors)) {
         $user_input = [
            $useractors->getItilObjectForeignKey() => $this->fields['id'],
            '_do_not_compute_takeintoaccount'      => $do_not_compute_takeintoaccount,
            '_from_object'                         => true,
         ];

         if (isset($this->input["_users_id_requester"])) {

            if (is_array($this->input["_users_id_requester"])) {
               $tab_requester = $this->input["_users_id_requester"];
            } else {
               $tab_requester   = [];
               $tab_requester[] = $this->input["_users_id_requester"];
            }

            $requesterToAdd = [];
            foreach ($tab_requester as $key_requester => $requester) {
               if (in_array($requester, $requesterToAdd)) {
                  // This requester ID is already added;
                  continue;
               }

               $input2 = [
                            'users_id' => $requester,
                            'type'     => CommonITILActor::REQUESTER,
                         ] + $user_input;

               if (isset($this->input["_users_id_requester_notif"])) {
                  foreach ($this->input["_users_id_requester_notif"] as $key => $val) {
                     if (isset($val[$key_requester])) {
                        $input2[$key] = $val[$key_requester];
                     }
                  }
               }

               //empty actor
               if ($input2['users_id'] == 0
                   && (!isset($input2['alternative_email'])
                       || empty($input2['alternative_email']))) {
                  continue;
               } else if ($requester != 0) {
                  $requesterToAdd[] = $requester;
               }

               $useractors->add($input2);
            }
         }

         if (isset($this->input["_users_id_observer"])) {

            if (is_array($this->input["_users_id_observer"])) {
               $tab_observer = $this->input["_users_id_observer"];
            } else {
               $tab_observer   = [];
               $tab_observer[] = $this->input["_users_id_observer"];
            }

            $observerToAdd = [];
            foreach ($tab_observer as $key_observer => $observer) {
               if (in_array($observer, $observerToAdd)) {
                  // This observer ID is already added;
                  continue;
               }

               $input2 = [
                            'users_id' => $observer,
                            'type'     => CommonITILActor::OBSERVER,
                         ] + $user_input;

               if (isset($this->input["_users_id_observer_notif"])) {
                  foreach ($this->input["_users_id_observer_notif"] as $key => $val) {
                     if (isset($val[$key_observer])) {
                        $input2[$key] = $val[$key_observer];
                     }
                  }
               }

               //empty actor
               if ($input2['users_id'] == 0
                   && (!isset($input2['alternative_email'])
                       || empty($input2['alternative_email']))) {
                  continue;
               } else if ($observer != 0) {
                  $observerToAdd[] = $observer;
               }

               $useractors->add($input2);
            }
         }

         if (isset($this->input["_users_id_assign"])) {

            if (is_array($this->input["_users_id_assign"])) {
               $tab_assign = $this->input["_users_id_assign"];
            } else {
               $tab_assign   = [];
               $tab_assign[] = $this->input["_users_id_assign"];
            }

            $assignToAdd = [];
            foreach ($tab_assign as $key_assign => $assign) {
               if (in_array($assign, $assignToAdd)) {
                  // This assigned user ID is already added;
                  continue;
               }

               $input2 = [
                            'users_id' => $assign,
                            'type'     => CommonITILActor::ASSIGN,
                         ] + $user_input;

               if (isset($this->input["_users_id_assign_notif"])) {
                  foreach ($this->input["_users_id_assign_notif"] as $key => $val) {
                     if (isset($val[$key_assign])) {
                        $input2[$key] = $val[$key_assign];
                     }
                  }
               }

               //empty actor
               if ($input2['users_id'] == 0
                   && (!isset($input2['alternative_email'])
                       || empty($input2['alternative_email']))) {
                  continue;
               } else if ($assign != 0) {
                  $assignToAdd[] = $assign;
               }

               $useractors->add($input2);
            }
         }
      }

      if (!is_null($groupactors)) {
         $group_input = [
            $groupactors->getItilObjectForeignKey() => $this->fields['id'],
            '_do_not_compute_takeintoaccount'       => $do_not_compute_takeintoaccount,
            '_from_object'                          => true,
         ];

         if (isset($this->input["_groups_id_requester"])) {
            $groups_id_requester = $this->input["_groups_id_requester"];
            if (!is_array($this->input["_groups_id_requester"])) {
               $groups_id_requester = [$this->input["_groups_id_requester"]];
            } else {
               $groups_id_requester = $this->input["_groups_id_requester"];
            }
            foreach ($groups_id_requester as $groups_id) {
               if ($groups_id > 0) {
                  $groupactors->add(
                     [
                        'groups_id' => $groups_id,
                        'type'      => CommonITILActor::REQUESTER,
                     ] + $group_input
                  );
               }
            }
         }

         if (isset($this->input["_groups_id_assign"])) {
            if (!is_array($this->input["_groups_id_assign"])) {
               $groups_id_assign = [$this->input["_groups_id_assign"]];
            } else {
               $groups_id_assign = $this->input["_groups_id_assign"];
            }
            foreach ($groups_id_assign as $groups_id) {
               if ($groups_id > 0) {
                  $groupactors->add(
                     [
                        'groups_id' => $groups_id,
                        'type'      => CommonITILActor::ASSIGN,
                     ] + $group_input
                  );
               }
            }
         }

         if (isset($this->input["_groups_id_observer"])) {
            if (!is_array($this->input["_groups_id_observer"])) {
               $groups_id_observer = [$this->input["_groups_id_observer"]];
            } else {
               $groups_id_observer = $this->input["_groups_id_observer"];
            }
            foreach ($groups_id_observer as $groups_id) {
               if ($groups_id > 0) {
                  $groupactors->add(
                     [
                        'groups_id' => $groups_id,
                        'type'      => CommonITILActor::OBSERVER,
                     ] + $group_input
                  );
               }
            }
         }
      }

      if (!is_null($supplieractors)) {
         $supplier_input = [
            $supplieractors->getItilObjectForeignKey() => $this->fields['id'],
            '_do_not_compute_takeintoaccount'          => $do_not_compute_takeintoaccount,
            '_from_object'                             => true,
         ];

         if (isset($this->input["_suppliers_id_assign"])
             && ($this->input["_suppliers_id_assign"] > 0)) {

            if (is_array($this->input["_suppliers_id_assign"])) {
               $tab_assign = $this->input["_suppliers_id_assign"];
            } else {
               $tab_assign   = [];
               $tab_assign[] = $this->input["_suppliers_id_assign"];
            }

            $supplierToAdd = [];
            foreach ($tab_assign as $key_assign => $assign) {
               if (in_array($assign, $supplierToAdd)) {
                  // This assigned supplier ID is already added;
                  continue;
               }
               $input3 = [
                            'suppliers_id' => $assign,
                            'type'         => CommonITILActor::ASSIGN,
                         ] + $supplier_input;

               if (isset($this->input["_suppliers_id_assign_notif"])) {
                  foreach ($this->input["_suppliers_id_assign_notif"] as $key => $val) {
                     $input3[$key] = $val[$key_assign];
                  }
               }

               //empty supplier
               if ($input3['suppliers_id'] == 0
                   && (!isset($input3['alternative_email'])
                       || empty($input3['alternative_email']))) {
                  continue;
               } else if ($assign != 0) {
                  $supplierToAdd[] = $assign;
               }

               $supplieractors->add($input3);
            }
         }
      }

      // Additional actors
      $this->addAdditionalActors($this->input);
      return $input;
   }

   function showFormOld($ID, $options = []) {
      global $CFG_GLPI, $DB;
      $this->initForm($ID, $options);
      $this->showFormHeader($options);


      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input("name", ["value" => $this->getField('name')]);

      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td >";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Release area', 'releases') . "</td>";
      echo "<td colspan='3'>";
      Html::textarea(["name"            => "content",
                      "enable_richtext" => true,
                      "value"           => $this->getField('content')]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Pre-production planned date', 'releases') . "</td>";
      echo "<td >";
      $date_preprod = Html::convDateTime($this->getField('date_preproduction'));
      Html::showDateTimeField("date_preproduction", ["value" => $date_preprod]);
      echo "</td>";
      echo "<td>" . __('Production planned date', 'releases') . "</td>";
      echo "<td >";
      $date_prod = Html::convDateTime($this->getField('date_production'));
      Html::showDateTimeField("date_production", ["value" => $date_prod]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Location') . "</td>";
      echo "<td >";
      Dropdown::show(Location::getType(), ["name" => "locations_id", "value" => $this->getField('locations_id')]);
      echo "</td>";
      echo "<td>" . __('Service shutdown', 'releases') . "</td>";
      echo "<td >";
      Dropdown::showYesNo("service_shutdown", $this->getField('service_shutdown'));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Service shutdown details', 'releases') . "</td>";
      echo "<td colspan='3'>";
      Html::textarea(["name" => "service_shutdown_details", "enable_richtext" => true, "value" => $this->getField('service_shutdown_details')]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Non-working hours', 'releases') . "</td>";
      echo "<td >";
      Dropdown::showYesNo("hour_type", $this->getField('hour_type'));
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
      echo _n('Test', 'Tests', 2, 'releases');


      echo "</td>";

      echo "<td>";
      $item      = new PluginReleasesRollbacktemplate();
      $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
      $rolltemp  = new PluginReleasesRollbacktemplate();
      $alltemps  = $rolltemp->find($condition);
      $rolls     = [];
      foreach ($alltemps as $roll) {
         $rolls[$roll["id"]] = $roll["name"];
      }

      $val = $this->getField("rollbacks");
      $val = json_decode($val);
      if ($val == "") {
         $val = [];
      }
      Dropdown::showFromArray("rollbacks", $rolls, array('id' => 'rollbacks', 'multiple' => true, 'values' => $val, "display" => true));

      echo "</td>";
      echo "<td>";
      echo _n('Rollback', 'Rollbacks', 2, 'releases');


      echo "</td>";
      echo "<td>";

      $item      = new PluginReleasesTesttemplate();
      $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
      $testtemp  = new PluginReleasesTesttemplate();
      $alltemps  = $testtemp->find($condition);
      $tests     = [];
      foreach ($alltemps as $test) {
         $tests[$test["id"]] = $test["name"];
      }

      $val = $this->getField("tests");
      $val = json_decode($val);
      if ($val == "") {
         $val = [];
      }
      Dropdown::showFromArray("tests", $tests, array('id' => 'tests', 'multiple' => true, 'values' => $val, "display" => true));
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo _n('Deploy task', 'Deploy tasks', 2, 'releases');
      echo "</td>";
      echo "<td>";
      $item      = new PluginReleasesDeploytasktemplate();
      $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
      $tasktemp  = new PluginReleasesDeploytasktemplate();
      $alltemps  = $tasktemp->find($condition);
      $tasks     = [];
      foreach ($alltemps as $task) {
         $tasks[$task["id"]] = $task["name"];
      }

      $val = $this->getField("tasks");
      $val = json_decode($val);
      if ($val == "") {
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

   function showForm($ID, $options = []) {
      global $CFG_GLPI, $DB;

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $this->check(-1, CREATE, $options);
      }

      if (!$this->isNewItem()) {
         $options['formtitle'] = sprintf(
            __('%1$s - ID %2$d'),
            $this->getTypeName(1),
            $ID
         );
         //set ID as already defined
         $options['noid'] = true;
      }


      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      $default_values = self::getDefaultValues();

      // Restore saved value or override with page parameter
      $saved                  = $this->restoreInput();
      $options['entities_id'] = Session::getActiveEntity();
      foreach ($default_values as $name => $value) {
         if (!isset($this->fields[$name])) {
            if (isset($saved[$name])) {
               $this->fields[$name] = $saved[$name];
               $options[$name]      = $saved[$name];
            } else {
               $this->fields[$name] = $value;
               $options[$name]      = $value;
            }
         }
      }


      // In percent
      $colsize1 = '13';
      $colsize2 = '37';


      echo "<tr class='tab_bg_1'>";
      echo "<th>" . __('Location') . "</th>";
      echo "<td >";
      Dropdown::show(Location::getType(), ["name"  => "locations_id",
                                           "value" => $this->fields["locations_id"]]);
      echo "</td>";
      echo "<th>" . __('Non-working hours', 'releases') . "</th>";
      echo "<td >";
      Dropdown::showYesNo("hour_type", $this->fields["hour_type"]);
      echo "</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      echo "<th style='width:$colsize1%'>" . __('Title') . "</th>";
      echo "<td colspan='3'>";
      $opt = [
         'value'     => $this->fields['name'],
         'maxlength' => 250,
         'style'     => 'width:98%',
      ];
      echo Html::input("name", $opt);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>" . __('Release area', 'releases') . "</th>";
      echo "<td colspan='3'>";
      Html::textarea(["name"            => "content",
                      "enable_richtext" => true,
                      "value"           => $this->fields["content"]]);
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      $this->showActorsPartForm($ID, $options);
      echo "<table class='tab_cadre_fixe' id='mainformtable3'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>" . __('Service shutdown', 'releases') . "</th>";
      echo "<td width='$colsize1%'>";
      $rand = mt_rand();
      Dropdown::showYesNo("service_shutdown", $this->fields["service_shutdown"], -1, ["rand" => $rand]);
      echo "</td>";
      echo "<td colspan='2' name='fakeupdate' id='fakeupdate'></td>";
      echo "</tr>";

      $hidden = "";
      if ($this->fields["service_shutdown"] == 0) {
         $hidden = "hidden='true'";
      }

      echo "<tr id='shutdowndetails' class='tab_bg_1' $hidden >";
      Ajax::updateItemOnSelectEvent("dropdown_service_shutdown$rand", "fakeupdate",
                                    PLUGIN_RELEASES_WEBDIR . "/ajax/showShutdownDetails.php", ["value" => '__VALUE__']);

      echo "<th>" . __('Service shutdown details', 'releases') . "</th>";
      echo "<td colspan='3'>";
      Html::textarea(["name"            => "service_shutdown_details",
                      "enable_richtext" => true,
                      "value"           => $this->fields["service_shutdown_details"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>" . __('Communication', 'releases') . "</th>";
      echo "<td width='$colsize2%'>";
      Dropdown::showYesNo("communication", $this->fields["communication"]);
      echo "</td>";

      echo "<th width='$colsize1%'>" . __('Communication type', 'releases') . "</th>";
      echo "<td>";
      $types   = ['Entity'   => 'Entity',
                  'Group'    => 'Group',
                  'Profile'  => 'Profile',
                  'User'     => 'User',
                  'Location' => 'Location'];
      $addrand = Dropdown::showItemTypes('communication_type', $types, ["id" => "communication_type", "value" => $this->fields["communication_type"]]);
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      $targets = json_decode($this->fields["target"]);

      echo "<th>" . _n('Target', 'Targets',
                       Session::getPluralNumber()) . "</th>";

      echo "<td id='targets'>";

      echo "</td>";
      Ajax::updateItem("targets",
                       PLUGIN_RELEASES_WEBDIR . "/ajax/changeTarget.php",
                       ['type'         => $this->fields["communication_type"],
                        'current_type' => $this->fields["communication_type"],
                        'values'       => $targets],
                       true);
      Ajax::updateItemOnSelectEvent("dropdown_communication_type" . $addrand, "targets",
                                    PLUGIN_RELEASES_WEBDIR . "/ajax/changeTarget.php",
                                    ['type'         => '__VALUE__',
                                     'current_type' => $this->fields["communication_type"],
                                     'values'       => $targets],
                                    true);
      echo "</td>";

      echo "<th></th>";
      echo "<td></td>";

      echo "</tr>";


      $this->showFormButtons($options);

      return true;
   }

   function displayMenu($ID, $options = []) {
      echo "<div class='center'>";
      echo "<table class='tab_cadre'>";
      echo "<tr  class='tab_bg_1'>";
      echo "<th>" . __("Release", "releases") . "</th>";
      echo "</tr>";

      echo "<tr  class='tab_bg_1'>";
      echo "<td class='center b' >";
      $dbu       = new DbUtils();
      $condition = $dbu->getEntitiesRestrictCriteria($this->getTable(), '', '', true);
      $template  = new PluginReleasesReleasetemplate();
      $templates = $template->find($condition);
      if (count($templates) != 0) {
         self::dropdown(["name" => "releasetemplates_id"] + $condition);
         echo "<br/><br/>";
      }
      $url = PluginReleasesRelease::getFormURL();
      echo "<a  id='link' href='$url'>";
      $url    = $url . "?template_id=";
      $script = "
      var link = function (id,linkurl) {
         var link = linkurl+id;
         $(\"a#link\").attr(\"href\", link);
      };
      $(\"select[name='releasetemplates_id']\").change(function() {
         link($(\"select[name='releasetemplates_id']\").val(),'$url');
         });";

      echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');

      echo __("Create a release", 'releases');
      echo "</a>";
      echo "</td>";
      echo "</tr>";
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
            $timeline[$rollback['date_mod'] . "_rollback_" . $rollbacks_id] = ['type'     => $rollbackClass,
                                                                           'item'     => $rollback,
                                                                           'itiltype' => 'Rollback'];
         }
      }

      if ($task_obj->canview()) {
         //         $tasks = $task_obj->find([$foreignKey => $this->getID()] + $restrict_task);
         $tasks = $task_obj->find([$foreignKey => $this->getID()] + $restrict_task, ['level ASC']);
         foreach ($tasks as $tasks_id => $task) {
            $task_obj->getFromDB($tasks_id);
            $task['can_edit']                                                      = $task_obj->canUpdateItem();
            $rand                                                                  = mt_rand();
            $timeline["task" . $task_obj->getField('level') . "$tasks_id" . $rand] = ['type'     => $taskClass,
                                                                                      'item'     => $task,
                                                                                      'itiltype' => 'Deploytask'];
         }
      }

      if ($test_obj->canview()) {
         $tests = $test_obj->find([$foreignKey => $this->getID()] + $restrict_test, ['date_mod DESC', 'id DESC']);
         foreach ($tests as $tests_id => $test) {
            $test_obj->getFromDB($tests_id);
            $test['can_edit']                                   = $test_obj->canUpdateItem();
            $timeline[$test['date_mod'] . "_test_" . $tests_id] = ['type'     => $testClass,
                                                                   'item'     => $test,
                                                                   'itiltype' => 'Test'];
         }
      }

      //reverse sort timeline items by key (date)
//      ksort($timeline);

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
                                                 .load('" . PLUGIN_RELEASES_WEBDIR . "/ajax/timeline.php',
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
                                       PLUGIN_RELEASES_WEBDIR . "/ajax/timeline.php",
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

      echo "<li class='PluginReleasesRisk'>";
      echo "<a href='#' data-type='risk' title='" . $riskClass::getTypeName(2) .
           "'><i class='ti ti-bug'></i>&nbsp;" . $riskClass::getTypeName(2) . " (" . $riskClass::countForItem($release) . ")</a></li>";
      if ($canadd_risk) {
         echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$riskClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }


      echo "<li class='PluginReleasesRollback'>";
      echo "<a href='#' data-type='rollback' title='" . $rollbackClass::getTypeName(2) .
           "'><i class='ti ti-arrow-back-up'></i>&nbsp;" . $rollbackClass::getTypeName(2) . " (" . $rollbackClass::countForItem($release) . ")</a></li>";
      if ($canadd_rollback) {
         echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$rollbackClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }


      echo "<li class='PluginReleasesDeploytask'>";
      echo "<a href='#' data-type='task' title='" . $taskClass::getTypeName(2) .
           "'><i class='ti ti-checkbox'></i>&nbsp;" . $taskClass::getTypeName(2) . " (" . $taskClass::countForItem($release) . ")</a></li>";
      if ($canadd_task) {
         echo "<i class='fas fa-plus-circle pointer'  onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$taskClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }


      echo "<li class='PluginReleasesTest'>";
      echo "<a href='#' data-type='Test' title='" . $testClass::getTypeName(2) .
           "'><i class='ti ti-check'></i>&nbsp;" . $testClass::getTypeName(2) . " (" . $testClass::countForItem($release) . ")</a></li>";
      if ($canadd_test) {
         echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$testClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }


      echo "</ul>"; // timeline_choices
      echo "</div>";

      echo "<div class='clear'>&nbsp;</div>";

      echo "</div>"; //end timeline_form

      echo "<div class='ajax_box' id='viewitem" . $this->fields['id'] . "$rand'></div>\n";
   }

   static function isAllowedStatus($old, $new) {
      if ($old != PluginReleasesRelease::CLOSED && $old != PluginReleasesRelease::REVIEW) {
         return true;
      }
      return false;
   }

   /**
    * is the current user could reopen the current change
    *
    * @return boolean
    * @since 9.4.0
    *
    */
   function canReopen() {
      return Session::haveRight('plugin_releases_releases', CREATE)
             && in_array($this->fields["status"], $this->getClosedStatusArray());
   }

   /**
    * Get the ITIL object closed status list
    *
    * @return array
    **@since 0.83
    *
    */
   static function getClosedStatusArray() {


      $tab = [PluginReleasesRelease::CLOSED, PluginReleasesRelease::REVIEW];
      return $tab;
   }

   /**
    * Get the ITIL object closed, solved or waiting status list
    *
    * @return array
    * @since 9.4.0
    *
    */
   static function getReopenableStatusArray() {
      return [PluginReleasesRelease::CLOSED];
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
    * @param CommonDBTM $item The item whose form should be shown
    * @param integer    $id ID of the item
    * @param mixed[]    $params Array of extra parameters
    *
    * @return void
    * @since 9.4.0
    *
    */
   static function showSubForm(CommonDBTM $item, $id, $params) {

      if ($item instanceof Document_Item) {
         Document_Item::showAddFormForItem($params['parent'], '');

      } else if (method_exists($item, "showForm")
                 && $item->can(-1, CREATE, $params)) {
         $item->showForm($id);
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
      echo "<div class='timeline_releasehistory'>";

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
         if ($item['itiltype'] == "Followup") {
            $user_position = 'right';
         } else {
            $user_position = 'left'; // default position
         }

         $class = "";
         if (isset($item['itiltype']) && $item['itiltype'] == "Followup") {
            $class .= " ITIL{$item['itiltype']}";
         } else {
            $class .= " PluginReleases{$item['itiltype']}";
         }

         echo "<div class='h_item $user_position $class'>";

         echo "<div class='h_info'>";

         echo "<div class='h_date'><i class='far fa-clock'></i>&nbsp;" . Html::convDateTime($date) . "</div>";
         if ($item_i['users_id'] !== false) {
            echo "<div class='h_user'>";
            if (isset($item_i['users_id']) && ($item_i['users_id'] != 0)) {
               $user->getFromDB($item_i['users_id']);

//               echo "<div class=''>";//tooltip_picture_border
//               echo "<img class='user_picture' alt=\"" . __s('Picture') . "\" src='" .
//                    User::getThumbnailURLForPicture($user->fields['picture']) . "'>";
//               echo "</div>";

               echo "<span class='h_user_name'>";
               $userdata = getUserName($item_i['users_id']);
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
//         if (isset($item['itiltype']) && $item['itiltype'] == "Followup") {
//            $class .= " ITIL{$item['itiltype']}";
//         } else {
//            $class .= " PluginReleases{$item['itiltype']}";
//         }


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
            if (isset($item_i["name"])) {
               $content = "<h2>" . $item_i['name'] . "  </h2>" . Glpi\RichText\RichText::getEnhancedHtml($item_i['content']);
            } else {
               $content = Glpi\RichText\RichText::getEnhancedHtml($item_i['content']);
            }


            $content = autolink($content, false);

            $long_text = "";
            if ((substr_count($content, "<br") > 30) || (strlen($content) > 2000)) {
               $long_text = "long_text";
            }

            echo "<div class='item_content $long_text'>";
            echo "<div class='rich_text_container'>";
            echo $content;
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
            echo __("Associated with", 'releases') . " ";
            echo Dropdown::getDropdownName("glpi_plugin_releases_risktemplates", $item_i['plugin_releases_risks_id']) . "<br>";
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
            $userdata = getUserName($item_i['users_id_editor']);
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
      if (count($timeline) == 0) {
         $display = "<br><br><div align='center'><h3 class='noinfo'>";
         $display .= __("No data available", 'releases');
         $display .= "</h3></div>";
         echo $display;
      } else {
         if (isset($_SESSION["releases"]["template"][Session::getLoginUserID()])) {
            $catToLoad = $_SESSION["releases"]["template"][Session::getLoginUserID()];
         } else {
            $catToLoad = 'risk';
         }

         unset($_SESSION["releases"]["template"][Session::getLoginUserID()]);
         echo Html::scriptBlock("$(document).ready(function (){        
                                        $('.filter_timeline_release li a').removeClass('h_active');
                                        $('.h_item').removeClass('h_hidden');
                                       $('.h_item').addClass('h_hidden');
                                      $(\"a[data-type='$catToLoad']\").addClass('h_active');
                                       $('.ajax_box').empty();
                                       //activate clicked element
                                       //find active classname
                                       $(\"a[data-type='$catToLoad'].filterEle\").addClass('h_active');
                                       $(\".h_content.$catToLoad\").parent().removeClass('h_hidden');

                                    });");
      }
      // end timeline
      echo "</div>"; // h_item $user_position
   }

   /**
    * Displays the timeline header (filters)
    *
    * @return void
    * @since 9.4.0
    *
    */
   function showTimelineHeader() {

      echo "<h2>" . __("Release actions details", 'releases') . " : </h2>";
//      $this->filterTimeline();
   }

   function canAddFollowups() {
      return Session::haveRightsOr("plugin_releases_releases", [CREATE, UPDATE]);
   }

   static function getDefaultValues($entity = 0) {
      global $CFG_GLPI;

      $users_id_requester = 0;
      $users_id_assign    = 0;
      $requesttype        = $CFG_GLPI['default_requesttypes_id'];

      $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $entity, '', 1);
      // Set default values...
      return ['_users_id_requester'        => $users_id_requester,
              '_users_id_requester_notif'  => ['use_notification'  => [$default_use_notif],
                                               'alternative_email' => ['']],
              '_groups_id_requester'       => 0,
              '_users_id_assign'           => $users_id_assign,
              '_users_id_assign_notif'     => ['use_notification'  => [$default_use_notif],
                                               'alternative_email' => ['']],
              '_groups_id_assign'          => 0,
              '_users_id_observer'         => 0,
              '_users_id_observer_notif'   => ['use_notification'  => [$default_use_notif],
                                               'alternative_email' => ['']],
              '_groups_id_observer'        => 0,
              '_link'                      => ['tickets_id_2' => '',
                                               'link'         => ''],
              '_suppliers_id_assign'       => 0,
              '_suppliers_id_assign_notif' => ['use_notification'  => [$default_use_notif],
                                               'alternative_email' => ['']],
              'name'                       => '',
              'content'                    => '',
              'date_preproduction'         => null,
              'date_production'            => null,
              'entities_id'                => $entity,
              'status'                     => PluginReleasesRelease::NEWRELEASE,
              'service_shutdown'           => false,
              'service_shutdown_details'   => '',
              'hour_type'                  => 0,
              'communication'              => false,
              'communication_type'         => false,
              'target'                     => "",
              'locations_id'               => 0,
      ];
   }

   /**
    * show actor part in ITIL object form
    *
    * @param $ID        integer  ITIL object ID
    * @param $options   array    options for default values ($options of showForm)
    *
    * @return void
    **/
   function showActorsPartForm($ID, array $options) {
      global $CFG_GLPI;

      $options['_default_use_notification'] = 0;

      if (isset($options['entities_id'])) {
         $options['_default_use_notification'] = Entity::getUsedConfig('is_notif_enable_default', $options['entities_id'], '', 1);
      }
      if ($ID) {
         $release_user     = new PluginReleasesReleasetemplate_User();
         $release_supplier = new PluginReleasesReleasetemplate_Supplier();
         $group_release    = new PluginReleasesGroup_Releasetemplate();
         $users            = $release_user->find(['plugin_releases_releasetemplates_id' => $ID]);
         $suppliers        = $release_supplier->find(['plugin_releases_releasetemplates_id' => $ID]);
         $groups           = $group_release->find(['plugin_releases_releasetemplates_id' => $ID]);
         foreach ($users as $user) {
            $options["_users_id_" . self::getActorFieldNameType($user["type"])] = $user["users_id"];
         }
         foreach ($suppliers as $supplier) {
            $options["_suppliers_id_" . self::getActorFieldNameType($supplier["type"])] = $supplier["suppliers_id"];
         }
         foreach ($groups as $group) {
            $options["_groups_id_" . self::getActorFieldNameType($group["type"])] = $group["groups_id"];
         }
      }


      $can_admin = $this->canAdminActors();
      // on creation can select actor

      $can_admin = true;


      $can_assign     = $this->canAssign();
      $can_assigntome = $this->canAssignToMe();

      if (isset($options['_noupdate']) && !$options['_noupdate']) {
         $can_admin      = false;
         $can_assign     = false;
         $can_assigntome = false;
      }

      // Manage actors
      echo "<div class='tab_actors tab_cadre_fixe' id='mainformtable5'>";
      echo "<div class='responsive_hidden actor_title'>" . __('Actor') . "</div>";

      // ====== Requesters BLOC ======
      //
      //
      echo "<span class='actor-bloc'>";
      echo "<div class='actor-head'>";
      echo __('Requester');

      echo "</div>"; // end .actor-head

      echo "<div class='actor-content'>";

      // Requester

      $reqdisplay = false;
      if ($can_admin) {

         $this->showActorAddFormOnCreate(CommonITILActor::REQUESTER, $options);
         $reqdisplay = true;
      } else {
         $delegating = User::getDelegateGroupsForUser($options['entities_id']);
         if (count($delegating)) {
            //$this->getDefaultActor(CommonITILActor::REQUESTER);
            $options['_right'] = "delegate";
            $this->showActorAddFormOnCreate(CommonITILActor::REQUESTER, $options);
            $reqdisplay = true;
         } else { // predefined value
            if (isset($options["_users_id_requester"]) && $options["_users_id_requester"]) {
               echo static::getActorIcon('user', CommonITILActor::REQUESTER) . "&nbsp;";
               echo Dropdown::getDropdownName("glpi_users", $options["_users_id_requester"]);
               echo Html::hidden('_users_id_requester', ['value' => $options["_users_id_requester"]]);
               echo '<br>';
               $reqdisplay = true;
            }
         }
      }

      if ($this->userentity_oncreate
          && isset($this->countentitiesforuser)
          && ($this->countentitiesforuser > 1)) {
         echo "<br>";
         $rand = Entity::dropdown(['value'     => $this->fields["entities_id"],
                                   'entity'    => $this->userentities,
                                   'on_change' => 'this.form.submit()']);
      } else {
         echo Html::hidden('entities_id', ['value' => $this->fields["entities_id"]]);
      }
      if ($reqdisplay) {
         echo '<hr>';
      }


      // Requester Group

      if ($can_admin) {
         echo static::getActorIcon('group', CommonITILActor::REQUESTER);

         Group::dropdown([
                            'name'      => '_groups_id_requester',
                            'value'     => $options["_groups_id_requester"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => ['is_requester' => 1]
                         ]);

      } else { // predefined value
         if (isset($options["_groups_id_requester"]) && $options["_groups_id_requester"]) {
            echo static::getActorIcon('group', CommonITILActor::REQUESTER) . "&nbsp;";
            echo Dropdown::getDropdownName("glpi_groups", $options["_groups_id_requester"]);
            echo Html::hidden('_groups_id_requester', ['value' => $options["_groups_id_requester"]]);
            echo '<br>';
         }
      }

      echo "</div>"; // end .actor-content
      echo "</span>"; // end .actor-bloc

      // ====== Observers BLOC ======

      echo "<span class='actor-bloc'>";
      echo "<div class='actor-head'>";
      echo __('Watcher');

      echo "</div>"; // end .actor-head
      echo "<div class='actor-content'>";


      // Observer

      if ($can_admin) {
         $this->showActorAddFormOnCreate(CommonITILActor::OBSERVER, $options);
         echo '<hr>';
      } else { // predefined value
         if (isset($options["_users_id_observer"][0]) && $options["_users_id_observer"][0]) {
            echo static::getActorIcon('user', CommonITILActor::OBSERVER) . "&nbsp;";
            echo Dropdown::getDropdownName("glpi_users", $options["_users_id_observer"][0]);
            echo Html::hidden('_users_id_observer', ['value' => $options["_users_id_observer"][0]]);
            echo '<hr>';
         }
      }


      // Observer Group

      if ($can_admin) {
         echo static::getActorIcon('group', CommonITILActor::OBSERVER);

         Group::dropdown([
                            'name'      => '_groups_id_observer',
                            'value'     => $options["_groups_id_observer"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => ['is_requester' => 1]
                         ]);
      } else { // predefined value
         if (isset($options["_groups_id_observer"]) && $options["_groups_id_observer"]) {
            echo static::getActorIcon('group', CommonITILActor::OBSERVER) . "&nbsp;";
            echo Dropdown::getDropdownName("glpi_groups", $options["_groups_id_observer"]);
            echo Html::hidden('_groups_id_observer', ['value' => $options["_groups_id_observer"]]);
            echo '<br>';
         }
      }

      echo "</div>"; // end .actor-content
      echo "</span>"; // end .actor-bloc

      // ====== Assign BLOC ======

      echo "<span class='actor-bloc'>";
      echo "<div class='actor-head'>";

      echo __('Assigned to');


      echo "</div>"; // end .actor-head

      echo "<div class='actor-content'>";


      // Assign User

      if ($can_assign
          && $this->isAllowedStatus(CommonITILObject::INCOMING, CommonITILObject::ASSIGNED)) {
         $this->showActorAddFormOnCreate(CommonITILActor::ASSIGN, $options);
         echo '<hr>';

      } else if ($can_assigntome
                 && $this->isAllowedStatus(CommonITILObject::INCOMING, CommonITILObject::ASSIGNED)) {
         echo static::getActorIcon('user', CommonITILActor::ASSIGN) . "&nbsp;";
         User::dropdown(['name'        => '_users_id_assign',
                         'value'       => $options["_users_id_assign"],
                         'entity'      => $this->fields["entities_id"],
                         'ldap_import' => true]);
         echo '<hr>';

      } else { // predefined value
         if (isset($options["_users_id_assign"]) && $options["_users_id_assign"]
             && $this->isAllowedStatus(CommonITILObject::INCOMING, CommonITILObject::ASSIGNED)) {
            echo static::getActorIcon('user', CommonITILActor::ASSIGN) . "&nbsp;";
            echo Dropdown::getDropdownName("glpi_users", $options["_users_id_assign"]);
            echo Html::hidden('_users_id_assign', ['value' => $options["_users_id_assign"]]);
            echo '<hr>';
         }
      }


      // Assign Groups

      if ($can_assign
          && $this->isAllowedStatus(CommonITILObject::INCOMING, CommonITILObject::ASSIGNED)) {
         echo static::getActorIcon('group', CommonITILActor::ASSIGN);

         $rand   = mt_rand();
         $params = [
            'name'      => '_groups_id_assign',
            'value'     => $options["_groups_id_assign"],
            'entity'    => $this->fields["entities_id"],
            'condition' => ['is_assign' => 1],
            'rand'      => $rand
         ];


         Group::dropdown($params);


         echo '<hr>';
      } else { // predefined value
         if (isset($options["_groups_id_assign"])
             && $options["_groups_id_assign"]
             && $this->isAllowedStatus(CommonITILObject::INCOMING, CommonITILObject::ASSIGNED)) {
            echo static::getActorIcon('group', CommonITILActor::ASSIGN) . "&nbsp;";
            echo Dropdown::getDropdownName("glpi_groups", $options["_groups_id_assign"]);
            echo Html::hidden('_groups_id_assign', ['value' => $options["_groups_id_assign"]]);
            echo '<hr>';
         }
      }


      // Assign Suppliers


      if ($can_assign
          && $this->isAllowedStatus(CommonITILObject::INCOMING, CommonITILObject::ASSIGNED)) {
         $this->showSupplierAddFormOnCreate($options);
      } else { // predefined value
         if (isset($options["_suppliers_id_assign"])
             && $options["_suppliers_id_assign"]
             && $this->isAllowedStatus(CommonITILObject::INCOMING, CommonITILObject::ASSIGNED)) {
            echo static::getActorIcon('supplier', CommonITILActor::ASSIGN) . "&nbsp;";
            echo Dropdown::getDropdownName("glpi_suppliers", $options["_suppliers_id_assign"]);
            echo Html::hidden('_suppliers_id_assign', ['value' => $options["_suppliers_id_assign"]]);
            echo '<hr>';
         }
      }


      echo "</div>"; // end .actor-content
      echo "</span>"; // end .actor-bloc

      echo "</div>"; // tab_actors
   }

   /**
    * Can manage actors
    *
    * @return boolean
    */
   function canAdminActors() {
      if (isset($this->fields['is_deleted']) && $this->fields['is_deleted'] == 1) {
         return false;
      }
      return Session::haveRight(static::$rightname, UPDATE);
   }

   /**
    * Can assign object
    *
    * @return boolean
    */
   function canAssign() {
      if (isset($this->fields['is_deleted']) && ($this->fields['is_deleted'] == 1)
          || isset($this->fields['status']) && in_array($this->fields['status'], $this->getClosedStatusArray())
      ) {
         return false;
      }
      return Session::haveRight(static::$rightname, UPDATE);
   }


   /**
    * Can be assigned to me
    *
    * @return boolean
    */
   function canAssignToMe() {
      if (isset($this->fields['is_deleted']) && $this->fields['is_deleted'] == 1
          || isset($this->fields['status']) && in_array($this->fields['status'], $this->getClosedStatusArray())
      ) {
         return false;
      }
      return Session::haveRight(static::$rightname, UPDATE);
   }

   /**
    * show actor add div
    *
    * @param $type         string   actor type
    * @param $rand_type    integer  rand value of div to use
    * @param $entities_id  integer  entity ID
    * @param $is_hidden    array    of hidden fields (if empty consider as not hidden)
    * @param $withgroup    boolean  allow adding a group (true by default)
    * @param $withsupplier boolean  allow adding a supplier (only one possible in ASSIGN case)
    *                               (false by default)
    * @param $inobject     boolean  display in ITIL object ? (true by default)
    *
    * @return void|boolean Nothing if displayed, false if not applicable
    **/
   function showActorAddForm($type, $rand_type, $entities_id, $is_hidden = [],
                             $withgroup = true, $withsupplier = false, $inobject = true) {
      global $CFG_GLPI;

      $types = ['user' => __('User')];

      if ($withgroup) {
         $types['group'] = __('Group');
      }

      if ($withsupplier
          && ($type == CommonITILActor::ASSIGN)) {
         $types['supplier'] = __('Supplier');
      }

      $typename = static::getActorFieldNameType($type);
      switch ($type) {
         case CommonITILActor::REQUESTER :
            if (isset($is_hidden['_users_id_requester']) && $is_hidden['_users_id_requester']) {
               unset($types['user']);
            }
            if (isset($is_hidden['_groups_id_requester']) && $is_hidden['_groups_id_requester']) {
               unset($types['group']);
            }
            break;

         case CommonITILActor::OBSERVER :
            if (isset($is_hidden['_users_id_observer']) && $is_hidden['_users_id_observer']) {
               unset($types['user']);
            }
            if (isset($is_hidden['_groups_id_observer']) && $is_hidden['_groups_id_observer']) {
               unset($types['group']);
            }
            break;

         case CommonITILActor::ASSIGN :
            if (isset($is_hidden['_users_id_assign']) && $is_hidden['_users_id_assign']) {
               unset($types['user']);
            }
            if (isset($is_hidden['_groups_id_assign']) && $is_hidden['_groups_id_assign']) {
               unset($types['group']);
            }
            if (isset($types['supplier'])
                && isset($is_hidden['_suppliers_id_assign']) && $is_hidden['_suppliers_id_assign']) {
               unset($types['supplier']);
            }
            break;

         default :
            return false;
      }

      echo "<div " . ($inobject ? "style='display:none'" : '') . " id='itilactor$rand_type' class='actor-dropdown'>";
      $rand   = Dropdown::showFromArray("_itil_" . $typename . "[_type]", $types,
                                        ['display_emptychoice' => true]);
      $params = ['type'            => '__VALUE__',
                 'actortype'       => $typename,
                 'itemtype'        => $this->getType(),
                 'allow_email'     => (($type == CommonITILActor::OBSERVER)
                                       || $type == CommonITILActor::REQUESTER),
                 'entity_restrict' => $entities_id,
                 'use_notif'       => Entity::getUsedConfig('is_notif_enable_default', $entities_id, '', 1)];

      Ajax::updateItemOnSelectEvent("dropdown__itil_" . $typename . "[_type]$rand",
                                    "showitilactor" . $typename . "_$rand",
                                    $CFG_GLPI["root_doc"] . "/ajax/dropdownItilActors.php",
                                    $params);
      echo "<span id='showitilactor" . $typename . "_$rand' class='actor-dropdown'>&nbsp;</span>";
      if ($inobject) {
         echo "<hr>";
      }
      echo "</div>";
   }


   /**
    * get field part name corresponding to actor type
    *
    * @param $type      integer : user type
    *
    * @return string|boolean Field part or false if not applicable
    **@since 0.84.6
    *
    */
   static function getActorFieldNameType($type) {

      switch ($type) {
         case CommonITILActor::REQUESTER :
            return 'requester';

         case CommonITILActor::OBSERVER :
            return 'observer';

         case CommonITILActor::ASSIGN :
            return 'assign';

         default :
            return false;
      }
   }

   /**
    * show tooltip for user notification information
    *
    * @param $type      integer  user type
    * @param $canedit   boolean  can edit ?
    * @param $options   array    options for default values ($options of showForm)
    *
    * @return void
    **/
   function showUsersAssociated($type, $canedit, array $options = []) {
      global $CFG_GLPI;

      $showuserlink = 0;
      if (User::canView()) {
         $showuserlink = 2;
      }
      $usericon = static::getActorIcon('user', $type);
      $user     = new User();
      $linkuser = new $this->userlinkclass();

      $typename = static::getActorFieldNameType($type);

      $candelete = true;
      $mandatory = '';


      if (isset($this->users[$type]) && count($this->users[$type])) {
         foreach ($this->users[$type] as $d) {
            echo "<div class='actor_row'>";
            $k = $d['users_id'];

            echo "$mandatory$usericon&nbsp;";

            if ($k) {
               $userdata = getUserName($k);
            } else {
               $email    = $d['alternative_email'];
               $userdata = "<a href='mailto:$email'>$email</a>";
            }

            if (Entity::getUsedConfig('anonymize_support_agents')
                && Session::getCurrentInterface() == 'helpdesk'
                && $type == CommonITILActor::ASSIGN
            ) {
               echo __("Helpdesk");
            } else {
               if ($k) {
                  $param = ['display' => false];
                  if ($showuserlink) {
                     $param['link'] = $userdata["link"];
                  }
                  echo $userdata['name'] . "&nbsp;" . Html::showToolTip($userdata["comment"], $param);
               } else {
                  echo $userdata;
               }
            }


            if ($canedit && $candelete) {
               Html::showSimpleForm($linkuser->getFormURL(), 'delete',
                                    _x('button', 'Delete permanently'),
                                    ['id' => $d['id']],
                                    'fa-times-circle');
            }
            echo "</div>";
         }
      }
   }

   /**
    * Get Icon for Actor
    *
    * @param $user_group   string   'user or 'group'
    * @param $type         integer  user/group type
    *
    * @return string
    **/
   static function getActorIcon($user_group, $type) {

      switch ($user_group) {
         case 'user' :
            $icontitle = __s('User') . ' - ' . $type; // should never be used
            switch ($type) {
               case CommonITILActor::REQUESTER :
                  $icontitle = __s('Requester user');
                  break;

               case CommonITILActor::OBSERVER :
                  $icontitle = __s('Watcher user');
                  break;

               case CommonITILActor::ASSIGN :
                  $icontitle = __s('Technician');
                  break;
            }
            return "<i class='fas fa-user' title='$icontitle'></i><span class='sr-only'>$icontitle</span>";

         case 'group' :
            $icontitle = __('Group');
            switch ($type) {
               case CommonITILActor::REQUESTER :
                  $icontitle = __s('Requester group');
                  break;

               case CommonITILActor::OBSERVER :
                  $icontitle = __s('Watcher group');
                  break;

               case CommonITILActor::ASSIGN :
                  $icontitle = __s('Group in charge of the release', 'releases');
                  break;
            }

            return "<i class='fas fa-users' title='$icontitle'></i>" .
                   "<span class='sr-only'>$icontitle</span>";

         case 'supplier' :
            $icontitle = __('Supplier');
            return "<i class='fas fa-dolly' title='$icontitle'></i>" .
                   "<span class='sr-only'>$icontitle</span>";

      }
      return '';

   }

   /**
    * show groups asociated
    *
    * @param $type      integer : user type
    * @param $canedit   boolean : can edit ?
    * @param $options   array    options for default values ($options of showForm)
    *
    * @return void
    **/
   function showGroupsAssociated($type, $canedit, array $options = []) {

      $groupicon = static::getActorIcon('group', $type);
      $group     = new Group();
      $linkclass = new $this->grouplinkclass();

      $typename = static::getActorFieldNameType($type);

      $candelete = true;
      $mandatory = '';


      if (isset($this->groups[$type]) && count($this->groups[$type])) {
         foreach ($this->groups[$type] as $d) {
            echo "<div class='actor_row'>";
            $k = $d['groups_id'];
            echo "$mandatory$groupicon&nbsp;";
            if ($group->getFromDB($k)) {
               if (Entity::getUsedConfig('anonymize_support_agents')
                   && Session::getCurrentInterface() == 'helpdesk'
                   && $type == CommonITILActor::ASSIGN
               ) {
                  echo __("Helpdesk group");
               } else {
                  echo $group->getLink(['comments' => true]);
               }
            }
            if ($canedit && $candelete) {
               Html::showSimpleForm($linkclass->getFormURL(), 'delete',
                                    _x('button', 'Delete permanently'),
                                    ['id' => $d['id']],
                                    'fa-times-circle');
            }
            echo "</div>";
         }
      }
   }

   /**
    * Is a user linked to the object ?
    *
    * @param integer $type type to search (see constants)
    * @param integer $users_id user ID
    *
    * @return boolean
    **/
   function isUser($type, $users_id) {

      if (isset($this->users[$type])) {
         foreach ($this->users[$type] as $data) {
            if ($data['users_id'] == $users_id) {
               return true;
            }
         }
      }

      return false;
   }

   /**
    * show suppliers associated
    *
    * @param $type      integer : user type
    * @param $canedit   boolean : can edit ?
    * @param $options   array    options for default values ($options of showForm)
    *
    * @return void
    **@since 0.84
    *
    */
   function showSuppliersAssociated($type, $canedit, array $options = []) {
      global $CFG_GLPI;

      $showsupplierlink = 0;
      if (Session::haveRight('contact_enterprise', READ)) {
         $showsupplierlink = 2;
      }

      $suppliericon = static::getActorIcon('supplier', $type);
      $supplier     = new Supplier();
      $linksupplier = new $this->supplierlinkclass();

      $typename = static::getActorFieldNameType($type);

      $candelete = true;
      $mandatory = '';


      if (isset($this->suppliers[$type]) && count($this->suppliers[$type])) {
         foreach ($this->suppliers[$type] as $d) {
            echo "<div class='actor_row'>";
            $suppliers_id = $d['suppliers_id'];

            echo "$mandatory$suppliericon&nbsp;";

            $email = $d['alternative_email'];
            if ($suppliers_id) {
               if ($supplier->getFromDB($suppliers_id)) {
                  echo $supplier->getLink(['comments' => $showsupplierlink]);
                  echo "&nbsp;";

                  $tmpname = Dropdown::getDropdownName($supplier->getTable(), $suppliers_id, 1);
                  Html::showToolTip($tmpname['comment']);

                  if (empty($email)) {
                     $email = $supplier->fields['email'];
                  }
               }
            } else {
               echo "<a href='mailto:$email'>$email</a>";
            }


            if ($canedit && $candelete) {
               Html::showSimpleForm($linksupplier->getFormURL(), 'delete',
                                    _x('button', 'Delete permanently'),
                                    ['id' => $d['id']],
                                    'fa-times-circle');
            }

            echo '</div>';
         }
      }
   }

   function post_addItem() {
      parent::post_addItem();
      $useractors = null;
      // Add user groups linked to ITIL objects
      if (!empty($this->userlinkclass)) {
         $useractors = new $this->userlinkclass();
      }
      $groupactors = null;
      if (!empty($this->grouplinkclass)) {
         $groupactors = new $this->grouplinkclass();
      }
      $supplieractors = null;
      if (!empty($this->supplierlinkclass)) {
         $supplieractors = new $this->supplierlinkclass();
      }

      // "do not compute" flag set by business rules for "takeintoaccount_delay_stat" field
      $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($this->input);

      if (!is_null($useractors)) {
         $user_input = [
            $useractors->getItilObjectForeignKey() => $this->fields['id'],
            '_do_not_compute_takeintoaccount'      => $do_not_compute_takeintoaccount,
            '_from_object'                         => true,
         ];

         if (isset($this->input["_users_id_requester"])) {

            if (is_array($this->input["_users_id_requester"])) {
               $tab_requester = $this->input["_users_id_requester"];
            } else {
               $tab_requester   = [];
               $tab_requester[] = $this->input["_users_id_requester"];
            }

            $requesterToAdd = [];
            foreach ($tab_requester as $key_requester => $requester) {
               if (in_array($requester, $requesterToAdd)) {
                  // This requester ID is already added;
                  continue;
               }

               $input2 = [
                            'users_id' => $requester,
                            'type'     => CommonITILActor::REQUESTER,
                         ] + $user_input;

               if (isset($this->input["_users_id_requester_notif"])) {
                  foreach ($this->input["_users_id_requester_notif"] as $key => $val) {
                     if (isset($val[$key_requester])) {
                        $input2[$key] = $val[$key_requester];
                     }
                  }
               }

               //empty actor
               if ($input2['users_id'] == 0
                   && (!isset($input2['alternative_email'])
                       || empty($input2['alternative_email']))) {
                  continue;
               } else if ($requester != 0) {
                  $requesterToAdd[] = $requester;
               }

               $useractors->add($input2);
            }
         }

         if (isset($this->input["_users_id_observer"])) {

            if (is_array($this->input["_users_id_observer"])) {
               $tab_observer = $this->input["_users_id_observer"];
            } else {
               $tab_observer   = [];
               $tab_observer[] = $this->input["_users_id_observer"];
            }

            $observerToAdd = [];
            foreach ($tab_observer as $key_observer => $observer) {
               if (in_array($observer, $observerToAdd)) {
                  // This observer ID is already added;
                  continue;
               }

               $input2 = [
                            'users_id' => $observer,
                            'type'     => CommonITILActor::OBSERVER,
                         ] + $user_input;

               if (isset($this->input["_users_id_observer_notif"])) {
                  foreach ($this->input["_users_id_observer_notif"] as $key => $val) {
                     if (isset($val[$key_observer])) {
                        $input2[$key] = $val[$key_observer];
                     }
                  }
               }

               //empty actor
               if ($input2['users_id'] == 0
                   && (!isset($input2['alternative_email'])
                       || empty($input2['alternative_email']))) {
                  continue;
               } else if ($observer != 0) {
                  $observerToAdd[] = $observer;
               }

               $useractors->add($input2);
            }
         }

         if (isset($this->input["_users_id_assign"])) {

            if (is_array($this->input["_users_id_assign"])) {
               $tab_assign = $this->input["_users_id_assign"];
            } else {
               $tab_assign   = [];
               $tab_assign[] = $this->input["_users_id_assign"];
            }

            $assignToAdd = [];
            foreach ($tab_assign as $key_assign => $assign) {
               if (in_array($assign, $assignToAdd)) {
                  // This assigned user ID is already added;
                  continue;
               }

               $input2 = [
                            'users_id' => $assign,
                            'type'     => CommonITILActor::ASSIGN,
                         ] + $user_input;

               if (isset($this->input["_users_id_assign_notif"])) {
                  foreach ($this->input["_users_id_assign_notif"] as $key => $val) {
                     if (isset($val[$key_assign])) {
                        $input2[$key] = $val[$key_assign];
                     }
                  }
               }

               //empty actor
               if ($input2['users_id'] == 0
                   && (!isset($input2['alternative_email'])
                       || empty($input2['alternative_email']))) {
                  continue;
               } else if ($assign != 0) {
                  $assignToAdd[] = $assign;
               }

               $useractors->add($input2);
            }
         }
      }

      if (!is_null($groupactors)) {
         $group_input = [
            $groupactors->getItilObjectForeignKey() => $this->fields['id'],
            '_do_not_compute_takeintoaccount'       => $do_not_compute_takeintoaccount,
            '_from_object'                          => true,
         ];

         if (isset($this->input["_groups_id_requester"])) {
            $groups_id_requester = $this->input["_groups_id_requester"];
            if (!is_array($this->input["_groups_id_requester"])) {
               $groups_id_requester = [$this->input["_groups_id_requester"]];
            } else {
               $groups_id_requester = $this->input["_groups_id_requester"];
            }
            foreach ($groups_id_requester as $groups_id) {
               if ($groups_id > 0) {
                  $groupactors->add(
                     [
                        'groups_id' => $groups_id,
                        'type'      => CommonITILActor::REQUESTER,
                     ] + $group_input
                  );
               }
            }
         }

         if (isset($this->input["_groups_id_assign"])) {
            if (!is_array($this->input["_groups_id_assign"])) {
               $groups_id_assign = [$this->input["_groups_id_assign"]];
            } else {
               $groups_id_assign = $this->input["_groups_id_assign"];
            }
            foreach ($groups_id_assign as $groups_id) {
               if ($groups_id > 0) {
                  $groupactors->add(
                     [
                        'groups_id' => $groups_id,
                        'type'      => CommonITILActor::ASSIGN,
                     ] + $group_input
                  );
               }
            }
         }

         if (isset($this->input["_groups_id_observer"])) {
            if (!is_array($this->input["_groups_id_observer"])) {
               $groups_id_observer = [$this->input["_groups_id_observer"]];
            } else {
               $groups_id_observer = $this->input["_groups_id_observer"];
            }
            foreach ($groups_id_observer as $groups_id) {
               if ($groups_id > 0) {
                  $groupactors->add(
                     [
                        'groups_id' => $groups_id,
                        'type'      => CommonITILActor::OBSERVER,
                     ] + $group_input
                  );
               }
            }
         }
      }

      if (!is_null($supplieractors)) {
         $supplier_input = [
            $supplieractors->getItilObjectForeignKey() => $this->fields['id'],
            '_do_not_compute_takeintoaccount'          => $do_not_compute_takeintoaccount,
            '_from_object'                             => true,
         ];

         if (isset($this->input["_suppliers_id_assign"])
             && ($this->input["_suppliers_id_assign"] > 0)) {

            if (is_array($this->input["_suppliers_id_assign"])) {
               $tab_assign = $this->input["_suppliers_id_assign"];
            } else {
               $tab_assign   = [];
               $tab_assign[] = $this->input["_suppliers_id_assign"];
            }

            $supplierToAdd = [];
            foreach ($tab_assign as $key_assign => $assign) {
               if (in_array($assign, $supplierToAdd)) {
                  // This assigned supplier ID is already added;
                  continue;
               }
               $input3 = [
                            'suppliers_id' => $assign,
                            'type'         => CommonITILActor::ASSIGN,
                         ] + $supplier_input;

               if (isset($this->input["_suppliers_id_assign_notif"])) {
                  foreach ($this->input["_suppliers_id_assign_notif"] as $key => $val) {
                     $input3[$key] = $val[$key_assign];
                  }
               }

               //empty supplier
               if ($input3['suppliers_id'] == 0
                   && (!isset($input3['alternative_email'])
                       || empty($input3['alternative_email']))) {
                  continue;
               } else if ($assign != 0) {
                  $supplierToAdd[] = $assign;
               }

               $supplieractors->add($input3);
            }
         }
      }

      // Additional actors
      $this->addAdditionalActors($this->input);
   }

   /**
    * Check if input contains a flag set to prevent 'takeintoaccount' delay computation.
    *
    * @param array $input
    *
    * @return boolean
    */
   public function isTakeIntoAccountComputationBlocked($input) {
      return array_key_exists('_do_not_compute_takeintoaccount', $input)
             && $input['_do_not_compute_takeintoaccount'];
   }

   /**
    * @since 0.84
    * @since 0.85 must have param $input
    **/
   private function addAdditionalActors($input) {

      $useractors = null;
      // Add user groups linked to ITIL objects
      if (!empty($this->userlinkclass)) {
         $useractors = new $this->userlinkclass();
      }
      $groupactors = null;
      if (!empty($this->grouplinkclass)) {
         $groupactors = new $this->grouplinkclass();
      }
      $supplieractors = null;
      if (!empty($this->supplierlinkclass)) {
         $supplieractors = new $this->supplierlinkclass();
      }

      // "do not compute" flag set by business rules for "takeintoaccount_delay_stat" field
      $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($input);

      // Additional groups actors
      if (!is_null($groupactors)) {
         $group_input = [
            $groupactors->getItilObjectForeignKey() => $this->fields['id'],
            '_do_not_compute_takeintoaccount'       => $do_not_compute_takeintoaccount,
            '_from_object'                          => true,
         ];

         // Requesters
         if (isset($input['_additional_groups_requesters'])
             && is_array($input['_additional_groups_requesters'])
             && count($input['_additional_groups_requesters'])) {
            foreach ($input['_additional_groups_requesters'] as $tmp) {
               if ($tmp > 0) {
                  $groupactors->add(
                     [
                        'type'      => CommonITILActor::REQUESTER,
                        'groups_id' => $tmp,
                     ] + $group_input
                  );
               }
            }
         }

         // Observers
         if (isset($input['_additional_groups_observers'])
             && is_array($input['_additional_groups_observers'])
             && count($input['_additional_groups_observers'])) {
            foreach ($input['_additional_groups_observers'] as $tmp) {
               if ($tmp > 0) {
                  $groupactors->add(
                     [
                        'type'      => CommonITILActor::OBSERVER,
                        'groups_id' => $tmp,
                     ] + $group_input
                  );
               }
            }
         }

         // Assigns
         if (isset($input['_additional_groups_assigns'])
             && is_array($input['_additional_groups_assigns'])
             && count($input['_additional_groups_assigns'])) {
            foreach ($input['_additional_groups_assigns'] as $tmp) {
               if ($tmp > 0) {
                  $groupactors->add(
                     [
                        'type'      => CommonITILActor::ASSIGN,
                        'groups_id' => $tmp,
                     ] + $group_input
                  );
               }
            }
         }
      }

      // Additional suppliers actors
      if (!is_null($supplieractors)) {
         $supplier_input = [
            $supplieractors->getItilObjectForeignKey() => $this->fields['id'],
            '_do_not_compute_takeintoaccount'          => $do_not_compute_takeintoaccount,
            '_from_object'                             => true,
         ];

         // Assigns
         if (isset($input['_additional_suppliers_assigns'])
             && is_array($input['_additional_suppliers_assigns'])
             && count($input['_additional_suppliers_assigns'])) {

            $input2 = [
                         'type' => CommonITILActor::ASSIGN,
                      ] + $supplier_input;

            foreach ($input["_additional_suppliers_assigns"] as $tmp) {
               if (isset($tmp['suppliers_id'])) {
                  foreach ($tmp as $key => $val) {
                     $input2[$key] = $val;
                  }
                  $supplieractors->add($input2);
               }
            }
         }
      }

      // Additional actors : using default notification parameters
      if (!is_null($useractors)) {
         $user_input = [
            $useractors->getItilObjectForeignKey() => $this->fields['id'],
            '_do_not_compute_takeintoaccount'      => $do_not_compute_takeintoaccount,
            '_from_object'                         => true,
         ];

         // Observers : for mailcollector
         if (isset($input["_additional_observers"])
             && is_array($input["_additional_observers"])
             && count($input["_additional_observers"])) {

            $input2 = [
                         'type' => CommonITILActor::OBSERVER,
                      ] + $user_input;

            foreach ($input["_additional_observers"] as $tmp) {
               if (isset($tmp['users_id'])) {
                  foreach ($tmp as $key => $val) {
                     $input2[$key] = $val;
                  }
                  $useractors->add($input2);
               }
            }
         }

         if (isset($input["_additional_assigns"])
             && is_array($input["_additional_assigns"])
             && count($input["_additional_assigns"])) {

            $input2 = [
                         'type' => CommonITILActor::ASSIGN,
                      ] + $user_input;

            foreach ($input["_additional_assigns"] as $tmp) {
               if (isset($tmp['users_id'])) {
                  foreach ($tmp as $key => $val) {
                     $input2[$key] = $val;
                  }
                  $useractors->add($input2);
               }
            }
         }
         if (isset($input["_additional_requesters"])
             && is_array($input["_additional_requesters"])
             && count($input["_additional_requesters"])) {

            $input2 = [
                         'type' => CommonITILActor::REQUESTER,
                      ] + $user_input;

            foreach ($input["_additional_requesters"] as $tmp) {
               if (isset($tmp['users_id'])) {
                  foreach ($tmp as $key => $val) {
                     $input2[$key] = $val;
                  }
                  $useractors->add($input2);
               }
            }
         }
      }
   }

   /**
    * Update date mod of the ITIL object
    *
    * @param $ID                    integer  ID of the ITIL object
    * @param $no_stat_computation   boolean  do not cumpute take into account stat (false by default)
    * @param $users_id_lastupdater  integer  to force last_update id (default 0 = not used)
    **/
   function updateDateMod($ID, $no_stat_computation = false, $users_id_lastupdater = 0) {
   }

   function post_getFromDB() {
      $this->loadActors();
   }


   /**
    * @since 0.84
    **/
   function loadActors() {

      if (!empty($this->grouplinkclass)) {
         $class        = new $this->grouplinkclass();
         $this->groups = $class->getActors($this->fields['id']);
      }

      if (!empty($this->userlinkclass)) {
         $class       = new $this->userlinkclass();
         $this->users = $class->getActors($this->fields['id']);
      }

      if (!empty($this->supplierlinkclass)) {
         $class           = new $this->supplierlinkclass();
         $this->suppliers = $class->getActors($this->fields['id']);
      }
   }


   /**
    * show user add div on creation
    *
    * @param $type      integer  actor type
    * @param $options   array    options for default values ($options of showForm)
    *
    * @return integer Random part of inputs ids
    **/
   function showActorAddFormOnCreate($type, array $options) {
      global $CFG_GLPI;

      $typename = static::getActorFieldNameType($type);

      $itemtype = $this->getType();

      echo static::getActorIcon('user', $type);

      if (!isset($options["_right"])) {
         $right = $this->getDefaultActorRightSearch($type);
      } else {
         $right = $options["_right"];
      }


      $rand       = mt_rand();
      $actor_name = '_users_id_' . $typename;
      if ($type == CommonITILActor::OBSERVER) {
         $actor_name = '_users_id_' . $typename . '[]';
      }
      $params = ['name'   => $actor_name,
                 'value'  => $options["_users_id_" . $typename],
                 'right'  => $right,
                 'rand'   => $rand,
                 'entity' => (isset($options['entities_id'])
                    ? $options['entities_id'] : $options['entity_restrict'])];

      //only for active ldap and corresponding right
      $ldap_methods = getAllDataFromTable('glpi_authldaps', ['is_active' => 1]);
      if (count($ldap_methods)
          && Session::haveRight('user', User::IMPORTEXTAUTHUSERS)) {
         $params['ldap_import'] = true;
      }

      if ($this->userentity_oncreate
          && ($type == CommonITILActor::REQUESTER)) {
         $params['on_change'] = 'this.form.submit()';
         unset($params['entity']);
      }

      $params['_user_index'] = 0;
      if (isset($options['_user_index'])) {
         $params['_user_index'] = $options['_user_index'];
      }


      // List all users in the active entities
      User::dropdown($params);


      return $rand;
   }


   /**
    * show supplier add div on creation
    *
    * @param $options   array    options for default values ($options of showForm)
    *
    * @return void
    **/
   function showSupplierAddFormOnCreate(array $options) {
      global $CFG_GLPI;

      $itemtype = $this->getType();

      echo static::getActorIcon('supplier', 'assign');


      $rand   = mt_rand();
      $params = ['name'  => '_suppliers_id_assign',
                 'value' => $options["_suppliers_id_assign"],
                 'rand'  => $rand];


      Supplier::dropdown($params);


   }

   /**
    * Get Default actor when creating the object
    *
    * @param integer $type type to search (see constants)
    *
    * @return boolean
    **/
   function getDefaultActorRightSearch($type) {

      if ($type == CommonITILActor::ASSIGN) {
         return "own_ticket";
      }
      return "all";
   }

   /**
    * @see CommonITILObject::getDefaultActor()
    **/
   function getDefaultActor($type) {

      if ($type == CommonITILActor::ASSIGN) {
         if (Session::haveRight(self::$rightname, UPDATE)
             && $_SESSION['glpiset_default_tech']) {
            return Session::getLoginUserID();
         }
      }
      if ($type == CommonITILActor::REQUESTER) {
         if (Session::haveRight(self::$rightname, CREATE)
             && $_SESSION['glpiset_default_requester']) {
            return Session::getLoginUserID();
         }
      }
      return 0;
   }

   /**
    * count users linked to object by type or global
    *
    * @param integer $type type to search (see constants) / 0 for all (default 0)
    *
    * @return integer
    **/
   function countUsers($type = 0) {

      if ($type > 0) {
         if (isset($this->users[$type])) {
            return count($this->users[$type]);
         }

      } else {
         if (count($this->users)) {
            $count = 0;
            foreach ($this->users as $u) {
               $count += count($u);
            }
            return $count;
         }
      }
      return 0;
   }


   /**
    * count groups linked to object by type or global
    *
    * @param integer $type type to search (see constants) / 0 for all (default 0)
    *
    * @return integer
    **/
   function countGroups($type = 0) {

      if ($type > 0) {
         if (isset($this->groups[$type])) {
            return count($this->groups[$type]);
         }

      } else {
         if (count($this->groups)) {
            $count = 0;
            foreach ($this->groups as $u) {
               $count += count($u);
            }
            return $count;
         }
      }
      return 0;
   }

   /**
    * count suppliers linked to object by type or global
    *
    * @param integer $type type to search (see constants) / 0 for all (default 0)
    *
    * @return integer
    **@since 0.84
    *
    */
   function countSuppliers($type = 0) {

      if ($type > 0) {
         if (isset($this->suppliers[$type])) {
            return count($this->suppliers[$type]);
         }

      } else {
         if (count($this->suppliers)) {
            $count = 0;
            foreach ($this->suppliers as $u) {
               $count += count($u);
            }
            return $count;
         }
      }
      return 0;
   }

   /**
    * @param null $checkitem
    *
    * @return array
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
    *
    */
   function getSpecificMassiveActions($checkitem = null) {
      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if (Session::getCurrentInterface() == 'central') {
         if ($isadmin) {
            $actions['PluginReleasesReleasetemplate' . MassiveAction::CLASS_ACTION_SEPARATOR . 'transfer'] = __('Transfer');
         }
      }
      return $actions;
   }

   /**
    * @param MassiveAction $ma
    *
    * @return bool|false
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
    *
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case "transfer" :
            Dropdown::show('Entity');
            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
            return true;
            break;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   /**
    * @param MassiveAction $ma
    * @param CommonDBTM    $item
    * @param array         $ids
    *
    * @return nothing|void
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    *
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {


      switch ($ma->getAction()) {

         case "transfer" :
            $input = $ma->getInput();
            if ($item->getType() == PluginReleasesReleasetemplate::getType()) {
               foreach ($ids as $key) {
                  $item->getFromDB($key);


                  unset($values);
                  $values["id"]          = $key;
                  $values["entities_id"] = $input['entities_id'];

                  if ($item->update($values)) {
                     PluginReleasesDeploytasktemplate::transfer($key, $input["entities_id"]);
                     PluginReleasesTesttemplate::transfer($key, $input["entities_id"]);
                     PluginReleasesRisktemplate::transfer($key, $input["entities_id"]);
                     PluginReleasesRollbacktemplate::transfer($key, $input["entities_id"]);
                     self::transferDocument($key, $input["entities_id"]);
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                  }
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   static function transferDocument($ID, $entity) {
      global $DB;

      if ($ID > 0) {
         $self      = new self();
         $documents = new Document_Item();
         $items     = $documents->find(["items_id" => $ID, "itemtype" => self::getType()]);
         foreach ($items as $id => $vals) {
            $input                = [];
            $input["id"]          = $id;
            $input["entities_id"] = $entity;
            $documents->update($input);
         }
         return true;

      }
      return 0;
   }

   /**
    * Retrieve an item from the database with additional datas
    *
    * @since 0.83
    *
    * @param $ID                    integer  ID of the item to get
    * @param $withtypeandcategory   boolean  with type and category (true by default)
    *
    * @return true if succeed else false
    **/
   public function getFromDBWithData($ID, $withtypeandcategory = true)
   {
      if ($this->getFromDB($ID)) {
         $itiltype = str_replace('Template', '', static::getType());
         $itil_object  = new $itiltype();
         $itemstable = $itil_object->getItemsTable();
         $tth_class = $itiltype . 'TemplateHiddenField';
         $tth          = new $tth_class();
         $this->hidden = $tth->getHiddenFields($ID, $withtypeandcategory);

         // Force items_id if itemtype is defined
         if (
            isset($this->hidden['itemtype'])
            && !isset($this->hidden['items_id'])
         ) {
            $this->hidden['items_id'] = $itil_object->getSearchOptionIDByField(
               'field',
               'items_id',
               $itemstable
            );
         }
         // Always get all mandatory fields
         $ttm_class = $itiltype . 'TemplateMandatoryField';
         $ttm             = new $ttm_class();
         $this->mandatory = $ttm->getMandatoryFields($ID);

         // Force items_id if itemtype is defined
         if (
            isset($this->mandatory['itemtype'])
            && !isset($this->mandatory['items_id'])
         ) {
            $this->mandatory['items_id'] = $itil_object->getSearchOptionIDByField(
               'field',
               'items_id',
               $itemstable
            );
         }

         $ttp_class = $itiltype . 'TemplatePredefinedField';
         $ttp              = new $ttp_class();
         $this->predefined = $ttp->getPredefinedFields($ID, $withtypeandcategory);
         // Compute time_to_resolve
         if (isset($this->predefined['time_to_resolve'])) {
            $this->predefined['time_to_resolve']
               = Html::computeGenericDateTimeSearch($this->predefined['time_to_resolve'], false);
         }
         if (isset($this->predefined['time_to_own'])) {
            $this->predefined['time_to_own']
               = Html::computeGenericDateTimeSearch($this->predefined['time_to_own'], false);
         }

         // Compute internal_time_to_resolve
         if (isset($this->predefined['internal_time_to_resolve'])) {
            $this->predefined['internal_time_to_resolve']
               = Html::computeGenericDateTimeSearch($this->predefined['internal_time_to_resolve'], false);
         }
         if (isset($this->predefined['internal_time_to_own'])) {
            $this->predefined['internal_time_to_own']
               = Html::computeGenericDateTimeSearch($this->predefined['internal_time_to_own'], false);
         }

         // Compute date
         if (isset($this->predefined['date'])) {
            $this->predefined['date']
               = Html::computeGenericDateTimeSearch($this->predefined['date'], false);
         }
         return true;
      }
      return false;
   }
}
