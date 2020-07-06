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
 * Class PluginReleasesRelease
 */
class PluginReleasesRelease extends CommonITILObject {

   public    $dohistory         = true;
   static    $rightname         = 'plugin_releases_releases';
   protected $usenotepad        = true;
   static    $types             = [];
   public    $userlinkclass     = 'PluginReleasesRelease_User';
   public    $grouplinkclass    = 'PluginReleasesGroup_Release';
   public    $supplierlinkclass = 'PluginReleasesRelease_Supplier';

   // STATUS
   const TODO       = 1; // todo
   const DONE       = 2; // done
   const PROCESSING = 3; // processing
   const WAITING    = 4; // waiting
   const LATE       = 5; // late
   const DEF        = 6; // default

   const NEWRELEASE         = 7;
   const RELEASEDEFINITION  = 8; // default
   const DATEDEFINITION     = 9; // date definition
   const CHANGEDEFINITION   = 10; // changes defenition
   const RISKDEFINITION     = 11; // risks definition
   const ROLLBACKDEFINITION = 12; // rollbacks definition
   const TASKDEFINITION     = 13; // tasks definition
   const TESTDEFINITION     = 14; // tests definition
   const FINALIZE           = 15; // finalized
   const REVIEW             = 16; // reviewed
   const CLOSED             = 17; // closed
   const FAIL               = 18;


