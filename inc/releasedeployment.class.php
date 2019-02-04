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

class PluginReleasesReleaseDeployment extends CommonDBTM {

   static $rightname = "plugin_releases";

   /**
    * @since version 0.84
    * */
   static function getTypeName($nb = 0) {
      return _n('Deployment', 'Deployments', $nb, 'releases');
   }

   /**
    * Return the name of the tab for item including forms like the config page
    *
    * @param  CommonGLPI $item Instance of a CommonGLPI Item (The Config Item)
    * @param  integer    $withtemplate
    *
    * @return String                   Name to be displayed
    */
   static function countForItem(CommonDBTM $item) {
      $dbu = new DbUtils();
      return $dbu->countElementsInTable('glpi_plugin_releases_releasedeployments',
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
      $deplo = new self();
      $deplo->showSummary($item);
   }

   function showSummary($item, $options = array()) {
      global $CFG_GLPI;

      if (!$this->find(["plugin_releases_releases_id" => $item->getID()])) {

         $this->add(array('plugin_releases_releases_id' => $item->getID()));
         $this->showSummary($item);

      } else if ($datas = $this->find(["plugin_releases_releases_id" => $item->getID()])) {

         $datas = reset($datas);
         $ID    = $datas['id'];

         $this->initForm($ID, $options);
         $options["formtitle"] = "";
         $options['colspan'] = 1;
         $this->showFormHeader($options);

         echo "<tr class='tab_bg_1'>";
         echo '<th colspan="2">' . __('Choice of deployment method', 'releases') . '</th>';
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>";
         static::dropdownDeploymentType(array('value' => $this->fields['type']));
         echo "</td>";

         if (isset($this->fields['type'])
             && $this->fields['type'] == 2) {
            echo "<td>";
            echo "<a id='addbutton' class='vsubmit' href='javascript:viewAddPhase" . $ID . "();'>";
            echo __('Add a new phase', 'releases') . "</a>\n";

            echo "</td>";
            echo "<script type='text/javascript' >\n";
            echo "function viewAddPhase" . $ID . "() {\n";
            $params = array('type'                           => $this->getType(),
                            'parenttype'                     => $item->getType(),
                            $item->getForeignKeyField()      => $item->fields['id'],
                            'plugin_releases_deployments_id' => $this->getID(),
                            'id'                             => -1);
            Ajax::updateItemJsCode("addphase" . $ID . "", $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/viewsubitemphase.php", $params);
            echo "};";
            echo "</script>\n";

         }
         echo "</tr>";
         $this->showFormButtons($options);

         echo "<br><div id='addphase" . $ID . "'></div>\n";

         $phases = getAllDatasFromTable('glpi_plugin_releases_releasephases', '`plugin_releases_deployments_id`="' . $this->getID() . '"');

         if (isset($this->fields['type'])
             && $this->fields['type'] == 2
             && count($phases) > 0) {
            echo '<table class="tab_cadre_fixe" id="mainformtable">';
            echo '<tbody>';
            echo '<tr class="headerRow">';
            echo '<th>' . __('List of phases', 'releases') . "</th>";
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';

            echo '<table class="tab_cadre_fixe" id="mainformtable">';

            echo '<thead>';
            echo '<tr role="row">';
            echo '<th class="sorting_disabled">' . __('Name') . "</th>";
            echo '<th class="sorting_disabled">' . __('Description') . "</th>";
            echo "</tr>";
            echo '</thead/>';

            echo '<tbody role=alert>';

            foreach ($phases as $data) {
               echo "<tr class='tab_bg_2' style='cursor:pointer' onClick=\"viewEditPhase" . $data['id'] . "();\">";
               echo '<td class="center">';
               echo $data['name'];
               echo '</td>';
               echo '<td class="center">';
               echo $data['comment'];
               echo '</td>';
               echo '</tr>';

               echo "\n<script type='text/javascript' >\n";
               echo "function viewEditPhase" . $data['id'] . "() {\n";
               $params = array('type'                           => $this->getType(),
                               'parenttype'                     => $item->getType(),
                               'plugin_releases_deployments_id' => $this->getID(),
                               'id'                             => $data["id"]);
               Ajax::updateItemJsCode("viewphase" . $ID, $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/viewsubitemphase.php", $params);
               echo "};";
               echo "</script>\n";
            }
         }
         echo '</tbody>';

         echo '</table>';
         echo "<div id='viewphase" . $ID . "'></div>\n";

         $plugin = new Plugin();
         if ($plugin->isActivated("pdf")) {
            echo '<br><br>';
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='headerRow'>";
            echo '<th colspan="2">' . __('Save the configuration of associated elements of change', 'releases') . '</th>';
            echo '</tr>';
            echo '<td class="center">';
            echo "<a id='addbutton' class='vsubmit' href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/saveConf.php?id=" . $ID . "'>";
            echo __('Save configuration', 'releases') . "</a>\n";
            echo '</td>';
            echo '</table>';
         } else {
            echo '<br><br>';
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='headerRow'>";
            echo '<th colspan="2">' . __('Save the configuration of associated elements of change', 'releases') . '</th>';
            echo '</tr>';
            echo '<td class="center">';
            echo __('You need pdf plugin for this fonctionality', 'releases');
            echo '</td>';
            echo '</table>';
         }
      }
   }

   /**
    * get the change status list
    * To be overridden by class
    *
    * @param $withmetaforsearch boolean (default false)
    *
    * @return an array
    * */
   static function dropdownDeploymentType($options) {

      $tab = array(0 => Dropdown::EMPTY_VALUE,
                   1 => __('Big bang', 'releases'),
                   2 => __('Phases', 'releases'));

      return Dropdown::showFromArray('type', $tab, $options);
   }
}
