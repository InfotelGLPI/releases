<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Presales plugin for GLPI
 Copyright (C) 2018 by the Presales Development Team.

 https://github.com/InfotelGLPI/presales
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Presales.

 Presales is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Presales is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Presales. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginPresalesTask
 */
class PluginReleasesDeployTask extends CommonDBTM {

   public $dohistory = true;
   static $rightname = 'ticket';
   protected $usenotepad = true;
   static $types = [];


   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Deploy task', 'Deploy tasks', $nb, 'releases');
   }
   /**
    *
    * @return css class
    */
   static function getCssClass() {

      return "task";
   }


   //TODO
   /**
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];
      return $tab;

   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == self::getType()) {
        return self::getTypeName(2);
      } else if ($item->getType() == PluginReleasesRelease::getType()){
         return self::createTabEntry(self::getTypeName(2), self::countForItem($item));
      }

      return '';
   }
   static function countForItem(CommonDBTM $item) {
      $dbu = new DbUtils();
      $table = CommonDBTM::getTable(PluginReleasesDeployTask::class);
      return $dbu->countElementsInTable($table,
         ["plugin_release_releases_id" => $item->getID()]);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $CFG_GLPI;
      if ($item->getType() == PluginReleasesRelease::getType()) {
         $self = new self();
         if(self::canView()){
            $self->showScripts($item);
         }

      }

   }
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      return $ong;
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

   function initShowForm($ID, $options = []){


      $this->initForm($ID, $options);
      $this->showFormHeader($options);

   }

   function closeShowForm($options){
      $this->showFormButtons($options);
   }

   function showForm($ID, $options = []) {

      global $CFG_GLPI;

      $rand_template   = mt_rand();
      $rand_text       = mt_rand();
      $rand_type       = mt_rand();
      $rand_time       = mt_rand();
      $rand_user       = mt_rand();
      $rand_is_private = mt_rand();
      $rand_group      = mt_rand();
      $rand_name      = mt_rand();
      $rand_state      = mt_rand();

      if (isset($options['parent']) && !empty($options['parent'])) {
         $item = $options['parent'];
      }

      $fkfield = $item::getForeignKeyField();

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[$fkfield] = $item->getField('id');
         $this->check(-1, CREATE, $options);
      }

      //prevent null fields due to getFromDB
      if (is_null($this->fields['begin'])) {
         $this->fields['begin'] = "";
      }

      $rand = mt_rand();
      $this->showFormHeader($options);

//      $canplan = (!$item->isStatusExists(CommonITILObject::PLANNED)
//         || $item->isAllowedStatus($item->fields['status'], CommonITILObject::PLANNED));
      $canplan = true;
      $rowspan = 7;
      if ($this->maybePrivate()) {
         $rowspan++;
      }
      if (isset($this->fields["state"])) {
         $rowspan++;
      }
      echo "<tr class='tab_bg_1'>";
      echo "<td class='fa-label'>
         <span>".__('Name')."</span>&nbsp;";
      echo "</td>";
      echo "<td class='fa-label'>";
      echo Html::input("name",["id"=>"name".$rand_name,"rand"=>$rand_name,"value"=>$this->getField('name')]);

      echo "</td>";
      echo "<td colspan='2'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='3' id='content$rand_text'>";

      $rand_text  = mt_rand();
      $content_id = "content$rand_text";
      $cols       = 100;
      $rows       = 10;

      Html::textarea(['name'              => 'content',
         'value'             => $this->fields["content"],
         'rand'              => $rand_text,
         'editor_id'         => $content_id,
         'enable_fileupload' => true,
         'enable_richtext'   => true,
         'cols'              => $cols,
         'rows'              => $rows]);

      echo "<input type='hidden' name='$fkfield' value='".$this->fields[$fkfield]."'>";
      echo "</td>";

      echo "<td style='vertical-align: middle'>";
      echo "<div class='fa-label'>
            <i class='fas fa-reply fa-fw'
               title='"._n('Task template', 'Task templates', 2)."'></i>";
      PluginReleasesDeploytasktemplate::dropdown(['value'     => $this->fields['plugin_release_deploytasktemplates_id'],
         'entity'    => $this->getEntityID(),
         'rand'      => $rand_template,
         'on_change' => 'tasktemplate_update(this.value)']);
      echo "</div>";
      echo Html::scriptBlock('
         function tasktemplate_update(value) {
            $.ajax({
               url: "' . $CFG_GLPI["root_doc"] . '/plugins/releases/ajax/deploytask.php",
               type: "POST",
               data: {
                  tasktemplates_id: value
               }
            }).done(function(data) {
               console.log(data);
               var taskcategories_id = isNaN(parseInt(data.taskcategories_id))
                  ? 0
                  : parseInt(data.taskcategories_id);
               var actiontime = isNaN(parseInt(data.actiontime))
                  ? 0
                  : parseInt(data.actiontime);
               var user_tech = isNaN(parseInt(data.users_id_tech))
                  ? 0
                  : parseInt(data.users_id_tech);
               var group_tech = isNaN(parseInt(data.groups_id_tech))
                  ? 0
                  : parseInt(data.groups_id_tech);

               // set textarea content
               $("#content'.$rand_text.'").html(data.content);
               // set name
               $("#name'.$rand_name.'").val(data.name);
               // set also tinmyce (if enabled)
               if (tasktinymce = tinymce.get("content'.$rand_text.'")) {
                  tasktinymce.setContent(data.content.replace(/\r?\n/g, "<br />"));
               }
               // set category
               $("#dropdown_taskcategories_id'.$rand_type.'").trigger("setValue", taskcategories_id);
               // set action time
               $("#dropdown_actiontime'.$rand_time.'").trigger("setValue", actiontime);
               // set is_private
               $("#is_privateswitch'.$rand_is_private.'")
                  .prop("checked", data.is_private == "0"
                     ? false
                     : true);
               // set users_tech
               $("#dropdown_users_id_tech'.$rand_user.'").trigger("setValue", user_tech);
               // set group_tech
               $("#dropdown_groups_id_tech'.$rand_group.'").trigger("setValue", group_tech);
               // set state
               $("#dropdown_state'.$rand_state.'").trigger("setValue", data.state);
            });
         }
      ');


      if ($ID > 0) {
         echo "<div class='fa-label'>
         <i class='far fa-calendar fa-fw'
            title='".__('Date')."'></i>";
         Html::showDateTimeField("date", ['value'      => $this->fields["date"],
            'timestep'   => 1,
            'maybeempty' => false]);
         echo "</div>";
      }

      echo "<div class='fa-label'>
         <i class='fas fa-tag fa-fw'
            title='".__('Category')."'></i>";
      PluginReleasesTypeDeployTask::dropdown([
         'value'     => $this->fields["plugin_release_typedeploytasks_id"],
         'rand'      => $rand_type,
         'entity'    => $item->fields["entities_id"],
//         'condition' => ['is_active' => 1]
      ]);
      echo "</div>";
      echo "<div class='fa-label'>
         <span>".__('Risk')."</span>&nbsp;";
      Dropdown::show(PluginReleasesRisk::getType(), ['name' => "plugin_release_risks_id", "condition"=>["plugin_release_releases_id"=>$options['plugin_release_releases_id']],
         'value' =>  $this->fields["plugin_release_risks_id"]]);
      echo "</div>";

      if (isset($this->fields["state"])) {
         echo "<div class='fa-label'>
            <i class='fas fa-tasks fa-fw'
               title='".__('Status')."'></i>";
         PluginReleasesRelease::dropdownStateItem("state", $this->fields["state"], true, ['rand' => $rand_state]);
         echo "</div>";
      }

      if ($this->maybePrivate()) {
         echo "<div class='fa-label'>
            <i class='fas fa-lock fa-fw' title='".__('Private')."'></i>
            <span class='switch pager_controls'>
               <label for='is_privateswitch$rand_is_private' title='".__('Private')."'>
                  <input type='hidden' name='is_private' value='0'>
                  <input type='checkbox' id='is_privateswitch$rand_is_private' name='is_private' value='1'".
            ($this->fields["is_private"]
               ? "checked='checked'"
               : "")."
                  >
                  <span class='lever'></span>
               </label>
            </span>
         </div>";
      }

      echo "<div class='fa-label'>
         <i class='fas fa-stopwatch fa-fw'
            title='".__('Duration')."'></i>";

      $toadd = [];
      for ($i=9; $i<=100; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("actiontime", ['min'             => 0,
         'max'             => 8*HOUR_TIMESTAMP,
         'value'           => $this->fields["actiontime"],
         'rand'            => $rand_time,
         'addfirstminutes' => true,
         'inhours'         => true,
         'toadd'           => $toadd,
         'width'  => '']);

      echo "</div>";

      echo "<div class='fa-label'>";
      echo "<i class='fas fa-user fa-fw' title='"._n('User', 'Users', 1)."'></i>";
      $params             = ['name'   => "users_id_tech",
         'value'  => (($ID > -1)
            ?$this->fields["users_id_tech"]
            :Session::getLoginUserID()),
         'right'  => "own_ticket",
         'rand'   => $rand_user,
         'entity' => $item->fields["entities_id"],
         'width'  => ''];

      $params['toupdate'] = ['value_fieldname'
      => 'users_id',
         'to_update' => "user_available$rand_user",
         'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php"];
      User::dropdown($params);

      echo " <a href='#' title=\"".__s('Availability')."\" onClick=\"".Html::jsGetElementbyID('planningcheck'.$rand).".dialog('open'); return false;\">";
      echo "<i class='far fa-calendar-alt'></i>";
      echo "<span class='sr-only'>".__('Availability')."</span>";
      echo "</a>";
      Ajax::createIframeModalWindow('planningcheck'.$rand,
         $CFG_GLPI["root_doc"].
         "/front/planning.php?checkavailability=checkavailability".
         "&itemtype=".$item->getType()."&$fkfield=".$item->getID(),
         ['title'  => __('Availability')]);
      echo "</div>";

      echo "<div class='fa-label'>";
      echo "<i class='fas fa-users fa-fw' title='"._n('Group', 'Groups', 1)."'></i>";
      $params     = [
         'name'      => "groups_id_tech",
         'value'     => (($ID > -1)
            ?$this->fields["groups_id_tech"]
            :Dropdown::EMPTY_VALUE),
         'condition' => ['is_task' => 1],
         'rand'      => $rand_group,
         'entity'    => $item->fields["entities_id"]
      ];

      $params['toupdate'] = ['value_fieldname' => 'users_id',
         'to_update' => "group_available$rand_group",
         'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php"];
      Group::dropdown($params);
      echo "</div>";

      if (!empty($this->fields["begin"])) {

         if (Session::haveRight('planning', Planning::READMY)) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlan".$ID.$rand_text."() {\n";
            echo Html::jsHide("plan$rand_text");
            $params = ['action'    => 'add_event_classic_form',
               'form'      => 'followups',
               'users_id'  => $this->fields["users_id_tech"],
               'groups_id' => $this->fields["groups_id_tech"],
               'id'        => $this->fields["id"],
               'begin'     => $this->fields["begin"],
               'end'       => $this->fields["end"],
               'rand_user' => $rand_user,
               'rand_group' => $rand_group,
               'entity'    => $item->fields["entities_id"],
               'itemtype'  => $this->getType(),
               'items_id'  => $this->getID()];
            Ajax::updateItemJsCode("viewplan$rand_text", $CFG_GLPI["root_doc"] . "/ajax/planning.php",
               $params);
            echo "}";
            echo "</script>\n";
            echo "<div id='plan$rand_text' onClick='showPlan".$ID.$rand_text."()'>\n";
            echo "<span class='showplan'>";
         }

         if (isset($this->fields["state"])) {
            echo Planning::getState($this->fields["state"])."<br>";
         }
         printf(__('From %1$s to %2$s'), Html::convDateTime($this->fields["begin"]),
            Html::convDateTime($this->fields["end"]));
         if (isset($this->fields["users_id_tech"]) && ($this->fields["users_id_tech"] > 0)) {
            echo "<br>".getUserName($this->fields["users_id_tech"]);
         }
         if (isset($this->fields["groups_id_tech"]) && ($this->fields["groups_id_tech"] > 0)) {
            echo "<br>".Dropdown::getDropdownName('glpi_groups', $this->fields["groups_id_tech"]);
         }
         if (Session::haveRight('planning', Planning::READMY)) {
            echo "</span>";
            echo "</div>\n";
            echo "<div id='viewplan$rand_text'></div>\n";
         }

      } else {
         if ($canplan) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlanUpdate$rand_text() {\n";
            echo Html::jsHide("plan$rand_text");
            $params = ['action'    => 'add_event_classic_form',
               'form'      => 'followups',
               'entity'    => $item->fields['entities_id'],
               'rand_user' => $rand_user,
               'rand_group' => $rand_group,
               'itemtype'  => $this->getType(),
               'items_id'  => $this->getID()];
            Ajax::updateItemJsCode("viewplan$rand_text", $CFG_GLPI["root_doc"]."/ajax/planning.php",
               $params);
            echo "};";
            echo "</script>";

            if ($canplan) {
               echo "<div id='plan$rand_text'  onClick='showPlanUpdate$rand_text()'>\n";
               echo "<span class='vsubmit'>".__('Plan this task')."</span>";
               echo "</div>\n";
               echo "<div id='viewplan$rand_text'></div>\n";
            }
         } else {
            echo __('None');
         }
      }

      echo "</td></tr>";

      if (!empty($this->fields["begin"])
         && PlanningRecall::isAvailable()) {

         echo "<tr class='tab_bg_1'><td>"._x('Planning', 'Reminder')."</td><td class='center'>";
         PlanningRecall::dropdown(['itemtype' => $this->getType(),
            'items_id' => $this->getID()]);
         echo "</td><td colspan='2'></td></tr>";
      }

      $this->showFormButtons($options);

      return true;

   }

   function prepareInputForAdd($input) {

      $input =  parent::prepareInputForAdd($input);

      $input["users_id"] = Session::getLoginUserID();

      return $input;
   }
   function prepareInputForUpdate($input) {

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

      if (isset($input['_planningrecall'])) {
         PlanningRecall::manageDatas($input['_planningrecall']);
      }

      // update last editor if content change
      if (isset($input['update'])
         && ($uid = Session::getLoginUserID())) { // Change from task form
         $input["users_id_editor"] = $uid;
      }


      $input["_job"] = new PluginReleasesRelease();

      if (isset($input[$input["_job"]->getForeignKeyField()])
         && !$input["_job"]->getFromDB($input[$input["_job"]->getForeignKeyField()])) {
         return false;
      }

      if (isset($input["plan"])) {
         $input["begin"]         = $input['plan']["begin"];
         $input["end"]           = $input['plan']["end"];

         $timestart              = strtotime($input["begin"]);
         $timeend                = strtotime($input["end"]);
         $input["actiontime"]    = $timeend-$timestart;

         unset($input["plan"]);

         if (!$this->test_valid_date($input)) {
            Session::addMessageAfterRedirect(__('Error in entering dates. The starting date is later than the ending date'),
               false, ERROR);
            return false;
         }
         Planning::checkAlreadyPlanned($input["users_id_tech"], $input["begin"], $input["end"],
            [$this->getType() => [$input["id"]]]);

         $calendars_id = Entity::getUsedConfig('calendars_id', $input["_job"]->fields['entities_id']);
         $calendar     = new Calendar();

         // Using calendar
         if (($calendars_id > 0)
            && $calendar->getFromDB($calendars_id)) {
            if (!$calendar->isAWorkingHour(strtotime($input["begin"]))) {
               Session::addMessageAfterRedirect(__('Start of the selected timeframe is not a working hour.'),
                  false, ERROR);
            }
            if (!$calendar->isAWorkingHour(strtotime($input["end"]))) {
               Session::addMessageAfterRedirect(__('End of the selected timeframe is not a working hour.'),
                  false, ERROR);
            }
         }
      }

      $input = $this->addFiles($input);

      return $input;
   }
   function post_addItem() {

      $this->input["_job"] = new PluginReleasesRelease();

      if (isset($this->input[$this->input["_job"]->getForeignKeyField()])
         && !$this->input["_job"]->getFromDB($this->input[$this->input["_job"]->getForeignKeyField()])) {
         return false;
      }

      // Add document if needed, without notification
      $this->input = $this->addFiles($this->input, ['force_update' => true]);
   }
   /**
    * Current dates are valid ? begin before end
    *
    * @param $input
    *
    *@return boolean
    **/
   function test_valid_date($input) {

      return (!empty($input["begin"])
         && !empty($input["end"])
         && (strtotime($input["begin"]) < strtotime($input["end"])));
   }
   function showScripts(PluginReleasesRelease $release) {
      echo "<div class='timeline_box'>";
      $rand = mt_rand();
      $release->showTimelineForm($rand,self::class);
      $release->showTimeLine($rand,self::class);

      echo "</div>";
   }



}

