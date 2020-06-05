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
class PluginReleasesDeploytasktemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'plugin_releases_tasks';



   static function getTypeName($nb = 0) {
      return _n('Deploy Task template', 'Deploy Task templates', $nb,'releases');
   }


   function getAdditionalFields() {

      return [['name'  => 'content',
         'label' => __('Content'),
         'type'  => 'textarea',
         'rows' => 10],

         ['name'  => 'plugin_releases_typedeploytasks_id',
            'label' => __('Deploy Task type','releases'),
            'type'  => 'dropdownValue',
            'list'  => true],
         ['name'  => 'state',
            'label' => __('Status'),
            'type'  => 'state'],
         ['name'  => 'is_private',
            'label' => __('Private'),
            'type'  => 'bool'],
         ['name'  => 'actiontime',
            'label' => __('Duration'),
            'type'  => 'actiontime'],
         ['name'  => 'users_id_tech',
            'label' => __('By'),
            'type'  => 'users_id_tech'],
         ['name'  => 'groups_id_tech',
            'label' => __('Group'),
            'type'  => 'groups_id_tech'],
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
         case 'state' :
            PluginReleasesRelease::dropdownStateItem("state", $this->fields["state"]);
            break;
         case 'users_id_tech' :
            User::dropdown([
               'name'   => "users_id_tech",
               'right'  => "own_ticket",
               'value'  => $this->fields["users_id_tech"],
               'entity' => $this->fields["entities_id"],
            ]);
            break;
         case 'groups_id_tech' :
            Group::dropdown([
               'name'     => "groups_id_tech",
               'condition' => ['is_task' => 1],
               'value'     => $this->fields["groups_id_tech"],
               'entity'    => $this->fields["entities_id"],
            ]);
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

//   public function showForm($ID, $options = []) {
//      $rand_text      = mt_rand();
//      $rand_name      = mt_rand();
//      $rand_type      = mt_rand();
//      $rand_state     = mt_rand();
//      $rand_risk     = mt_rand();
//      $rand_time     = mt_rand();
//
//      $this->initForm($ID, $options);
//      $this->showFormHeader($options);
//
//      echo "<tr class='tab_bg_1'>";
//      echo "<td>" . __('Name') . "</td>";
//      echo "<td>";
//      echo Html::input("name",["id"=>"name".$rand_name,"value"=>$this->getField('name'),  'rand'      => $rand_name,]);
//      echo "</td>";
//      echo "<td colspan='2'>";
//      echo "</td>";
//
//      echo "</tr>";
//
//      echo "<tr class='tab_bg_1'>";
//      echo "<td>";
//      echo __("Deploy task type",'releases');
//      echo "</td>";
//
//      echo "<td>";
//
//      $value = $this->fields["plugin_releases_typedeploytasks_id"];
//
//      Dropdown::show(PluginReleasesTypeDeployTask::getType(), ['rand'=>$rand_type,'name' => "plugin_releases_typedeploytasks_id",
//         'value' => $value]);
//      echo "</td>";
//      echo "<td>";
//      echo __('Status');
//      echo "</td>";
//      echo "<td>";
//      PluginReleasesRelease::dropdownStateItem("state", $this->fields["state"], true, ['rand' => $rand_state]);
//      echo "</td>";
//
//      echo "</tr>";
//
//      echo "<tr class='tab_bg_1'>";
//      echo "<td>";
//      echo __("Associated risk",'releases');
//      echo "</td>";
//
//      echo "<td>";
//
//      $value = $this->fields["plugin_releases_risks_id"];
//
//      Dropdown::show(PluginReleasesTypeDeployTask::getType(), ['rand'=>$rand_risk,'name' => "plugin_releases_risks_id",
//         'value' => $value]);
//      echo "</td>";
//      echo "<td>";
//      echo __('Private');
//      echo "</td>";
//      echo "<td>";
//      Dropdown::showYesNo("is_private",$this->fields["is_private"]);
//      echo "</td>";
//
//      echo "</tr>";
//
//      echo "<tr class='tab_bg_1'>";
//      echo "<td>";
//      echo __("Duration");
//      echo "</td>";
//
//      echo "<td>";
//
//      $toadd = [];
//      for ($i=9; $i<=100; $i++) {
//         $toadd[] = $i*HOUR_TIMESTAMP;
//      }
//
//      Dropdown::showTimeStamp("actiontime", ['min'             => 0,
//         'max'             => 8*HOUR_TIMESTAMP,
//         'value'           => $this->fields["actiontime"],
//         'rand'            => $rand_time,
//         'addfirstminutes' => true,
//         'inhours'         => true,
//         'toadd'           => $toadd,
//         'width'  => '']);
//      echo "</td>";
//      echo "<td>";
////      echo __('Private');
//      echo "</td>";
//      echo "<td>";
////      Dropdown::showYesNo("is_private",$this->fields["is_private"]);
//      echo "</td>";
//
//      echo "</tr>";
//
//
//      $this->showFormButtons($options);
//   }
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




      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
//         $options[$fkfield] = $item->getField('id');
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
         'enable_fileupload' => false,
         'enable_richtext'   => true,
         'cols'              => $cols,
         'rows'              => $rows]);

//      echo "<input type='hidden' name='$fkfield' value='".$this->fields[$fkfield]."'>";
      echo "</td>";

      echo "<td style='vertical-align: middle'>";


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
         'value'     => $this->fields["plugin_releases_typedeploytasks_id"],
         'rand'      => $rand_type,
//         'entity'    => $item->fields["entities_id"],
//         'condition' => ['is_active' => 1]
      ]);
      echo "</div>";
      echo "<div class='fa-label'>
         <span>".__('Risk')."</span>&nbsp;";
      Dropdown::show(PluginReleasesRisktemplate::getType(), ['name' => "plugin_releases_risks_id",
         'value' =>  $this->fields["plugin_releases_risks_id"]]);
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
//         'entity' => $item->fields["entities_id"],
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
//      Ajax::createIframeModalWindow('planningcheck'.$rand,
//         $CFG_GLPI["root_doc"].
//         "/front/planning.php?checkavailability=checkavailability".
//         "&itemtype=".$item->getType()."&$fkfield=".$item->getID(),
//         ['title'  => __('Availability')]);
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
//         'entity'    => $item->fields["entities_id"]      ];
      ];

      $params['toupdate'] = ['value_fieldname' => 'users_id',
         'to_update' => "group_available$rand_group",
         'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php"];
      Group::dropdown($params);
      echo "</div>";


      echo "</td></tr>";



      $this->showFormButtons($options);

      return true;

   }
}
