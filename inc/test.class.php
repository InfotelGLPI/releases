<?php
/*
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2015 by the Releases Development Team.
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
 along with Releases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginReleasesTest extends CommonDBTM {

   var          $dohistory          = true;
   static       $rightname          = "plugin_releases";
   protected    $usenotepad         = true;
   protected    $usenotepadrights   = true;


   /**
    * @since version 0.84
   **/
   static function getTypeName($nb=0) {
      return _n('Test', 'Tests', $nb, 'plugin_releases');
   }


   /**
    * Return the name of the tab for item including forms like the config page
    *
    * @param  CommonGLPI $item Instance of a CommonGLPI Item (The Config Item)
    * @param  integer    $withtemplate
    *
    * @return String                   Name to be displayed
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item->getType()) {
         case "Change":
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = countElementsInTable('glpi_plugin_releases_tests',
                     ["changes_id" => $item->getID()]);
            }
            return self::createTabEntry(self::getTypeName($nb), $nb);
            break;
      }
      return '';
   }
   
   /**
    * @param CommonGLPI $item
    * @param int $tabnum
    * @param int $withtemplate
    * @return bool
    */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $test = new self();
      $ID = $_GET['id'];
      $test->showSummary($item, $ID);
   }

   static function canCreate() {
      return Session::haveRight('plugin_releases', UPDATE);
       return true;
   }


   static function canView() {
      return Session::haveRightsOr('plugin_releases', array(Change::READALL, Change::READMY));
       return true;
   }


   static function canUpdate() {
      return Session::haveRight('plugin_releases', UPDATE);
        return true;
   }


   function canViewPrivates() {
      return true;
   }


   function canEditAll() {
      return Session::haveRightsOr('plugin_releases', array(CREATE, UPDATE, DELETE, PURGE));
       return true;
   }



   /**
    * Is the current user have right to create the current task ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      $change = new Change();

      if ($change->getFromDB($this->fields['changes_id'])) {
         return (Session::haveRight('plugin_releases', UPDATE)
                 || (Session::haveRight('plugin_releases', Change::READMY)
                     && ($change->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                         || (isset($_SESSION["glpigroups"])
                             && $change->haveAGroup(CommonITILActor::ASSIGN,
                                                    $_SESSION['glpigroups'])))));
      }
      return false;
       return true;

   }


   /**
    * Is the current user have right to update the current task ?
    *
    * @return boolean
   **/
   function canUpdateItem() {

      if (($this->fields["users_id"] != Session::getLoginUserID())
          && !Session::haveRight('plugin_releases', UPDATE)) {
         return false;
      }

      return true;
   }


   /**
    * Is the current user have right to purge the current task ?
    *
    * @return boolean
   **/
   function canPurgeItem() {
      return $this->canUpdateItem();
       return true;
   }


      
      /**
    * @param $item         CommonITILObject
    * @param $rand
    * @param $showprivate  (false by default)
   **/
   function showInObjectSumnary(CommonITILObject $item, $rand, $showprivate=false) {
      global $DB, $CFG_GLPI;

      $canedit = (isset($this->fields['can_edit']) && !$this->fields['can_edit']) ? false : $this->canEdit($this->fields['id']) ;
      $canview = $this->canViewItem();

      echo "<tr class='tab_bg_";
      if ($this->maybePrivate()
          && ($this->fields['is_private'] == 1)) {
         echo "4' ";
      } else {
         echo "2' ";
      }

      if (1) {//$canedit
         echo "style='cursor:pointer' onClick=\"viewEditTask".$item->fields['id'].
               $this->fields['id']."$rand();\"";
      }

      echo " id='viewfollowup" . $this->fields[$item->getForeignKeyField()] . $this->fields["id"] .
            "$rand'>";

      if (1) {//$canview
         echo "<td>";
         switch ($this->fields['state']) {
            case 0:
               echo '<img src="'.$CFG_GLPI['root_doc'].'/plugins/releases/pics/grey.png" alt="'.__('To do').'" width=10 height=10>';
               break;

            case 1:
               echo '<img src="'.$CFG_GLPI['root_doc'].'/plugins/releases/pics/yellow.png" alt="'.__('In progress').'" width=10 height=10>';
                break;

            case 2:
               echo '<img src="'.$CFG_GLPI['root_doc'].'/plugins/releases/pics/green.png" alt="'.__('Done').'" width=10 height=10>';
               break;
           
            case 3:
               echo '<img src="'.$CFG_GLPI['root_doc'].'/plugins/releases/pics/red.png" alt="'.__('Default').'" width=10 height=10>';
               break;
         }
         echo "</td>";
         echo "<td>";
         echo $this->fields['name'];
         echo "</td>";
         echo "<td>";
         echo static::getStateName($this->fields['state']);
         echo "</td>";
         echo "<td>";
         $typename = $this->getTypeName(1);
         if ($this->fields['taskcategories_id']) {
            printf(__('%1$s - %2$s'), $typename,
                   Dropdown::getDropdownName('glpi_taskcategories',
                                             $this->fields['taskcategories_id']));
         } else {
            echo getTreeLeafValueName('glpi_taskcategories', $this->fields['taskcategories_id']);
         }
         echo "</td>";
         echo "<td>";
         if ($canedit) {
            echo "\n<script type='text/javascript' >\n";
            echo "function viewEditTask" . $item->fields['id'] . $this->fields["id"] . "$rand() {\n";
            $params = array('type'       => $this->getType(),
                            'parenttype' => $item->getType(),
                            $item->getForeignKeyField()
                                         => $this->fields[$item->getForeignKeyField()],
                            'id'         => $this->fields["id"]);
            Ajax::updateItemJsCode("viewfollowup" . $item->fields['id'] . "$rand",
                                   $CFG_GLPI["root_doc"]."/plugins/releases/ajax/viewsubitem.php", $params);
            echo "};";
            echo "</script>\n";
         }
         //else echo "--no--";
         echo Html::convDateTime($this->fields["date"]) . "</td>";
         echo "<td class='left'>" . nl2br(html_entity_decode($this->fields["content"])) . "</td>";
         echo "<td>".Html::timestampToString($this->fields["actiontime"], 0)."</td>";
         echo "<td>" . getUserName($this->fields["users_id_tech"]) . "</td>";
         if ($this->maybePrivate() && $showprivate) {
            echo "<td>".Dropdown::getYesNo($this->fields["is_private"])."</td>";
         }
//         echo "<td>";
//         if (empty($this->fields["begin"])) {
//            if (isset($this->fields["state"])) {
//               echo Planning::getState($this->fields["state"])."<br>";
//            }
//            if ($this->fields["users_id_tech"] || $this->fields["groups_id_tech"]) {
//               if (isset($this->fields["users_id_tech"])) {
//                  printf('%1$s %2$s',__('By user'),getUserName($this->fields["users_id_tech"]));
//               }
//               if (isset($this->fields["groups_id_tech"])) {
//                  $groupname = sprintf('%1$s %2$s',"<br />".__('By group'),
//                                       Dropdown::getDropdownName('glpi_groups',
//                                                                 $this->fields["groups_id_tech"]));
//                  if ($_SESSION['glpiis_ids_visible']) {
//                     $groupname = printf(__('%1$s (%2$s)'), $groupname, $this->fields["groups_id_tech"]);
//                  }
//                  echo $groupname;
//               }
//            } else {
//               _e('None');
//            }
//         } else {
//            echo "<table width='100%'>";
//            if (isset($this->fields["state"])) {
//               echo "<tr><td>"._x('item', 'State')."</td><td>";
//               echo Planning::getState($this->fields["state"])."</td></tr>";
//            }
//            echo "<tr><td>".__('Begin')."</td><td>";
//            echo Html::convDateTime($this->fields["begin"])."</td></tr>";
//            echo "<tr><td>".__('End')."</td><td>";
//            echo Html::convDateTime($this->fields["end"])."</td></tr>";
//            echo "<tr><td>";
//            if ($this->fields["users_id_tech"]) {
//               printf('%1$s %2$s',__('By user'),getUserName($this->fields["users_id_tech"]));
//            }
//            if ($this->fields["groups_id_tech"]) {
//               $groupname = sprintf('%1$s %2$s',"<br />".__('By group'),
//                                     Dropdown::getDropdownName('glpi_groups',
//                                                               $this->fields["groups_id_tech"]));
//               if ($_SESSION['glpiis_ids_visible']) {
//                   $groupname = printf(__('%1$s (%2$s)'), $groupname,
//                                       $this->fields["groups_id_tech"]);
//               }
//               echo $groupname;
//            }
//            if (PlanningRecall::isAvailable()
//                && $_SESSION["glpiactiveprofile"]["interface"] == "central") {
//               echo "<tr><td>"._x('Planning','Reminder')."</td><td>";
//               PlanningRecall::specificForm(array('itemtype' => $this->getType(),
//                                                  'items_id' => $this->fields["id"]));
//            }
//            echo "</td></tr>";
//            echo "</table>";
//         }
         echo "</td></tr>\n";
      }
   }


   /** form for Task
    *
    * @param $ID        Integer : Id of the task
    * @param $options   array
    *     -  parent Object : the object
   **/
   function showForm($ID, $options=array()) {
      global $DB, $CFG_GLPI;

      $rand_template = mt_rand();
      $rand_text     = mt_rand();
      $rand_type     = mt_rand();
      $rand_time     = mt_rand();

      if (isset($options['parent']) && !empty($options['parent'])) {
         $item = $options['parent'];
      }
      else if (isset($options['idChange'])){
          $item = new Change();
          $item->getFromDB($options['idChange']);
      }

      $fkfield = $item->getForeignKeyField();

      if ($ID > 0) {
         //$this->check($ID, READ);
          $this->getFromDB($ID);
      } else {
         // Create item
         $options[$fkfield] = $item->getField('id');
         $this->check(-1, CREATE, $options);
      }

      $rand = mt_rand();
      $this->showFormHeader($options);

      $canplan = (!$item->isStatusExists(CommonITILObject::PLANNED)
                  || $item->isAllowedStatus($item->fields['status'], CommonITILObject::PLANNED));

      $rowspan = 5;
      if ($this->maybePrivate()) {
         $rowspan++;
      }
      if (isset($this->fields["state"])) {
         $rowspan++;
      }
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo '<td colspan="3">';
      Html::autocompletionTextField($this, "name");
      echo "<td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td rowspan='$rowspan' style='width:100px'>".__('Description')."</td>";
      echo "<td rowspan='$rowspan' style='width:50%' id='content$rand_text'>".
           "<textarea name='content' style='width: 95%; height: 160px' id='task$rand_text'>".$this->fields["content"].
           "</textarea>";
      echo Html::scriptBlock("$(document).ready(function() { $('#content$rand').autogrow(); });");
      echo "</td>";
      echo "<input type='hidden' name='$fkfield' value='".$this->fields[$fkfield]."'>";
      echo "</td><td colspan='2'></td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width:100px'>"._n('Task template', 'Task templates', 1)."</td><td>";
      TaskTemplate::dropdown(array('value'     => 0,
                                   'entity'    => $this->getEntityID(),
                                   'rand'      => $rand_template,
                                   'on_change' => 'tasktemplate_update(this.value)'));
      echo "</td>";
      echo "</tr>";
      echo Html::scriptBlock('
         function tasktemplate_update(value) {
            jQuery.ajax({
               url: "' . $CFG_GLPI["root_doc"] . '/ajax/task.php",
               type: "POST",
               data: {
                  tasktemplates_id: value
               }
            }).done(function(datas) {
               datas.taskcategories_id = isNaN(parseInt(datas.taskcategories_id)) ? 0 : parseInt(datas.taskcategories_id);
               datas.actiontime = isNaN(parseInt(datas.actiontime)) ? 0 : parseInt(datas.actiontime);

               $("#task' . $rand_text . '").html(datas.content);
               $("#dropdown_taskcategories_id' . $rand_type . '").select2("val", parseInt(datas.taskcategories_id));
               $("#dropdown_actiontime' . $rand_time . '").select2("val", parseInt(datas.actiontime));
            });
         }
      ');


      if ($ID > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Date')."</td>";
         echo "<td>";
         Html::showDateTimeField("date", array('value'      => $this->fields["date"],
                                               'timestep'   => 1,
                                               'maybeempty' => false));
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2'>&nbsp;";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Category')."</td><td>";
      TaskCategory::dropdown(array('value'  => $this->fields["taskcategories_id"],
                                   'rand'   => $rand_type,
                                   'entity' => $item->fields["entities_id"],
                                   'condition' => "`is_active` = '1'"));

      echo "</td></tr>\n";

      if (isset($this->fields["state"])) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Status')."</td><td>";
         self::dropdownStatus("state", $this->fields["state"]);
         echo "</td></tr>\n";
      }

      if ($this->maybePrivate()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Private')."</td>";
         echo "<td>";
         Dropdown::showYesNo('is_private',$this->fields["is_private"]);
         echo "</td>";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>". __('Duration')."</td><td>";

      $toadd = array();
      for ($i=9 ; $i<=100 ; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("actiontime", array('min'             => 0,
                                                  'max'             => 8*HOUR_TIMESTAMP,
                                                  'value'           => $this->fields["actiontime"],
                                                  'rand'            => $rand_time,
                                                  'addfirstminutes' => true,
                                                  'inhours'         => true,
                                                  'toadd'           => $toadd));

      echo "</td></tr>\n";

      if ($ID <= 0) {
         Document_Item::showSimpleAddForItem($item);
      }
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('By')."</td>";
      echo "<td colspan='2'>";
      echo Html::image($CFG_GLPI['root_doc']."/pics/user.png")."&nbsp;";
      echo _n('User', 'Users', 1);
      $rand_user          = mt_rand();
      $params             = array('name'   => "users_id_tech",
                                  'value'  => (($ID > -1)
                                                ?$this->fields["users_id_tech"]
                                                :Session::getLoginUserID()),
                                  'right'  => "own_ticket",
                                  'rand'   => $rand_user,
                                  'entity' => $item->fields["entities_id"],
                                  'width'  => '');

      $params['toupdate'] = array('value_fieldname'
                                              => 'users_id',
                                  'to_update' => "user_available$rand_user",
                                  'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php");
      User::dropdown($params);

      echo " <a href='#' onClick=\"".Html::jsGetElementbyID('planningcheck'.$rand).".dialog('open');\">";
      echo "&nbsp;<img src='".$CFG_GLPI["root_doc"]."/pics/reservation-3.png'
             title=\"".__s('Availability')."\" alt=\"".__s('Availability')."\"
             class='calendrier'>";
      echo "</a>";
      Ajax::createIframeModalWindow('planningcheck'.$rand,
                                    $CFG_GLPI["root_doc"].
                                          "/front/planning.php?checkavailability=checkavailability".
                                          "&itemtype=".$item->getType()."&$fkfield=".$item->getID(),
                                    array('title'  => __('Availability')));


      echo "<br />";
      echo Html::image($CFG_GLPI['root_doc']."/pics/group.png")."&nbsp;";
      echo _n('Group', 'Groups', 1)."&nbsp;";
      $rand_group = mt_rand();
      $params     = array('name'      => "groups_id_tech",
                          'value'     => (($ID > -1)
                                          ?$this->fields["groups_id_tech"]
                                          :Dropdown::EMPTY_VALUE),
                          'condition' => "is_task",
                          'rand'      => $rand_group,
                          'entity'    => $item->fields["entities_id"]);

      $params['toupdate'] = array('value_fieldname' => 'users_id',
                                  'to_update' => "group_available$rand_group",
                                  'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php");
      Group::dropdown($params);
      echo "</td>\n";
      echo "<td>";
      if ($canplan) {
         echo __('Planning');
      }

      if (!empty($this->fields["begin"])) {

         if (Session::haveRight('planning', Planning::READMY)) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlan".$ID.$rand_text."() {\n";
            echo Html::jsHide("plan$rand_text");
            $params = array('action'    => 'add_event_classic_form',
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
                            'items_id'  => $this->getID());
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
            $params = array('action'    => 'add_event_classic_form',
                            'form'      => 'followups',
                            'entity'    => $item->fields['entities_id'],
                            'rand_user' => $rand_user,
                            'rand_group' => $rand_group,
                            'itemtype'  => $this->getType(),
                            'items_id'  => $this->getID());
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
            _e('None');
         }
      }

      echo "</td></tr>";

      if (!empty($this->fields["begin"])
          && PlanningRecall::isAvailable()) {

         echo "<tr class='tab_bg_1'><td>"._x('Planning','Reminder')."</td><td class='center'>";
         PlanningRecall::dropdown(array('itemtype' => $this->getType(),
                                        'items_id' => $this->getID()));
         echo "</td><td colspan='2'></td></tr>";
      }

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Show the current task sumnary
    *
    * @param $item   CommonITILObject
   **/
   function showSummary(CommonITILObject $item, $id) {
      global $DB, $CFG_GLPI;

      if (!static::canView()) {
         return false;
      }

      $tID = $id;

      // Display existing Followups
      $showprivate = $this->canViewPrivates();
      $caneditall  = $this->canEditAll();
      $tmp         = array($item->getForeignKeyField() => $tID);
      $canadd      = $this->can(-1, CREATE, $tmp);
      $canpurge    = $this->canPurgeItem();
      $canview     = $this->canViewItem();

      $RESTRICT = "";
      if ($this->maybePrivate() && !$showprivate) {
         $RESTRICT = " AND (`is_private` = '0'
                            OR `users_id` ='" . Session::getLoginUserID() . "'
                            OR `users_id_tech` ='" . Session::getLoginUserID()."'
                            OR `groups_id_tech` IN ('".implode("','",$_SESSION["glpigroups"])."')) ";
      }

      $query = "SELECT `id`, `date`
                FROM `glpi_plugin_releases_tests`
                WHERE `changes_id` = '$id'
                      $RESTRICT
                ORDER BY `date` DESC";
      $result = $DB->query($query);

      $rand = mt_rand();

      if ($caneditall || $canadd || $canpurge) {
         echo "<div id='viewfollowup" . $tID . "$rand'></div>\n";
      }

      if (1) {//$canadd
         echo "<script type='text/javascript' >\n";
         echo "function viewAddTask" . $item->fields['id'] . "$rand() {\n";
         $params = array('type'                      => $this->getType(),
                         'parenttype'                => $item->getType(),
                         $item->getForeignKeyField() => $item->fields['id'],
                         'id'                        => -1);
         Ajax::updateItemJsCode("viewfollowup" . $item->fields['id'] . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo Html::jsHide('addbutton'.$item->fields['id'] . "$rand");
         echo "};";
         echo "</script>\n";
         if (!in_array($item->fields["status"],
               array_merge($item->getSolvedStatusArray(), $item->getClosedStatusArray()))) {
            echo "<div id='addbutton".$item->fields['id'] . "$rand' class='center firstbloc'>".
                 "<a class='vsubmit' href='javascript:viewAddTask".$item->fields['id']."$rand();'>";
            echo __('Add a new Test', 'releases')."</a></div>\n";
         }
      }

      if ($DB->numrows($result) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'><th>" . __('No Test found.','releases');
         echo "</th></tr></table>";
      } else {
         echo "<table class='tab_cadre_fixehov'>";

         $header = "<tr><th>&nbsp;</th><th>".__('Name')."</th><th>".__('Status')."</th><th>".__('Type')."</th><th>" . __('Date') . "</th>";
         $header .= "<th>" . __('Description') . "</th><th>" .  __('Duration') . "</th>";
         $header .= "<th>" . __('Writer') . "</th>";
         if ($this->maybePrivate() && $showprivate) {
            $header .= "<th>" . __('Private') . "</th>";
         }
         $header .= "</tr>\n";
         echo $header;

         while ($data = $DB->fetch_assoc($result)) {
            if ($this->getFromDB($data['id'])) {
               $options = array( 'parent' => $item, 
                                 'rand' => $rand, 
                                 'showprivate' => $showprivate ) ;
               Plugin::doHook('pre_show_item', array('item' => $this, 'options' => &$options));
               $this->showInObjectSumnary($item, $rand, $showprivate);
               Plugin::doHook('post_show_item', array('item' => $this, 'options' => $options));
            
            }
         }
         echo $header;
         echo "</table>";
      }
   }
   
   
   function showFormMassiveAction() {

      echo "&nbsp;".__('Category')."&nbsp;";
      TaskCategory::dropdown(array('condition' => "`is_active`= '1'"));

      echo "<br>".__('Description')." ";
      echo "<textarea name='content' cols='50' rows='6'></textarea>&nbsp;";

      if ($this->maybePrivate()) {
         echo "<input type='hidden' name='is_private' value='".$_SESSION['glpitask_private']."'>";
      }

       echo "<br>".__('Duration');

      $toadd = array();
      for ($i=9 ; $i<=100 ; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("actiontime", array('min'             => 0,
                                                  'max'             => 8*HOUR_TIMESTAMP,
                                                  'addfirstminutes' => true,
                                                  'inhours'         => true,
                                                  'toadd'           => $toadd));

      echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
   }
   
   
   /**
    * get the change status list
    * To be overridden by class
    *
    * @param $withmetaforsearch boolean (default false)
    *
    * @return an array
   **/
   static function dropdownStatus($name, $value) {

      $tab = array(0   => __('New', 'releases'),
                   1   => __('In Progress', 'releases'),
                   2   => __('Validated', 'releases'),
                   3   => __('Unvalidated', 'releases'));

      return Dropdown::showFromArray($name, $tab, array('value'=>$value));
   }
   
   static function getStateName($value) {

      $tab = array(0   => __('New', 'releases'),
                   1   => __('In Progress', 'releases'),
                   2   => __('Validated', 'releases'),
                   3   => __('Unvalidated', 'releases'));

      return $tab[$value];
   }
   
}
