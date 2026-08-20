<?php

/*
 -------------------------------------------------------------------------
 releases plugin for GLPI
 Copyright (C) 2020-2026 by the releases Development Team.

 https://github.com/InfotelGLPI/releases
 -------------------------------------------------------------------------

 LICENSE

 This file is part of releases.

 releases is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 releases is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with releases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Releases;

use Calendar;
use CommonDBTM;
use DbUtils;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QuerySubQuery;
use Glpi\RichText\RichText;
use Html;
use Log;
use NotificationEvent;
use Planning;
use PlanningRecall;
use Session;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}



/**
 * Class Deploytask
 */
class Deploytask extends CommonDBTM
{
    public static $rightname = 'plugin_releases_tasks';
    public const TODO = 1; // todo
    public const DONE = 2; // done
    public const FAIL = 3; // Failed

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {

        return _n('Deploy task', 'Deploy tasks', $nb, 'releases');
    }

    public function getItilObjectItemType()
    {
        return str_replace('Deploytask', 'Release', $this->getType());
    }

    public static function getNameField()
    {
        return 'name';
    }

    /**
     *
     * @return css class
     */
    public static function getCssClass()
    {
        return "task";
    }

    /**
     * @param CommonDBTM $item
     *
     * @return int
     */
    public static function countForItem(CommonDBTM $item)
    {
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable(self::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releases_id" => $item->getID()]
        );
    }

