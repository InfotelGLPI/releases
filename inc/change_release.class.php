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
 * Change_Ticket Class
 *
 * Relation between Changes and Tickets
 **/
class PluginReleasesChange_Release extends CommonDBRelation {


   // From CommonDBRelation
   static public $itemtype_1   = 'Change';
   static public $items_id_1   = 'changes_id';

   static public $itemtype_2   = 'PluginReleasesRelease';
   static public $items_id_2   = 'plugin_releases_releases_id';

   static $rightname                   = 'ticket';

   static function getTypeName($nb = 0) {
      return _n('Link Release/Change', 'Links Release/Change', $nb,'releases');
   }
   /**
    * @since 0.85
    *
    * @see CommonGLPI::getTabNameForItem()
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (static::canView()) {
         $nb = 0;
         switch ($item->getType()) {
             case 'PluginReleasesRelease' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_plugin_releases_changes_releases',
                     ['plugin_releases_releases_id' => $item->getID()]);
               }
               return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }

   /**
    * @since 0.85
    **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'PluginReleasesRelease' :
            self::showForRelease($item);
            break;
      }
      return true;
   }

   /**
    * Show changes for a release
    *
    * @param $ticket Ticket object
    **/
   static function showForRelease(PluginReleasesRelease $release) {
      global $DB;

      $ID = $release->getField('id');
      if (!$release->can($ID, READ)) {
         return false;
      }

      $canedit = $release->canEdit($ID);
      $rand = mt_rand();

      $iterator = $DB->request([
         'SELECT DISTINCT' => 'glpi_plugin_releases_changes_releases.id AS linkid',
         'FIELDS' => 'glpi_changes.*',
         'FROM' => 'glpi_plugin_releases_changes_releases',
         'LEFT JOIN' => [
            'glpi_changes' => [
               'ON' => [
                  'glpi_plugin_releases_changes_releases' => 'changes_id',
                  'glpi_changes' => 'id'
               ]
            ]
         ],
         'WHERE' => [
            'glpi_plugin_releases_changes_releases.plugin_releases_releases_id' => $ID,
         ],
         'ORDERBY' => [
            'glpi_changes.name'
         ]
      ]);

      $changes = [];
      $used = [];
      $numrows = count($iterator);
//      $change_release = new self();
//      $all = $change_release->find();
//      foreach ($all as $one){
//         $used[$one['changes_id']] = $one['changes_id'];
//      }


      while ($data = $iterator->next()) {
            $changes[$data['id']] = $data;

      }
      $statues = Change::getNotSolvedStatusArray();
      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='changeticket_form$rand' id='changeticket_form$rand' method='post'
               action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='3'>" . __('Add a change') . "</th></tr>";
         echo "<tr class='tab_bg_2'><td>";
         echo "<input type='hidden' name='plugin_releases_releases_id' value='$ID'>";
         Change::dropdown([
//            'used' => $used,
            'entity' => $release->getEntityID(),'condition'=>['status'=>Change::getNotSolvedStatusArray()]]);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='submit'>";
         echo "</td><td>";

         echo "</td></tr></table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
            'container' => 'mass' . __CLASS__ . $rand];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='12'>" . Change::getTypeName($numrows) . "</th>";
      echo "</tr>";
      if ($numrows) {
         Change::commonListHeader(Search::HTML_OUTPUT, 'mass' . __CLASS__ . $rand);
         Session::initNavigateListItems('Change',
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(__('%1$s = %2$s'), Ticket::getTypeName(1),
               $release->fields["name"]));

         $i = 0;
         foreach ($changes as $data) {
            Session::addToNavigateListItems('Change', $data["id"]);
            Change::showShort($data['id'], ['row_num' => $i,
               'type_for_massiveaction' => __CLASS__,
               'id_for_massiveaction' => $data['linkid']]);
            $i++;
         }
         Change::commonListHeader(Search::HTML_OUTPUT, 'mass' . __CLASS__ . $rand);
      }
      echo "</table>";

      if ($canedit && $numrows) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";

   }

   function post_addItem() {
      $release = new PluginReleasesRelease();
      if($release->getFromDB($this->getField("plugin_releases_releases_id"))){
         if($release->getField("state")<PluginReleasesRelease::CHANGEDEFINITION){
            $update["id"] = $release->getID();
            $update["state"] = PluginReleasesRelease::CHANGEDEFINITION;
            $release->update($update);
         }
      }

   }
   /**
    * Actions done after the PURGE of the item in the database
    *
    * @return void
    **/
   function post_purgeItem() {
    //TODO
   }

   static function canCreate() {
      return Session::haveRight('ticket', UPDATE);
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

