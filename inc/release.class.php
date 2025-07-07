<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.

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

use Glpi\Application\View\TemplateRenderer;

/**
 * Class PluginReleasesRelease
 */
class PluginReleasesRelease extends CommonITILObject
{

    public $dohistory = true;
    static $rightname = 'plugin_releases_releases';
    protected $usenotepad = true;
    static $types = [];
    public $userlinkclass = 'PluginReleasesRelease_User';
    public $grouplinkclass = 'PluginReleasesGroup_Release';
    public $supplierlinkclass = 'PluginReleasesRelease_Supplier';

    // STATUS


    const NEWRELEASE = 1;
    const RELEASEDEFINITION = 2; // default
    const DATEDEFINITION = 3; // date definition
    const CHANGEDEFINITION = 4; // changes defenition
    const RISKDEFINITION = 5; // risks definition
    const ROLLBACKDEFINITION = 6; // rollbacks definition
    const TASKDEFINITION = 7; // tasks definition
    const TESTDEFINITION = 8; // tests definition
    const FINALIZE = 9; // finalized
    const REVIEW = 10; // reviewed
    const CLOSED = 11; // closed
    const FAIL = 12;


    /**
     * @param int $nb
     *
     * @return translated
     */
    static function getTypeName($nb = 0)
    {
        return _n('Release', 'Releases', $nb, 'releases');
    }


    static function countForItem($ID, $class, $state = 0)
    {
        $dbu = new DbUtils();
        $table = CommonDBTM::getTable($class);
        if ($state) {
            return $dbu->countElementsInTable(
                $table,
                ["plugin_releases_releases_id" => $ID, "state" => $state]
            );
        }
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releases_id" => $ID]
        );
    }


    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (static::canView()) {
            switch ($item->getType()) {
                //            case __CLASS__ :
                //               $timeline    = $item->getTimelineItems();
                //               $nb_elements = count($timeline);
                //
                //               $ong = [
                //                  1 => __("Processing release", 'releases') . " <span class='badge'>$nb_elements</span>",
                //               ];
                //               $timeline    = $this->getTimelineItems(['with_logs' => false]);
                //               $nb_elements = count($timeline);
                //               $label = $this->getTypeName(1);
                //               if ($nb_elements > 0) {
                //                  $label .= " <span class='badge'>$nb_elements</span>";
                //               }
                //               return $ong;
                case "Change" :
                    return self::createTabEntry(self::getTypeName(2), self::countItemForAChange($item));
            }
        }
        return '';
    }


    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            //         case __CLASS__ :
            //            switch ($tabnum) {
            //               case 1 :
            //                  if (!$withtemplate) {
            //                     echo "<div class='timeline_box'>";
            //                     $rand = mt_rand();
            //                     $item->showTimelineForm($rand);
            //                     $item->showTimeline($rand);
            //                     echo "</div>";
            //                  } else {
            //                     echo "<div class='timeline_box'>";
            //                     $rand = mt_rand();
            //                     $item->showTimeline($rand);
            //                     echo "</div>";
            //                  }
            //
            //                  break;
            //            }
            //            break;
            case "Change" :
                PluginReleasesChange_Release::showReleaseFromChange($item);
                break;
        }
        return true;
    }

    static function countItemForAChange($item)
    {
        $dbu = new DbUtils();
        $table = CommonDBTM::getTable(PluginReleasesChange_Release::class);
        return $dbu->countElementsInTable(
            $table,
            ["changes_id" => $item->getID()]
        );
    }

    function defineTabs($options = [])
    {
        $ong = [];
        //      $this->addStandardTab(self::getType(), $ong, $options);
        $this->addDefaultFormTab($ong);
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
    function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(2)
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'name',
            'name' => __('name'),
            'datatype' => 'itemlink',
            'itemlink_type' => $this->getType()
        ];

        $tab[] = [
            'id' => '2',
            'table' => self::getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'massiveaction' => false,
            'datatype' => 'number'
        ];

        $tab[] = [
            'id' => '19',
            'table' => $this->getTable(),
            'field' => 'content',
            'name' => __('Description'),
//         'massiveaction' => false,
            'datatype' => 'text',
//         'htmltext'      => true
        ];

        $tab[] = [
            'id' => '18',
            'table' => $this->getTable(),
            'field' => 'date_preproduction',
            'name' => __('Pre-production run date', 'releases'),
            'massiveaction' => false,
            'datatype' => 'datetime'
        ];
        $tab[] = [
            'id' => '4',
            'table' => 'glpi_plugin_releases_risks',
            'field' => 'id',
            'name' => _x('quantity', 'Number of risks', 'releases'),
            'datatype' => 'count',
            'forcegroupby' => true,
            'usehaving' => true,
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'child',
            ]
        ];
        $tab[] = [
            'id' => '5',
            'table' => 'glpi_plugin_releases_rollbacks',
            'field' => 'id',
            'name' => _x('quantity', 'Number of rollbacks', 'releases'),
            'datatype' => 'count',
            'forcegroupby' => true,
            'usehaving' => true,
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'child',
            ]
        ];
        $tab[] = [
            'id' => '6',
            'table' => 'glpi_plugin_releases_tests',
            'field' => 'id',
            'name' => _x('quantity', 'Number of tests', 'releases'),
            'massiveaction' => false,
            'datatype' => 'count',
            'forcegroupby' => true,
            'usehaving' => true,
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'child',
            ]
        ];
        $tab[] = [
            'id' => '7',
            'table' => 'glpi_plugin_releases_deploytasks',
            'field' => 'id',
            'name' => _x('quantity', 'Number of tasks', 'releases'),
            'massiveaction' => false,
            'datatype' => 'count',
            'forcegroupby' => true,
            'usehaving' => true,
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'child',
            ]
        ];
        $tab[] = [
            'id' => '8',
            'table' => $this->getTable(),
            'field' => 'status',
            'name' => __('Status'),
            'massiveaction' => false,
            'datatype' => 'specific'
        ];
        $tab[] = [
            'id' => '9',
            'table' => $this->getTable(),
            'field' => 'date_production',
            'name' => __('Production run date', 'releases'),
            'massiveaction' => false,
            'datatype' => 'datetime'
        ];
        $tab[] = [
            'id' => '10',
            'table' => $this->getTable(),
            'field' => 'service_shutdown',
            'name' => __('Service shutdown', 'releases'),
            'massiveaction' => false,
            'datatype' => 'bool'
        ];
        $tab[] = [
            'id' => '11',
            'table' => $this->getTable(),
            'field' => 'service_shutdown_details',
            'name' => __('Service shutdown details', 'releases'),
            'massiveaction' => false,
            'datatype' => 'text',
            'htmltext' => true
        ];
        $tab[] = [
            'id' => '12',
            'table' => $this->getTable(),
            'field' => 'communication',
            'name' => __('Communication', 'releases'),
            'massiveaction' => false,
            'datatype' => 'bool'
        ];
        $types = [
            'Entity' => 'Entity',
            'Group' => 'Group',
            'Profile' => 'Profile',
            'User' => 'User',
            'Location' => 'Location'
        ];
        $tab[] = [
            'id' => '13',
            'table' => $this->getTable(),
            'field' => 'communication_type',
            'name' => __('Communication type', 'releases'),
            'massiveaction' => false,
            'datatype' => 'itemtypename',
            'itemtype_list' => $types,
        ];
        $tab[] = [
            'id' => '14',
            'table' => $this->getTable(),
            'field' => 'target',
            'name' => _n('Target', 'Targets', 2),
            'massiveaction' => false,
            'datatype' => 'specific'
        ];
        $tab[] = [
            'id' => '15',
            'table' => $this->getTable(),
            'field' => 'date_mod',
            'name' => __('Last update'),
            'datatype' => 'datetime',
            'massiveaction' => false
        ];

        $tab[] = [
            'id' => '16',
            'table' => $this->getTable(),
            'field' => 'date_creation',
            'name' => __('Creation date'),
            'datatype' => 'datetime',
            'massiveaction' => false
        ];
        $tab[] = [
            'id' => '17',
            'table' => $this->getTable(),
            'field' => 'date_end',
            'name' => __('Closing date'),
            'datatype' => 'datetime',
            'massiveaction' => false
        ];


        $tab[] = [
            'id' => '80',
            'table' => 'glpi_entities',
            'field' => 'completename',
            'name' => __('Entity'),
            'datatype' => 'dropdown'
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());
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
    static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'status':
//            $var = "<span class='status'>";
                $var = self::getStatusIcon($values["status"]);
                $var .= self::getStatus($values["status"]);
