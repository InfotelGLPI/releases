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

   static $rightname          = 'ticket';



   static function getTypeName($nb = 0) {
      return _n('Deploy Task template', 'Deploy Task templates', $nb,'releases');
   }


   function getAdditionalFields() {

      return [['name'  => 'content',
         'label' => __('Content'),
         'type'  => 'textarea',
         'rows' => 10],

         ['name'  => 'plugin_release_typedeploytasks_id',
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
      return Session::haveRightsOr('ticket', [UPDATE,CREATE]);
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
      return Session::haveRight('ticket', READ);
   }
}
