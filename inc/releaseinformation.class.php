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

class PluginReleasesReleaseInformation extends CommonDBTM {


   static $rightname = "plugin_releases";


   /**
    * @since version 0.84
    **/
   static function getTypeName($nb = 0) {
      return _n('Information', 'Informations', $nb, 'releases');
   }


   static function countForItem(CommonDBTM $item) {
      $dbu = new DbUtils();
      return $dbu->countElementsInTable('glpi_plugin_releases_informations',
                                        ["plugin_releases_releases_id" => $item->getID()]);
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
      if ($_SESSION['glpishow_count_on_tabs']) {
         return self::createTabEntry(self::getTypeName(2), self::countForItem($item));
      }
      return self::getTypeName(2);
   }

   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $info = new self();
      $ID   = $_GET['id'];
      $info->showForm($item, $ID);
   }

   function showForm($item, $ID, $options = array()) {
      global $CFG_GLPI;

      if ($this->find(["plugin_releases_releases_id" => $ID]) && isset($this->fields['id'])) {
         if (isset ($this->fields['alerts_id']) && $this->fields['alerts_id'] > 0) {
            $alert    = new PluginMydashboardAlert();
            $alert_id = $this->fields['alerts_id'];
            $alert->getFromDB($alert_id);
            $remind    = new Reminder();
            $remind_id = $alert->fields['reminders_id'];
            $remind->getFromDB($remind_id);
         } else {
            $remind    = new Reminder();
            $remind_id = $remind->add(array('name' => 'Information for release ' . $ID, 'users_id' => $_SESSION['glpiID'], 'text' => $item->fields['content']));
            $alert     = new PluginMydashboardAlert();
            $alert_id  = $alert->add(array('reminders_id' => $remind_id, 'type' => 2, 'impact' => 3));
            $this->update(array('id' => $this->fields['id'], 'alerts_id' => $alert_id, 'plugin_releases_releases_id' => $ID));
         }


         //targeting user
//         if (isset ($this->fields['is_active']) && $this->fields['is_active'] == 1) {
//            $change_items = new Change_Item();
//            $items        = $change_items->find("`glpi_changes_items`.`changes_id` = '" . $ID . "'");
//            foreach ($items as $data) {
//               $item_use = new $data['itemtype']();
//               $item_use->getFromDB($data['items_id']);
//               $target = new Reminder_User();
//               if (!$target->find("`glpi_reminders_users`.`reminders_id` = '" . $remind_id . "' AND `glpi_reminders_users`.`users_id` = '" . $item_use->fields['users_id'] . "'")) {
//                  $target->add(array('reminders_id' => $remind_id, 'users_id' => $item_use->fields['users_id']));
//               }
//            }
//         }


         echo '<div>';
         echo "<table class='tab_cadre_fixe'>";
         echo '<tr class="headerRow">';
         echo '<th>' . __('Information') . '</th>';
         echo '</tr>';
         echo '</table>';
         echo '</div>';
         $rand = mt_rand();
         echo "<div id='viewfollowup" . $item->fields['id'] . "$rand'></div>\n";
         echo "<script type='text/javascript' >\n";
         echo "function addNotif" . $item->fields['id'] . "$rand() {\n";
         $params = array('id'        => $this->fields['id'],
                         'is_active' => $this->fields['is_active'],
                         'alerts_id' => $alert_id,
                         'action'    => 'start');
         Ajax::updateItemJsCode("viewfollowup" . $item->fields['id'] . "$rand",
                                $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/notifInfo.php", $params);
         echo "};";
         echo "</script>\n";

         echo "<div id='addinfobutton" . $item->fields['id'] . "$rand' style='width:25%; margin-left:15%; height:50px; float:left'>" .
              "<a class='vsubmit' href='javascript:addNotif" . $item->fields['id'] . "$rand();'>";
         echo __('Start notification', 'releases') . "</a></div>\n";

         echo "<div id='viewfollowup" . $item->fields['id'] . "$rand" . "2" . "'></div>\n";
         echo "<script type='text/javascript' >\n";
         echo "function delNotif" . $item->fields['id'] . "$rand() {\n";
         $params2 = array('id'           => $this->fields['id'],
                          'is_active'    => $this->fields['is_active'],
                          'action'       => 'stop',
                          'alerts_id'    => $alert_id,
                          'reminders_id' => $remind_id);
         Ajax::updateItemJsCode("viewfollowup" . $item->fields['id'] . "$rand" . "2",
                                $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/notifInfo.php", $params2);
         echo "};";
         echo "</script>\n";

         echo "<div id='delinfobutton" . $item->fields['id'] . "$rand' style='width:25%; margin-right:15%; height:50px; float:right'>" .
              "<a class='vsubmit' href='javascript:delNotif" . $item->fields['id'] . "$rand();'>";
         echo __('Stop notification', 'releases') . "</a></div>\n";

         echo "<div style='width:20%; float:right'><img width=50 height=50 src='" . static::getBubble($this->fields['is_active']) . "'></div>";


      } else {
         $info_id = $this->add(array('plugin_releases_releases_id' => $ID));
         $this->getFromDB($info_id);
         $this->showForm($item, $ID);
      }


   }


   static function getBubble($stepstate) {
      global $CFG_GLPI;
      switch ($stepstate) {
         case 0:
            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/grey.png" width=50 height=50>';
            break;
         case 1:
            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/green.png" width=50 height=50>';
            break;
         case 2:
            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/red.png" width=50 height=50>';
            break;

         default:
            break;
      }
   }

}