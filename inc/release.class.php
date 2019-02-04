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
   die("Sorry. You can't access directly to this file");
}

/// Class Release
class PluginReleasesRelease extends CommonITILObject {

   // From CommonDBTM
   public           $dohistory         = true;
   static protected $forward_entity_to = ['PluginReleasesReleaseValidation'];

   // From CommonITIL
   //   public $userlinkclass               = 'PluginReleasesRelease_User';
   //   public $grouplinkclass              = 'PluginReleasesRelease_Group';
   //   public $supplierlinkclass           = 'PluginReleasesRelease_Supplier';

   static    $rightname  = 'plugin_releases';
   protected $usenotepad = true;

   const MATRIX_FIELD = 'priority_matrix';
   //   const URGENCY_MASK_FIELD            = 'urgency_mask';
   //   const IMPACT_MASK_FIELD             = 'impact_mask';
   const STATUS_MATRIX_FIELD = 'change_status';


   //   const READMY                        = 1;
   //   const READALL                       = 1024;


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type (default 0)
    **/
   static function getTypeName($nb = 0) {
      return _n('Release', 'Releases', $nb, 'releases');
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
      if ($item->getType() == 'Change') {
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry(self::getTypeName(2), self::countForItem($item));
         }
         return self::getTypeName(2);
      }
      return '';
   }

   /**
    * @param CommonDBTM $item
    *
    * @return int
    */
   static function countForItem(CommonDBTM $item) {
      $dbu = new DbUtils();
      return $dbu->countElementsInTable('glpi_plugin_releases_releases',
                                        ["changes_id" => $item->getID()]);
   }

   /**
    * @param array $options
    *
    * @return array
    */
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginReleasesChange_Release', $ong, $options);
      $this->addStandardTab('PluginReleasesReleaseOverview', $ong, $options);
      //TODO
      //      $this->addStandardTab('PluginReleasesReleaseValidation', $ong, $options);
      $this->addStandardTab('PluginReleasesReleaseTest', $ong, $options);
      $this->addStandardTab('PluginReleasesReleaseTask', $ong, $options);
      $this->addStandardTab('PluginReleasesReleaseDeployment', $ong, $options);
      //TODO