   //   static $typeslinkable = ["Computer"  => "Computer",
   //                            "Appliance" => "Appliance"];


   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Release', 'Releases', $nb, 'releases');
   }


   static function countForItem($ID, $class, $state = 0) {
      $dbu   = new DbUtils();
      $table = CommonDBTM::getTable($class);
      if ($state) {
         return $dbu->countElementsInTable($table,
                                           ["plugin_releases_releases_id" => $ID, "state" => 2]);
      }
      return $dbu->countElementsInTable($table,
                                        ["plugin_releases_releases_id" => $ID]);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (static::canView()) {
         switch ($item->getType()) {
            case __CLASS__ :
               $timeline    = $item->getTimelineItems();
               $nb_elements = count($timeline);

               $ong = [
                  1 => __("Processing release", 'releases') . " <sup class='tab_nb'>$nb_elements</sup>",
               ];

               return $ong;
            case "Change" :
               return self::createTabEntry(self::getTypeName(2), self::countItemForAChange($item));
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
         case "Change" :
            PluginReleasesChange_Release::showReleaseFromChange($item);
            break;
      }
      return true;
   }

   static function countItemForAChange($item) {
      $dbu   = new DbUtils();
      $table = CommonDBTM::getTable(PluginReleasesChange_Release::class);
      return $dbu->countElementsInTable($table,
                                        ["changes_id" => $item->getID()]);
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->defineDefaultObjectTabs($ong, $options);
      $this->addStandardTab('PluginReleasesChange_Release', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options); // todo hide in template
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('PluginReleasesRelease_Item', $ong, $options);

      if ($this->hasImpactTab()) {
         $this->addStandardTab('Impact', $ong, $options); // todo hide in template
      }
      $this->addStandardTab('PluginReleasesFinalization', $ong, $options);
      $this->addStandardTab('PluginReleasesReview', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }

   /**
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => self::getTypeName(2)
      ];

      $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('name'),
         'datatype'      => 'itemlink',
         'itemlink_type' => $this->getType()
      ];
      $tab[] = [
         'id'            => '2',
         'table'         => $this->getTable(),
         'field'         => 'content',
         'name'          => __('Description'),
         'massiveaction' => false,
         'datatype'      => 'text',
         'htmltext'      => true
      ];
      $tab[] = [
         'id'            => '3',
         'table'         => $this->getTable(),
         'field'         => 'date_preproduction',
         'name'          => __('Pre-production run date', 'releases'),
         'massiveaction' => false,
         'datatype'      => 'date'
      ];
      $tab[] = [
         'id'            => '4',
         'table'         => $this->getTable(),
         'field'         => 'is_recursive',
         'name'          => __('Number of risks', 'releases'),
         'massiveaction' => false,
         'datatype'      => 'specific'
      ];
      $tab[] = [
         'id'            => '5',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Number of tests', 'releases'),
         'massiveaction' => false,
         'datatype'      => 'specific'
      ];
      $tab[] = [
         'id'            => '6',
         'table'         => $this->getTable(),
         'field'         => 'service_shutdown',
         'name'          => __('Number of tasks', 'releases'),
         'massiveaction' => false,
         'datatype'      => 'specific'
      ];
      $tab[] = [
         'id'            => '7',
         'table'         => $this->getTable(),
         'field'         => 'status',
         'name'          => __('Status'),
         'massiveaction' => false,
         'datatype'      => 'specific'
      ];
      $tab[] = [
         'id'            => '8',
         'table'         => $this->getTable(),
         'field'         => 'date_production',
         'name'          => __('Production run date', 'releases'),
         'massiveaction' => false,
         'datatype'      => 'date'
      ];
      return $tab;

   }

   /**
    * display a value according to a field
    *
    * @param $field     String         name of the field
    * @param $values    String / Array with the value to display
    * @param $options   Array          of option
    *
    * @return a string
    **@since version 0.83
    *
    */
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'status':
            $var = "<span class='status'>";
            $var .= self::getStatusIcon($values["status"]);
            $var .= self::getStatus($values["status"]);
            $var .= "</span>";
            return $var;
            break;
         case 'service_shutdown':
            return self::countForItem($options["raw_data"]["id"], PluginReleasesDeploytask::class, 1) . ' / ' . self::countForItem($options["raw_data"]["id"], PluginReleasesDeploytask::class);
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
    * @param datas $input
    *
    * @return datas
    */
   function prepareInputForAdd($input) {


      $input = parent::prepareInputForAdd($input);

      if ((isset($input['target']) && empty($input['target'])) || !isset($input['target'])) {
         $input['target'] = [];
      }
      $input['target'] = json_encode($input['target']);
      if (!empty($input["date_preproduction"])
          && $input["date_preproduction"] != NULL
          && !empty($input["date_production"])
          && $input["date_production"] != NULL
          && $input["status"] < self::DATEDEFINITION) {

         $input['status'] = self::DATEDEFINITION;

      } else if (!empty($input["content"]) && $input["status"] < self::RELEASEDEFINITION) {

         $input['status'] = self::RELEASEDEFINITION;

      }
      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;

   }

   /**
    * @see CommonDBTM::post_clone
    **/
   function post_clone($source, $history) {
      //TODO imagine how to modify computer clone because elements are not DBConnexity
      parent::post_clone($source, $history);
      $relations_classes = [
         PluginReleasesRisk::class,
         PluginReleasesTest::class,
         PluginReleasesDeploytask::class,
         PluginReleasesRollback::class,
         PluginReleasesReview::class,
         PluginReleasesFinalization::class,
         Notepad::class,
         KnowbaseItem_Item::class,
         Document_Item::class

      ];

      $override_input['items_id'] = $this->getID();
      foreach ($relations_classes as $classname) {
         if (!is_a($classname, CommonDBConnexity::class, true)) {
            Toolbox::logWarning(
               sprintf(
                  'Unable to clone elements of class %s as it does not extends "CommonDBConnexity"',
                  $classname
               )
            );
            continue;
         } else {
            $relation_items = $classname::getItemsAssociatedTo($this->getType(), $source->getID());
            foreach ($relation_items as $relation_item) {
               $newId = $relation_item->clone($override_input, $history);
            }
         }


      }
   }


   /**
    * Actions done after the ADD of the item in the database
    *
    * @return void
    **/
   function post_addItem() {
      global $DB;
      if (isset($this->input["releasetemplates_id"])) {
         $template = new PluginReleasesReleasetemplate();
         $template->getFromDB($this->input["releasetemplates_id"]);
         $risks            = [];
         $releaseTest      = new PluginReleasesTest();
         $testTemplate     = new PluginReleasesTesttemplate();
         $releaseTask      = new PluginReleasesDeploytask();
         $taskTemplate     = new PluginReleasesDeploytasktemplate();
         $releaseRollback  = new PluginReleasesRollback();
         $rollbackTemplate = new PluginReleasesRollbacktemplate();
         $releaseRisk      = new PluginReleasesRisk();
         $riskTemplate     = new PluginReleasesRisktemplate();
         $itemLinkTemplate = new PluginReleasesReleasetemplate_Item();
         $itemLink         = new PluginReleasesRelease_Item();
         $risks            = $riskTemplate->find(["plugin_releases_releasetemplates_id" => $template->getID()]);
         $tests            = $testTemplate->find(["plugin_releases_releasetemplates_id" => $template->getID()]);
         $rollbacks        = $rollbackTemplate->find(["plugin_releases_releasetemplates_id" => $template->getID()]);
         $tasks            = $taskTemplate->find(["plugin_releases_releasetemplates_id" => $template->getID()], ["ASC" => "level"]);
         $items            = $itemLinkTemplate->find(["plugin_releases_releasetemplates_id" => $template->getID()]);
         $corresRisks      = [];
         $corresTests      = [];
         $corresRollbacks  = [];
         $corresTasks      = [];
         foreach ($risks as $risk) {
            $risk["plugin_releases_releases_id"] = $this->getID();
            unset($risk["date_mod"]);
            unset($risk["date_creation"]);
            unset($risk["state"]);
            $old_id = $risk["id"];
            unset($risk["id"]);
            $corresRisks[$old_id] = $releaseRisk->add($risk);
         }
         foreach ($tests as $test) {
            $test["plugin_releases_releases_id"] = $this->getID();
            unset($test["date_mod"]);
            unset($test["date_creation"]);
            unset($test["state"]);
            $old_id                           = $test["id"];
            $test["plugin_releases_risks_id"] = isset($corresRisks[$test["plugin_releases_risks_id"]]) ? $corresRisks[$test["plugin_releases_risks_id"]] : 0;
            unset($test["id"]);
            $corresTests[$old_id] = $releaseTest->add($test);

         }
         foreach ($tasks as $task) {
            $task["plugin_releases_releases_id"] = $this->getID();
            unset($task["date_mod"]);
            unset($task["date_creation"]);
            unset($task["state"]);
            $old_id                                 = $task["id"];
            $task["plugin_releases_risks_id"]       = isset($corresRisks[$task["plugin_releases_risks_id"]]) ? $corresRisks[$task["plugin_releases_risks_id"]] : 0;
            $task["plugin_releases_deploytasks_id"] = isset($corresTasks[$task["plugin_releases_deploytasktemplates_id"]]) ? $corresTasks[$task["plugin_releases_deploytasktemplates_id"]] : 0;
            unset($task["id"]);
            $corresTasks[$old_id] = $releaseTask->add($task);

         }
         foreach ($rollbacks as $rollback) {
            $rollback["plugin_releases_releases_id"] = $this->getID();
            unset($rollback["date_mod"]);
            unset($rollback["date_creation"]);
            unset($rollback["state"]);
            $old_id = $rollback["id"];
            unset($rollback["id"]);
            $corresRollbacks[$old_id] = $releaseRollback->add($rollback);
         }
         foreach ($items as $item) {
            $item["plugin_releases_releases_id"] = $this->getID();
            unset($item["id"]);
            $itemLink->add($item);

         }

      }
      if (isset($this->input["changes"])) {


         foreach ($this->input["changes"] as $change) {
            $release_change                      = new PluginReleasesChange_Release();
            $vals                                = [];
            $vals["changes_id"]                  = $change;
            $vals["plugin_releases_releases_id"] = $this->getID();
            $release_change->add($vals);
         }
      }
      //      $query = "INSERT INTO `glpi_plugin_release_globalstatues`
      //                             ( `plugin_release_releases_id`,`itemtype`, `status`)
      //                      VALUES (".$this->fields['id'].",'". PluginReleasesRisk::getType()."', 0),
      //                      (".$this->fields['id'].",'". PluginReleasesTest::getType()."', 0),
      //                      (".$this->fields['id'].",'". PluginReleasesRelease::getType()."', 0),
      //                      (".$this->fields['id'].",'". PluginReleasesDeploytask::getType()."', 0),
      //                      (".$this->fields['id'].",'PluginReleaseDate', 0),
      //                      (".$this->fields['id'].",'". PluginReleasesRollback::getType()."', 0)
      //                      ;";
      //      $DB->queryOrDie($query, "statues creation");
      parent::post_addItem();
      $relations_classes = [

         Notepad::class,
         KnowbaseItem_Item::class,
         Document_Item::class

      ];

      $override_input['items_id'] = $this->getID();
      $override_input['itemtype'] = $this->getType();
      if (isset($this->input["releasetemplates_id"])) {
         foreach ($relations_classes as $classname) {
            if (!is_a($classname, CommonDBConnexity::class, true)) {
               Toolbox::logWarning(
                  sprintf(
                     'Unable to clone elements of class %s as it does not extends "CommonDBConnexity"',
                     $classname
                  )
               );
               continue;
            } else {
               $relation_items = $classname::getItemsAssociatedTo($template->getType(), $template->getID());
               foreach ($relation_items as $relation_item) {
                  $newId = $relation_item->clone($override_input, 0);
               }
            }

         }
      }
   }

   /**
    * get the Ticket status list
    *
    * @param $withmetaforsearch boolean (false by default)
    *
    * @return array
    **/
   static function getAllStatusArray($releasestatus = false) {

      $tab = [
         self::NEWRELEASE         => _x('status', 'New'),
         self::RELEASEDEFINITION  => __('Release area defined', 'releases'),
         self::DATEDEFINITION     => __('Dates defined', 'releases'),
         self::CHANGEDEFINITION   => __('Changes defined', 'releases'),
         self::RISKDEFINITION     => __('Risks defined', 'releases'),
         self::ROLLBACKDEFINITION => __('Rollbacks defined', 'releases'),
         self::TASKDEFINITION     => __('Deployment tasks in progress', 'releases'),
         self::TESTDEFINITION     => __('Tests in progress', 'releases'),
         self::FINALIZE           => __('To Finalized', 'releases'),
         self::REVIEW             => __('Reviewed', 'releases'),
         self::CLOSED             => _x('status', 'End', 'releases'),
         self::FAIL               => __('Failed', 'releases')];

      return $tab;
   }

   /**
    * Get status icon
    *
    * @return string
    * @since 9.3
    *
    */
   public static function getStatusIcon($status) {
      $class = static::getStatusClass($status);
      $label = static::getStatus($status);
      return "<i class='$class' title='$label'></i>";
   }

   /**
    * Get ITIL object status Name
    *
    * @param integer $value status ID
    **@since 0.84
    *
    */
   static function getStatus($value) {

      $tab = static::getAllStatusArray(true);
      // Return $value if not defined
      return (isset($tab[$value]) ? $tab[$value] : $value);
   }

   /**
    * Get status class
    *
    * @return string
    * @since 9.3
    *
    */
   public static function getStatusClass($status) {
      $class = null;
      $solid = true;

      switch ($status) {
         case self::TODO :
            $class = 'circle';
            break;
         case self::DONE :
            $class = 'circle';
            //            $solid = false;
            break;
         case self::PROCESSING :
            $class = 'circle';
            break;
         case self::WAITING :
            $class = 'circle';
            break;
         case self::LATE :
            $class = 'circle';
            //            $solid = false;
            break;
         case self::DEF :
            $class = 'circle';
            break;
         case self::NEWRELEASE :
            $class = 'circle';
            break;
         case self::RELEASEDEFINITION :
            $class = 'circle';
            $solid = false;
            break;
         case self::DATEDEFINITION :
            $class = 'circle';
            $solid = false;
            break;
         case self::CHANGEDEFINITION :
            $class = 'circle';
            $solid = false;
            break;
         case self::RISKDEFINITION :
            $class = 'circle';
            $solid = false;
            break;
         case self::TESTDEFINITION :
            $class = 'circle';
            $solid = false;
            break;
         case self::TASKDEFINITION :
            $class = 'circle';
            $solid = false;
            break;
         case self::ROLLBACKDEFINITION :
            $class = 'circle';
            $solid = false;
            break;
         case self::FINALIZE :
            $class = 'circle';
            $solid = false;
            break;
         case self::REVIEW :
            $class = 'circle';
            $solid = false;
            break;
         case self::CLOSED :
            $class = 'circle';
            break;


         default:
            $class = 'circle';
            break;

      }

      return $class == null
         ? ''
         : 'releasestatus ' . ($solid ? 'fas fa-' : 'far fa-') . $class .
           " " . self::getStatusKey($status);
   }

   /**
    * Get status key
    *
    * @return string
    * @since 9.3
    *
    */
   public static function getStatusKey($status) {
      $key = '';
      switch ($status) {
         case self::DONE :
            $key = 'done';
            break;
         case self::TODO :
            $key = 'todo';
            break;
         case self::WAITING :
            $key = 'waiting';
            break;
         case self::PROCESSING :
            $key = 'inprogress';
            break;
         case self::LATE :
            $key = 'late';
            break;
         case self::DEF :
            $key = 'default';
            break;
         case self::NEWRELEASE :
            $key = 'newrelease';
            break;
         case self::RELEASEDEFINITION :
            $key = 'releasedef';
            break;
         case self::DATEDEFINITION :
            $key = 'datedef';
            break;
         case self::CHANGEDEFINITION :
            $key = 'changedef';
            break;
         case self::RISKDEFINITION :
            $key = 'riskdef';
            break;
         case self::TESTDEFINITION :
            $key = 'testdef';
            break;
         case self::TASKDEFINITION :
            $key = 'taskdef';
            break;
         case self::ROLLBACKDEFINITION :
            $key = 'rollbackdef';
            break;
         case self::FINALIZE :
            $key = 'finalize';
            break;
         case self::REVIEW :
            $key = 'review';
            break;
         case self::CLOSED :
            $key = 'closerelease';
            break;

      }
      return $key;
   }

   /**
    *
    * @param datas $input
    *
    * @return datas
    */
   function prepareInputForUpdate($input) {

      //      $input = parent::prepareInputForUpdate($input);
      if ((isset($input['target']) && empty($input['target'])) || !isset($input['target'])) {
         $input['target'] = [];
      }
      $input['target'] = json_encode($input['target']);
      if (!empty($input["date_preproduction"])
          && !empty($input["date_production"])
          && $input["status"] < self::DATEDEFINITION) {

         $input['status'] = self::DATEDEFINITION;

      } else if (!empty($input["content"])
                 && $input["status"] < self::RELEASEDEFINITION) {

         $input['status'] = self::RELEASEDEFINITION;

      }
      $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($input);
      if (isset($input['_itil_requester'])) {
         if (isset($input['_itil_requester']['_type'])) {
            $input['_itil_requester'] = [
                                           'type'                            => CommonITILActor::REQUESTER,
                                           $this->getForeignKeyField()       => $input['id'],
                                           '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                                           '_from_object'                    => true,
                                        ] + $input['_itil_requester'];

            switch ($input['_itil_requester']['_type']) {
               case "user" :
                  if (isset($input['_itil_requester']['use_notification'])
                      && is_array($input['_itil_requester']['use_notification'])) {
                     $input['_itil_requester']['use_notification'] = $input['_itil_requester']['use_notification'][0];
                  }
                  if (isset($input['_itil_requester']['alternative_email'])
                      && is_array($input['_itil_requester']['alternative_email'])) {
                     $input['_itil_requester']['alternative_email'] = $input['_itil_requester']['alternative_email'][0];
                  }

                  if (!empty($this->userlinkclass)) {
                     if (isset($input['_itil_requester']['alternative_email'])
                         && $input['_itil_requester']['alternative_email']
                         && !NotificationMailing::isUserAddressValid($input['_itil_requester']['alternative_email'])) {

                        $input['_itil_requester']['alternative_email'] = '';
                        Session::addMessageAfterRedirect(__('Invalid email address'), false, ERROR);
                     }

                     if ((isset($input['_itil_requester']['alternative_email'])
                          && $input['_itil_requester']['alternative_email'])
                         || ($input['_itil_requester']['users_id'] > 0)) {

                        $useractors = new $this->userlinkclass();
                        if (isset($input['_auto_update'])
                            || $useractors->can(-1, CREATE, $input['_itil_requester'])) {
                           $useractors->add($input['_itil_requester']);
                           $input['_forcenotif'] = true;
                        }
                     }
                  }
                  break;

               case "group" :
                  if (!empty($this->grouplinkclass)
                      && ($input['_itil_requester']['groups_id'] > 0)) {
                     $groupactors = new $this->grouplinkclass();
                     if (isset($input['_auto_update'])
                         || $groupactors->can(-1, CREATE, $input['_itil_requester'])) {
                        $groupactors->add($input['_itil_requester']);
                        $input['_forcenotif'] = true;
                     }
                  }
                  break;
            }
         }
      }

      if (isset($input['_itil_observer'])) {
         if (isset($input['_itil_observer']['_type'])) {
            $input['_itil_observer'] = [
                                          'type'                            => CommonITILActor::OBSERVER,
                                          $this->getForeignKeyField()       => $input['id'],
                                          '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                                          '_from_object'                    => true,
                                       ] + $input['_itil_observer'];

            switch ($input['_itil_observer']['_type']) {
               case "user" :
                  if (isset($input['_itil_observer']['use_notification'])
                      && is_array($input['_itil_observer']['use_notification'])) {
                     $input['_itil_observer']['use_notification'] = $input['_itil_observer']['use_notification'][0];
                  }
                  if (isset($input['_itil_observer']['alternative_email'])
                      && is_array($input['_itil_observer']['alternative_email'])) {
                     $input['_itil_observer']['alternative_email'] = $input['_itil_observer']['alternative_email'][0];
                  }

                  if (!empty($this->userlinkclass)) {
                     if (isset($input['_itil_observer']['alternative_email'])
                         && $input['_itil_observer']['alternative_email']
                         && !NotificationMailing::isUserAddressValid($input['_itil_observer']['alternative_email'])) {

                        $input['_itil_observer']['alternative_email'] = '';
                        Session::addMessageAfterRedirect(__('Invalid email address'), false, ERROR);
                     }
                     if ((isset($input['_itil_observer']['alternative_email'])
                          && $input['_itil_observer']['alternative_email'])
                         || ($input['_itil_observer']['users_id'] > 0)) {
                        $useractors = new $this->userlinkclass();
                        if (isset($input['_auto_update'])
                            || $useractors->can(-1, CREATE, $input['_itil_observer'])) {
                           $useractors->add($input['_itil_observer']);
                           $input['_forcenotif'] = true;
                        }
                     }
                  }
                  break;

               case "group" :
                  if (!empty($this->grouplinkclass)
                      && ($input['_itil_observer']['groups_id'] > 0)) {
                     $groupactors = new $this->grouplinkclass();
                     if (isset($input['_auto_update'])
                         || $groupactors->can(-1, CREATE, $input['_itil_observer'])) {
                        $groupactors->add($input['_itil_observer']);
                        $input['_forcenotif'] = true;
                     }
                  }
                  break;
            }
         }
      }

      if (isset($input['_itil_assign'])) {
         if (isset($input['_itil_assign']['_type'])) {
            $input['_itil_assign'] = [
                                        'type'                            => CommonITILActor::ASSIGN,
                                        $this->getForeignKeyField()       => $input['id'],
                                        '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                                        '_from_object'                    => true,
                                     ] + $input['_itil_assign'];

            if (isset($input['_itil_assign']['use_notification'])
                && is_array($input['_itil_assign']['use_notification'])) {
               $input['_itil_assign']['use_notification'] = $input['_itil_assign']['use_notification'][0];
            }
            if (isset($input['_itil_assign']['alternative_email'])
                && is_array($input['_itil_assign']['alternative_email'])) {
               $input['_itil_assign']['alternative_email'] = $input['_itil_assign']['alternative_email'][0];
            }

            switch ($input['_itil_assign']['_type']) {
               case "user" :
                  if (!empty($this->userlinkclass)
                      && ((isset($input['_itil_assign']['alternative_email'])
                           && $input['_itil_assign']['alternative_email'])
                          || $input['_itil_assign']['users_id'] > 0)) {
                     $useractors = new $this->userlinkclass();
                     if (isset($input['_auto_update'])
                         || $useractors->can(-1, CREATE, $input['_itil_assign'])) {
                        $useractors->add($input['_itil_assign']);
                        $input['_forcenotif'] = true;
                        if (((!isset($input['status'])
                              && in_array($this->fields['status'], $this->getNewStatusArray()))
                             || (isset($input['status'])
                                 && in_array($input['status'], $this->getNewStatusArray())))
                            && !$this->isStatusComputationBlocked($input)) {
                           if (in_array(self::ASSIGNED, array_keys($this->getAllStatusArray()))) {
                              $input['status'] = self::ASSIGNED;
                           }
                        }
                     }
                  }
                  break;

               case "group" :
                  if (!empty($this->grouplinkclass)
                      && ($input['_itil_assign']['groups_id'] > 0)) {
                     $groupactors = new $this->grouplinkclass();

                     if (isset($input['_auto_update'])
                         || $groupactors->can(-1, CREATE, $input['_itil_assign'])) {
                        $groupactors->add($input['_itil_assign']);
                        $input['_forcenotif'] = true;
                        if (((!isset($input['status'])
                              && (in_array($this->fields['status'], $this->getNewStatusArray())))
                             || (isset($input['status'])
                                 && (in_array($input['status'], $this->getNewStatusArray()))))
                            && !$this->isStatusComputationBlocked($input)) {
                           if (in_array(self::ASSIGNED, array_keys($this->getAllStatusArray()))) {
                              $input['status'] = self::ASSIGNED;
                           }
                        }
                     }
                  }
                  break;

               case "supplier" :
                  if (!empty($this->supplierlinkclass)
                      && ((isset($input['_itil_assign']['alternative_email'])
                           && $input['_itil_assign']['alternative_email'])
                          || $input['_itil_assign']['suppliers_id'] > 0)) {
                     $supplieractors = new $this->supplierlinkclass();
                     if (isset($input['_auto_update'])
                         || $supplieractors->can(-1, CREATE, $input['_itil_assign'])) {
                        $supplieractors->add($input['_itil_assign']);
                        $input['_forcenotif'] = true;
                        if (((!isset($input['status'])
                              && (in_array($this->fields['status'], $this->getNewStatusArray())))
                             || (isset($input['status'])
                                 && (in_array($input['status'], $this->getNewStatusArray()))))
                            && !$this->isStatusComputationBlocked($input)) {
                           if (in_array(self::ASSIGNED, array_keys($this->getAllStatusArray()))) {
                              $input['status'] = self::ASSIGNED;
                           }

                        }
                     }
                  }
                  break;
            }
         }
      }

      //      $this->addAdditionalActors($input);

      return $input;
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

   function prepareField($template_id) {
      $template = new PluginReleasesReleasetemplate();
      $template->getFromDB($template_id);

      foreach ($this->fields as $key => $field) {
         if ($key != "id") {
            $this->fields[$key] = $template->getField($key);
         }
      }
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

      if (!isset($options['template_preview'])) {
         $options['template_preview'] = 0;
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
      if (isset($options["template_id"]) && $options["template_id"] > 0) {
         $this->prepareField($options["template_id"]);
         echo Html::hidden("releasetemplates_id", ["value" => $options["template_id"]]);
      }


      $select_changes = [];
      if (isset($options["changes_id"])) {
         $select_changes = [$options["changes_id"]];
         if ((isset($options["template_id"]) && $options["template_id"] = 0) || !isset($options["template_id"])) {
            $c = new Change();
            if ($c->getFromDB($options["changes_id"])) {
               $this->fields["name"]        = $c->getField("name");
               $this->fields["content"]     = $c->getField("content");
               $this->fields["entities_id"] = $c->getField("entities_id");
            }

         }
      }

      // In percent
      $colsize1 = '13';
      $colsize2 = '37';

      echo "<tr class='tab_bg_1'>";
      echo "<th class='left' width='$colsize1%'>";
      echo __('Opening date');
      echo "</th>";
      echo "<td class='left' width='$colsize2%'>";
      $date = $this->fields["date"];
      if (!$ID) {
         $date = date("Y-m-d H:i:s");
      }
      Html::showDateTimeField(
         "date", [
                  'value'      => $date,
                  'maybeempty' => false,
                  'required'   => (!$ID)
               ]
      );
      echo "</td>";

      echo "<th width='$colsize1%'>" . __('By') . "</th>";
      echo "<td class='left'>";
      User::dropdown(['name'   => 'users_id_recipient',
                      'value'  => $this->fields["users_id_recipient"],
                      'entity' => $this->fields["entities_id"],
                      'right'  => 'all']);
      echo "</td></tr>";
      $showuserlink = 0;
      if (User::canView()) {
         $showuserlink = 1;
      }
      if ($ID) {
         echo "<tr class='tab_bg_1'>";
         echo "<th>" . __('Last update') . "</th>";
         echo "<td >" . Html::convDateTime($this->fields["date_mod"]) . "\n";
         if ($this->fields['users_id_lastupdater'] > 0) {
            printf(__('%1$s: %2$s'), __('By'),
                   getUserName($this->fields["users_id_lastupdater"], $showuserlink));
         }
         echo "</td><th></th><td></td></tr>";
      }

      echo "</table>";

      echo "<table class='tab_cadre_fixe' id='mainformtable2'>";
      echo "<tr class='tab_bg_1'>";

      echo "<th width='$colsize1%'>" . __('Status') . "</th>";
      echo "<td width='$colsize2%' >";
      Dropdown::showFromArray('status', self::getAllStatusArray(false), ['value' => $this->fields["status"]]);
      echo "</td>";

      if (!$ID) {
         echo "<th width='$colsize1%'>";
         echo __('Associated change', 'releases');
         echo "</th>";
         echo "<td>";
         $change  = new Change();
         $changes = $change->find(['entities_id' => $_SESSION['glpiactive_entity'], 'status' => Change::getNotSolvedStatusArray()]);
         $list    = [];
         foreach ($changes as $ch) {
            $list[$ch["id"]] = $ch["name"];
         }
         Dropdown::showFromArray("changes", $list, ["multiple" => true, "values" => $select_changes]);
         //      Change::dropdown([
         ////            'used' => $used,
         //         'entity' => $_SESSION['glpiactive_entity'],'condition'=>['status'=>Change::getNotSolvedStatusArray()]]);
         echo "</td>";
      } else {
         echo "<th width='$colsize1%'></th>";
         echo "<td></td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>" . __('Pre-production planned run date', 'releases') . "</th>";
      echo "<td>";
      Html::showDateField("date_preproduction", ["value" => $this->fields["date_preproduction"]]);
      echo "</td>";
      echo "<th>" . __('Production planned run date', 'releases') . "</th>";
      echo "<td>";
      Html::showDateField("date_production", ["value" => $this->fields["date_production"]]);
      echo "</td>";

      echo "</tr>";

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

      echo "</table>";
      $this->showActorsPartForm($ID, $options);
      echo "<table class='tab_cadre_fixe' id='mainformtable3'>";

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
                                    $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/showShutdownDetails.php", ["value" => '__VALUE__']);

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
                       $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/changeTarget.php",
                       ['type'         => $this->fields["communication_type"],
                        'current_type' => $this->fields["communication_type"],
                        'values'       => $targets],
                       true);
      Ajax::updateItemOnSelectEvent("dropdown_communication_type" . $addrand, "targets",
                                    $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/changeTarget.php",
                                    ['type'         => '__VALUE__',
                                     'current_type' => $this->fields["communication_type"],
                                     'values'       => $targets],
                                    true);
      echo "</td>";

      echo "<th></th>";
      echo "<td></td>";

      echo "</tr>";


      if ($ID != "") {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4'>";
         echo " <div class=\"container-fluid\">
                              <ul class=\"list-unstyled multi-steps\">";

         for ($i = 7; $i <= 17; $i++) {
            $class = "";
            //
            //            if ($value["ranking"] < $ranking) {
            ////                     $class = "class = active2";
            //
            //            } else
            if ($this->fields["status"] == $i - 1) {
               //               $class = "class='current'";
               $class = "class='is-active'";
            }
            $name = self::getStatus($i);
            echo "<li $class>" . $name . "</li>";
         }
         echo " </ul></div>";
         echo "</td>";
         echo "</tr>";
      }

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Return a field Value if exists
    *
    * @param string $field field name
    *
    * @return mixed value of the field / false if not exists
    **/
   function getField($field) {

      if (array_key_exists($field, $this->fields)) {
         return $this->fields[$field];
      }
      return NOT_AVAILABLE;
   }

   /**
    * @return mixed
    */
   function getNameAlert() {
      return $this->fields["name"];
   }

   /**
    * @return mixed
    */
   function getContentAlert() {
      return $this->fields["service_shutdown_details"];
   }


   /**
    * @param $state
    *
    * @return string
    */
   public static function getStateItem($state) {
      switch ($state) {
         case 0:
            //            return __("Waiting","releases");
            return "<span><i class=\"fas fa-4x fa-hourglass-half\"></i></span>";
            break;
         case 1:
            //            return __("Done");
            return "<span><i class=\"fas fa-4x fa-check\"></i></span>";
            break;
      }
   }

   /**
    * Displays the form at the top of the timeline.
    * Includes buttons to add items to the timeline, new item form, and approbation form.
    *
    * @param integer $rand random value used by JavaScript function names
    *
    * @return void
    * @since 9.4.0
    *
    */
   function showTimelineForm($rand) {
      global $CFG_GLPI;

      $objType    = static::getType();
      $foreignKey = static::getForeignKeyField();

      //check sub-items rights
      $tmp       = [$foreignKey => $this->getID()];
      $riskClass = "PluginReleasesRisk";
      $risk      = new $riskClass;
      $risk->getEmpty();
      $risk->fields['itemtype'] = $objType;
      $risk->fields['items_id'] = $this->getID();


      $rollbackClass = "PluginReleasesRollback";
      $rollback      = new $rollbackClass;
      $rollback->getEmpty();
      $rollback->fields['itemtype'] = $objType;
      $rollback->fields['items_id'] = $this->getID();

      $taskClass = "PluginReleasesDeploytask";
      $task      = new $taskClass;
      $task->getEmpty();
      $task->fields['itemtype'] = $objType;
      $task->fields['items_id'] = $this->getID();

      $testClass = "PluginReleasesTest";
      $test      = new $testClass;
      $test->getEmpty();
      $test->fields['itemtype'] = $objType;
      $test->fields['items_id'] = $this->getID();

      $canadd_risk = $risk->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                                                               array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));

      $canadd_rollback = $rollback->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                                                                       array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));

      $canadd_task = $task->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                                                               array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));

      $canadd_test = $test->can(-1, CREATE, $tmp) && !in_array($this->fields["status"], $this->getSolvedStatusArray());

      // javascript function for add and edit items
      $objType    = self::getType();
      $foreignKey = self::getForeignKeyField();

      echo "<script type='text/javascript' >
      function change_task_state(items_id, target, itemtype) {
         $.post('" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/timeline.php',
                {'action':     'change_task_state',
                  'items_id':   items_id,
                  'itemtype':   itemtype,
                  'parenttype': '$objType',
                  '$foreignKey': " . $this->fields['id'] . "
                })
                .done(function(response) {
                  $(target).removeClass('state_1 state_2')
                           .addClass('state_'+response.state)
                           .attr('title', response.label);
                });
      }
      function done_fail(items_id, target, itemtype,newStatus) {
         $.post('" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/timeline.php',
                {'action':     'done_fail',
                  'items_id':   items_id,
                  'itemtype':   itemtype,
                  'parenttype': '$objType',
                  '$foreignKey': " . $this->fields['id'] . ",
                  'newStatus': newStatus 
                })
                .done(function(response) {
                console.log('done '+response.state)
                $(target).parent().children().css('color','gray');//add gray to done and fail
                          
                if(response.state == 2){
//                console.log($(target).parent().children('i[data-type=\"done\"]'));
                   $(target).parent().children('i[data-type=\"done\"]').css('color','forestgreen');//green to done
                }else if (response.state == 3){
//                console.log($(target).parent().children('i[data-type=\"fail\"]'));
                   $(target).parent().children('i[data-type=\"fail\"]').css('color','firebrick');//red to fail
                }
                  
                });
      }

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

      $style = "color:firebrick;";
      $fa    = "fa-times-circle";
      if ($riskClass::countForItem($release) == $riskClass::countDoneForItem($release)) {
         $style = "color:forestgreen;";
         $fa    = "fa-check-circle";
      }
      echo "<i class='fas $fa' style='margin-right: 10px;$style'></i>";

      echo "<li class='rollback'>";
      echo "<a href='#'  data-type='rollback' title='" . $rollbackClass::getTypeName(2) .
           "'><i class='fas fa-undo-alt'></i>" . $rollbackClass::getTypeName(2) . " (" . $rollbackClass::countForItem($release) . ")</a></li>";
      if ($canadd_rollback) {
         echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$rollbackClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }


      $style = "color:firebrick;";
      $fa    = "fa-times-circle";
      if ($rollbackClass::countForItem($release) == $rollbackClass::countDoneForItem($release)) {
         $style = "color:forestgreen;";
         $fa    = "fa-check-circle";
      }
      echo "<i class='fas $fa' style='margin-right: 10px;$style'></i>";

      echo "<li class='task'>";
      echo "<a href='#'   data-type='task' title='" . _n('Deploy task', 'Deploy tasks', 2, 'releases') .
           "'><i class='fas fa-check-square'></i>" . _n('Deploy task', 'Deploy tasks', 2, 'releases') . " (" . $taskClass::countForItem($release) . ")</a></li>";
      if ($canadd_task) {
         echo "<i class='fas fa-plus-circle pointer'  onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$taskClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }

      $style = "color:firebrick;";
      $fa    = "fa-times-circle";
      if ($taskClass::countForItem($release) == $taskClass::countDoneForItem($release)) {
         $style = "color:forestgreen;";
         $fa    = "fa-check-circle";
      }
      echo "<i class='fas $fa' style='margin-right: 10px;$style'></i>";

      echo "<li class='test'>";
      echo "<a href='#'  data-type='test' title='" . $testClass::getTypeName(2) .
           "'><i class='fas fa-check'></i>" . $testClass::getTypeName(2) . " (" . $testClass::countForItem($release) . ")</a></li>";
      if ($canadd_test) {
         echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$testClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }
      $style = "color:firebrick;";
      $fa    = "fa-times-circle";
      if ($testClass::countForItem($release) == $testClass::countDoneForItem($release)) {
         $style = "color:forestgreen;";
         $fa    = "fa-check-circle";
      }
      echo "<i class='fas $fa' style='margin-right: 10px;$style'></i>";

      echo "<li class='followup'>" .
           "<a href='#'  data-type='ITILFollowup' title='" . __("Followup") . "'>"
           . "<i class='far fa-comment'></i>" . __("Followup") . "</a></li>";
      if ($canadd_test) {
         echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"ITILFollowup\");' style='margin-right: 10px;margin-left: -5px;'></i>";
      }
      echo "</ul>"; // timeline_choices
      echo "</div>";

      echo "<div class='clear'>&nbsp;</div>";

      echo "</div>"; //end timeline_form

      echo "<div class='ajax_box' id='viewitem" . $this->fields['id'] . "$rand'></div>\n";
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
         if ($item['itiltype'] == "Followup") {
            $user_position = 'right';
         } else {
            $user_position = 'left'; // default position
         }

         //         if (isset($item_i['timeline_position'])) {
         //            switch ($item_i['timeline_position']) {
         //               case self::TIMELINE_LEFT:
         //                  $user_position = 'left';
         //                  break;
         //               case self::TIMELINE_MIDLEFT:
         //                  $user_position = 'left middle';
         //                  break;
         //               case self::TIMELINE_MIDRIGHT:
         //                  $user_position = 'right middle';
         //                  break;
         //               case self::TIMELINE_RIGHT:
         //                  $user_position = 'right';
         //                  break;
         //            }
         //         }


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
         if ($item['itiltype'] == "Followup") {
            if (isset($item['itiltype'])) {
               $class .= " ITIL{$item['itiltype']}";
            } else {
               $class .= " {$item['type']}";
            }
         } else {
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
            if (isset($item_i["name"])) {
               $content = "<h2>" . $item_i['name'] . "  </h2>" . $item_i['content'];
            } else {
               $content = $item_i['content'];
            }

            $content = Toolbox::getHtmlToDisplay($content);
            $content = autolink($content, false);

            $long_text = "";
            if ((substr_count($content, "<br") > 30) || (strlen($content) > 2000)) {
               $long_text = "long_text";
            }

            echo "<div class='item_content $long_text'>";
            echo "<p>";
            if (isset($item_i['state'])) {

               if ($item['type'] == PluginReleasesTest::getType() || $item['type'] == pluginReleasesDeploytask::getType()) {
                  $onClickDone = "onclick='done_fail(" . $item_i['id'] . ", this,\"" . $item['type'] . "\",2)'";
                  $onClickFail = "onclick='done_fail(" . $item_i['id'] . ", this,\"" . $item['type'] . "\",3)'";
                  if (!$item_i['can_edit']) {
                     $onClick = "style='cursor: not-allowed;'";
                  }
                  if ($item_i['state'] == 1) {
                     echo "<span>";
                     $style = "color:gray;";
                     $fa    = "fa-times-circle fa-2x";
                     echo "<i data-type='fail' class='fas $fa' style='margin-right: 10px;$style' $onClickFail></i>";
                     $style = "color:gray;";
                     $fa    = "fa-check-circle fa-2x";

                     echo "<i data-type='done' class='fas $fa' style='margin-right: 10px;$style' $onClickDone></i>";
                     echo "</span>";
                  } else if ($item_i['state'] == 2) {
                     echo "<span>";
                     $style = "color:gray;";
                     $fa    = "fa-times-circle fa-2x";
                     echo "<i data-type='fail' class='fas $fa' style='margin-right: 10px;$style' $onClickFail></i>";
                     $style = "color:forestgreen;";
                     $fa    = "fa-check-circle fa-2x";

                     echo "<i data-type='done' class='fas $fa' style='margin-right: 10px;$style' $onClickDone></i>";
                     echo "</span>";
                  } else {
                     echo "<span>";
                     $style = "color:firebrick;";
                     $fa    = "fa-times-circle fa-2x";
                     echo "<i data-type='fail' class='fas $fa' style='margin-right: 10px;$style' $onClickFail></i>";
                     $style = "color:gray;";
                     $fa    = "fa-check-circle fa-2x";

                     echo "<i data-type='done' class='fas $fa' style='margin-right: 10px;$style' $onClickDone></i>";
                     echo "</span>";
                  }
               } else {
                  $onClick = "onclick='change_task_state(" . $item_i['id'] . ", this,\"" . $item['type'] . "\")'";
                  if (!$item_i['can_edit']) {
                     $onClick = "style='cursor: not-allowed;'";
                  }
                  echo "<span class='state state_" . $item_i['state'] . "'
                           $onClick
                           title='" . Planning::getState($item_i['state']) . "'>";
                  echo "</span>";
               }


            }
            echo "</p>";

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
      if (isset($_SESSION["releases"][Session::getLoginUserID()])) {
         $catToLoad = $_SESSION["releases"][Session::getLoginUserID()];
      } else {
         $catToLoad = 'risk';
      }

      unset($_SESSION["releases"][Session::getLoginUserID()]);
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
      // end timeline
      echo "</div>"; // h_item $user_position
   }


   function getTimelineItems() {

      $objType    = self::getType();
      $foreignKey = self::getForeignKeyField();

      $timeline = [];

      $riskClass     = 'PluginReleasesRisk';
      $risk_obj      = new $riskClass;
      $rollbackClass = 'PluginReleasesRollback';
      $rollback_obj  = new $rollbackClass;
      $taskClass     = 'PluginReleasesDeploytask';
      $task_obj      = new $taskClass;
      $testClass     = 'PluginReleasesTest';
      $test_obj      = new $testClass;

      //checks rights
      $restrict_risk = $restrict_rollback = $restrict_task = $restrict_test = [];
      //      $restrict_risk['itemtype'] = static::getType();
      //      $restrict_risk['items_id'] = $this->getID();
      $user = new User();

      $fupClass     = 'ITILFollowup';
      $followup_obj = new $fupClass;


      //checks rights
      $restrict_fup = $restrict_task = [];
      if (!Session::haveRight("followup", ITILFollowup::SEEPRIVATE)) {
         $restrict_fup = [
            'OR' => [
               'is_private' => 0,
               'users_id'   => Session::getLoginUserID()
            ]
         ];
      }

      $restrict_fup['itemtype'] = static::getType();
      $restrict_fup['items_id'] = $this->getID();
      //add followups to timeline
      if ($followup_obj->canview()) {
         $followups = $followup_obj->find(['items_id' => $this->getID()] + $restrict_fup, ['date DESC', 'id DESC']);
         foreach ($followups as $followups_id => $followup) {
            $followup_obj->getFromDB($followups_id);
            $followup['can_edit'] = $followup_obj->canUpdateItem();;
            $timeline[$followup['date'] . "_followup_" . $followups_id] = ['type'     => $fupClass,
                                                                           'item'     => $followup,
                                                                           'itiltype' => 'Followup'];
         }
      }
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

   /**
    * Dropdown of releases items state
    *
    * @param $name   select name
    * @param $value  default value (default '')
    * @param $display  display of send string ? (true by default)
    * @param $options  options
    **/
   static function dropdownStateItem($name, $value = '', $display = true, $options = []) {

      $values = [static::TODO => __('To do'),
                 static::DONE => __('Done'),
                 static::FAIL => __('Failed', 'releases')];

      return Dropdown::showFromArray($name, $values, array_merge(['value'   => $value,
                                                                  'display' => $display], $options));
   }

   /**
    * Dropdown of releases state
    *
    * @param $name   select name
    * @param $value  default value (default '')
    * @param $display  display of send string ? (true by default)
    * @param $options  options
    **/
   static function dropdownState($name, $value = '', $display = true, $options = []) {

      $values = [static::TODO       => __('To do'),
                 static::DONE       => __('Done'),
                 static::PROCESSING => __('Processing'),
                 static::WAITING    => __("Waiting"),
                 static::LATE       => __("Late"),
                 static::DEF        => __("Default"),
      ];

      return Dropdown::showFromArray($name, $values, array_merge(['value'   => $value,
                                                                  'display' => $display], $options));
   }


   static function showCreateRelease($item) {

      $item_t    = new PluginReleasesReleasetemplate();
      $dbu       = new DbUtils();
      $condition = $dbu->getEntitiesRestrictCriteria($item_t->getTable());
      PluginReleasesReleasetemplate::dropdown(["comments"   => false,
                                               "addicon"    => false,
                                               "emptylabel" => __("From this change", "releases"),
                                               "name"       => "releasetemplates_id"] + $condition);
      $url = PluginReleasesRelease::getFormURL();
      echo "<a  id='link' href='$url?changes_id=" . $item->getID() . "'>";
      $url    = $url . "?changes_id=" . $item->getID() . "&template_id=";
      $script = "
      var link = function (id,linkurl) {
         var link = linkurl+id;
         $(\"a#link\").attr(\"href\", link);
      };
      $(\"select[name='releasetemplates_id']\").change(function() {
         link($(\"select[name='releasetemplates_id']\").val(),'$url');
         });";


      echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
      echo "<br/><br/>";
      echo __("Create a release", 'releases');
      echo "</a>";
      //      echo "<form name='form' method='post' action='".$this->getFormURL()."'  enctype=\"multipart/form-data\">";
      //      echo Html::hidden("changes_id",["value"=>$item->getID()]);
      ////      echo '<a class="vsubmit"> '.__("Create a releases from this change",'release').'</a>';
      //      echo Html::submit(__("Create a release from this change",'releases'), ['name' => 'createRelease']);
      //      Html::closeForm();
   }

   function getLinkedItems(bool $addNames = true): array {
      global $DB;

      $assets = $DB->request([
                                'SELECT' => ['itemtype', 'items_id'],
                                'FROM'   => 'glpi_plugin_releases_releases_items',
                                'WHERE'  => ['plugin_releases_releases_id' => $this->getID()]
                             ]);

      $assets = iterator_to_array($assets);

      if ($addNames) {
         foreach ($assets as $key => $asset) {
            if (!class_exists($asset['itemtype'])) {
               //ignore if class does not exists (maybe a plugin)
               continue;
            }
            /** @var CommonDBTM $item */
            $item = new $asset['itemtype'];
            $item->getFromDB($asset['items_id']);

            // Add name
            $assets[$key]['name'] = $item->fields['name'];
         }
      }

      return $assets;
   }

   /**
    * Should impact tab be displayed? Check if there is a valid linked item
    *
    * @return boolean
    */
   protected function hasImpactTab() {
      foreach ($this->getLinkedItems() as $linkedItem) {
         $class = $linkedItem['itemtype'];
         if (Impact::isEnabled($class) && Session::getCurrentInterface() === "central") {
            return true;
         }
      }
      return false;
   }

   /**
    * @return array
    */
   static function getMenuContent() {

      $menu['title']           = self::getMenuName(2);
      $menu['page']            = self::getSearchURL(false);
      $menu['links']['search'] = self::getSearchURL(false);

      $menu['links']['template'] = "/plugins/releases/front/releasetemplate.php";
      //      $menu['links']['template'] = "/front/setup.templates.php?itemtype=PluginReleasesRelease&add=0";
      $menu['icon'] = static::getIcon();
      if (self::canCreate()) {
         $dbu       = new DbUtils();
         $template  = new PluginReleasesReleasetemplate();
         $condition = $dbu->getEntitiesRestrictCriteria($template->getTable(),'','',true);
         $templates = $template->find($condition);
         if (empty($templates)) {
            $menu['links']['add'] = self::getFormURL(false);
         } else {
            $menu['links']['add'] = "/plugins/releases/front/creationrelease.php";
         }
      }


      return $menu;
   }


   static function getIcon() {
      return "fas fa-tags";
   }

   static function getDefaultValues($entity = 0) {
      global $CFG_GLPI;

      if (is_numeric(Session::getLoginUserID(false))) {
         $users_id_requester = Session::getLoginUserID();
         $users_id_assign    = Session::getLoginUserID();
         // No default requester if own ticket right = tech and update_ticket right to update requester
         //         if (Session::haveRightsOr(self::$rightname, [UPDATE, self::OWN]) && !$_SESSION['glpiset_default_requester']) {
         //            $users_id_requester = 0;
         //         }
         if (!$_SESSION['glpiset_default_tech']) {
            $users_id_assign = 0;
         }
         $entity      = $_SESSION['glpiactive_entity'];
         $requesttype = $_SESSION['glpidefault_requesttypes_id'];
      } else {
         $users_id_requester = 0;
         $users_id_assign    = 0;
         $requesttype        = $CFG_GLPI['default_requesttypes_id'];
      }
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
              'status'                     => self::NEWRELEASE,
              'service_shutdown'           => false,
              'service_shutdown_details'   => '',
              'hour_type'                  => 0,
              'communication'              => false,
              'communication_type'         => false,
              'target'                     => [],
              'locations_id'               => 0,
              'risk_state'                 => 0,
              'test_state'                 => 0,
              'rollback_state'             => 0,
      ];
   }

   static function isAllowedStatus($old, $new) {
      if ($old != self::CLOSED && $old != self::REVIEW) {
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


      $tab = [self::CLOSED, self::REVIEW];
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
      return [self::CLOSED, self::WAITING];
   }


   static function failOrNot($itemtype, $items_id) {
      $self = new self();
      $self->getFromDB($items_id);
      return $itemtype::countFailForItem($self);
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
      $this->filterTimeline();
   }
}

