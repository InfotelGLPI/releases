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
class PluginReleasesRelease extends CommonDBTM {

   // From CommonDBTM
   var       $dohistory        = true;
   static    $rightname        = "plugin_releases";
   protected $usenotepad       = true;
   protected $usenotepadrights = true;


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
      //TODO
      $this->addStandardTab('PluginReleasesTest', $ong, $options);
      $this->addStandardTab('PluginReleasesTask', $ong, $options);
      $this->addStandardTab('PluginReleasesInformation', $ong, $options);
      $this->addStandardTab('PluginReleases_Change_Release', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Change') {
         $release = new PluginReleasesRelease();
         $ID      = $item->getField('id');
         $release->showForm($ID);
      }
      return true;
   }

   function showForm($ID, $options = array()) {
      global $CFG_GLPI, $DB;

      if ($ID > 0) {
         $release = new self();
         if (!$release->find(["changes_id" => $ID])) {

            $release->add(array('changes_id' => $ID));
            $query = "INSERT INTO `glpi_plugin_releases_overviews` (`id`, `changes_id`)
                  VALUES (" . $ID . "," . $ID . ")";
            $DB->query($query);
            $release->showForm($ID);

         } else {

            if (isset($release->fields['is_release']) && $release->fields['is_release'] == 0) {

               $release->initForm($release->getID(), $options);
               $release->showFormHeader($options);
               echo '<tbody>';
               echo '<tr>';
               echo '<td>' . __('Would you pass this change into release ?', 'releases') . '</td>';
               echo '<td>';
               dropdown::showYesNo('is_release',
                                   $release->fields['is_release']);
               echo '<td></tr>';
               $release->showFormButtons();

            } else {

               if (isset($release->fields['is_release']) && $release->fields['is_release'] == 2) {
                  $release->initForm($release->getID(), $options);
                  $release->showFormHeader($options);
                  echo '<tbody>';
                  echo '<tr>';
                  echo '<td>' . __('Would you reopen this release ?', 'releases') . '</td>';
                  echo '<td>';
                  dropdown::showYesNo('is_release',
                                      $release->fields['is_release']);
                  echo '<td></tr>';
                  $release->showFormButtons();
               }

               $overview = getAllDatasFromTable('glpi_plugin_releases_overviews', "`changes_id`='$ID'");
               echo "<div style='width:50%; margin-left:15%; background-color:#f1f1f1; float:left;'>";
               echo "<div style='height:70px;'>";
               echo '<div style="width:20%; float:left; margin-top:10px;">';
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_analyse&old=" . $overview[$ID]['is_validate_analyse'] . "'>";
               static::getBubble($overview[$ID]['is_validate_analyse']);
               echo '</a>';
               echo "</div>";
               echo "<div style='width:80%; line-height:35px; float:right;'>";
               echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
               echo '<br><b>' . __("Analyse state", 'releases') . '</b><br>';
               if ($overview[$ID]['is_validate_analyse'] != 1) {
                  echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_analyse&old=0'>" . __('Validate the analyse', 'releases') . "</a>";
               }
               echo '</div>';
               echo '</div></div>';

               echo "<div style='height:70px;'>";
               echo "<div style='width:20%; float:left; margin-top:10px;'>";
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_cost&old=" . $overview[$ID]['is_validate_cost'] . "'>";
               static::getBubble($overview[$ID]['is_validate_cost']);
               echo '</a>';
               echo "</div>";
               echo "<div style='width:80%; line-height:35px; float:right;'>";
               echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
               echo '<br><b>' . __("Estimation state", 'releases') . '</b><br>';
               if ($overview[$ID]['is_validate_cost'] != 1) {
                  echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_cost&old=0'>" . __('Validate the cost', 'releases') . "</a>";
               }
               echo '</div>';
               echo '</div></div>';

               echo "<div style='height:70px;'>";
               echo "<div style='width:20%; float:left; margin-top:10px;'>";
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_plan&old=" . $overview[$ID]['is_validate_plan'] . "'>";
               static::getBubble($overview[$ID]['is_validate_plan']);
               echo '</a>';
               echo "</div>";
               echo "<div style='width:80%; line-height:35px; float:right;'>";
               echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
               echo '<br><b>' . __("Planification state", 'releases') . '</b><br>';
               if ($overview[$ID]['is_validate_plan'] != 1) {
                  echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_plan&old=0'>" . __('Validate the cost', 'releases') . "</a>";
               }
               echo '</div>';
               echo '</div></div>';

               echo "<div style='height:70px;'>";
               echo "<div style='width:20%; float:left; margin-top:10px;'>";
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_test_done&old=" . $overview[$ID]['is_test_done'] . "'>";
               static::getBubble($overview[$ID]['is_test_done'], 'releases');
               echo '</a>';
               echo "</div>";
               echo "<div style='width:80%; line-height:35px; float:right;'>";
               echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
               echo '<br><b>' . __("Phase of tests", 'releases') . '</b><br>';
               echo '<a onclick="' . Html::jsGetElementbyID('new_test') . '.dialog(\'open\')" style="cursor:pointer;">' . __('Add a new test', 'releases') . "</a>";
               $test = new PluginReleasesTest();
               echo Ajax::createIframeModalWindow('new_test',
                                                  '../plugins/releases/front/test.form.php?idChange=' . $ID,
                                                  array('display' => false));
               echo '</div>';
               echo '</div></div>';

               echo "<div style='height:70px;'>";
               echo "<div style='width:20%; float:left; margin-top:10px;'>";
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_info_done&old=" . $overview[$ID]['is_info_done'] . "'>";
               static::getBubble($overview[$ID]['is_info_done']);
               echo '</a>';
               echo "</div>";
               echo "<div style='width:80%; line-height:35px; float:right;'>";
               echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
               echo '<br><b>' . __("Phase of information", 'releases') . '</b><br>';
               //echo '<a>'.__('Demarer phase info')."</a>";
               echo '</div>';
               echo '</div></div>';

               echo "<div style='height:70px;'>";
               echo "<div style='width:20%; float:left; margin-top:10px;'>";
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_deployment_done&old=" . $overview[$ID]['is_deployment_done'] . "'>";
               static::getBubble($overview[$ID]['is_deployment_done']);
               echo '</a>';
               echo "</div>";
               echo "<div style='width:80%; line-height:35px; float:right;'>";
               echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
               echo '<br><b>' . __("Phase of deployment", 'releases') . '</b><br>';
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/saveConf.php?id=" . $ID . "'>" . __('Save the configuration', 'releases') . "</a>";
               echo '</div>';
               echo '</div></div>';

               echo "<div style='height:70px;'>";
               echo "<div style='width:20%; float:left; margin-top:10px;'>";
               echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_end&old=" . $overview[$ID]['is_end'] . "'>";
               static::getBubble($overview[$ID]['is_end']);
               echo '</a>';
               echo "</div>";
               echo "<div style='width:80%; line-height:35px; float:right;'>";
               echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
               echo '<br><b>' . __("End") . '</b><br>';
               echo "<a style='a:visited:#004F91' href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/closeRelease.php?id=" . $ID . "'>" . __('Close release', 'releases') . "</a>";
               echo '</div>';
               echo '</div></div></div>';

               echo "<div style='width:25%; line-height:30px; margin-left:10%; vertical-align:middle; float:right; display:inline-block;'>";

               echo "<div style='line-height:30px;'>";
               echo "<h2>" . __('Legend', 'releases') . "</h2>";
               echo "</div>";

               echo "<div style='width:20%; float:left;'>";
               echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/grey.png" width=30 height=30"/>';
               echo "</div>";
               echo "<div style='width:80%; line-height:30px; float:right;'>";
               echo "<div style='vertical-align:middle; display: inline-block;'>";
               echo '<b>' . __("To do") . '</b>';
               echo '</div></div>';

               echo "<div style='width:20%; float:left;'>";
               echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/green.png" width=30 height=30"/>';
               echo "</div>";
               echo "<div style='width:80%; line-height:30px; float:right;'>";
               echo "<div style='vertical-align:middle; display: inline-block;'>";
               echo '<b>' . __("Done") . '</b>';
               echo '</div></div>';

               echo "<div style='width:20%; float:left;'>";
               echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/yellow.png" width=30 height=30"/>';
               echo "</div>";
               echo "<div style='width:80%; line-height:30px; float:right;'>";
               echo "<div style='vertical-align:middle; display: inline-block;'>";
               echo '<b>' . __("In progress", 'releases') . '</b>';
               echo '</div></div>';

               echo "<div style='width:20%; float:left;'>";
               echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/blue.png" width=30 height=30"/>';
               echo "</div>";
               echo "<div style='width:80%; line-height:30px; float:right;'>";
               echo "<div style='vertical-align:middle; display: inline-block;'>";
               echo '<b>' . __("Waiting", 'releases') . '</b>';
               echo '</div></div>';

               echo "<div style='width:20%; float:left;'>";
               echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/orange.png" width=30 height=30"/>';
               echo "</div>";
               echo "<div style='width:80%; line-height:30px; float:right;'>";
               echo "<div style='vertical-align:middle; display: inline-block;'>";
               echo '<b>' . __("Late") . '</b>';
               echo '</div></div>';

               echo "<div style='width:20%; float:left;'>";
               echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/red.png" width=30 height=30"/>';
               echo "</div>";
               echo "<div style='width:80%; line-height:30px; float:right;'>";
               echo "<div style='vertical-align:middle; display: inline-block;'>";
               echo '<b>' . __("Default", 'releases') . '</b>';
               echo '</div></div></div>';
            }
         }
      } else {
         echo "<div class='center'><br><br>" .
              "<i class='fas fa-exclamation-triangle fa-4x' style='color:orange'></i><br><br>";
         echo "<b>" . __('Please select a change', 'releases') . "</b></div>";
      }

   }

   static function getBubble($stepstate) {
      global $CFG_GLPI;
      switch ($stepstate) {
         case 0:
            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/grey.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            break;
         case 1:
            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/green.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            break;
         case 2:
            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/blue.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            break;
         case 3:
            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/yellow.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            break;
         case 4:
            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/orange.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            break;
         case 5:
            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/red.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            break;

         default:
            break;
      }
   }
   //
   //     /**
   //    * get the change status list
   //    * To be overridden by class
   //    *
   //    * @param $withmetaforsearch boolean (default false)
   //    *
   //    * @return an array
   //   **/
   //   static function dropdownState($withmetaforsearch=false) {
   //
   //      $tab = array(1   => __('New'),
   //                   2   => __('Analysing'),
   //                   3   => __('Estimating'),
   //                   4   => __('Planificating'),
   //                   5   => __('Validated'),
   //                   6   => __('Test'),
   //                   7   => __('Information'),
   //                   8   => __('Deployment'),
   //                   9   => __('Achived'),
   //                   10  => __('Closed'));
   //      return Dropdown::showFromArray('status', $tab);
   //   }
   //
   //
}