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

 Releases is distributed in the hope that it will be useful,
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

        Toolbox::manageBeginAndEndPlanDates($input['plan']);

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

        //      global $CFG_GLPI;
        //
        //      $rand_template   = mt_rand();
        //      $rand_text       = mt_rand();
        //      $rand_type       = mt_rand();
        //      $rand_time       = mt_rand();
        //      $rand_user       = mt_rand();
        //      $rand_is_private = mt_rand();
        //      $rand_group      = mt_rand();
        //      $rand_name       = mt_rand();
        //      $rand_state      = mt_rand();
        //
        //      if (isset($options['parent']) && !empty($options['parent'])) {
        //         $item    = $options['parent'];
        //         $fkfield = $item::getForeignKeyField();
        //      }
        //
        //      if ($ID > 0) {
        //         $this->check($ID, READ);
        //      } else {
        //         // Create item
        //         $options[$fkfield] = $item->getField('id');
        //         $this->check(-1, CREATE, $options);
        //      }
        //
        //      //prevent null fields due to getFromDB
        //      if (is_null($this->fields['begin'])) {
        //         $this->fields['begin'] = "";
        //      }
        //
        //      $rand = mt_rand();
        //
        //      //      $canplan = (!$item->isStatusExists(CommonITILObject::PLANNED)
        //      //         || $item->isAllowedStatus($item->fields['status'], CommonITILObject::PLANNED));
        //      $canplan = true;
        //      $rowspan = 7;
        //      if ($this->maybePrivate()) {
        //         $rowspan++;
        //      }
        //      if (isset($this->fields["state"])) {
        //         $rowspan++;
        //      }
        //
        //      $this->initForm($ID, $options);
        //      $this->showFormHeader($options);
        //
        //      echo "<tr class='tab_bg_1'>";
        //      echo "<td class='fa-label'>
        //         <span>" . __('Name') . "</span>&nbsp;";
        //      echo "</td>";
        //      echo "<td class='fa-label'>";
        //      echo Html::input("name", ["id"    => "name" . $rand_name,
        //                                "rand"  => $rand_name,
        //                                "value" => $this->fields['name']]);
        //
        //      echo "</td>";
        //      echo "<td >" . __("Previous task", "releases") . "</td>";
        //      echo "<td>";
        //      if ($ID != -1 && $ID != 0) {
        //         $forbidden_id = self::getAllDescendant($this->getID());
        //         Dropdown::show(Deploytask::getType(), ["condition" => ["plugin_releases_releases_id" => $this->fields['plugin_releases_releases_id'],
        //                                                                              "NOT"                         => ["id" => $forbidden_id]],
        //                                                              "value"     => $this->fields["plugin_releases_deploytasks_id"], "comments" => false]);
        //      } else {
        //         Dropdown::show(Deploytask::getType(), ["condition" => ["plugin_releases_releases_id" => $this->fields['plugin_releases_releases_id'],
        //                                                                              "NOT"                         => ["id" => $this->getID()]],
        //                                                              "value"     => $this->fields["plugin_releases_deploytasks_id"],
        //                                                              "comments"  => false]);
        //      }
        //
        //      echo "</td>";
        //      echo "</tr>";
        //
        //      echo "<tr class='tab_bg_1'>";
        //      echo "<td colspan='3' id='content$rand_text'>";
        //
        //      $rand_text  = mt_rand();
        //      $content_id = "content$rand_text";
        //      $cols       = 100;
        //      $rows       = 10;
        //
        //      Html::textarea(['name'              => 'content',
        //                      'value'             => $this->fields["content"],
        //                      'rand'              => $rand_text,
        //                      'editor_id'         => $content_id,
        //                      'enable_fileupload' => true,
        //                      'enable_richtext'   => true,
        //                      'cols'              => $cols,
        //                      'rows'              => $rows]);
        //
        //      echo Html::hidden($fkfield, ['value' => $this->fields[$fkfield]]);
        //
        //      $document = new Document_Item();
        //      $type     = self::getType();
        //
        //      if ($document->find(["itemtype" => $type, "items_id" => $this->getID()])) {
        //         $d       = new Document();
        //         $items_i = $document->find(["itemtype" => $type, "items_id" => $this->getID()]);
        //         //         $item_i = reset($items_i);
        //         foreach ($items_i as $item_d) {
        //            $items_i    = $d->find(["id" => $item_d["documents_id"]]);
        //            $item_i     = reset($items_i);
        //            $foreignKey = "plugin_releases_reviews_id";
        //            $pics_url   = $CFG_GLPI['root_doc'] . "/pics/timeline";
        //
        //            if ($item_i['filename']) {
        //               $filename = $item_i['filename'];
        //               $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        //               echo "<img src='";
        //               if (empty($filename)) {
        //                  $filename = $item_i['name'];
        //               }
        //               if (file_exists(GLPI_ROOT . "/pics/icones/$ext-dist.png")) {
        //                  echo $CFG_GLPI['root_doc'] . "/pics/icones/$ext-dist.png";
        //               } else {
        //                  echo "$pics_url/file.png";
        //               }
        //               echo "'/>&nbsp;";
        //
        //               echo "<a href='" . $CFG_GLPI['root_doc'] . "/front/document.send.php?docid=" . $item_i['id']
        //                    . "&$foreignKey=" . $this->getID() . "' target='_blank'>$filename";
        //               if (Document::isImage(GLPI_DOC_DIR . '/' . $item_i['filepath'])) {
        //                  echo "<div class='timeline_img_preview'>";
        //                  echo "<img src='" . $CFG_GLPI['root_doc'] . "/front/document.send.php?docid=" . $item_i['id']
        //                       . "&$foreignKey=" . $this->getID() . "&context=timeline'/>";
        //                  echo "</div>";
        //               }
        //               echo "</a>";
        //            }
        //            if ($item_i['link']) {
        //               echo "<a href='{$item_i['link']}' target='_blank'><i class='fa fa-external-link'></i>{$item_i['name']}</a>";
        //            }
        //            if (!empty($item_i['mime'])) {
        //               echo "&nbsp;(" . $item_i['mime'] . ")";
        //            }
        //            echo "<span class='buttons'>";
        //            echo "<a href='" . Document::getFormURLWithID($item_i['id']) . "' class='edit_document fa fa-eye pointer' title='" .
        //                 _sx("button", "Show") . "'>";
        //            echo "<span class='sr-only'>" . _sx('button', 'Show') . "</span></a>";
        //
        //            $doc = new Document();
        //            $doc->getFromDB($item_i['id']);
        //            if ($doc->can($item_i['id'], UPDATE)) {
        //               echo "<a href='" . static::getFormURL() .
        //                    "?delete_document&documents_id=" . $item_i['id'] .
        //                    "&$foreignKey=" . $this->getID() . "' class='delete_document fas fa-trash-alt pointer' title='" .
        //                    _sx("button", "Delete permanently") . "'>";
        //               echo "<span class='sr-only'>" . _sx('button', 'Delete permanently') . "</span></a>";
        //            }
        //            echo "</span>";
        //            echo "<br />";
        //         }
        //      }
        //      echo "</td>";
        //
        //      echo "<td style='vertical-align: middle'>";
        //      if ($ID < 0) {
        //         echo "<div class='fa-label'>
        //            <i class='fas fa-reply fa-fw'
        //               title='" . _n('Task template', 'Task templates', 1, 'releases') . "'></i>";
        //         Deploytasktemplate::dropdown(['value'     => $this->fields['plugin_releases_deploytasktemplates_id'],
        //                                                     'entity'    => $this->getEntityID(),
        //                                                     'rand'      => $rand_template,
        //                                                     'on_change' => 'tasktemplate_update(this.value)']);
        //         echo "</div>";
        //         echo Html::scriptBlock('
        //         function tasktemplate_update(value) {
        //            $.ajax({
        //               url: "' . PLUGIN_RELEASES_WEBDIR . '/ajax/deploytask.php",
        //               type: "POST",
        //               data: {
        //                  tasktemplates_id: value
        //               }
        //            }).done(function(data) {
        //               var taskcategories_id = isNaN(parseInt(data.taskcategories_id))
        //                  ? 0
        //                  : parseInt(data.taskcategories_id);
        //               var actiontime = isNaN(parseInt(data.actiontime))
        //                  ? 0
        //                  : parseInt(data.actiontime);
        //               var user_tech = isNaN(parseInt(data.users_id_tech))
        //                  ? 0
        //                  : parseInt(data.users_id_tech);
        //               var group_tech = isNaN(parseInt(data.groups_id_tech))
        //                  ? 0
        //                  : parseInt(data.groups_id_tech);
        //
        //               // set textarea content
        //               $("#content' . $rand_text . '").html(data.content);
        //               // set name
        //               $("#name' . $rand_name . '").val(data.name);
        //               // set also tinmyce (if enabled)
        //               if (tasktinymce = tinymce.get("content' . $rand_text . '")) {
        //                  tasktinymce.setContent(data.content.replace(/\r?\n/g, "<br />"));
        //               }
        //               // set category
        //               $("#dropdown_taskcategories_id' . $rand_type . '").trigger("setValue", taskcategories_id);
        //               // set action time
        //               $("#dropdown_actiontime' . $rand_time . '").trigger("setValue", actiontime);
        //               // set is_private
        //               $("#is_privateswitch' . $rand_is_private . '")
        //                  .prop("checked", data.is_private == "0"
        //                     ? false
        //                     : true);
        //               // set users_tech
        //               $("#dropdown_users_id_tech' . $rand_user . '").trigger("setValue", user_tech);
        //               // set group_tech
        //               $("#dropdown_groups_id_tech' . $rand_group . '").trigger("setValue", group_tech);
        //               // set state
        //               $("#dropdown_state' . $rand_state . '").trigger("setValue", data.state);
        //            });
        //         }
        //      ');
        //      }
        //
        //
        //      if ($ID > 0) {
        //         echo "<div class='fa-label'>
        //         <i class='far fa-calendar fa-fw'
        //            title='" . __('Date') . "'></i>";
        //         Html::showDateTimeField("date", ['value'      => $this->fields["date"],
        //                                          'timestep'   => 1,
        //                                          'maybeempty' => false]);
        //         echo "</div>";
        //      }
        //
        //      echo "<div class='fa-label'>
        //         <i class='fas fa-tag fa-fw'
        //            title='" . __('Category') . "'></i>";
        //      TypeDeployTask::dropdown([
        //                                                'value'  => $this->fields["plugin_releases_typedeploytasks_id"],
        //                                                'rand'   => $rand_type,
        //                                                'entity' => $item->fields["entities_id"],
        //                                                //         'condition' => ['is_active' => 1]
        //                                             ]);
        //      echo "</div>";
        //      echo "<div class='fa-label'>
        //         <span>" . __('Risk', 'releases') . "</span>&nbsp;";
        //      Dropdown::show(Risk::getType(), ['name'      => "plugin_releases_risks_id",
        //                                                     "condition" => ["plugin_releases_releases_id" => $this->fields['plugin_releases_releases_id']],
        //                                                     'value'     => $this->fields["plugin_releases_risks_id"]]);
        //      echo "</div>";
        //
        //      if (isset($this->fields["state"])) {
        //         echo "<div class='fa-label'>
        //            <i class='fas fa-tasks fa-fw'
        //               title='" . __('Status') . "'></i>";
        //         self::dropdownStateTask("state", $this->fields["state"], true, ['rand' => $rand_state]);
        //         echo "</div>";
        //      }
        //
        //      echo "<div class='fa-label'>
        //         <i class='fas fa-stopwatch fa-fw'
        //            title='" . __('Duration') . "'></i>";
        //
        //      $toadd = [];
        //      for ($i = 9; $i <= 100; $i++) {
        //         $toadd[] = $i * HOUR_TIMESTAMP;
        //      }
        //
        //      Dropdown::showTimeStamp("actiontime", ['min'             => 0,
        //                                             'max'             => 8 * HOUR_TIMESTAMP,
        //                                             'value'           => $this->fields["actiontime"],
        //                                             'rand'            => $rand_time,
        //                                             'addfirstminutes' => true,
        //                                             'inhours'         => true,
        //                                             'toadd'           => $toadd,
        //                                             'width'           => '']);
        //
        //      echo "</div>";
        //
        //      echo "<div class='fa-label'>";
        //      echo "<i class='fas fa-user fa-fw' title='" . _n('User', 'Users', 1) . "'></i>";
        //      $params = ['name'   => "users_id_tech",
        //                 'value'  => (($ID > -1)
        //                    ? $this->fields["users_id_tech"]
        //                    : Session::getLoginUserID()),
        //                 'right'  => "own_ticket",
        //                 'rand'   => $rand_user,
        //                 'entity' => $item->fields["entities_id"],
        //                 'width'  => ''];
        //
        //      $params['toupdate'] = ['value_fieldname'
        //                                         => 'users_id',
        //                             'to_update' => "user_available$rand_user",
        //                             'url'       => $CFG_GLPI["root_doc"] . "/ajax/planningcheck.php"];
        //      User::dropdown($params);
        //
        //      echo " <a href='#' title=\"" . __s('Availability') . "\" onClick=\"" . Html::jsGetElementbyID('planningcheck' . $rand) . ".dialog('open'); return false;\">";
        //      echo "<i class='far fa-calendar-alt'></i>";
        //      echo "<span class='sr-only'>" . __('Availability') . "</span>";
        //      echo "</a>";
        //      Ajax::createIframeModalWindow('planningcheck' . $rand,
        //                                    $CFG_GLPI["root_doc"] .
        //                                    "/front/planning.php?checkavailability=checkavailability" .
        //                                    "&itemtype=" . $item->getType() . "&$fkfield=" . $item->getID(),
        //                                    ['title' => __('Availability')]);
        //      echo "</div>";
        //
        //      echo "<div class='fa-label'>";
        //      echo "<i class='fas fa-users fa-fw' title='" . _n('Group', 'Groups', 1) . "'></i>";
        //      $params = [
        //         'name'      => "groups_id_tech",
        //         'value'     => (($ID > -1)
        //            ? $this->fields["groups_id_tech"]
        //            : Dropdown::EMPTY_VALUE),
        //         'condition' => ['is_task' => 1],
        //         'rand'      => $rand_group,
        //         'entity'    => $item->fields["entities_id"]
        //      ];
        //
        //      $params['toupdate'] = ['value_fieldname' => 'users_id',
        //                             'to_update'       => "group_available$rand_group",
        //                             'url'             => $CFG_GLPI["root_doc"] . "/ajax/planningcheck.php"];
        //      Group::dropdown($params);
        //      echo "</div>";
        //
        //      if (!empty($this->fields["begin"])) {
        //
        //         if (Session::haveRight('planning', Planning::READMY)) {
        //            echo "<script type='text/javascript' >\n";
        //            echo "function showPlan" . $ID . $rand_text . "() {\n";
        //            echo Html::jsHide("plan$rand_text");
        //            $params = ['action'     => 'add_event_classic_form',
        //                       'form'       => 'followups',
        //                       'users_id'   => $this->fields["users_id_tech"],
        //                       'groups_id'  => $this->fields["groups_id_tech"],
        //                       'id'         => $this->fields["id"],
        //                       'begin'      => $this->fields["begin"],
        //                       'end'        => $this->fields["end"],
        //                       'rand_user'  => $rand_user,
        //                       'rand_group' => $rand_group,
        //                       'entity'     => $item->fields["entities_id"],
        //                       'itemtype'   => $this->getType(),
        //                       'items_id'   => $this->getID()];
        //            Ajax::updateItemJsCode("viewplan$rand_text", $CFG_GLPI["root_doc"] . "/ajax/planning.php",
        //                                   $params);
        //            echo "}";
        //            echo "</script>\n";
        //            echo "<div id='plan$rand_text' onClick='showPlan" . $ID . $rand_text . "()'>\n";
        //            echo "<span class='showplan'>";
        //         }
        //
        //         if (isset($this->fields["state"])) {
        //            echo Planning::getState($this->fields["state"]) . "<br>";
        //         }
        //         printf(__('From %1$s to %2$s'), Html::convDateTime($this->fields["begin"]),
        //                Html::convDateTime($this->fields["end"]));
        //         if (isset($this->fields["users_id_tech"]) && ($this->fields["users_id_tech"] > 0)) {
        //            echo "<br>" . getUserName($this->fields["users_id_tech"]);
        //         }
        //         if (isset($this->fields["groups_id_tech"]) && ($this->fields["groups_id_tech"] > 0)) {
        //            echo "<br>" . Dropdown::getDropdownName('glpi_groups', $this->fields["groups_id_tech"]);
        //         }
        //         if (Session::haveRight('planning', Planning::READMY)) {
        //            echo "</span>";
        //            echo "</div>\n";
        //            echo "<div id='viewplan$rand_text'></div>\n";
        //         }
        //
        //      } else {
        //         if ($canplan) {
        //            echo "<script type='text/javascript' >\n";
        //            echo "function showPlanUpdate$rand_text() {\n";
        //            echo Html::jsHide("plan$rand_text");
        //            $params = ['action'     => 'add_event_classic_form',
        //                       'form'       => 'followups',
        //                       'entity'     => $item->fields['entities_id'],
        //                       'rand_user'  => $rand_user,
        //                       'rand_group' => $rand_group,
        //                       'itemtype'   => $this->getType(),
        //                       'items_id'   => $this->getID()];
        //            Ajax::updateItemJsCode("viewplan$rand_text", $CFG_GLPI["root_doc"] . "/ajax/planning.php",
        //                                   $params);
        //            echo "};";
        //            echo "</script>";
        //
        //            if ($canplan) {
        //               echo "<div id='plan$rand_text'  onClick='showPlanUpdate$rand_text()'>\n";
        //               echo "<span class='btn btn-primary'>" . __('Plan this task') . "</span>";
        //               echo "</div>\n";
        //               echo "<div id='viewplan$rand_text'></div>\n";
        //            }
        //         } else {
        //            echo __('None');
        //         }
        //      }
        //
        //      echo "</td></tr>";
        //
        //      if (!empty($this->fields["begin"])
        //          && PlanningRecall::isAvailable()) {
        //
        //         echo "<tr class='tab_bg_1'><td>" . _x('Planning', 'Reminder') . "</td><td class='center'>";
        //         PlanningRecall::dropdown(['itemtype' => $this->getType(),
        //                                   'items_id' => $this->getID()]);
        //         echo "</td><td colspan='2'></td></tr>";
        //      }
        //
        //      $this->showFormButtons($options);

        //      return true;
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
            'LEFT JOIN' => [
                'glpi_plugin_releases_typedeploytasks' => [
                    'ON' => 'glpi_plugin_releases_typedeploytasks.id = glpi_plugin_releases_deploytasks.plugin_releases_typedeploytasks_id',
                ],
            ],
            'WHERE'  => $WHERE,
            'ORDER BY' => 'begin',
        ];
        $result = $DB->doQuery($query);

        if ($DB->numrows($result) > 0) {
            for ($i = 0; $data = $DB->fetchArray($result); $i++) {
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

    //   /**
    //    * @param $ID
    //    * @param $entity
    //    *
    //    * @return ID|int|the
    //    */
    //   static function transfer($ID, $entity) {
    //      global $DB;
    //
    //      if ($ID > 0) {
    //         $self  = new self();
    //         $items = $self->find(["plugin_releases_releases_id" => $ID]);
    //         foreach ($items as $id => $vals) {
    //            $input                = [];
    //            $input["id"]          = $id;
    //            $input["entities_id"] = $entity;
    //            $self->update($input);
    //            self::transferDocument($id, $entity);
    //         }
    //         return true;
    //
    //      }
    //      return 0;
    //   }
    //
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