//            $var .= "</span>";
                return $var;
                break;
            //         case 'nb_tasks':
            //            return self::countForItem($options["raw_data"]["id"], PluginReleasesDeploytask::class,PluginReleasesDeploytask::DONE)
            //                   . ' / ' . self::countForItem($options["raw_data"]["id"], PluginReleasesDeploytask::class);
            //            break;
            //         case 'nb_risks':
            //            $self = new self();
            //            $self->getFromDB($options["raw_data"]["id"]);
            //            return PluginReleasesRisk::countDoneForItem($self) . " / ".PluginReleasesRisk::countForItem($self);
            //            break;
            //         case 'nb_rollbacks':
            //            $self = new self();
            //            $self->getFromDB($options["raw_data"]["id"]);
            //            return PluginReleasesRollback::countDoneForItem($self) . " / ".PluginReleasesRollback::countForItem($self);
            //            break;
            //         case 'nb_tests':
            //            $self = new self();
            //            $self->getFromDB($options["raw_data"]["id"]);
            //            return PluginReleasesTest::countDoneForItem($self) . " / ".PluginReleasesTest::countForItem($self);
            //            break;
            case 'communication_type':
                if ($values["communication_type"] == "0" || $values["communication_type"] == "ALL") {
                    return " ";
                }
                return $values["communication_type"]::getTypeName();
                break;
            case 'target':
                $self = new self();
                if (isset($options["raw_data"]["id"])) {
                    $self->getFromDB($options["raw_data"]["id"]);
                } else {
                    $self->getFromDB($_REQUEST["id"]);
                }

                if ($self->fields["communication_type"] == "0" || $values["target"] == "[]") {
                    return " ";
                }
                if ($self->fields["communication_type"] == "User") {
                    $text = "";
                    $user = new User();
                    $items = json_decode($values["target"]);
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $user->getFromDB($item);
                            $text .= $user->getFriendlyName() . "<br />";
                        }
                    }
                    return $text;
                }
                if ($self->fields["communication_type"] == "Profile") {
                    $text = "";
                    $profile = new Profile();
                    $items = json_decode($values["target"]);
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $profile->getFromDB($item);
                            $text .= $profile->getFriendlyName() . "<br />";
                        }
                    }
                    return $text;
                }
                if ($self->fields["communication_type"] == "Group") {
                    $text = "";
                    $group = new Group();
                    $items = json_decode($values["target"]);
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $group->getFromDB($item);
                            $text .= $group->getFriendlyName() . "<br />";
                        }
                    }
                    return $text;
                }
                if ($self->fields["communication_type"] == "Entity") {
                    $text = "";
                    $entity = new Entity();
                    $items = json_decode($values["target"]);
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $entity->getFromDB($item);
                            $text .= $entity->getFriendlyName() . "<br />";
                        }
                    }
                    return $text;
                }
                if ($self->fields["communication_type"] == "Location") {
                    $text = "";
                    $location = new Location();
                    $items = json_decode($values["target"]);
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $location->getFromDB($item);
                            $text .= $location->getFriendlyName() . "<br />";
                        }
                    }
                    return $text;
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * @param datas $input
     *
     * @return datas
     */
    function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);

        if ((isset($input['target']) && empty($input['target'])) || !isset($input['target'])) {
            $input['target'] = [];
        }
        $input['target'] = json_encode($input['target']);
        if (!empty($input["date_preproduction"])
            && $input["date_preproduction"] != null
            && !empty($input["date_production"])
            && $input["date_production"] != null
            && $input["status"] < self::DATEDEFINITION) {
            $input['status'] = self::DATEDEFINITION;
        } elseif (!empty($input["content"]) && $input["status"] < self::RELEASEDEFINITION) {
            $input['status'] = self::RELEASEDEFINITION;
        }
        if (empty($input["date_preproduction"])) {
            $input["date_preproduction"] = null;
        }
        if (empty($input["date_production"])) {
            $input["date_production"] = null;
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
    function post_clone($source, $history)
    {
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
                $item = new $classname();
                $items = $item->find(['plugin_releases_releases_id' => $source->getID()]);
                foreach ($items as $input) {
                    unset($input["id"]);
                    $input["plugin_releases_releases_id"] = $this->getID();
                    $item->add($input);
                }
                //            Toolbox::logWarning(
                //               sprintf(
                //                  'Unable to clone elements of class %s as it does not extends "CommonDBConnexity"',
                //                  $classname
                //               )
                //            );
                //            continue;

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
    function post_addItem()
    {
        global $DB, $CFG_GLPI;

        if (isset($this->input["releasetemplates_id"])) {
            $template = new PluginReleasesReleasetemplate();
            $template->getFromDB($this->input["releasetemplates_id"]);
            $risks = [];
            $releaseTest = new PluginReleasesTest();
            $testTemplate = new PluginReleasesTesttemplate();
            $releaseTask = new PluginReleasesDeploytask();
            $taskTemplate = new PluginReleasesDeploytasktemplate();
            $releaseRollback = new PluginReleasesRollback();
            $rollbackTemplate = new PluginReleasesRollbacktemplate();
            $releaseRisk = new PluginReleasesRisk();
            $riskTemplate = new PluginReleasesRisktemplate();
            $itemLinkTemplate = new PluginReleasesReleasetemplate_Item();
            $itemLink = new PluginReleasesRelease_Item();
            $risks = $riskTemplate->find(["plugin_releases_releasetemplates_id" => $template->getID()]);
            $tests = $testTemplate->find(["plugin_releases_releasetemplates_id" => $template->getID()]);
            $rollbacks = $rollbackTemplate->find(["plugin_releases_releasetemplates_id" => $template->getID()]);
            $tasks = $taskTemplate->find(["plugin_releases_releasetemplates_id" => $template->getID()],
                ["ASC" => "level"]);
            $items = $itemLinkTemplate->find(["plugin_releases_releasetemplates_id" => $template->getID()]);
            $corresRisks = [];
            $corresTests = [];
            $corresRollbacks = [];
            $corresTasks = [];

            foreach ($risks as $risk) {
                $risk["items_id"] = $this->getID();
                unset($risk["date_mod"]);
                unset($risk["date_creation"]);
                unset($risk["state"]);
                $old_id = $risk["id"];
                unset($risk["id"]);
                $risk["name"] = Toolbox::addslashes_deep($risk["name"]);
                $risk["content"] = Toolbox::addslashes_deep($risk["content"]);
                $corresRisks[$old_id] = $releaseRisk->add($risk);
            }
            foreach ($tests as $test) {
                $test["items_id"] = $this->getID();
                unset($test["date_mod"]);
                unset($test["date_creation"]);
                unset($test["state"]);
                $old_id = $test["id"];
                $test["name"] = Toolbox::addslashes_deep($test["name"]);
                $test["content"] = Toolbox::addslashes_deep($test["content"]);
                $test["plugin_releases_risks_id"] = $corresRisks[$test["plugin_releases_risks_id"]] ?? 0;
                unset($test["id"]);
                $corresTests[$old_id] = $releaseTest->add($test);
            }
            foreach ($tasks as $task) {
                $task["items_id"] = $this->getID();
                unset($task["date_mod"]);
                unset($task["date_creation"]);
                unset($task["state"]);
                $old_id = $task["id"];
                $task["name"] = Toolbox::addslashes_deep($task["name"]);
                $task["content"] = Toolbox::addslashes_deep($task["content"]);
                $task["plugin_releases_risks_id"] = $corresRisks[$task["plugin_releases_risks_id"]] ?? 0;
                $task["plugin_releases_deploytasks_id"] = $corresTasks[$task["plugin_releases_deploytasktemplates_id"]] ?? 0;
                unset($task["id"]);
                $corresTasks[$old_id] = $releaseTask->add($task);
            }
            foreach ($rollbacks as $rollback) {
                $rollback["items_id"] = $this->getID();
                unset($rollback["date_mod"]);
                unset($rollback["date_creation"]);
                unset($rollback["state"]);
                $old_id = $rollback["id"];
                unset($rollback["id"]);
                $rollback["name"] = Toolbox::addslashes_deep($rollback["name"]);
                $rollback["content"] = Toolbox::addslashes_deep($rollback["content"]);
                $corresRollbacks[$old_id] = $releaseRollback->add($rollback);
            }
            foreach ($items as $item) {
                $item["plugin_releases_releases_id"] = $this->getID();
                unset($item["id"]);
                $itemLink->add($item);
            }
        }
        if (isset($this->input["changes_id"])) {
            $release_change = new PluginReleasesChange_Release();
            $vals["changes_id"] = $this->input["changes_id"];
            $vals["plugin_releases_releases_id"] = $this->getID();
            $release_change->add($vals);
            //         foreach ($this->input["changes"] as $change) {
            //
            //         }
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
        if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
            NotificationEvent::raiseEvent('newRelease', $this);
        }
    }

    function post_updateItem($history = 1)
    {
        global $CFG_GLPI;
        parent::post_updateItem($history); // TODO: Change the autogenerated stub
        if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
            NotificationEvent::raiseEvent('updateRelease', $this);
        }
    }

    /**
     * Actions done after the DELETE (mark as deleted) of the item in the database
     *
     * @return void
     **/
    function post_deleteItem()
    {
        global $CFG_GLPI;
        parent::post_deleteItem(); // TODO: Change the autogenerated stub
        if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
            NotificationEvent::raiseEvent('updateRelease', $this);
        }
    }

    /**
     * get the Ticket status list
     *
     * @param $withmetaforsearch boolean (false by default)
     *
     * @return array
     **/
    static function getAllStatusArray($releasestatus = false)
    {
        $tab = [
            self::NEWRELEASE => _x('status', 'New'),
            self::RELEASEDEFINITION => __('Release area defined', 'releases'),
            self::DATEDEFINITION => __('Dates defined', 'releases'),
            self::CHANGEDEFINITION => __('Changes defined', 'releases'),
            self::RISKDEFINITION => __('Risks defined', 'releases'),
            self::ROLLBACKDEFINITION => __('Rollbacks defined', 'releases'),
            self::TASKDEFINITION => __('Deployment tasks in progress', 'releases'),
            self::TESTDEFINITION => __('Tests in progress', 'releases'),
            self::FINALIZE => __('To finalized', 'releases'),
            self::REVIEW => __('Reviewed', 'releases'),
            self::CLOSED => _x('status', 'End', 'releases'),
            self::FAIL => __('Failed', 'releases')
        ];

        return $tab;
    }

    /**
     * Get status icon
     *
     * @return string
     * @since 9.3
     *
     */
    public static function getStatusIcon($status)
    {
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
    static function getStatus($value)
    {
        $tab = static::getAllStatusArray(true);
        // Return $value if not defined
        return (isset($tab[$value]) ? $tab[$value] : $value);
    }

    /**
     * Get status class
     *
     * @param $status
     *
     * @return string
     * @since 9.3
     */
    public static function getStatusClass($status)
    {
        $class = null;
        $solid = true;

        switch ($status) {
            case self::DATEDEFINITION:
            case self::CHANGEDEFINITION:
            case self::RISKDEFINITION:
            case self::TESTDEFINITION:
            case self::TASKDEFINITION:
            case self::ROLLBACKDEFINITION:
            case self::REVIEW:
            case self::FINALIZE:
            case self::RELEASEDEFINITION :
                $class = 'circle';
                $solid = false;
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
    public static function getStatusKey($status)
    {
        $key = '';
        switch ($status) {
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
            case self::FAIL :
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
    function prepareInputForUpdate($input)
    {
        //      $input = parent::prepareInputForUpdate($input);
        if ((isset($input['target']) && empty($input['target']))
            || (!isset($input['target']) && isset($input["communication_type"]) && $input["communication_type"] != $this->fields["communication_type"])) {
            $input['target'] = [];
        }
        if (isset($input["communication_type"]) && isset($input['target'])) {
            $input['target'] = json_encode($input['target']);
        }

        if (!empty($input["date_preproduction"])
            && !empty($input["date_production"])
            && $input["status"] < self::DATEDEFINITION) {
            $input['status'] = self::DATEDEFINITION;
        } elseif (!empty($input["content"])
            && $input["status"] < self::RELEASEDEFINITION) {
            $input['status'] = self::RELEASEDEFINITION;
        }
        $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($input);
        if (isset($input['_itil_requester'])) {
            if (isset($input['_itil_requester']['_type'])) {
                $input['_itil_requester'] = [
                        'type' => CommonITILActor::REQUESTER,
                        $this->getForeignKeyField() => $input['id'],
                        '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                        '_from_object' => true,
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
                                && !NotificationMailing::isUserAddressValid(
                                    $input['_itil_requester']['alternative_email']
                                )) {
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
                        'type' => CommonITILActor::OBSERVER,
                        $this->getForeignKeyField() => $input['id'],
                        '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                        '_from_object' => true,
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
                                && !NotificationMailing::isUserAddressValid(
                                    $input['_itil_observer']['alternative_email']
                                )) {
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
                        'type' => CommonITILActor::ASSIGN,
                        $this->getForeignKeyField() => $input['id'],
                        '_do_not_compute_takeintoaccount' => $do_not_compute_takeintoaccount,
                        '_from_object' => true,
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
                                //                        if (((!isset($input['status'])
                                //                              && in_array($this->fields['status'], $this->getNewStatusArray()))
                                //                             || (isset($input['status'])
                                //                                 && in_array($input['status'], $this->getNewStatusArray())))
                                //                            && !$this->isStatusComputationBlocked($input)) {
                                //                           if (in_array(self::ASSIGNED, array_keys($this->getAllStatusArray()))) {
                                //                              $input['status'] = self::ASSIGNED;
                                //                           }
                                //                        }
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
                                //                        if (((!isset($input['status'])
                                //                              && (in_array($this->fields['status'], $this->getNewStatusArray())))
                                //                             || (isset($input['status'])
                                //                                 && (in_array($input['status'], $this->getNewStatusArray()))))
                                //                            && !$this->isStatusComputationBlocked($input)) {
                                //                           if (in_array(self::ASSIGNED, array_keys($this->getAllStatusArray()))) {
                                //                              $input['status'] = self::ASSIGNED;
                                //                           }
                                //                        }
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
                                //                        if (((!isset($input['status'])
                                //                              && (in_array($this->fields['status'], $this->getNewStatusArray())))
                                //                             || (isset($input['status'])
                                //                                 && (in_array($input['status'], $this->getNewStatusArray()))))
                                //                            && !$this->isStatusComputationBlocked($input)) {
                                //                           if (in_array(self::ASSIGNED, array_keys($this->getAllStatusArray()))) {
                                //                              $input['status'] = self::ASSIGNED;
                                //                           }
                                //                        }
                            }
                        }
                        break;
                }
            }
        }

        //      $this->addAdditionalActors($input);

        return $input;
    }


    function prepareField($template_id)
    {
    }

    public function getTimelineItemtypes(): array
    {
        $itemtypes = [];
        $solved_statuses = static::getSolvedStatusArray();
        $closed_statuses = static::getClosedStatusArray();
        $solved_closed_statuses = array_merge($solved_statuses, $closed_statuses);

        $obj_type = static::getType();
        $foreign_key = static::getForeignKeyField();

        //check sub-items rights
        $tmp = [$foreign_key => $this->getID()];

        $fup = new ITILFollowup();
        $fup->getEmpty();
        $fup->fields['itemtype'] = $obj_type;
        //      $fup->fields['items_id'] = $this->getID();
        $canadd_fup = $fup->can(-1, CREATE, $tmp) && !in_array($this->fields["status"], $solved_closed_statuses, true);

        if ($canadd_fup) {
            $itemtypes['answer'] = [
                'type' => 'ITILFollowup',
                'class' => 'ITILFollowup',
                'icon' => 'ti ti-message-circle',
                'label' => _x('button', 'Add a followup', 'releases'),
                'template' => 'components/itilobject/timeline/form_followup.html.twig',
                'item' => $fup
            ];
        }

        $risk = new PluginReleasesRisk();

        $canadd_risk = $risk->can(-1, CREATE, $tmp) && !in_array(
                $this->fields["status"],
                $solved_closed_statuses,
                true
            );

        if ($canadd_risk) {
            $itemtypes['Risk'] = [
                'type' => 'PluginReleasesRisk',
                'class' => 'PluginReleasesRisk',
                'icon' => 'ti ti-bug',
                'label' => _x('button', 'Add a risk', 'releases'),
                'template' => '@releases/form_risk.html.twig',
                'item' => $risk
            ];
        }

        $rollback = new PluginReleasesRollback();

        $canadd_roll = $rollback->can(-1, CREATE, $tmp) && !in_array(
                $this->fields["status"],
                $solved_closed_statuses,
                true
            );

        if ($canadd_roll) {
            $itemtypes['Rollback'] = [
                'type' => 'PluginReleasesRollback',
                'class' => 'PluginReleasesRollback',
                'icon' => 'ti ti-arrow-back-up',
                'label' => _x('button', 'Add a rollback', 'releases'),
                'template' => '@releases/form_rollback.html.twig',
                'item' => $rollback
            ];
        }

        $task = new PluginReleasesDeploytask();

        $canadd_task = $task->can(-1, CREATE, $tmp) && !in_array(
                $this->fields["status"],
                $solved_closed_statuses,
                true
            );

        if ($canadd_task) {
            $itemtypes['Deploytask'] = [
                'type' => 'PluginReleasesDeploytask',
                'class' => 'PluginReleasesDeploytask',
                'icon' => 'ti ti-checkbox',
                'label' => _x('button', 'Add a deployment task', 'releases'),
                'template' => '@releases/form_deploytask.html.twig',
                'item' => $task
            ];
        }

        $test = new PluginReleasesTest();

        $canadd_test = $test->can(-1, CREATE, $tmp) && !in_array(
                $this->fields["status"],
                $solved_closed_statuses,
                true
            );

        if ($canadd_test) {
            $itemtypes['Test'] = [
                'type' => 'PluginReleasesTest',
                'class' => 'PluginReleasesTest',
                'icon' => 'ti ti-check',
                'label' => _x('button', 'Add a test', 'releases'),
                'template' => '@releases/form_test.html.twig',
                'item' => $test
            ];
        }

        return $itemtypes;
    }

    function showForm($ID, $options = [])
    {
        if (!static::canView()) {
            return false;
        }

        $default_values = self::getDefaultValues();

        // Restore saved value or override with page parameter
        $saved = $this->restoreInput();

        // Restore saved values and override $this->fields
        $this->restoreSavedValues($saved);

        // Set default options
        if ($ID == 0) {
            foreach ($default_values as $key => $val) {
                if (!isset($options[$key])) {
                    if (isset($saved[$key])) {
                        $options[$key] = $saved[$key];
                    } else {
                        $options[$key] = $val;
                    }
                }
            }

            if (isset($options["template_id"]) && $options["template_id"] > 0) {
                $template = new PluginReleasesReleasetemplate();
                $template->getFromDB($options["template_id"]);

                foreach ($this->fields as $key => $field) {
                    if ($key != "id"
                        && $key != "entities_id"
                        && $template->getField($key) != NOT_AVAILABLE) {
                        $this->fields[$key] = $template->getField($key);
                        $options[$key] = $template->getField($key);
                    }
                }

                $this->fields["status"] = self::NEWRELEASE;
                $release_user = new PluginReleasesReleasetemplate_User();
                $release_supplier = new PluginReleasesReleasetemplate_Supplier();
                $group_release = new PluginReleasesGroup_Releasetemplate();
                $users = $release_user->find(['plugin_releases_releasetemplates_id' => $options["template_id"]]);
                $suppliers = $release_supplier->find(['plugin_releases_releasetemplates_id' => $options["template_id"]]
                );
                $groups = $group_release->find(['plugin_releases_releasetemplates_id' => $options["template_id"]]);
                foreach ($users as $user) {
                    $options["_users_id_" . self::getActorFieldNameType($user["type"])] = $user["users_id"];
                }
                foreach ($suppliers as $supplier) {
                    $options["_suppliers_id_" . self::getActorFieldNameType(
                        $supplier["type"]
                    )] = $supplier["suppliers_id"];
                }
                foreach ($groups as $group) {
                    $options["_groups_id_" . self::getActorFieldNameType($group["type"])] = $group["groups_id"];
                }
//            echo Html::hidden("releasetemplates_id", ["value" => $options["template_id"]]);
            }


            $select_changes = [];
            if (isset($options["changes_id"])) {
                $select_changes = [$options["changes_id"]];
                $c = new Change();
                if ($c->getFromDB($options["changes_id"])) {
                    if ((isset($options["template_id"]) && $options["template_id"] = 0) || !isset($options["template_id"])) {
                        $this->fields["name"] = $c->getField("name");
                        $options["name"] = $c->getField("name");
                        $this->fields["content"] = Toolbox::stripslashes_deep($c->getField("content"));
                        $options["content"] = Toolbox::stripslashes_deep($c->getField("content"));
                    }
                    $options['entities_id'] = $c->getField("entities_id");
                    $this->fields["entities_id"] = $c->getField("entities_id");
                }
            }
        }

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $this->check(-1, CREATE, $options);
        }

        $userentities = [];
        if (!$ID) {
            $userentities = $this->getEntitiesForRequesters($options);

            if (
                count($userentities) > 0
                && !in_array($this->fields["entities_id"], $userentities)
            ) {
                // If entity is not in the list of user's entities,
                // then use as default value the first value of the user's entites list
                $first_entity = current($userentities);
                $this->fields["entities_id"] = $first_entity;
                // Pass to values
                $options['entities_id'] = $first_entity;
            }
        }

        $canupdate = !$ID || (Session::getCurrentInterface() == "central" && $this->canUpdateItem());

        if (!$this->isNewItem()) {
            $options['formtitle'] = sprintf(
                __('%1$s - ID %2$d'),
                $this->getTypeName(1),
                $ID
            );
            //set ID as already defined
            $options['noid'] = true;
        }

//      if (!isset($options['template_preview'])) {
//         $options['template_preview'] = 0;
//      }
//
//      if((isset($this->fields['entities_id'])) && $this->fields['entities_id'] != 0 || (isset($options['entities_id']) && $options['entities_id'] != 0)){
//          $crit = ($ID ? $this->fields['entities_id'] : $options['entities_id']);
//      }else{
//          $crit = "";
//      }
//
//      // Load template if available :
//      $tt = new PluginReleasesReleasetemplate();
//
//      // Predefined fields from template : reset them
//      if (isset($options['_predefined_fields'])) {
//         $options['_predefined_fields']
//            = Toolbox::decodeArrayFromInput($options['_predefined_fields']);
//      } else {
//         $options['_predefined_fields'] = [];
//      }
//
//      // Store predefined fields to be able not to take into account on change template
//      // Only manage predefined values on ticket creation
//      $predefined_fields = [];
//      $tpl_key           = $this->getTemplateFormFieldName();
//      if (!$ID) {
//         if (isset($tt->predefined) && count($tt->predefined)) {
//            foreach ($tt->predefined as $predeffield => $predefvalue) {
//               if (isset($default_values[$predeffield])) {
//                  // Is always default value : not set
//                  // Set if already predefined field
//                  // Set if ticket template change
//                  if (
//                     ((count($options['_predefined_fields']) == 0)
//                      && ($options[$predeffield] == $default_values[$predeffield]))
//                     || (isset($options['_predefined_fields'][$predeffield])
//                         && ($options[$predeffield] == $options['_predefined_fields'][$predeffield]))
//                     || (isset($options[$tpl_key])
//                         && ($options[$tpl_key] != $tt->getID()))
//                     // user pref for requestype can't overwrite requestype from template
//                     // when change category
//                     || (($predeffield == 'requesttypes_id')
//                         && empty($saved))
//                     || (isset($ticket) && $options[$predeffield] == $ticket->getField($predeffield))
//                     || (isset($problem) && $options[$predeffield] == $problem->getField($predeffield))
//                  ) {
//                     // Load template data
//                     $options[$predeffield]           = $predefvalue;
//                     $this->fields[$predeffield]      = $predefvalue;
//                     $predefined_fields[$predeffield] = $predefvalue;
//                  }
//               }
//            }
//            // All predefined override : add option to say predifined exists
//            if (count($predefined_fields) == 0) {
//               $predefined_fields['_all_predefined_override'] = 1;
//            }
//         } else { // No template load : reset predefined values
//            if (count($options['_predefined_fields'])) {
//               foreach ($options['_predefined_fields'] as $predeffield => $predefvalue) {
//                  if ($options[$predeffield] == $predefvalue) {
//                     $options[$predeffield] = $default_values[$predeffield];
//                  }
//               }
//            }
//         }
//      }
//
//      foreach ($default_values as $name => $value) {
//         if (!isset($options[$name])) {
//            if (isset($saved[$name])) {
//               $options[$name] = $saved[$name];
//            } else {
//               $options[$name] = $value;
//            }
//         }
//      }
//
//      // Put ticket template on $options for actors
//      $options[str_replace('s_id', '', $tpl_key)] = $tt;
//
//      if ($options['template_preview']) {
//         // Add all values to fields of tickets for template preview
//         foreach ($options as $key => $val) {
//            if (!isset($this->fields[$key])) {
//               $this->fields[$key] = $val;
//            }
//         }
//      }
        $tt = new ChangeTemplate();

        $predefined_fields = $this->setPredefinedFields($tt, $options, self::getDefaultValues());


        TemplateRenderer::getInstance()->display('@releases/layout.html.twig', [
            'item' => $this,
            'timeline_itemtypes' => $this->getTimelineItemtypes(),
            'legacy_timeline_actions' => $this->getLegacyTimelineActionsHTML(),
            'params' => $options,
            'entities_id' => $ID ? $this->fields['entities_id'] : $options['entities_id'],
            'timeline' => $this->getTimelineItems(),
            'itiltemplate_key' => self::getTemplateFormFieldName(),
            'itiltemplate' => $tt,
            'predefined_fields' => Toolbox::prepareArrayForInput($predefined_fields),
            'canupdate' => $canupdate,
            'canpriority' => $canupdate,
            'canassign' => $canupdate,
            'root_release' => PLUGIN_RELEASES_WEBDIR,
            'userentities' => $userentities,
            'has_pending_reason' => false,
        ]);
        //       In percent
        //      $colsize1 = '13';
        //      $colsize2 = '37';

        //            echo "<tr class='tab_bg_1'>";
        //            echo "<th class='left' width='$colsize1%'>";
        //            echo __('Opening date');
        //            echo "</th>";
        //            echo "<td class='left' width='$colsize2%'>";
        //            $date = $this->fields["date"];
        //            if (!$ID) {
        //               $date = date("Y-m-d H:i:s");
        //            }
        //            Html::showDateTimeField(
        //               "date", [
        //                        'value'      => $date,
        //                        'maybeempty' => false,
        //                        'required'   => (!$ID)
        //                     ]
        //            );
        //            echo "</td>";
        //
        //            echo "<th width='$colsize1%'>" . __('By') . "</th>";
        //            echo "<td class='left'>";
        //            User::dropdown(['name'   => 'users_id_recipient',
        //                            'value'  => $this->fields["users_id_recipient"],
        //                            'entity' => $this->fields["entities_id"],
        //                            'right'  => 'all']);
        //            echo "</td></tr>";
        //            $showuserlink = 0;
        //            if (User::canView()) {
        //               $showuserlink = 1;
        //            }
        //            if ($ID) {
        //               echo "<tr class='tab_bg_1'>";
        //               echo "<th>" . __('Last update') . "</th>";
        //               echo "<td >" . Html::convDateTime($this->fields["date_mod"]) . "\n";
        //               if ($this->fields['users_id_lastupdater'] > 0) {
        //                  printf(__('%1$s: %2$s'), __('By'),
        //                         getUserName($this->fields["users_id_lastupdater"], $showuserlink));
        //               }
        //               echo "</td><th></th><td></td></tr>";
        //            }

        //            echo "</table>";

        //            echo "<table class='tab_cadre_fixe' id='mainformtable2'>";
        //            echo "<tr class='tab_bg_1'>";
        //
        //            echo "<th width='$colsize1%'>" . __('Status') . "</th>";
        //            echo "<td width='$colsize2%' >";
        //            Dropdown::showFromArray('status', self::getAllStatusArray(false), ['value' => $this->fields["status"]]);
        //            echo "</td>";

        //            if (empty($ID) || $ID < 0) {
        //               echo "<th width='$colsize1%'>";
        //               echo __('Associated change', 'releases');
        //               echo "</th>";
        //               echo "<td>";
        //               $change                  = new Change();
        //               $condition["status"]     = Change::getNotSolvedStatusArray();
        //               $condition["is_deleted"] = 0;
        //               $condition[]             = getEntitiesRestrictCriteria($change->getTable(), '', $options["entities_id"], true);
        //               $changes                 = $change->find($condition);
        //               $list                    = [];
        //               foreach ($changes as $ch) {
        //                  $list[$ch["id"]] = $ch["name"];
        //               }
        //               Dropdown::showFromArray("changes", $list, ["multiple" => true, "values" => $select_changes]);
        //               //      Change::dropdown([
        //               ////            'used' => $used,
        //               //         'entity' => $_SESSION['glpiactive_entity'],'condition'=>['status'=>Change::getNotSolvedStatusArray()]]);
        //               echo "</td>";
        //            } else {
        //               echo "<th width='$colsize1%'></th>";
        //               echo "<td></td>";
        //            }
        //            echo "</tr>";

        //            echo "<tr class='tab_bg_1'>";
        //            echo "<th>" . __('Pre-production planned date', 'releases') . "</th>";
        //            echo "<td>";
        //            Html::showDateTimeField("date_preproduction", ["value" => $this->fields["date_preproduction"]]);
        //            echo "</td>";
        //            echo "<th>" . __('Production planned date', 'releases') . "</th>";
        //            echo "<td>";
        //            Html::showDateTimeField("date_production", ["value" => $this->fields["date_production"]]);
        //            echo "</td>";
        //
        //            echo "</tr>";
        //
        //            echo "<tr class='tab_bg_1'>";
        //            echo "<th>" . __('Location') . "</th>";
        //            echo "<td >";
        //            Dropdown::show(Location::getType(), ["name"  => "locations_id",
        //                                                 "value" => $this->fields["locations_id"]]);
        //            echo "</td>";
        //            echo "<th>" . __('Non-working hours', 'releases') . "</th>";
        //            echo "<td >";
        //            Dropdown::showYesNo("hour_type", $this->fields["hour_type"]);
        //            echo "</td>";
        //            echo "</tr>";
        //
        //            echo "</table>";
        //      $this->showActorsPartForm($ID, $options);
        //            echo "<table class='tab_cadre_fixe' id='mainformtable3'>";
        //
        //            echo "<tr class='tab_bg_1'>";
        //            echo "<th style='width:$colsize1%'>" . __('Title') . "</th>";
        //            echo "<td colspan='3'>";
        //            $opt = [
        //               'value'     => $this->fields['name'],
        //               'maxlength' => 250,
        //               'style'     => 'width:98%',
        //            ];
        //            echo Html::input("name", $opt);
        //            echo "</td>";
        //            echo "</tr>";
        //
        //            echo "<tr class='tab_bg_1'>";
        //            echo "<th width='$colsize1%'>" . __('Release area', 'releases') . "</th>";
        //            echo "<td colspan='3'>";
        //            Html::textarea(["name"              => "content",
        //                            "enable_richtext"   => true,
        //                            'enable_fileupload' => false,
        //                            'enable_images'     => false,
        //                            "value"             => $this->fields["content"]]);
        //            echo "</td>";
        //            echo "</tr>";


        //            echo "<tr class='tab_bg_1'>";
        //            echo "<th>" . __('Service shutdown', 'releases') . "</th>";
        //            echo "<td width='$colsize1%'>";
        //            $rand = mt_rand();
        //            Dropdown::showYesNo("service_shutdown", $this->fields["service_shutdown"], -1, ["rand" => $rand]);
        //            echo "</td>";
        //            echo "<td colspan='2' name='fakeupdate' id='fakeupdate'></td>";
        //            echo "</tr>";
        //
        //            $hidden = "";
        //            if ($this->fields["service_shutdown"] == 0) {
        //               $hidden = "hidden='true'";
        //            }
        //
        //            echo "<tr id='shutdowndetails' class='tab_bg_1' $hidden >";
        //            Ajax::updateItemOnSelectEvent("dropdown_service_shutdown$rand", "fakeupdate",
        //                                          PLUGIN_RELEASES_WEBDIR . "/ajax/showShutdownDetails.php", ["value" => '__VALUE__']);

        //            echo "<th>" . __('Service shutdown details', 'releases') . "</th>";
        //            echo "<td colspan='3'>";
        //            Html::textarea(["name"              => "service_shutdown_details",
        //                            "enable_richtext"   => true,
        //                            'enable_fileupload' => false,
        //                            'enable_images'     => false,
        //                            "value"             => $this->fields["service_shutdown_details"]]);
        //            echo "</td>";
        //            echo "</tr>";
        //
        //            echo "<tr class='tab_bg_1'>";
        //            echo "<th width='$colsize1%'>" . __('Communication', 'releases') . "</th>";
        //            echo "<td width='$colsize2%'>";
        //            Dropdown::showYesNo("communication", $this->fields["communication"]);
        //            echo "</td>";

        //            echo "<th width='$colsize1%'>" . __('Communication type', 'releases') . "</th>";
        //            echo "<td>";
        //            $types   = ['Entity'   => 'Entity',
        //                        'Group'    => 'Group',
        //                        'Profile'  => 'Profile',
        //                        'User'     => 'User',
        //                        'Location' => 'Location'];
        //            $addrand = Dropdown::showItemTypes('communication_type', $types, ["id" => "communication_type", "value" => $this->fields["communication_type"]]);
        //            echo "</td>";
        //
        //            echo "</tr>";
        //
        //            echo "<tr class='tab_bg_1'>";
        //
        //            $targets = json_decode($this->fields["target"]);
        //
        //            echo "<th>" . _n('Target', 'Targets',
        //                             Session::getPluralNumber()) . "</th>";
        //
        //            echo "<td id='targets'>";
        //
        //            echo "</td>";
        //            Ajax::updateItem("targets",
        //                             PLUGIN_RELEASES_WEBDIR . "/ajax/changeTarget.php",
        //                             ['type'         => $this->fields["communication_type"],
        //                              'current_type' => $this->fields["communication_type"],
        //                              'values'       => $targets],
        //                             true);
        //            Ajax::updateItemOnSelectEvent("dropdown_communication_type" . $addrand, "targets",
        //                                          PLUGIN_RELEASES_WEBDIR . "/ajax/changeTarget.php",
        //                                          ['type'         => '__VALUE__',
        //                                           'current_type' => $this->fields["communication_type"],
        //                                           'values'       => $targets],
        //                                          true);
        //            echo "</td>";
        //
        //            echo "<th></th>";
        //            echo "<td></td>";
        //
        //            echo "</tr>";
        //
        //
        //            if (!empty($ID) && $ID > 0) {
        //               echo "<tr class='tab_bg_1'>";
        //               echo "<td colspan='4'>";
        //               echo " <div class=\"container-fluid\">
        //                                    <ul class=\"list-unstyled multi-steps\">";
        //
        //               for ($i = 1; $i <= 12; $i++) {
        //                  $class = "";
        //                  //
        //                  //            if ($value["ranking"] < $ranking) {
        //                  ////                     $class = "class = active2";
        //                  //
        //                  //            } else
        //                  if ($this->fields["status"] == $i) {
        //                     //               $class = "class='current'";
        //                     $class = "class='is-active'";
        //                  }
        //                  $name = self::getStatus($i);
        //                  echo "<li $class>" . $name . "</li>";
        //               }
        //               echo " </ul></div>";
        //               echo "</td>";
        //               echo "</tr>";
        //            }
        //      //
        //      $this->showFormButtons($options);

        return true;
    }


    /**
     * Return a field Value if exists
     *
     * @param string $field field name
     *
     * @return mixed value of the field / false if not exists
     **/
    function getField($field)
    {
        if (array_key_exists($field, $this->fields)) {
            return $this->fields[$field];
        }
        return NOT_AVAILABLE;
    }

    /**
     * @return mixed
     */
    function getNameAlert()
    {
        return $this->fields["name"];
    }

    /**
     * @return mixed
     */
    function getContentAlert()
    {
        return $this->fields["service_shutdown_details"];
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
    function showTimelineForm($rand)
    {
        global $CFG_GLPI;

        $objType = static::getType();
        $foreignKey = static::getForeignKeyField();

        //check sub-items rights
        $tmp = [$foreignKey => $this->getID()];
        $riskClass = "PluginReleasesRisk";
        $risk = new $riskClass;
        $risk->getEmpty();
        $risk->fields['itemtype'] = $objType;
        $risk->fields['items_id'] = $this->getID();


        $rollbackClass = "PluginReleasesRollback";
        $rollback = new $rollbackClass;
        $rollback->getEmpty();
        $rollback->fields['itemtype'] = $objType;
        $rollback->fields['items_id'] = $this->getID();

        $taskClass = "PluginReleasesDeploytask";
        $task = new $taskClass;
        $task->getEmpty();
        $task->fields['itemtype'] = $objType;
        $task->fields['items_id'] = $this->getID();

        $testClass = "PluginReleasesTest";
        $test = new $testClass;
        $test->getEmpty();
        $test->fields['itemtype'] = $objType;
        $test->fields['items_id'] = $this->getID();

        $canadd_risk = $risk->can(-1, CREATE, $tmp) && !in_array(
                $this->fields["status"],
                array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray())
            );

        $canadd_rollback = $rollback->can(-1, CREATE, $tmp) && !in_array(
                $this->fields["status"],
                array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray())
            );

        $canadd_task = $task->can(-1, CREATE, $tmp) && !in_array(
                $this->fields["status"],
                array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray())
            );

        $canadd_test = $test->can(-1, CREATE, $tmp) && !in_array(
                $this->fields["status"],
                $this->getSolvedStatusArray()
            );

        // javascript function for add and edit items
        $objType = self::getType();
        $foreignKey = self::getForeignKeyField();

        echo "<script type='text/javascript' >
      function change_task_state(items_id, target, itemtype) {
         $.post('" . PLUGIN_RELEASES_WEBDIR . "/ajax/timeline.php',
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
         $.post('" . PLUGIN_RELEASES_WEBDIR . "/ajax/timeline.php',
                {'action':     'done_fail',
                  'items_id':   items_id,
                  'itemtype':   itemtype,
                  'parenttype': '$objType',
                  '$foreignKey': " . $this->fields['id'] . ",
                  'newStatus': newStatus 
                })
                .done(function(response) {
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

        $params = [
            'action' => 'viewsubitem',
            'type' => 'itemtype',
            'parenttype' => $objType,
            $foreignKey => $this->fields['id'],
            'id' => -1
        ];
        $out = Ajax::updateItemJsCode(
            "viewitem" . $this->fields['id'] . "$rand",
            PLUGIN_RELEASES_WEBDIR . "/ajax/timeline.php",
            $params,
            "",
            false
        );
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
            "'><i class='fas fa-bug'></i>" . $riskClass::getTypeName(2) . " (" . $riskClass::countForItem(
                $release
            ) . ")</a></li>";
        if ($canadd_risk) {
            echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$riskClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
        }

        $style = "color:orange;";
        $fa = "fa-pencil-alt";
        if ($riskClass::countForItem($release) == $riskClass::countDoneForItem($release)) {
            $style = "color:forestgreen;";
            $fa = "fa-check-circle";
        }

        echo "<i class='fas $fa' style='margin-right: 10px;$style'></i>";

        echo "<li class='rollback'>";
        echo "<a href='#'  data-type='rollback' title='" . $rollbackClass::getTypeName(2) .
            "'><i class='fas fa-undo-alt'></i>" . $rollbackClass::getTypeName(2) . " (" . $rollbackClass::countForItem(
                $release
            ) . ")</a></li>";
        if ($canadd_rollback) {
            echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$rollbackClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
        }


        $style = "color:orange;";
        $fa = "fa-pencil-alt";
        if ($rollbackClass::countForItem($release) == $rollbackClass::countDoneForItem($release)) {
            $style = "color:forestgreen;";
            $fa = "fa-check-circle";
        }
        echo "<i class='fas $fa' style='margin-right: 10px;$style'></i>";

        echo "<li class='task'>";
        echo "<a href='#'   data-type='task' title='" . _n('Deploy task', 'Deploy tasks', 2, 'releases') .
            "'><i class='fas fa-check-square'></i>" . _n(
                'Deploy task',
                'Deploy tasks',
                2,
                'releases'
            ) . " (" . $taskClass::countForItem($release) . ")</a></li>";
        if ($canadd_task) {
            echo "<i class='fas fa-plus-circle pointer'  onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$taskClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
        }

        $style = "color:orange;";
        $fa = "fa-pencil-alt";
        if ($taskClass::countForItem($release) == $taskClass::countDoneForItem($release)) {
            $style = "color:forestgreen;";
            $fa = "fa-check-circle";
        }
        if ($taskClass::countFailForItem($release) > 0) {
            $style = "color:firebrick;";
            $fa = "fa-times-circle";
        }
        echo "<i class='fas $fa' style='margin-right: 10px;$style'></i>";

        echo "<li class='test'>";
        echo "<a href='#'  data-type='test' title='" . $testClass::getTypeName(2) .
            "'><i class='fas fa-check'></i>" . $testClass::getTypeName(2) . " (" . $testClass::countForItem(
                $release
            ) . ")</a></li>";
        if ($canadd_test) {
            echo "<i class='fas fa-plus-circle pointer' onclick='" . "javascript:viewAddSubitem" . $this->fields['id'] . "$rand(\"$testClass\");' style='margin-right: 10px;margin-left: -5px;'></i>";
        }
        $style = "color:orange;";
        $fa = "fa-pencil-alt";
        if ($testClass::countForItem($release) == $testClass::countDoneForItem($release)) {
            $style = "color:forestgreen;";
            $fa = "fa-check-circle";
        }
        if ($testClass::countFailForItem($release) > 0) {
            $style = "color:firebrick;";
            $fa = "fa-times-circle";
        }
        echo "<i class='fas $fa' style='margin-right: 10px;$style'></i>";

        echo "<li class='followup'>" .
            "<a href='#'  data-type='ITILFollowup' title='" . __("Followup") . "'>"
            . "<i class='far fa-comment'></i>" . __("Followup") . " (" . self::countFollowupForItem(
                $release
            ) . ")</a></li>";
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
     * @param \CommonDBTM $item
     *
     * @return int
     */
    static function countFollowupForItem(CommonDBTM $item)
    {
        $dbu = new DbUtils();
        return $dbu->countElementsInTable(
            "glpi_itilfollowups",
            [
                "items_id" => $item->getID(),
                "itemtype" => $item->getType()
            ]
        );
    }

    /**
     * Displays the timeline filter buttons
     *
     * @return void
     * @since 9.4.0
     *
     */
    function filterTimeline()
    {
        echo "<div class='filter_timeline'>";
        echo "<h3>" . __("Timeline filter") . " : </h3>";
        echo "<ul>";

        $riskClass = "PluginReleasesRisk";
        echo "<li><a href='#' class='filterEle fas fa-bug pointer' data-type='risk' title='" . $riskClass::getTypeName(
                2
            ) .
            "'><span class='sr-only'>" . $riskClass::getTypeName(2) . "</span></a></li>";
        $rollbackClass = "PluginReleasesRollback";
        echo "<li><a href='#' class='filterEle fas fa-undo-alt pointer' data-type='rollback' title='" . $rollbackClass::getTypeName(
                2
            ) .
            "'><span class='sr-only'>" . $rollbackClass::getTypeName(2) . "</span></a></li>";
        $taskClass = "PluginReleasesDeploytask";
        echo "<li><a href='#' class=' filterEle fas fa-check-square pointer' data-type='task' title='" . $taskClass::getTypeName(
                2
            ) .
            "'><span class='sr-only'>" . $taskClass::getTypeName(2) . "</span></a></li>";
        $testClass = "PluginReleasesTest";
        echo "<li><a href='#' class=' filterEle fas fa-check pointer' data-type='test' title='" . $testClass::getTypeName(
                2
            ) .
            "'><span class='sr-only'>" . $testClass::getTypeName(2) . "</span></a></li>";
        echo "<li><a href='#' class=' filterEle fas fa-comment pointer' data-type='ITILFollowup' title='" . __(
                'Followup'
            ) .
            "'><span class='sr-only'>" . __('Followup') . "</span></a></li>";
        echo "<li><a href='#' class=' filterEle fa fa-ban pointer' data-type='reset' title=\"" . __s(
                "Reset display options"
            ) .
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
    function showTimeLine($rand)
    {
        global $CFG_GLPI, $autolink_options;

        $user = new User();
        $pics_url = $CFG_GLPI['root_doc'] . "/pics/timeline";
        $timeline = $this->getTimelineItems();

        $autolink_options['strip_protocols'] = false;

        $objType = static::getType();
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
            } elseif (isset($item_i['date_mod'])) {
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

            $domid = "viewitem{$item['type']}{$item_i['id']}";
            $randdomid = $domid . $rand;
            $domid = Toolbox::slugify($domid);

            $fa = null;
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

                $content = Glpi\RichText\RichText::getEnhancedHtml($content);
                $content = autolink($content, false);

                $long_text = "";
                if ((substr_count($content, "<br") > 30) || (strlen($content) > 2000)) {
                    $long_text = "long_text";
                }

                echo "<div class='item_content $long_text'>";
                echo "<p>";
                if (isset($item_i['state'])) {
                    if ($item['type'] == PluginReleasesTest::getType()
                        || $item['type'] == pluginReleasesDeploytask::getType()) {
                        $onClickDone = "onclick='done_fail(" . $item_i['id'] . ", this,\"" . $item['type'] . "\"," . PluginReleasesTest::DONE . ")'";
                        $onClickFail = "onclick='done_fail(" . $item_i['id'] . ", this,\"" . $item['type'] . "\"," . PluginReleasesTest::FAIL . ")'";
                        $cursor = "";
                        if (!$item_i['can_edit']) {
                            $cursor = "cursor: not-allowed;";
                            $onClickDone = "";
                            $onClickFail = "";
                        }
                        if ($item_i['state'] == PluginReleasesDeploytask::TODO || $item_i['state'] == PluginReleasesTest::TODO) {
                            echo "<span>";
                            $style = "color:gray;";
                            $fa = "fa-times-circle fa-2x";
                            echo "<i data-type='fail' class='fas $fa pointer' style='margin-right: 10px;$style $cursor' $onClickFail></i>";
                            $style = "color:gray;";
                            $fa = "fa-check-circle fa-2x";

                            echo "<i data-type='done' class='fas $fa pointer' style='margin-right: 10px;$style $cursor' $onClickDone></i>";
                            echo "</span>";
                        } elseif ($item_i['state'] == PluginReleasesDeploytask::DONE || $item_i['state'] == PluginReleasesTest::DONE) {
                            echo "<span>";
                            $style = "color:gray;";
                            $fa = "fa-times-circle fa-2x";
                            echo "<i data-type='fail' class='fas $fa pointer' style='margin-right: 10px;$style $cursor' $onClickFail></i>";
                            $style = "color:forestgreen;";
                            $fa = "fa-check-circle fa-2x";

                            echo "<i data-type='done' class='fas $fa pointer' style='margin-right: 10px;$style $cursor' $onClickDone></i>";
                            echo "</span>";
                        } else {
                            echo "<span>";
                            $style = "color:firebrick;";
                            $fa = "fa-times-circle fa-2x";
                            echo "<i data-type='fail' class='fas $fa pointer' style='margin-right: 10px;$style $cursor' $onClickFail></i>";
                            $style = "color:gray;";
                            $fa = "fa-check-circle fa-2x";

                            echo "<i data-type='done' class='fas $fa pointer' style='margin-right: 10px;$style $cursor' $onClickDone></i>";
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
                $richtext = Glpi\RichText\RichText::getEnhancedHtml($content);
                //            $richtext = Html::replaceImagesByGallery($richtext);
                echo $richtext;
                echo "</div>";

                if (!empty($long_text)) {
                    echo "<p class='read_more'>";
                    echo "<a class='read_more_button'>.....</a>";
                    echo "</p>";
                }
                $document = new Document_Item();
                $type = $item['type'];

                if ($document->find(["itemtype" => $type, "items_id" => $item_i['id']])) {
                    $d = new Document();
                    $items_i = $document->find(["itemtype" => $type, "items_id" => $item_i['id']]);
                    //         $item_i = reset($items_i);
                    foreach ($items_i as $item_d) {
                        $items_i = $d->find(["id" => $item_d["documents_id"]]);
                        $item_i_ = reset($items_i);
                        $foreignKey = "plugin_releases_reviews_id";
                        $pics_url = $CFG_GLPI['root_doc'] . "/pics/timeline";

                        if ($item_i_['filename']) {
                            $filename = $item_i_['filename'];
                            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            echo "<img src='";
                            if (empty($filename)) {
                                $filename = $item_i_['name'];
                            }
                            if (file_exists(GLPI_ROOT . "/pics/icones/$ext-dist.png")) {
                                echo $CFG_GLPI['root_doc'] . "/pics/icones/$ext-dist.png";
                            } else {
                                echo "$pics_url/file.png";
                            }
                            echo "'/>&nbsp;";

                            echo "<a href='" . $CFG_GLPI['root_doc'] . "/front/document.send.php?docid=" . $item_i_['id']
                                . "&$foreignKey=" . $this->getID() . "' target='_blank'>$filename";
                            if (Document::isImage(GLPI_DOC_DIR . '/' . $item_i_['filepath'])) {
                                echo "<div class='timeline_img_preview2'>";
                                echo "<img src='" . $CFG_GLPI['root_doc'] . "/front/document.send.php?docid=" . $item_i_['id']
                                    . "&$foreignKey=" . $this->getID() . "&context=timeline'/>";
                                echo "</div>";
                            }
                            echo "</a>";
                        }
                        if ($item_i_['link']) {
                            echo "<a href='{$item_i_['link']}' target='_blank'><i class='fa fa-external-link'></i>{$item_i['name']}</a>";
                        }
                        if (!empty($item_i_['mime'])) {
                            echo "&nbsp;(" . $item_i_['mime'] . ")";
                        }
                        echo "<span class='buttons'>";
                        echo "<a href='" . Document::getFormURLWithID(
                                $item_i_['id']
                            ) . "' class='edit_document fa fa-eye pointer' title='" .
                            _sx("button", "Show") . "'>";
                        echo "<span class='sr-only'>" . _sx('button', 'Show') . "</span></a>";

                        $doc = new Document();
                        $doc->getFromDB($item_i_['id']);
                        if ($doc->can($item_i_['id'], UPDATE)) {
                            echo "<a href='" . static::getFormURL() .
                                "?delete_document&documents_id=" . $item_i_['id'] .
                                "&$foreignKey=" . $this->getID(
                                ) . "' class='delete_document fas fa-trash-alt pointer' title='" .
                                _sx("button", "Delete permanently") . "'>";
                            echo "<span class='sr-only'>" . _sx('button', 'Delete permanently') . "</span></a>";
                        }
                        echo "</span>";
                        echo "<br />";
                    }
                }
                echo "</div>";
            }

            echo "<div class='b_right'>";

            if (isset($item_i['plugin_releases_typedeploytasks_id'])
                && !empty($item_i['plugin_releases_typedeploytasks_id'])) {
                echo Dropdown::getDropdownName(
                        "glpi_plugin_releases_typedeploytasks",
                        $item_i['plugin_releases_typedeploytasks_id']
                    ) . "<br>";
            }
            if (isset($item_i['plugin_releases_typerisks_id'])
                && !empty($item_i['plugin_releases_typerisks_id'])) {
                echo Dropdown::getDropdownName(
                        "glpi_plugin_releases_typerisks",
                        $item_i['plugin_releases_typerisks_id']
                    ) . "<br>";
            }
            if (isset($item_i['plugin_releases_typetests_id'])
                && !empty($item_i['plugin_releases_typetests_id'])) {
                echo Dropdown::getDropdownName(
                        "glpi_plugin_releases_typetests",
                        $item_i['plugin_releases_typetests_id']
                    ) . "<br>";
            }
            if (isset($item_i['plugin_releases_risks_id'])
                && !empty($item_i['plugin_releases_risks_id'])) {
                echo __("Associated with", 'releases') . " ";
                echo Dropdown::getDropdownName(
                        "glpi_plugin_releases_risks",
                        $item_i['plugin_releases_risks_id']
                    ) . "<br>";
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
                if (isset($item_i['date_mod'])) {
                    echo sprintf(
                        __('Last edited on %1$s by %2$s'),
                        Html::convDateTime($item_i['date_mod']),
                        $user->getLink()
                    );
                }
                echo Html::showToolTip(
                    $userdata["comment"],
                    ['link' => $userdata['link']]
                );
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
            if (isset($_SESSION["releases"][Session::getLoginUserID()])) {
                $catToLoad = $_SESSION["releases"][Session::getLoginUserID()];
            } else {
                $catToLoad = 'risk';
            }

            unset($_SESSION["releases"][Session::getLoginUserID()]);
            echo Html::scriptBlock(
                "$(document).ready(function (){        
                                        $('.filter_timeline_release li a').removeClass('h_active');
                                        $('.h_item').removeClass('h_hidden');
                                       $('.h_item').addClass('h_hidden');
                                      $(\"a[data-type='$catToLoad']\").addClass('h_active');
                                       $('.ajax_box').empty();
                                       //activate clicked element
                                       //find active classname
                                       $(\"a[data-type='$catToLoad'].filterEle\").addClass('h_active');
                                       $(\".h_content.$catToLoad\").parent().removeClass('h_hidden');

                                    });"
            );
        }

        // end timeline
        echo "</div>"; // h_item $user_position
    }


    function getTimelineItems(array $options = [])
    {
        $objType = self::getType();
        $foreignKey = self::getForeignKeyField();

        $timeline = [];

        $riskClass = 'PluginReleasesRisk';
        $risk_obj = new $riskClass;
        $rollbackClass = 'PluginReleasesRollback';
        $rollback_obj = new $rollbackClass;
        $taskClass = 'PluginReleasesDeploytask';
        $task_obj = new $taskClass;
        $testClass = 'PluginReleasesTest';
        $test_obj = new $testClass;

        //checks rights
        $restrict_risk = $restrict_rollback = $restrict_task = $restrict_test = [];
        //      $restrict_risk['itemtype'] = static::getType();
        //      $restrict_risk['items_id'] = $this->getID();
        $user = new User();

        $fupClass = 'ITILFollowup';
        $followup_obj = new $fupClass;


        //checks rights
        $restrict_fup = $restrict_task = [];
        if (!Session::haveRight("followup", ITILFollowup::SEEPRIVATE)) {
            $restrict_fup = [
                'OR' => [
                    'is_private' => 0,
                    'users_id' => Session::getLoginUserID()
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
                $timeline[$followup['date'] . "_followup_" . $followups_id] = [
                    'type' => $fupClass,
                    'item' => $followup,
                    'itiltype' => 'ITILFollowup'
                ];
            }
        }
        //add risks to timeline
        if ($risk_obj->canview()) {
            $risks = $risk_obj->find([$foreignKey => $this->getID()] + $restrict_risk, ['date_mod DESC', 'id DESC']);
            foreach ($risks as $risks_id => $risk) {
                $risk_obj->getFromDB($risks_id);
                $risk['can_edit'] = $risk_obj->canUpdate();
                $timeline[$risk['date_mod'] . "_risk_" . $risks_id] = [
                    'type' => $riskClass,
                    'item' => $risk,
                    'itiltype' => 'PluginReleasesRisk'
                ];
            }
        }

        if ($rollback_obj->canview()) {
            $rollbacks = $rollback_obj->find(
                [$foreignKey => $this->getID()] + $restrict_rollback,
                ['date_mod DESC', 'id DESC']
            );
            foreach ($rollbacks as $rollbacks_id => $rollback) {
                $rollback_obj->getFromDB($rollbacks_id);
                $rollback['can_edit'] = $rollback_obj->canUpdate();
                $timeline[$rollback['date_mod'] . "_rollback_" . $rollbacks_id] = [
                    'type' => $rollbackClass,
                    'item' => $rollback,
                    'itiltype' => 'PluginReleasesRollback'
                ];
            }
        }

        if ($task_obj->canview()) {
            //         $tasks = $task_obj->find([$foreignKey => $this->getID()] + $restrict_task);
            $tasks = $task_obj->find([$foreignKey => $this->getID()] + $restrict_task, ['level DESC']);
            foreach ($tasks as $tasks_id => $task) {
                $task_obj->getFromDB($tasks_id);
                $task['can_edit'] = $task_obj->canUpdate();
                $rand = mt_rand();
                $timeline["task" . $task_obj->getField('level') . "$tasks_id" . $rand] = [
                    'type' => $taskClass,
                    'item' => $task,
                    'itiltype' => 'PluginReleasesDeploytask'
                ];
            }
        }

        if ($test_obj->canview()) {
            $tests = $test_obj->find([$foreignKey => $this->getID()] + $restrict_test, ['date_mod DESC', 'id DESC']);
            foreach ($tests as $tests_id => $test) {
                $test_obj->getFromDB($tests_id);
                $test['can_edit'] = $test_obj->canUpdate();
                $timeline[$test['date_mod'] . "_test_" . $tests_id] = [
                    'type' => $testClass,
                    'item' => $test,
                    'itiltype' => 'PluginReleasesTest'
                ];
            }
        }

        //reverse sort timeline items by key (date)
        //      ksort($timeline);

        return $timeline;
    }


    static function showCreateRelease($item)
    {
        $item_t = new PluginReleasesReleasetemplate();
        $dbu = new DbUtils();
        $condition = $dbu->getEntitiesRestrictCriteria($item_t->getTable());
        PluginReleasesReleasetemplate::dropdown(
            [
                "comments" => false,
                "addicon" => false,
                "emptylabel" => __("For this change", "releases"),
                "name" => "releasetemplates_id"
            ] + $condition
        );
        $url = PluginReleasesRelease::getFormURL();
        echo "<br/><br/>";
        echo "<a class='submit btn btn-primary' id='link' href='$url?changes_id=" . $item->getID() . "'>";
        $url = $url . "?changes_id=" . $item->getID() . "&template_id=";
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
        //      echo "<form name='form' method='post' action='".$this->getFormURL()."'  enctype=\"multipart/form-data\">";
        //      echo Html::hidden("changes_id",["value"=>$item->getID()]);
        ////      echo '<a class="btn btn-primary"> '.__("Create a releases from this change",'release').'</a>';
        //      echo Html::submit(__("Create a release from this change",'releases'), ['name' => 'createRelease']);
        //      Html::closeForm();
    }

    function getLinkedItems(bool $addNames = true): array
    {
        global $DB;

        $assets = $DB->request([
            'SELECT' => ['itemtype', 'items_id'],
            'FROM' => 'glpi_plugin_releases_releases_items',
            'WHERE' => ['plugin_releases_releases_id' => $this->getID()]
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
    protected function hasImpactTab()
    {
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
    static function getMenuContent()
    {
        $menu['title'] = self::getMenuName(2);
        $menu['page'] = self::getSearchURL(false);
        $menu['links']['search'] = self::getSearchURL(false);

//        $menu['links']['template'] = PLUGIN_RELEASES_NOTFULL_WEBDIR . "/front/releasetemplate.php";
        //      $menu['links']['template'] = "/front/setup.templates.php?itemtype=PluginReleasesRelease&add=0";
        $menu['icon'] = static::getIcon();
        if (self::canCreate()) {
            $menu['links']['add'] = PLUGIN_RELEASES_NOTFULL_WEBDIR . "/front/creationrelease.php";
        }


        return $menu;
    }


    static function getIcon()
    {
        return "ti ti-building-factory";
    }

    static function getDefaultValues($entity = 0)
    {
        global $CFG_GLPI;

        if (is_numeric(Session::getLoginUserID(false))) {
            $users_id_requester = Session::getLoginUserID();
            $users_id_assign = Session::getLoginUserID();
            // No default requester if own ticket right = tech and update_ticket right to update requester
            //         if (Session::haveRightsOr(self::$rightname, [UPDATE, self::OWN]) && !$_SESSION['glpiset_default_requester']) {
            //            $users_id_requester = 0;
            //         }
            if (!$_SESSION['glpiset_default_tech']) {
                $users_id_assign = 0;
            }
            $entity = $_SESSION['glpiactive_entity'];
            $requesttype = $_SESSION['glpidefault_requesttypes_id'];
        } else {
            $users_id_requester = 0;
            $users_id_assign = 0;
            $requesttype = $CFG_GLPI['default_requesttypes_id'];
        }
        $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $entity, '', 1);
        // Set default values...
        return [
            '_users_id_requester' => $users_id_requester,
            '_users_id_requester_notif' => [
                'use_notification' => [$default_use_notif],
                'alternative_email' => ''
            ],
            '_groups_id_requester' => 0,
            '_users_id_assign' => $users_id_assign,
            '_users_id_assign_notif' => [
                'use_notification' => [$default_use_notif],
                'alternative_email' => ''
            ],
            '_groups_id_assign' => 0,
            '_users_id_observer' => 0,
            '_users_id_observer_notif' => [
                'use_notification' => [$default_use_notif],
                'alternative_email' => ''
            ],
            '_groups_id_observer' => 0,
            '_link' => [
                'tickets_id_2' => '',
                'link' => ''
            ],
            '_suppliers_id_assign' => 0,
            '_suppliers_id_assign_notif' => [
                'use_notification' => [$default_use_notif],
                'alternative_email' => ''
            ],
            'name' => '',
            'content' => '',
            'date_preproduction' => null,
            'users_id_recipient' => Session::getLoginUserID(),
            'date_production' => null,
            'entities_id' => $entity,
            'status' => self::NEWRELEASE,
            'service_shutdown' => false,
            'service_shutdown_details' => '',
            'hour_type' => 0,
            'communication' => false,
            'communication_type' => false,
            'target' => '',
            'locations_id' => 0,
            'items_id' => 0,
            '_actors' => [],
        ];
    }

    static function isAllowedStatus($old, $new)
    {
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
    function canReopen()
    {
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
    static function getClosedStatusArray()
    {
        $tab = [self::CLOSED, self::FAIL];
        return $tab;
    }

    /**
     * Get the ITIL object closed, solved or waiting status list
     *
     * @return array
     * @since 9.4.0
     *
     */
    static function getReopenableStatusArray()
    {
        return [self::CLOSED];
    }


    static function failOrNot($itemtype, $items_id)
    {
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
    function showTimelineHeader()
    {
        echo "<h2>" . __("Release actions details", 'releases') . "</h2>";
        $this->filterTimeline();
    }

    /**
     * Display releases for an item
     *
     * Will also display releases of linked items
     *
     * @param CommonDBTM $item
     * @param boolean $withtemplate
     *
     * @return boolean|void
     **/
    static function showListForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $DB;

        if (!Session::haveRight(self::$rightname, READ)) {
            return false;
        }

        if ($item->isNewID($item->getID())) {
            return false;
        }

        $restrict = [];
        $options = [
            'criteria' => [],
            'reset' => 'reset',
        ];

        switch ($item->getType()) {
            case 'User' :
                $restrict['glpi_plugin_releases_releases_users.users_id'] = $item->getID();

                $options['criteria'][0]['field'] = 4; // status
                $options['criteria'][0]['searchtype'] = 'equals';
                $options['criteria'][0]['value'] = $item->getID();
                $options['criteria'][0]['link'] = 'OR';

                $options['criteria'][1]['field'] = 66; // status
                $options['criteria'][1]['searchtype'] = 'equals';
                $options['criteria'][1]['value'] = $item->getID();
                $options['criteria'][1]['link'] = 'OR';

                $options['criteria'][5]['field'] = 5; // status
                $options['criteria'][5]['searchtype'] = 'equals';
                $options['criteria'][5]['value'] = $item->getID();
                $options['criteria'][5]['link'] = 'OR';

                break;

            case 'Supplier' :
                $restrict['glpi_plugin_releases_releases_suppliers.suppliers_id'] = $item->getID();

                $options['criteria'][0]['field'] = 6;
                $options['criteria'][0]['searchtype'] = 'equals';
                $options['criteria'][0]['value'] = $item->getID();
                $options['criteria'][0]['link'] = 'AND';
                break;

            case 'Group' :
                // Mini search engine
                if ($item->haveChildren()) {
                    $tree = Session::getSavedOption(__CLASS__, 'tree', 0);
                    echo "<table class='tab_cadre_fixe'>";
                    echo "<tr class='tab_bg_1'><th>" . __('Last releases') . "</th></tr>";
                    echo "<tr class='tab_bg_1'><td class='center'>";
                    echo __('Child groups');
                    Dropdown::showYesNo(
                        'tree',
                        $tree,
                        -1,
                        ['on_change' => 'reloadTab("start=0&tree="+this.value)']
                    );
                } else {
                    $tree = 0;
                }
                echo "</td></tr></table>";

                $restrict['glpi_plugin_releases_groups_releases.groups_id'] = ($tree ? getSonsOf(
                    'glpi_groups',
                    $item->getID()
                ) : $item->getID());

                $options['criteria'][0]['field'] = 71;
                $options['criteria'][0]['searchtype'] = ($tree ? 'under' : 'equals');
                $options['criteria'][0]['value'] = $item->getID();
                $options['criteria'][0]['link'] = 'AND';
                break;

            default :
                $restrict['items_id'] = $item->getID();
                $restrict['itemtype'] = $item->getType();
                break;
        }

        // Link to open a new release
        //      if ($item->getID()
        //          && PluginReleasesRelease::isPossibleToAssignType($item->getType())
        //          && self::canCreate()
        //          && !(!empty($withtemplate) && $withtemplate == 2)
        //          && (!isset($item->fields['is_template']) || $item->fields['is_template'] == 0)) {
        //         echo "<div class='firstbloc'>";
        //         Html::showSimpleForm(
        //            PluginReleasesRelease::getFormURL(),
        //            '_add_fromitem',
        //            __('New release for this item...'),
        //            [
        //               '_from_itemtype' => $item->getType(),
        //               '_from_items_id' => $item->getID(),
        //               'entities_id'    => $item->fields['entities_id']
        //            ]
        //         );
        //         echo "</div>";
        //      }

        $criteria = self::getCommonCriteria();
        $criteria['WHERE'] = $restrict + getEntitiesRestrictCriteria(self::getTable());
        $criteria['LIMIT'] = (int)$_SESSION['glpilist_limit'];
        $iterator = $DB->request($criteria);
        $number = count($iterator);

        // Ticket for the item
        echo "<div><table class='tab_cadre_fixe'>";

        $colspan = 11;
        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $colspan++;
        }
        if ($number > 0) {
            Session::initNavigateListItems(
                'PluginReleasesRelease',
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    $item->getTypeName(1),
                    $item->getName()
                )
            );

            echo "<tr><th colspan='$colspan'>";

            //TRANS : %d is the number of problems
            echo sprintf(_n('%d last release', '%d last releases', $number, 'releases'), $number);

            echo "</th></tr>";
        } else {
            echo "<tr><th>" . __('No release found.', 'releases') . "</th></tr>";
        }
        // Ticket list
        if ($number > 0) {
            self::commonListHeader(Search::HTML_OUTPUT);

            foreach ($iterator as $data) {
                Session::addToNavigateListItems('PluginReleasesRelease', $data["id"]);
                self::showShort($data["id"]);
            }
            self::commonListHeader(Search::HTML_OUTPUT);
        }

        echo "</table></div>";

        // Tickets for linked items
        $linkeditems = $item->getLinkedItems();
        $restrict = [];
        if (count($linkeditems)) {
            foreach ($linkeditems as $ltype => $tab) {
                foreach ($tab as $lID) {
                    $restrict[] = ['AND' => ['itemtype' => $ltype, 'items_id' => $lID]];
                }
            }
        }

        if (count($restrict)) {
            $criteria = self::getCommonCriteria();
            $criteria['WHERE'] = ['OR' => $restrict]
                + getEntitiesRestrictCriteria(self::getTable());
            $iterator = $DB->request($criteria);
            $number = count($iterator);

            echo "<div class='spaced'><table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='$colspan'>";
            echo __('Releases on linked items', 'releases');

            echo "</th></tr>";
            if ($number > 0) {
                self::commonListHeader(Search::HTML_OUTPUT);

                foreach ($iterator as $data) {
                    // Session::addToNavigateListItems(TRACKING_TYPE,$data["id"]);
                    self::showShort($data["id"]);
                }
                self::commonListHeader(Search::HTML_OUTPUT);
            } else {
                echo "<tr><th>" . __('No release found.', 'releases') . "</th></tr>";
            }
            echo "</table></div>";
        } // Subquery for linked item

    }

    /**
     * Get common request criteria
     *
     * @return array
     * @since 9.5.0
     *
     */
    public static function getCommonCriteria()
    {
        $fk = self::getForeignKeyField();
        $gtable = 'glpi_plugin_releases_groups_releases';
        $itable = 'glpi_plugin_releases_releases_items';
        $utable = static::getTable() . '_users';
        $stable = static::getTable() . '_suppliers';

        $table = static::getTable();
        $criteria = [
            'SELECT' => [
                "$table.*"
            ],
            'DISTINCT' => true,
            'FROM' => $table,
            'LEFT JOIN' => [
                $gtable => [
                    'ON' => [
                        $table => 'id',
                        $gtable => $fk
                    ]
                ],
                $utable => [
                    'ON' => [
                        $table => 'id',
                        $utable => $fk
                    ]
                ],
                $stable => [
                    'ON' => [
                        $table => 'id',
                        $stable => $fk
                    ]
                ],
                $itable => [
                    'ON' => [
                        $table => 'id',
                        $itable => $fk
                    ]
                ]
            ],
            'ORDERBY' => "$table.date_mod DESC"
        ];
        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $criteria['LEFT JOIN']['glpi_entities'] = [
                'ON' => [
                    'glpi_entities' => 'id',
                    $table => 'entities_id'
                ]
            ];
            $criteria['SELECT'] = array_merge(
                $criteria['SELECT'], [
                    'glpi_entities.completename AS entityname',
                    "$table.entities_id AS entityID"
                ]
            );
        }
        return $criteria;
    }

    /**
     * @param integer $output_type Output type
     * @param string $mass_id id of the form to check all
     */
    static function commonListHeader(
        $output_type = Search::HTML_OUTPUT,
        $mass_id = '',
        array $params = []
    ) {
        // New Line for Header Items Line
        echo Search::showNewLine($output_type);
        // $show_sort if
        $header_num = 1;

        $items = [];
        $items[(empty($mass_id) ? '&nbsp' : Html::getCheckAllAsCheckbox($mass_id))] = '';
        $items[__('Status')] = "status";
        $items[__('Date')] = "date";
        $items[__('Last update')] = "date_mod";

        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $items[_n('Entity', 'Entities', Session::getPluralNumber())] = "glpi_entities.completename";
        }

        //      $items[__('Priority')]           = "priority";
        $items[__('Requester')] = "users_id";
        $items[__('Assigned')] = "users_id_assign";
        if (static::getType() == 'Ticket') {
            $items[_n('Associated element', 'Associated elements', Session::getPluralNumber())] = "";
        }
        //      $items[__('Category')]           = "glpi_itilcategories.completename";
        $items[__('Title')] = "name";
        $items[__('Planification')] = "glpi_plugin_releases_deploytasks.begin";

        foreach (array_keys($items) as $key) {
            $link = "";
            echo Search::showHeaderItem($output_type, $key, $header_num, $link);
        }

        // End Line for column headers
        echo Search::showEndLine($output_type);
    }

    /**
     * Display a line for an object
     *
     * @param $id                 Integer  ID of the object
     * @param $options            array of options
     *      output_type            : Default output type (see Search class / default Search::HTML_OUTPUT)
     *      row_num                : row num used for display
     *      type_for_massiveaction : itemtype for massive action
     *      id_for_massaction      : default 0 means no massive action
     *      followups              : show followup columns
     *
     * @since 0.85 (befor in each object with differents parameters)
     *
     */
    static function showShort($id, $options = [])
    {
        global $DB;

        $p = [
            'output_type' => Search::HTML_OUTPUT,
            'row_num' => 0,
            'type_for_massiveaction' => 0,
            'id_for_massiveaction' => 0,
            'followups' => false,
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $rand = mt_rand();

        /// TODO to be cleaned. Get datas and clean display links

        // Prints a job in short form
        // Should be called in a <table>-segment
        // Print links or not in case of user view
        // Make new job object and fill it from database, if success, print it
        $item = new static();

        $candelete = static::canDelete();
        $canupdate = Session::haveRight(static::$rightname, UPDATE);
        $showprivate = Session::haveRight('followup', ITILFollowup::SEEPRIVATE);
        $align = "class='center";
        $align_desc = "class='left";

        if ($p['followups']) {
            $align .= " top'";
            $align_desc .= " top'";
        } else {
            $align .= "'";
            $align_desc .= "'";
        }

        if ($item->getFromDB($id)) {
            $item_num = 1;
            //         $bgcolor  = $_SESSION["glpipriority_".$item->fields["priority"]];

            echo Search::showNewLine($p['output_type'], $p['row_num'] % 2, $item->isDeleted());

            $check_col = '';
            if (($candelete || $canupdate)
                && ($p['output_type'] == Search::HTML_OUTPUT)
                && $p['id_for_massiveaction']) {
                $check_col = Html::getMassiveActionCheckBox($p['type_for_massiveaction'], $p['id_for_massiveaction']);
            }
            echo Search::showItem($p['output_type'], $check_col, $item_num, $p['row_num'], $align);

            // First column
            $first_col = sprintf(__('%1$s: %2$s'), __('ID'), $item->fields["id"]);
            if ($p['output_type'] == Search::HTML_OUTPUT) {
                $first_col .= static::getStatusIcon($item->fields["status"]);
            } else {
                $first_col = sprintf(
                    __('%1$s - %2$s'),
                    $first_col,
                    static::getStatus($item->fields["status"])
                );
            }

            echo Search::showItem($p['output_type'], $first_col, $item_num, $p['row_num'], $align);

            // Second column
            if ($item->fields['status'] == static::CLOSED) {
                $second_col = sprintf(
                    __('Closed on %s'),
                    ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : '') .
                    Html::convDateTime($item->fields['date_end'])
                );
            } elseif ($item->fields['begin_waiting_date']) {
                $second_col = sprintf(
                    __('Put on hold on %s'),
                    ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : '') .
                    Html::convDateTime($item->fields['begin_waiting_date'])
                );
            } else {
                $second_col = sprintf(
                    __('Opened on %s'),
                    ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : '') .
                    Html::convDateTime($item->fields['date'])
                );
            }

            echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'], $align . " width=130");

            // Second BIS column
            $second_col = Html::convDateTime($item->fields["date_mod"]);
            echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'], $align . " width=90");

            // Second TER column
            if (count($_SESSION["glpiactiveentities"]) > 1) {
                $second_col = Dropdown::getDropdownName('glpi_entities', $item->fields['entities_id']);
                echo Search::showItem(
                    $p['output_type'],
                    $second_col,
                    $item_num,
                    $p['row_num'],
                    $align . " width=100"
                );
            }

            // Third Column
            //         echo Search::showItem($p['output_type'],
            //                               "<span class='b'>".static::getPriorityName($item->fields["priority"]).
            //                               "</span>",
            //                               $item_num, $p['row_num'], "$align bgcolor='$bgcolor'");

            // Fourth Column
            $fourth_col = "";

            foreach ($item->getUsers(CommonITILActor::REQUESTER) as $d) {
                $userdata = getUserName($d["users_id"], 2);
                $fourth_col .= sprintf(
                    __('%1$s %2$s'),
                    "<span class='b'>" . $userdata['name'] . "</span>",
                    Html::showToolTip(
                        $userdata["comment"],
                        [
                            'link' => $userdata["link"],
                            'display' => false
                        ]
                    )
                );
                $fourth_col .= "<br>";
            }

            foreach ($item->getGroups(CommonITILActor::REQUESTER) as $d) {
                $fourth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
                $fourth_col .= "<br>";
            }

            echo Search::showItem($p['output_type'], $fourth_col, $item_num, $p['row_num'], $align);

            // Fifth column
            $fifth_col = "";

            $entity = $item->getEntityID();
            $anonymize_helpdesk = Entity::getUsedConfig('anonymize_support_agents', $entity)
                && Session::getCurrentInterface() == 'helpdesk';

            foreach ($item->getUsers(CommonITILActor::ASSIGN) as $d) {
                if ($anonymize_helpdesk) {
                    $fifth_col .= __("Helpdesk");
                } else {
                    $userdata = getUserName($d["users_id"], 2);
                    $fifth_col .= sprintf(
                        __('%1$s %2$s'),
                        "<span class='b'>" . $userdata['name'] . "</span>",
                        Html::showToolTip(
                            $userdata["comment"],
                            [
                                'link' => $userdata["link"],
                                'display' => false
                            ]
                        )
                    );
                }

                $fifth_col .= "<br>";
            }

            foreach ($item->getGroups(CommonITILActor::ASSIGN) as $d) {
                if ($anonymize_helpdesk) {
                    $fifth_col .= __("Helpdesk group");
                } else {
                    $fifth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
                }
                $fifth_col .= "<br>";
            }

            foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $d) {
                $fifth_col .= Dropdown::getDropdownName("glpi_suppliers", $d["suppliers_id"]);
                $fifth_col .= "<br>";
            }

            echo Search::showItem($p['output_type'], $fifth_col, $item_num, $p['row_num'], $align);

            // Sixth Colum
            // Ticket : simple link to item
            $sixth_col = "";
            $is_deleted = false;
            $item_ticket = new Item_Ticket();
            $data = $item_ticket->find(['tickets_id' => $item->fields['id']]);

            if ($item->getType() == 'Ticket') {
                if (!empty($data)) {
                    foreach ($data as $val) {
                        if (!empty($val["itemtype"]) && ($val["items_id"] > 0)) {
                            if ($object = getItemForItemtype($val["itemtype"])) {
                                if ($object->getFromDB($val["items_id"])) {
                                    $is_deleted = $object->isDeleted();

                                    $sixth_col .= $object->getTypeName();
                                    $sixth_col .= " - <span class='b'>";
                                    if ($item->canView()) {
                                        $sixth_col .= $object->getLink();
                                    } else {
                                        $sixth_col .= $object->getNameID();
                                    }
                                    $sixth_col .= "</span><br>";
                                }
                            }
                        }
                    }
                } else {
                    $sixth_col = __('General');
                }

                echo Search::showItem(
                    $p['output_type'],
                    $sixth_col,
                    $item_num,
                    $p['row_num'],
                    ($is_deleted ? " class='center deleted' " : $align)
                );
            }

            // Seventh column
            //         echo Search::showItem($p['output_type'],
            //                               "<span class='b'>".
            //                               Dropdown::getDropdownName('glpi_itilcategories',
            //                                                         $item->fields["itilcategories_id"]).
            //                               "</span>",
            //                               $item_num, $p['row_num'], $align);

            // Eigth column
            $eigth_column = "<span class='b'>" . $item->getName() . "</span>&nbsp;";

            // Add link
            if ($item->canViewItem()) {
                $eigth_column = "<a id='" . $item->getType(
                    ) . $item->fields["id"] . "$rand' href=\"" . $item->getLinkURL()
                    . "\">$eigth_column</a>";

                if ($p['followups']
                    && ($p['output_type'] == Search::HTML_OUTPUT)) {
                    $eigth_column .= ITILFollowup::showShortForITILObject($item->fields["id"], static::class);
                } else {
                    $eigth_column = sprintf(
                        __('%1$s (%2$s)'),
                        $eigth_column,
                        sprintf(
                            __('%1$s - %2$s'),
                            $item->numberOfFollowups($showprivate),
                            $item->numberOfTasks($showprivate)
                        )
                    );
                }
            }

            if ($p['output_type'] == Search::HTML_OUTPUT) {
                $eigth_column = sprintf(
                    __('%1$s %2$s'),
                    $eigth_column,
                    Html::showToolTip(
                        Glpi\RichText\RichText::getSafeHtml($item->fields["content"]),
                        [
                            'display' => false,
                            'applyto' => $item->getType() . $item->fields["id"] .
                                $rand
                        ]
                    )
                );
            }

            echo Search::showItem(
                $p['output_type'],
                $eigth_column,
                $item_num,
                $p['row_num'],
                $align_desc . " width='200'"
            );

            //tenth column
            $tenth_column = '';
            $planned_infos = '';

            $tasktype = "PluginReleasesDeploytask";
            $plan = new $tasktype();
            $items = [];

            $result = $DB->request(
                [
                    'FROM' => $plan->getTable(),
                    'WHERE' => [
                        $item->getForeignKeyField() => $item->fields['id'],
                    ],
                ]
            );
            foreach ($result as $plan) {
                if (isset($plan['begin']) && $plan['begin']) {
                    $items[$plan['id']] = $plan['id'];
                    $planned_infos .= sprintf(
                        __('From %s') .
                        ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : ''),
                        Html::convDateTime($plan['begin'])
                    );
                    $planned_infos .= sprintf(
                        __('To %s') .
                        ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : ''),
                        Html::convDateTime($plan['end'])
                    );
                    if ($plan['users_id_tech']) {
                        $planned_infos .= sprintf(
                            __('By %s') .
                            ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : ''),
                            getUserName($plan['users_id_tech'])
                        );
                    }
                    $planned_infos .= "<br>";
                }
            }

            $tenth_column = count($items);
            if ($tenth_column) {
                $tenth_column = "<span class='pointer'
                              id='" . $item->getType() . $item->fields["id"] . "planning$rand'>" .
                    $tenth_column . '</span>';
                $tenth_column = sprintf(
                    __('%1$s %2$s'),
                    $tenth_column,
                    Html::showToolTip(
                        $planned_infos,
                        [
                            'display' => false,
                            'applyto' => $item->getType() .
                                $item->fields["id"] .
                                "planning" . $rand
                        ]
                    )
                );
            }
            echo Search::showItem(
                $p['output_type'],
                $tenth_column,
                $item_num,
                $p['row_num'],
                $align_desc . " width='150'"
            );

            // Finish Line
            echo Search::showEndLine($p['output_type']);
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='6' ><i>" . __('No item in progress.') . "</i></td></tr>";
        }
    }

    /**
     * Number of tasks of the object
     *
     * @param boolean $with_private true : all followups / false : only public ones (default 1)
     *
     * @return integer
     **/
    function numberOfTasks($with_private = true)
    {
        global $DB;

        $table = 'glpi_plugin_releases_deploytasks';

        $RESTRICT = [];

        // Set number of tasks
        $row = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => $table,
            'WHERE' => [
                    $this->getForeignKeyField() => $this->fields['id']
                ] + $RESTRICT
        ])->next();

        return (isset($row['cpt']) && $row['cpt'] > 0) ? $row['cpt'] : 0;
    }

    function post_getEmpty()
    {
        $this->fields['users_id_recipient'] = Session::getLoginUserID();
    }

    //TODO delete useless lines
    function pre_updateInDB()
    {
        global $DB;

        // get again object to reload actors
        $this->loadActors();

        // Check dates change interval due to the fact that second are not displayed in form
        if ((($key = array_search('date', $this->updates)) !== false)
            && (substr($this->fields["date"], 0, 16) == substr($this->oldvalues['date'], 0, 16))) {
            unset($this->updates[$key]);
            unset($this->oldvalues['date']);
        }

        if ((($key = array_search('status', $this->updates)) !== false)
            && $this->oldvalues['status'] == $this->fields['status']) {
            unset($this->updates[$key]);
            unset($this->oldvalues['status']);
        }


        // Do not take into account date_mod if no update is done
        if ((count($this->updates) == 1)
            && (($key = array_search('date_mod', $this->updates)) !== false)) {
            unset($this->updates[$key]);
        }
    }

    public static function getTimelinePosition($items_id, $sub_type, $users_id)
    {
        return self::TIMELINE_RIGHT;
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
    //   function getSpecificMassiveActions($checkitem = null) {
    //      $isadmin = static::canUpdate();
    //      $actions = parent::getSpecificMassiveActions($checkitem);
    //
    //      if (Session::getCurrentInterface() == 'central') {
    //         if ($isadmin) {
    //            $actions['PluginReleasesRelease' . MassiveAction::CLASS_ACTION_SEPARATOR . 'transfer'] = __('Transfer');
    //         }
    //      }
    //      return $actions;
    //   }

    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     * @since version 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     *
     */
    //   static function showMassiveActionsSubForm(MassiveAction $ma) {
    //
    //      switch ($ma->getAction()) {
    //         case "transfer" :
    //            Dropdown::show('Entity');
    //            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
    //            return true;
    //            break;
    //      }
    //      return parent::showMassiveActionsSubForm($ma);
    //   }

    /**
     * @param MassiveAction $ma
     * @param CommonDBTM $item
     * @param array $ids
     *
     * @return nothing|void
     * @since version 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     *
     */
    //   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
    //                                                       array         $ids) {
    //
    //
    //      switch ($ma->getAction()) {
    //
    //         case "transfer" :
    //            $input = $ma->getInput();
    //            if ($item->getType() == PluginReleasesRelease::getType()) {
    //               foreach ($ids as $key) {
    //                  $item->getFromDB($key);
    //
    //
    //                  unset($values);
    //                  $values["id"]          = $key;
    //                  $values["entities_id"] = $input['entities_id'];
    //
    //                  if ($item->update($values)) {
    //                     PluginReleasesDeploytask::transfer($key, $input["entities_id"]);
    //                     PluginReleasesTest::transfer($key, $input["entities_id"]);
    //                     PluginReleasesRisk::transfer($key, $input["entities_id"]);
    //                     PluginReleasesRollback::transfer($key, $input["entities_id"]);
    //                     PluginReleasesReview::transfer($key, $input["entities_id"]);
    //                     self::transferDocument($key, $input["entities_id"]);
    //                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
    //                  } else {
    //                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
    //                  }
    //               }
    //            }
    //            return;
    //      }
    //      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    //   }

    //   static function transferDocument($ID, $entity) {
    //      global $DB;
    //
    //      if ($ID > 0) {
    //         $self      = new self();
    //         $documents = new Document_Item();
    //         $items     = $documents->find(["items_id" => $ID, "itemtype" => self::getType()]);
    //         foreach ($items as $id => $vals) {
    //            $input                = [];
    //            $input["id"]          = $id;
    //            $input["entities_id"] = $entity;
    //            $documents->update($input);
    //         }
    //         return true;
    //
    //      }
    //      return 0;
    //   }


    /**
     * Returns criteria that can be used to get documents related to current instance.
     *
     * @return array
     */
    public function getAssociatedDocumentsCriteria($bypass_rights = false): array
    {
        $task_class = PluginReleasesDeploytask::getType();
        $review_class = PluginReleasesReview::getType();

        $or_crits = [
            // documents associated to ITIL item directly
            [
                Document_Item::getTableField('itemtype') => $this->getType(),
                Document_Item::getTableField('items_id') => $this->getID(),
            ],
        ];


        // documents associated to tasks
        if ($bypass_rights || $task_class::canView()) {
            $tasks_crit = [
                $this->getForeignKeyField() => $this->getID(),
            ];

            $or_crits[] = [
                'glpi_documents_items.itemtype' => $task_class::getType(),
                'glpi_documents_items.items_id' => new QuerySubQuery(
                    [
                        'SELECT' => 'id',
                        'FROM' => $task_class::getTable(),
                        'WHERE' => $tasks_crit,
                    ]
                ),
            ];
        }

        if ($bypass_rights || $review_class::canView()) {
            $reviews_crit = [
                $this->getForeignKeyField() => $this->getID(),
            ];

            $or_crits[] = [
                'glpi_documents_items.itemtype' => $review_class::getType(),
                'glpi_documents_items.items_id' => new QuerySubQuery(
                    [
                        'SELECT' => 'id',
                        'FROM' => $review_class::getTable(),
                        'WHERE' => $reviews_crit,
                    ]
                ),
            ];
        }

        return ['OR' => $or_crits];
    }

    public static function getItemLinkClass(): string
    {
        return PluginReleasesRelease_Item::class;
    }

    public static function getTaskClass()
    {
        return PluginReleasesDeploytask::class;
    }

    public static function getContentTemplatesParametersClass(): string
    {
        // TODO: Implement getContentTemplatesParametersClass() method.
    }
}