//      $this->addStandardTab('PluginReleasesReleaseInformation', $ong, $options);
      //TODO
      //      $this->addStandardTab('PluginReleasesRelease_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   function cleanDBonPurge() {

      // CommonITILTask does not extends CommonDBConnexity
      $ct = new PluginReleasesReleaseTask();
      $ct->deleteByCriteria(['plugin_releases_releases_id' => $this->fields['id']]);

      $this->deleteChildrenAndRelationsFromDb(
         [
            // Done by parent: PluginReleasesRelease_Group::class,
            //            PluginReleasesRelease_Item::class,
            //            PluginReleasesRelease_Problem::class,
            // Done by parent: PluginReleasesRelease_Supplier::class,
            //            PluginReleasesRelease_Ticket::class,
            // Done by parent: PluginReleasesRelease_User::class,
            //            PluginReleasesReleaseValidation::class,
         ]
      );

      parent::cleanDBonPurge();
   }


   function prepareInputForUpdate($input) {

      $input = parent::prepareInputForUpdate($input);
      return $input;
   }


   function pre_updateInDB() {
      parent::pre_updateInDB();
   }


   function post_updateItem($history = 1) {
      global $CFG_GLPI;

      //      $donotif =  count($this->updates);
      //
      //      if (isset($this->input['_forcenotif'])) {
      //         $donotif = true;
      //      }
      //
      //      if (isset($this->input['_disablenotif'])) {
      //         $donotif = false;
      //      }
      //
      //      if ($donotif && $CFG_GLPI["use_notifications"]) {
      //         $mailtype = "update";
      //         if (isset($this->input["status"]) && $this->input["status"]
      //             && in_array("status", $this->updates)
      //             && in_array($this->input["status"], $this->getSolvedStatusArray())) {
      //
      //            $mailtype = "solved";
      //         }
      //
      //         if (isset($this->input["status"]) && $this->input["status"]
      //             && in_array("status", $this->updates)
      //             && in_array($this->input["status"], $this->getClosedStatusArray())) {
      //
      //            $mailtype = "closed";
      //         }
      //
      //         // Read again change to be sure that all data are up to date
      //         $this->getFromDB($this->fields['id']);
      //         NotificationEvent::raiseEvent($mailtype, $this);
      //      }
   }


   function prepareInputForAdd($input) {

      $input = parent::prepareInputForAdd($input);
      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      parent::post_addItem();

      if (isset($this->input['_changes_id'])) {
         $change = new Change();
         if ($change->getFromDB($this->input['_changes_id'])) {
            $pt = new PluginReleasesChange_Release();
            $pt->add(['changes_id'                  => $this->input['_changes_id'],
                      'plugin_releases_releases_id' => $this->fields['id']]);

            if (!empty($change->fields['itemtype']) && $change->fields['items_id'] > 0) {
               $it = new PluginReleasesRelease_Item();
               $it->add(['plugin_releases_releases_id' => $this->fields['id'],
                         'itemtype'                    => $change->fields['itemtype'],
                         'items_id'                    => $change->fields['items_id']]);
            }
         }
      }


      // Processing notifications
      //      if ($CFG_GLPI["use_notifications"]) {
      //         // Clean reload of the change
      //         $this->getFromDB($this->fields['id']);
      //
      //         $type = "new";
      //         if (isset($this->fields["status"])
      //             && in_array($this->input["status"], $this->getSolvedStatusArray())) {
      //            $type = "solved";
      //         }
      //         NotificationEvent::raiseEvent($type, $this);
      //      }

      if (isset($this->input['_from_items_id'])
          && isset($this->input['_from_itemtype'])) {
         $it = new PluginReleasesRelease_Item();
         $it->add([
                     'items_id'                    => (int)$this->input['_from_items_id'],
                     'itemtype'                    => $this->input['_from_itemtype'],
                     'plugin_releases_releases_id' => $this->fields['id'],
                     '_disablenotif'               => true
                  ]);
      }
   }

   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if (!static::canView()) {
         return false;
      }

      // In percent
      $colsize1 = '13';
      $colsize2 = '37';

      $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $_SESSION['glpiactive_entity'], '', 1);

      // Set default options
      if (!$ID) {
         $values = [
            '_users_id_requester'        => Session::getLoginUserID(),
            '_users_id_requester_notif'  => ['use_notification'  => $default_use_notif,
                                             'alternative_email' => ''],
            '_groups_id_requester'       => 0,
            '_users_id_assign'           => 0,
            '_users_id_assign_notif'     => ['use_notification'  => $default_use_notif,
                                             'alternative_email' => ''],
            '_groups_id_assign'          => 0,
            '_users_id_observer'         => 0,
            '_users_id_observer_notif'   => ['use_notification'  => $default_use_notif,
                                             'alternative_email' => ''],
            '_suppliers_id_assign_notif' => ['use_notification'  => $default_use_notif,
                                             'alternative_email' => ''],
            '_groups_id_observer'        => 0,
            '_suppliers_id_assign'       => 0,
            'priority'                   => 3,
            //                    'urgency'                    => 3,
            //                    'impact'                     => 3,
            'content'                    => '',
            'entities_id'                => $_SESSION['glpiactive_entity'],
            'name'                       => '',
            //                    'itilcategories_id'          => 0
         ];
         foreach ($values as $key => $val) {
            if (!isset($options[$key])) {
               $options[$key] = $val;
            }
         }

         if (isset($options['changes_id'])) {
            $change = new Change();
            if ($change->getFromDB($options['changes_id'])) {
               $options['content'] = $change->getField('content');
               $options['name']    = $change->getField('name');
               //               $options['impact']              = $change->getField('impact');
               //               $options['urgency']             = $change->getField('urgency');
               $options['priority'] = $change->getField('priority');
               //               $options['itilcategories_id']   = $change->getField('itilcategories_id');
               $options['time_to_resolve'] = $change->getField('time_to_resolve');
            }
         }
      }

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $this->check(-1, CREATE, $options);
      }

      $showuserlink = 0;
      if (User::canView()) {
         $showuserlink = 1;
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
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<th class='left' width='$colsize1%'>" . __('Opening date') . "</th>";
      echo "<td class='left' width='$colsize2%'>";

      if (isset($options['changes_id'])) {
         echo "<input type='hidden' name='_changes_id' value='" . $options['changes_id'] . "'>";
      }

      if (isset($options['_add_fromitem'])
          && isset($options['_from_items_id'])
          && isset($options['_from_itemtype'])) {
         echo Html::hidden('_from_items_id', ['value' => $options['_from_items_id']]);
         echo Html::hidden('_from_itemtype', ['value' => $options['_from_itemtype']]);
      }

      $date = $this->fields["date"];
      if (!$ID) {
         $date = date("Y-m-d H:i:s");
      }
      Html::showDateTimeField("date", ['value'      => $date,
                                       'timestep'   => 1,
                                       'maybeempty' => false]);
      echo "</td>";
      echo "<th width='$colsize1%'>" . __('Time to resolve') . "</th>";
      echo "<td width='$colsize2%' class='left'>";

      if ($this->fields["time_to_resolve"] == 'NULL') {
         $this->fields["time_to_resolve"] = '';
      }
      Html::showDateTimeField("time_to_resolve", ['value'    => $this->fields["time_to_resolve"],
                                                  'timestep' => 1]);

      echo "</td></tr>";

      //      if ($ID) {
      //         echo "<tr class='tab_bg_1'><th>".__('By')."</th><td>";
      //         User::dropdown(['name'   => 'users_id_recipient',
      //                         'value'  => $this->fields["users_id_recipient"],
      //                         'entity' => $this->fields["entities_id"],
      //                         'right'  => 'all']);
      //         echo "</td>";
      //         echo "<th>".__('Last update')."</th>";
      //         echo "<td>".Html::convDateTime($this->fields["date_mod"])."\n";
      //         if ($this->fields['users_id_lastupdater'] > 0) {
      //            printf(__('%1$s: %2$s'), __('By'),
      //                   getUserName($this->fields["users_id_lastupdater"], $showuserlink));
      //         }
      //         echo "</td></tr>";
      //      }

      if ($ID
          && (in_array($this->fields["status"], $this->getSolvedStatusArray())
              || in_array($this->fields["status"], $this->getClosedStatusArray()))) {
         echo "<tr class='tab_bg_1'>";
         echo "<th>" . __('Date of solving') . "</th>";
         echo "<td>";
         Html::showDateTimeField("solvedate", ['value'      => $this->fields["solvedate"],
                                               'timestep'   => 1,
                                               'maybeempty' => false]);
         echo "</td>";
         if (in_array($this->fields["status"], $this->getClosedStatusArray())) {
            echo "<th>" . __('Closing date') . "</th>";
            echo "<td>";
            Html::showDateTimeField("closedate", ['value'      => $this->fields["closedate"],
                                                  'timestep'   => 1,
                                                  'maybeempty' => false]);
            echo "</td>";
         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
         echo "</tr>";
      }
      echo "</table>";

      echo "<table class='tab_cadre_fixe' id='mainformtable2'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>" . __('Status') . "</th>";
      echo "<td width='$colsize2%'>";
      self::dropdownStatus(['value'    => $this->fields["status"],
                            'showtype' => 'allowed']);
//      ChangeValidation::alertValidation($this, 'status');
      echo "</td>";
      //      echo "<th width='$colsize1%'>".__('Urgency')."</th>";
      //      echo "<td width='$colsize2%'>";
      //      // Only change during creation OR when allowed to change priority OR when user is the creator
      //      $idurgency = self::dropdownUrgency(['value' => $this->fields["urgency"]]);
      //      echo "</td>";
      echo "</tr>";

      //      echo "<tr class='tab_bg_1'>";
      //      echo "<th>".__('Category')."</th>";
      //      echo "<td >";
      //      $opt = [
      //         'value'  => $this->fields["itilcategories_id"],
      //         'entity' => $this->fields["entities_id"],
      //         'condition' => ['is_change' => 1]
      //      ];
      //      ITILCategory::dropdown($opt);
      //      echo "</td>";
      //      echo "<th>".__('Impact')."</th>";
      //      echo "<td>";
      //      $idimpact = self::dropdownImpact(['value' => $this->fields["impact"]]);
      //      echo "</td>";
      //      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>" . __('Total duration') . "</th>";
      echo "<td>" . parent::getActionTime($this->fields["actiontime"]) . "</td>";
      echo "<th class='left'>" . __('Priority') . "</th>";
      echo "<td>";
      //      $idpriority = parent::dropdownPriority(['value'     => $this->fields["priority"],
      //                                              'withmajor' => true]);
      //      $idajax     = 'change_priority_' . mt_rand();
      //      echo "&nbsp;<span id='$idajax' style='display:none'></span>";
      //      $params = ['urgency'  => '__VALUE0__',
      //                 'impact'   => '__VALUE1__',
      //                 'priority' => 'dropdown_priority'.$idpriority];
      //      Ajax::updateItemOnSelectEvent(['dropdown_urgency'.$idurgency,
      //                                     'dropdown_impact'.$idimpact],
      //                                    $idajax,
      //                                    $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo __('Approval');
      echo "</th>";
      echo "<td>";
//      echo ChangeValidation::getStatus($this->fields['global_validation']);
      echo "</td>";
      echo "<th></th>";
      echo "<td></td>";
      echo "</tr>";
      echo "</table>";

      //      $this->showActorsPartForm($ID, $options);

      echo "<table class='tab_cadre_fixe' id='mainformtable3'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>" . __('Title') . "</th>";
      echo "<td colspan='3'>";
      echo "<input type='text' size='90' maxlength=250 name='name' " .
           " value=\"" . Html::cleanInputText($this->fields["name"]) . "\">";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>" . __('Description') . "</th>";
      echo "<td colspan='3'>";
      $rand = mt_rand();
      echo "<textarea id='content$rand' name='content' cols='90' rows='6'>" .
           Html::clean(Html::entity_decode_deep($this->fields["content"])) . "</textarea>";
      echo "</td>";
      echo "</tr>";
      $options['colspan'] = 2;
      $this->showFormButtons($options);

      return true;

   }

   /**
    * get the change status list
    * To be overridden by class
    *
    * @param $withmetaforsearch boolean (default false)
    *
    * @return array
    **/
   static function getAllStatusArray($withmetaforsearch = false) {

      $tab = [self::INCOMING      => _x('status', 'New'),
              self::EVALUATION    => __('Evaluation'),
              self::APPROVAL      => __('Approval'),
              self::ACCEPTED      => _x('status', 'Accepted'),
              self::WAITING       => __('Pending'),
              self::TEST          => _x('change', 'Testing'),
              self::QUALIFICATION => __('Qualification'),
              self::SOLVED        => __('Applied'),
              self::OBSERVED      => __('Review'),
              self::CLOSED        => _x('status', 'Closed'),
      ];

      if ($withmetaforsearch) {
         $tab['notold']    = _x('status', 'Not solved');
         $tab['notclosed'] = _x('status', 'Not closed');
         $tab['process']   = __('Processing');
         $tab['old']       = _x('status', 'Solved + Closed');
         $tab['all']       = __('All');
      }
      return $tab;
   }


   /**
    * Get the ITIL object closed status list
    * To be overridden by class
    *
    * @since 0.83
    *
    * @return array
    **/
   static function getClosedStatusArray() {

      // To be overridden by class
      $tab = [self::CLOSED];
      return $tab;
   }


   /**
    * Get the ITIL object solved or observe status list
    * To be overridden by class
    *
    * @since 0.83
    *
    * @return array
    **/
   static function getSolvedStatusArray() {
      // To be overridden by class
      $tab = [self::OBSERVED, self::SOLVED];
      return $tab;
   }

   /**
    * Get the ITIL object new status list
    *
    * @since 0.83.8
    *
    * @return array
    **/
   static function getNewStatusArray() {
      return [self::INCOMING, self::ACCEPTED, self::EVALUATION, self::APPROVAL];
   }

   /**
    * Get the ITIL object test, qualification or accepted status list
    * To be overridden by class
    *
    * @since 0.83
    *
    * @return array
    **/
   static function getProcessStatusArray() {

      // To be overridden by class
      $tab = [self::ACCEPTED, self::QUALIFICATION, self::TEST];
      return $tab;
   }

   /**
    * @param integer $output_type Output type
    * @param string  $mass_id id of the form to check all
    */
   static function commonListHeader($output_type = Search::HTML_OUTPUT, $mass_id = '') {

      // New Line for Header Items Line
      echo Search::showNewLine($output_type);
      // $show_sort if
      $header_num = 1;

      $items                                                                      = [];
      $items[(empty($mass_id) ? '&nbsp' : Html::getCheckAllAsCheckbox($mass_id))] = '';
      $items[__('Status')]                                                        = "status";
      $items[__('Date')]                                                          = "date_creation";
      $items[__('Last update')]                                                   = "date_mod";

      if (count($_SESSION["glpiactiveentities"]) > 1) {
         $items[_n('Entity', 'Entities', Session::getPluralNumber())] = "glpi_entities.completename";
      }

      $items[__('Priority')] = "priority";
      //      $items[__('Requester')]          = "users_id";
      //      $items[__('Assigned')]           = "users_id_assign";
      if (static::getType() == 'Ticket') {
         $items[_n('Associated element', 'Associated elements', Session::getPluralNumber())] = "";
      }
      //      $items[__('Category')]           = "glpi_itilcategories.completename";
      $items[__('Title')]         = "name";
      $items[__('Planification')] = "glpi_plugin_releases_releasetasks.begin";

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
    * @since 0.85 (befor in each object with differents parameters)
    *
    * @param $id                 Integer  ID of the object
    * @param $options            array of options
    *      output_type            : Default output type (see Search class / default Search::HTML_OUTPUT)
    *      row_num                : row num used for display
    *      type_for_massiveaction : itemtype for massive action
    *      id_for_massaction      : default 0 means no massive action
    *      followups              : show followup columns
    */
   static function showShort($id, $options = []) {
      global $DB;

      $p = [
         'output_type'            => Search::HTML_OUTPUT,
         'row_num'                => 0,
         'type_for_massiveaction' => 0,
         'id_for_massiveaction'   => 0,
         'followups'              => false,
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

      $candelete   = static::canDelete();
      $canupdate   = Session::haveRight(static::$rightname, UPDATE);
      $showprivate = Session::haveRight('followup', ITILFollowup::SEEPRIVATE);
      $align       = "class='center";
      $align_desc  = "class='left";

      if ($p['followups']) {
         $align      .= " top'";
         $align_desc .= " top'";
      } else {
         $align      .= "'";
         $align_desc .= "'";
      }

      if ($item->getFromDB($id)) {
         $item_num = 1;
         $bgcolor  = $_SESSION["glpipriority_" . $item->fields["priority"]];

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
         //         if ($p['output_type'] == Search::HTML_OUTPUT) {
         //            $first_col .= static::getStatusIcon($item->fields["status"]);
         //         } else {
         //            $first_col = sprintf(__('%1$s - %2$s'), $first_col,
         //                                 static::getStatus($item->fields["status"]));
         //         }

         echo Search::showItem($p['output_type'], $first_col, $item_num, $p['row_num'], $align);

         // Second column
         //         if ($item->fields['status'] == static::CLOSED) {
         //            $second_col = sprintf(__('Closed on %s'),
         //                                  ($p['output_type'] == Search::HTML_OUTPUT?'<br>':'').
         //                                  Html::convDateTime($item->fields['closedate']));
         //         } else if ($item->fields['status'] == static::SOLVED) {
         //            $second_col = sprintf(__('Solved on %s'),
         //                                  ($p['output_type'] == Search::HTML_OUTPUT?'<br>':'').
         //                                  Html::convDateTime($item->fields['solvedate']));
         //         } else if ($item->fields['begin_waiting_date']) {
         //            $second_col = sprintf(__('Put on hold on %s'),
         //                                  ($p['output_type'] == Search::HTML_OUTPUT?'<br>':'').
         //                                  Html::convDateTime($item->fields['begin_waiting_date']));
         //         } else if ($item->fields['time_to_resolve']) {
         //            $second_col = sprintf(__('%1$s: %2$s'), __('Time to resolve'),
         //                                  ($p['output_type'] == Search::HTML_OUTPUT?'<br>':'').
         //                                  Html::convDateTime($item->fields['time_to_resolve']));
         //         } else {
         $second_col = sprintf(__('Created on %s'),
                               ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : '') .
                               Html::convDateTime($item->fields['date_creation']));
         //         }

         echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'], $align . " width=130");

         // Second BIS column
         $second_col = Html::convDateTime($item->fields["date_mod"]);
         echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'], $align . " width=90");

         // Second TER column
         if (count($_SESSION["glpiactiveentities"]) > 1) {
            $second_col = Dropdown::getDropdownName('glpi_entities', $item->fields['entities_id']);
            echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'],
                                  $align . " width=100");
         }

         // Third Column

         $priority = Ticket::getPriorityName($item->fields["priority"]);
         echo Search::showItem($p['output_type'],
                               "<span class='b'>" . $priority .
                               "</span>",
                               $item_num, $p['row_num'], "$align bgcolor='$bgcolor'");
         //
         //         // Fourth Column
         //         $fourth_col = "";

         //         foreach ($item->getUsers(CommonITILActor::REQUESTER) as $d) {
         //            $userdata    = getUserName($d["users_id"], 2);
         //            $fourth_col .= sprintf(__('%1$s %2$s'),
         //                                   "<span class='b'>".$userdata['name']."</span>",
         //                                   Html::showToolTip($userdata["comment"],
         //                                                     ['link'    => $userdata["link"],
         //                                                      'display' => false]));
         //            $fourth_col .= "<br>";
         //         }
         //
         //         foreach ($item->getGroups(CommonITILActor::REQUESTER) as $d) {
         //            $fourth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
         //            $fourth_col .= "<br>";
         //         }

         //         echo Search::showItem($p['output_type'], $fourth_col, $item_num, $p['row_num'], $align);

         // Fifth column
         //         $fifth_col = "";

         //         foreach ($item->getUsers(CommonITILActor::ASSIGN) as $d) {
         //            $userdata   = getUserName($d["users_id"], 2);
         //            $fifth_col .= sprintf(__('%1$s %2$s'),
         //                                  "<span class='b'>".$userdata['name']."</span>",
         //                                  Html::showToolTip($userdata["comment"],
         //                                                    ['link'    => $userdata["link"],
         //                                                     'display' => false]));
         //            $fifth_col .= "<br>";
         //         }
         //
         //         foreach ($item->getGroups(CommonITILActor::ASSIGN) as $d) {
         //            $fifth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
         //            $fifth_col .= "<br>";
         //         }
         //
         //         foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $d) {
         //            $fifth_col .= Dropdown::getDropdownName("glpi_suppliers", $d["suppliers_id"]);
         //            $fifth_col .= "<br>";
         //         }

         //         echo Search::showItem($p['output_type'], $fifth_col, $item_num, $p['row_num'], $align);

         // Sixth Colum
         // Ticket : simple link to item
         $sixth_col = "";
         //         $is_deleted = false;
         //         $item_ticket = new Item_Ticket();
         //         $data = $item_ticket->find(['tickets_id' => $item->fields['id']]);
         //
         //         if ($item->getType() == 'Ticket') {
         //            if (!empty($data)) {
         //               foreach ($data as $val) {
         //                  if (!empty($val["itemtype"]) && ($val["items_id"] > 0)) {
         //                     if ($object = getItemForItemtype($val["itemtype"])) {
         //                        if ($object->getFromDB($val["items_id"])) {
         //                           $is_deleted = $object->isDeleted();
         //
         //                           $sixth_col .= $object->getTypeName();
         //                           $sixth_col .= " - <span class='b'>";
         //                           if ($item->canView()) {
         //                              $sixth_col .= $object->getLink();
         //                           } else {
         //                              $sixth_col .= $object->getNameID();
         //                           }
         //                           $sixth_col .= "</span><br>";
         //                        }
         //                     }
         //                  }
         //               }
         //            } else {
         //               $sixth_col = __('General');
         //            }
         //
         //            echo Search::showItem($p['output_type'], $sixth_col, $item_num, $p['row_num'], ($is_deleted ? " class='center deleted' " : $align));
         //         }

         // Seventh column
         //         $categories = "";
         //         $categories = Dropdown::getDropdownName('glpi_itilcategories',
         //                                                 $item->fields["itilcategories_id"]);
         //         echo Search::showItem($p['output_type'],
         //                               "<span class='b'>".$categories
         //                               .
         //                               "</span>",
         //                               $item_num, $p['row_num'], $align);

         // Eigth column
         $eigth_column = "<span class='b'>" . $item->getName() . "</span>&nbsp;";

         // Add link
         if ($item->canViewItem()) {
            $eigth_column = "<a id='" . $item->getType() . $item->fields["id"] . "$rand' href=\"" . $item->getLinkURL()
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
                     0//$item->numberOfFollowups($showprivate)
                     ,
                     0 //$item->numberOfTasks($showprivate)
                  )
               );
            }
         }

         if ($p['output_type'] == Search::HTML_OUTPUT) {
            $eigth_column = sprintf(__('%1$s %2$s'), $eigth_column,
                                    Html::showToolTip(Html::clean(Html::entity_decode_deep($item->fields["content"])),
                                                      ['display' => false,
                                                       'applyto' => $item->getType() . $item->fields["id"] .
                                                                    $rand]));
         }

         echo Search::showItem($p['output_type'], $eigth_column, $item_num, $p['row_num'],
                               $align_desc . " width='200'");

         //tenth column
         $tenth_column  = '';
         $planned_infos = '';

         $tasktype = $item->getType() . "Task";
         $plan     = new $tasktype();
         $items    = [];

         $result = $DB->request(
            [
               'FROM'  => $plan->getTable(),
               'WHERE' => [
                  $item->getForeignKeyField() => $item->fields['id'],
               ],
            ]
         );
         foreach ($result as $plan) {

            if (isset($plan['begin']) && $plan['begin']) {
               $items[$plan['id']] = $plan['id'];
               $planned_infos      .= sprintf(__('From %s') .
                                              ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : ''),
                                              Html::convDateTime($plan['begin']));
               $planned_infos      .= sprintf(__('To %s') .
                                              ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : ''),
                                              Html::convDateTime($plan['end']));
               if ($plan['users_id_tech']) {
                  $planned_infos .= sprintf(__('By %s') .
                                            ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : ''),
                                            getUserName($plan['users_id_tech']));
               }
               $planned_infos .= "<br>";
            }

         }

         $tenth_column = count($items);
         if ($tenth_column) {
            $tenth_column = "<span class='pointer'
                              id='" . $item->getType() . $item->fields["id"] . "planning$rand'>" .
                            $tenth_column . '</span>';
            $tenth_column = sprintf(__('%1$s %2$s'), $tenth_column,
                                    Html::showToolTip($planned_infos,
                                                      ['display' => false,
                                                       'applyto' => $item->getType() .
                                                                    $item->fields["id"] .
                                                                    "planning" . $rand]));
         }
         echo Search::showItem($p['output_type'], $tenth_column, $item_num, $p['row_num'],
                               $align_desc . " width='150'");

         // Finish Line
         echo Search::showEndLine($p['output_type']);
      } else {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='6' ><i>" . __('No item in progress.') . "</i></td></tr>";
      }
   }
}