    /**
     * @param CommonDBTM $item
     *
     * @return int
     */
    public static function countDoneForItem(CommonDBTM $item)
    {
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable(self::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releases_id" => $item->getID(),
                "state"                       => self::DONE]
        );
    }

    /**
     * @param CommonDBTM $item
     *
     * @return int
     */
    public static function countFailForItem(CommonDBTM $item)
    {
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable(self::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releases_id" => $item->getID(),
                "state"                       => self::FAIL]
        );
    }

    /**
     * Prepare input datas for adding the item
     *
     * @param array $input datas used to add the item
     *
     * @return array the modified $input array
     **/
    public function prepareInputForAdd($input)
    {

        if (isset($input['plan'])) {
            Toolbox::manageBeginAndEndPlanDates($input['plan']);
        }

        if (isset($input["plan"])) {
            $input["begin"] = $input['plan']["begin"];
            $input["end"]   = $input['plan']["end"];

            $timestart           = strtotime($input["begin"]);
            $timeend             = strtotime($input["end"]);
            $input["actiontime"] = $timeend - $timestart;

            unset($input["plan"]);
            if (!$this->test_valid_date($input)) {
                Session::addMessageAfterRedirect(
                    __('Error in entering dates. The starting date is later than the ending date'),
                    false,
                    ERROR
                );
                return false;
            }
        }

        if (!isset($input["users_id"])
          && ($uid = Session::getLoginUserID())) {
            $input["users_id"] = $uid;
        }

        $input["plugin_releases_releases_id"] = $input["items_id"];
        $release                              = new Release();
        $release->getFromDB($input["items_id"]);
        $input["entities_id"] = $release->getField("entities_id");

        if (isset($input["plugin_releases_deploytasks_id"])
            && $input["plugin_releases_deploytasks_id"] != 0) {
            $task = new self();
            $task->getFromDB($input["plugin_releases_deploytasks_id"]);
            $input["level"] = $task->getField("level") + 1;
        }

        if (!isset($input["date"])) {
            $input["date"] = $_SESSION["glpi_currenttime"];
        }

        return $input;
    }

    /**
     *
     */
    public function post_addItem()
    {
        global $CFG_GLPI;
        //      $this->input["_job"] = new Release();
        //
        //      if (isset($this->input[$this->input["_job"]->getForeignKeyField()])
        //         && !$this->input["_job"]->getFromDB($this->input[$this->input["_job"]->getForeignKeyField()])) {
        //         return false;
        //      }

        // Add document if needed, without notification
        $this->input = $this->addFiles($this->input, ['force_update' => true]);
        $itemtype    = $this->getItilObjectItemType();
        $item        = new $itemtype();
        $item->getFromDB($this->fields[$item->getForeignKeyField()]);
        $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];
        if ($donotif) {
            $options = ['task_id'    => $this->fields["id"],
                'is_private' => 0];
            NotificationEvent::raiseEvent('add_task', $item, $options);
        }
    }

    /**
     * Prepare input datas for updating the item
     *
     * @param array $input data used to update the item
     *
     * @return array the modified $input array
     **/
    public function prepareInputForUpdate($input)
    {

        Toolbox::manageBeginAndEndPlanDates($input['plan']);

        //      if (isset($input["plugin_releases_deploytasks_id"]) && $input["plugin_releases_deploytasks_id"] != 0) {
        //         $task = new self();
        //         $task->getFromDB($input["plugin_releases_deploytasks_id"]);
        //         $input["level"] = $task->getField("level") + 1;
        //      }

        if (isset($input['_planningrecall'])) {
            PlanningRecall::manageDatas($input['_planningrecall']);
        }

        // update last editor if content change
        if (isset($input['update'])
          && ($uid = Session::getLoginUserID())) { // Change from task form
            $input["users_id_editor"] = $uid;
        }


        //      $input["_job"] = new Release();
        //
        //      if (isset($input[$input["_job"]->getForeignKeyField()])
        //         && !$input["_job"]->getFromDB($input[$input["_job"]->getForeignKeyField()])) {
        //         return false;
        //      }

        if (isset($input["plan"])) {
            $input["begin"] = $input['plan']["begin"];
            $input["end"]   = $input['plan']["end"];

            $timestart           = strtotime($input["begin"]);
            $timeend             = strtotime($input["end"]);
            $input["actiontime"] = $timeend - $timestart;

            unset($input["plan"]);

            if (!$this->test_valid_date($input)) {
                Session::addMessageAfterRedirect(
                    __('Error in entering dates. The starting date is later than the ending date'),
                    false,
                    ERROR
                );
                return false;
            }
            Planning::checkAlreadyPlanned(
                $input["users_id_tech"],
                $input["begin"],
                $input["end"],
                [$this->getType() => [$input["id"]]]
            );

            $calendars_id = Entity::getUsedConfig('calendars_strategy', $this->fields['entities_id'], 'calendars_id', 0);
            $calendar     = new Calendar();

            // Using calendar
            if (($calendars_id > 0)
             && $calendar->getFromDB($calendars_id)) {
                if (!$calendar->isAWorkingHour(strtotime($input["begin"]))) {
                    Session::addMessageAfterRedirect(
                        __('Start of the selected timeframe is not a working hour.'),
                        false,
                        ERROR
                    );
                }
                if (!$calendar->isAWorkingHour(strtotime($input["end"]))) {
                    Session::addMessageAfterRedirect(
                        __('End of the selected timeframe is not a working hour.'),
                        false,
                        ERROR
                    );
                }
            }
        }

        $input = $this->addFiles($input);

        return $input;
    }


    /**
     * Current dates are valid ? begin before end
     *
     * @param $input
     *
     * @return boolean
     **/
    public function test_valid_date($input)
    {

        return (!empty($input["begin"])
              && !empty($input["end"])
              && (strtotime($input["begin"]) < strtotime($input["end"])));
    }


    //TODO
    //   Post_update for change release status ? deploytask_state to be created ?


    /**
     * Dropdown of deploytask & tests state
     *
     * @param $name   select name
     * @param $value  default value (default '')
     * @param $display  display of send string ? (true by default)
     * @param $options  options
     **/
    public static function dropdownStateTask($name, $value = '', $display = true, $options = [])
    {

        $values = [static::TODO => __('To do'),
            static::DONE => __('Done'),
            static::FAIL => __('Failed', 'releases')];

        return Dropdown::showFromArray($name, $values, array_merge(['value'   => $value,
            'display' => $display], $options));
    }

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     */
    public function showForm($ID, $options = [])
    {


        if ($this->isNewItem()) {
            $this->getEmpty();
        }

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $this->check(-1, CREATE, $options);
        }

        $alltasks = [];
        if (isset($options['parent'])
          && $this->getID() > 0) {
            $item  = $options['parent'];

            $task  = new Deploytask();
            $tasks = $task->find(["plugin_releases_releases_id" => $item->getField('id')]);
            foreach ($tasks as $t) {
                $alltasks[] = $t['id'];
            }
            $forbidden_id = self::getAllDescendant($this->getID(), $item->getField('id'));
            foreach ($alltasks as $k => $v) {
                if (in_array($v, $forbidden_id)) {
                    unset($alltasks[$k]);
                }
            }
        }

        TemplateRenderer::getInstance()->display('@releases/form_deploytask.html.twig', ['item'    => $options['parent'],
            'subitem' => $this,
            'tasks'   => $alltasks]);
    }


    /**
     * @param $parm
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    public static function populatePlanning($options = []): array
    {
        global $DB, $CFG_GLPI;

        $output = [];

        $parm = $options;

        if (!isset($parm['begin']) || $parm['begin'] == 'NULL' || !isset($parm['end']) || $parm['end'] == 'NULL') {
            return $parm;
        }

        $who       = $parm['who'];
        $who_group = $parm['whogroup'];
        $begin     = $parm['begin'];
        $end       = $parm['end'];
        // Get items to print
        $ASSIGN = [];

        //      if ($who_group === "mine") {
        //         if (count($_SESSION["glpigroups"])) {
        //            $groups = implode("','", $_SESSION['glpigroups']);
        //            $ASSIGN = " `glpi_plugin_releases_deploytasks`.`users_id_tech` IN (SELECT DISTINCT `users_id`
        //                                    FROM `glpi_groups_users`
        //                                    WHERE `groups_id` IN ('$groups'))
        //                                          AND ";
        //         } else { // Only personal ones
        //            $ASSIGN = "`glpi_plugin_releases_deploytasks`.`users_id_tech` = '$who'
        //                     AND ";
        //         }
        //      } else {
        if ($who > 0) {
            $ASSIGN = ['glpi_plugin_releases_deploytasks.users_id_tech' => $who];
        }
        if ($who_group > 0) {
            $ASSIGN = [
                'glpi_plugin_releases_deploytasks.users_id_tech' => new QuerySubQuery([
                    'SELECT'          => 'users_id',
                    'FROM'            => 'glpi_groups_users',
                    'WHERE'           => [
                            'groups_id'  => '$who_group',
                        ],
                ]),
            ];
        }
        //      }

        if (!count($ASSIGN)) {
            $ASSIGN = [
                'glpi_plugin_releases_deploytasks.users_id_tech' => new QuerySubQuery([
                    'SELECT'          => 'glpi_profiles_users.users_id',
                    'DISTINCT'        => true,
                    'FROM'            => 'glpi_profiles',
                    'LEFT JOIN'       => [
                        'glpi_profiles_users'   => [
                            'ON' => [
                                'glpi_profiles_users' => 'profiles_id',
                                'glpi_profiles'       => 'id',
                            ],
                        ],
                    ],
                    'WHERE'           => [
                            'glpi_profiles.interface'  => 'central',
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $_SESSION['glpiactive_entity'], true),
                ]),
            ];
        }


        $WHERE = [
            "'$begin' < `end`",
            "'$end' > `begin`"
        ];

        if (count($ASSIGN) > 0) {
            $WHERE[] = ['AND' => $ASSIGN];
        }

        $query = [
            'SELECT' => 'glpi_plugin_releases_deploytasks.*',
            'FROM'   => 'glpi_plugin_releases_deploytasks',
            'LEFT JOIN'       => [
                'glpi_plugin_releases_typedeploytasks' => [
                    'ON' => [
                        'glpi_plugin_releases_typedeploytasks' => 'id',
                        'glpi_plugin_releases_deploytasks'          => 'plugin_releases_typedeploytasks_id'
                    ]
                ]
            ],
            'WHERE'  => $WHERE,
            'ORDERBY' => 'begin',
        ];
        $iterator = $DB->request($query);

        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $key                              = $parm["begin"] . $data["id"] . "$$$" . "plugin_releases";
                $output[$key]['color']            = $parm['color'] ?? null;
                $output[$key]['event_type_color'] = $parm['event_type_color'] ?? null;
                ;
                $output[$key]["id"]             = $data["id"];
                $output[$key]["users_id_tech"]  = $data["users_id_tech"];
                $output[$key]["begin"]          = $data["begin"];
                $output[$key]["end"]            = $data["end"];
                $output[$key]["name"]           = $data["name"];
                $output[$key]["editable"]       = true;
                $output[$key]["content"]        = Html::resume_text($data["content"], $CFG_GLPI["cut"]);
                $output[$key]["itemtype"]       = Deploytask::class;
                $url_id                         = $data["plugin_releases_releases_id"];
                $output[$key]["parentitemtype"] = Release::class;

                $parentitemtype           = new $output[$key]["parentitemtype"]();
                $output[$key]["url"]      = $CFG_GLPI["url_base"]
                                        . $parentitemtype::getFormURLWithID($url_id, false);
                $output[$key]["parentid"] = $data["plugin_releases_releases_id"];
                $output[$key]["ajaxurl"]  = $CFG_GLPI["root_doc"] . "/ajax/planning.php"
                                        . "?action=edit_event_form"
                                        . "&itemtype=" . $output[$key]["itemtype"]
                                        . "&parentitemtype=" . $output[$key]["parentitemtype"]
                                        . "&parentid=" . $data["plugin_releases_releases_id"]
                                        . "&id=" . $data['id']
                                        . "&url=" . $output[$key]["url"];
            }
        }

        return $output;
    }

    /**
     * Display a Planning Item
     *
     * @param $parm Array of the item to display
     *
     * @return Nothing (display function)
     * */
    public static function displayPlanningItem(array $val, $who, $type = "", $complete = 0)
    {
        global $CFG_GLPI;

        $html = "";

        $rand = mt_rand();
        $html .= "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/releases/front/deploytask.form.php?id=" . $val["id"] . "'";

        $html .= " onmouseout=\"cleanhide('content_task_" . $val["id"] . $rand . "')\"
               onmouseover=\"cleandisplay('content_task_" . $val["id"] . $rand . "')\"";
        $html .= ">";

        switch ($type) {
            case "in":
                //TRANS: %1$s is the start time of a planned item, %2$s is the end
                $beginend = sprintf(__('From %1$s to %2$s'), date("H:i", strtotime($val["begin"])), date("H:i", strtotime($val["end"])));
                $html     .= sprintf(__('%1$s %2$s'), $beginend, Html::resume_text($val["name"], 80));

                break;
            case "begin":
                $start = sprintf(__('Start at %s'), date("H:i", strtotime($val["begin"])));
                $html  .= sprintf(__('%1$s: %2$s'), $start, Html::resume_text($val["name"], 80));
                break;

            case "end":
                $end  = sprintf(__('End at %s'), date("H:i", strtotime($val["end"])));
                $html .= sprintf(__('%1$s: %2$s'), $end, Html::resume_text($val["name"], 80));
                break;
        }

        if ($val["users_id_tech"] && $who == 0) {
            $dbu  = new DbUtils();
            $html .= " - " . __('User') . " " . $dbu->getUserName($val["users_id_tech"]);
        }
        $html .= "</a><br>";

        $html .= User::getTypeName(1)
               . " : <a href='" . User::getFormURL() . "?id="
               . $val["users_id_tech"] . "'";
        $user = new User();
        $user->getFromDB($val["users_id_tech"]);
        $html .= ">" . $user->getFriendlyName() . "</a>";

        $html .= "<div class='over_link' id='content_task_" . $val["id"] . $rand . "'>";
        if ($val["end"]) {
            $html .= "<strong>" . __('End date') . "</strong> : " . Html::convdatetime($val["end"]) . "<br>";
        }
        //      if ($val["type"]) {
        //         $html .= "<strong>" . TaskType::getTypeName(1) . "</strong> : " .
        //            $val["type"] . "<br>";
        //      }
        if ($val["content"]) {
            $html .= "<strong>" . __('Description') . "</strong> : " . RichText::getTextFromHtml($val["content"]);
        }
        $html .= "</div>";

        return $html;
    }

    public function post_updateItem($history = 1)
    {
        global $CFG_GLPI;

        $task = new self();
        if (!isset($this->input['no_leveling'])) {
            if ($task->getFromDB($this->getField("plugin_releases_deploytasks_id"))) {
                self::leveling_task($this->getID(), $task);
            } else {
                self::leveling_task($this->getID(), null);
            }
        }

        $options     = [
            'force_update'  => true,
            'name'          => 'content',
            'content_field' => 'content',
        ];
        $this->input = $this->addFiles($this->input, $options);

        if (in_array("begin", $this->updates)) {
            PlanningRecall::managePlanningUpdates(
                $this->getType(),
                $this->getID(),
                $this->fields["begin"]
            );
        }

        if (isset($this->input['_planningrecall'])) {
            $this->input['_planningrecall']['items_id'] = $this->fields['id'];
            PlanningRecall::manageDatas($this->input['_planningrecall']);
        }

        $update_done = false;
        $itemtype    = $this->getItilObjectItemType();
        $item        = new $itemtype();

        if ($item->getFromDB($this->fields[$item->getForeignKeyField()])) {
            $item->updateDateMod($this->fields[$item->getForeignKeyField()]);

            $proceed = count($this->updates);

            //Also check if item status has changed
            if (!$proceed) {
                if (isset($this->input['_status'])
                && $this->input['status'] != $item->getField('status')
                ) {
                    $proceed = true;
                }
            }
            if ($proceed) {
                $update_done = true;

                //todo change for notifications
                if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
                    $options = ['task_id'    => $this->fields["id"],
                        'is_private' => 0];
                    NotificationEvent::raiseEvent('update_task', $item, $options);
                }
            }
        }

        if ($update_done) {
            // Add log entry in the ITIL object
            $changes = [
                0,
                '',
                $this->fields['id'],
            ];
            Log::history(
                $this->getField($item->getForeignKeyField()),
                $itemtype,
                $changes,
                $this->getType(),
                Log::HISTORY_UPDATE_SUBITEM
            );
        }
    }


    /**
     * Get deploytask state name
     *
     * @param $value status ID
     **/
    public static function getState($value)
    {

        switch ($value) {
            case static::FAIL:
                return __('Failed', 'releases');

            case static::TODO:
                return __('To do');

            case static::DONE:
                return __('Done');
        }
    }

    public function post_deleteFromDB()
    {
        global $CFG_GLPI;
        $task  = new self();
        $tasks = $task->find(["plugin_releases_deploytasks_id" => $this->getID()]);
        foreach ($tasks as $t) {
            $input                                   = [];
            $input['id']                             = $t["id"];
            $input['plugin_releases_deploytasks_id'] = $this->getField('plugin_releases_deploytasks_id');
            $input['_disablenotif']                  = true;
            $task->update($input);
        }
        $itemtype = $this->getItilObjectItemType();
        $item     = new $itemtype();
        $item->getFromDB($this->fields[$item->getForeignKeyField()]);
        $item->updateDateMod($this->fields[$item->getForeignKeyField()]);

        // Add log entry in the ITIL object
        $changes = [
            0,
            '',
            $this->fields['id'],
        ];
        Log::history(
            $this->getField($item->getForeignKeyField()),
            $this->getItilObjectItemType(),
            $changes,
            $this->getType(),
            Log::HISTORY_DELETE_SUBITEM
        );

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            $options = ['task_id'             => $this->fields["id"],
                // Force is_private with data / not available
                'is_private'          => 0,
                // Pass users values
                'task_users_id'       => $this->fields['users_id'],
                'task_users_id_tech'  => $this->fields['users_id_tech'],
                'task_groups_id_tech' => $this->fields['groups_id_tech']];
            NotificationEvent::raiseEvent('delete_task', $item, $options);
        }
    }


    public static function leveling_task($id, $previous_task)
    {

        $task                   = new Deploytask();
        $input                  = [];
        $input['id']            = $id;
        $input['_disablenotif'] = true;
        $input['no_leveling']   = true;
        if ($previous_task != null) {
            $input["level"] = $previous_task->getField('level') + 1;
        } else {
            $input["level"] = 0;
        }


        $task->update($input);
        $tasks = $task->find(["plugin_releases_deploytasks_id" => $id]);
        $task->getFromDB($id);
        foreach ($tasks as $t) {
            self::leveling_task($t['id'], $task);
        }
    }

    /**
     * @param $id
     *
     * @return array
     */
    public static function getAllDescendant($id, $release_id)
    {
        $childrens   = [];
        $task        = new Deploytask();
        $tasks       = $task->find(["plugin_releases_deploytasks_id" => $id,
            "plugin_releases_releases_id" => $release_id]);
        $childrens[] = $id;
        foreach ($tasks as $t) {
            $childs    = self::getAllDescendant($t['id'], $release_id);
            $childrens = array_merge($childrens, $childs);
        }
        return $childrens;
    }
}
