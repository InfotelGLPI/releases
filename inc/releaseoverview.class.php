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

/// Class PluginReleasesOverview
class PluginReleasesReleaseOverview extends CommonDBTM {

   // From CommonDBTM
   static    $rightname        = "plugin_releases";

   /**
    * Name of the type
    *
    * @param $nb : number of item in the type (default 0)
    **/
   static function getTypeName($nb = 0) {
      return __('Overview', 'releases');
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
      if ($item->getType() == 'PluginReleasesRelease') {
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
      return $dbu->countElementsInTable('glpi_plugin_releases_releaseoverviews',
                                        ["plugin_releases_releases_id" => $item->getID()]);
   }


   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'PluginReleasesRelease') {
         $over = new self();
         $ID      = $item->getField('id');
         $over->showForm($ID);
      }
      return true;
   }

   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if ($ID > 0) {
         $overview = new self();
         if (!$overview->find(["plugin_releases_releases_id" => $ID])) {
            $overview->add(array('plugin_releases_releases_id' => $ID));
            $overview->showForm($ID);

         } else {
            $overview = new PluginReleasesReleaseOverview();
            if ($overview->getFromDB($ID)) {
               if (isset($overview->fields['is_release']) && $overview->fields['is_release'] == 0) {

                  $overview->initForm($overview->getID(), $options);
                  $overview->showFormHeader($options);
                  echo '<tbody>';
                  echo '<tr>';
                  echo '<td>' . __('Would you launch the release ?', 'releases') . '</td>';
                  echo '<td>';
                  dropdown::showYesNo('is_release',
                                      $overview->fields['is_release']);
                  echo '<td></tr>';
                  $overview->showFormButtons();

               } else {

                  if (isset($overview->fields['is_release']) && $overview->fields['is_release'] == 2) {
                     $overview->initForm($ID, $options);
                     $overview->showFormHeader($options);
                     echo '<tbody>';
                     echo '<tr>';
                     echo '<td>' . __('Would you reopen this release ?', 'releases') . '</td>';
                     echo '<td>';
                     dropdown::showYesNo('is_release',
                                         $overview->fields['is_release']);
                     echo '<td></tr>';
                     $overview->showFormButtons();
                  }

                  $overviews = getAllDatasFromTable('glpi_plugin_releases_releaseoverviews', "`plugin_releases_releases_id`='$ID'");
                  echo "<div style='width:50%; margin-left:15%; background-color:#f1f1f1; float:left;'>";
                  echo "<div style='height:70px;'>";
                  echo '<div style="width:20%; float:left; margin-top:10px;">';
                  echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_analyse&old=" . $overviews[$ID]['is_validate_analyse'] . "'>";
                  static::getBubble($overviews[$ID]['is_validate_analyse']);
                  echo '</a>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:35px; float:right;'>";
                  echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
                  echo '<br><b>' . __("Analyse state", 'releases') . '</b><br>';
                  if ($overviews[$ID]['is_validate_analyse'] != 1) {
                     echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_analyse&old=0'>" . __('Validate the analyse', 'releases') . "</a>";
                  }
                  echo '</div>';
                  echo '</div></div>';

                  echo "<div style='height:70px;'>";
                  echo "<div style='width:20%; float:left; margin-top:10px;'>";
                  echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_cost&old=" . $overviews[$ID]['is_validate_cost'] . "'>";
                  static::getBubble($overviews[$ID]['is_validate_cost']);
                  echo '</a>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:35px; float:right;'>";
                  echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
                  echo '<br><b>' . __("Estimation state", 'releases') . '</b><br>';
                  if ($overviews[$ID]['is_validate_cost'] != 1) {
                     echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_cost&old=0'>" . __('Validate the cost', 'releases') . "</a>";
                  }
                  echo '</div>';
                  echo '</div></div>';

                  echo "<div style='height:70px;'>";
                  echo "<div style='width:20%; float:left; margin-top:10px;'>";
                  echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_plan&old=" . $overviews[$ID]['is_validate_plan'] . "'>";
                  static::getBubble($overviews[$ID]['is_validate_plan']);
                  echo '</a>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:35px; float:right;'>";
                  echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
                  echo '<br><b>' . __("Planification state", 'releases') . '</b><br>';
                  if ($overviews[$ID]['is_validate_plan'] != 1) {
                     echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_validate_plan&old=0'>" . __('Validate the planning', 'releases') . "</a>";
                  }
                  echo '</div>';
                  echo '</div></div>';

                  echo "<div style='height:70px;'>";
                  echo "<div style='width:20%; float:left; margin-top:10px;'>";
                  echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_test_done&old=" . $overviews[$ID]['is_test_done'] . "'>";
                  static::getBubble($overviews[$ID]['is_test_done'], 'releases');
                  echo '</a>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:35px; float:right;'>";
                  echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
                  echo '<br><b>' . __("Phase of tests", 'releases') . '</b><br>';
                  echo '<a onclick="' . Html::jsGetElementbyID('new_test') . '.dialog(\'open\')" style="cursor:pointer;">' . __('Add a new test', 'releases') . "</a>";
                  $test = new PluginReleasesReleaseTest();
                  echo Ajax::createIframeModalWindow('new_test',
                                                     $test->getFormURL().'?plugin_releases_releases_id=' . $ID,
                                                     ['display' => false]);
                  echo '</div>';
                  echo '</div></div>';

                  echo "<div style='height:70px;'>";
                  echo "<div style='width:20%; float:left; margin-top:10px;'>";
                  echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_info_done&old=" . $overviews[$ID]['is_info_done'] . "'>";
                  static::getBubble($overviews[$ID]['is_info_done']);
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
                  echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_deployment_done&old=" . $overviews[$ID]['is_deployment_done'] . "'>";
                  static::getBubble($overviews[$ID]['is_deployment_done']);
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
                  echo "<a href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/updateState.php?id=" . $ID . "&field=is_end&old=" . $overviews[$ID]['is_end'] . "'>";
                  static::getBubble($overviews[$ID]['is_end']);
                  echo '</a>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:35px; float:right;'>";
                  echo "<div style='vertical-align:middle; line-height: 1.5; display: inline-block;'>";
                  echo '<br><b>' . __("End") . '</b><br>';
                  echo "<a style='a:visited:#004F91' href='" . $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/closeRelease.php?plugin_releases_releases_id=" . $ID . "'>" . __('Close release', 'releases') . "</a>";
                  echo '</div>';
                  echo '</div></div></div>';

                  echo "<div style='width:25%; line-height:30px; margin-left:10%; vertical-align:middle; float:right; display:inline-block;'>";

                  echo "<div style='line-height:30px;'>";
                  echo "<h2>" . __('Legend', 'releases') . "</h2>";
                  echo "</div>";

                  echo "<div style='width:20%; float:left;'>";
//                  echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/grey.png" width=30 height=30"/>';
                  echo '<i class="itilstatus fas fa-circle eval"></i>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:30px; float:right;'>";
                  echo "<div style='vertical-align:middle; display: inline-block;'>";
                  echo '<b>' . __("To do") . '</b>';
                  echo '</div></div>';

                  echo "<div style='width:20%; float:left;'>";
//                  echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/green.png" width=30 height=30"/>';
                  echo '<i class="itilstatus fas fa-circle accepted"></i>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:30px; float:right;'>";
                  echo "<div style='vertical-align:middle; display: inline-block;'>";
                  echo '<b>' . __("Done") . '</b>';
                  echo '</div></div>';

                  echo "<div style='width:20%; float:left;'>";
//                  echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/yellow.png" width=30 height=30"/>';
                  echo '<i class="itilstatus fas fa-circle assigned"></i>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:30px; float:right;'>";
                  echo "<div style='vertical-align:middle; display: inline-block;'>";
                  echo '<b>' . __("In progress", 'releases') . '</b>';
                  echo '</div></div>';

                  echo "<div style='width:20%; float:left;'>";
//                  echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/blue.png" width=30 height=30"/>';
                  echo '<i class="itilstatus fas fa-circle approval"></i>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:30px; float:right;'>";
                  echo "<div style='vertical-align:middle; display: inline-block;'>";
                  echo '<b>' . __("Waiting", 'releases') . '</b>';
                  echo '</div></div>';

                  echo "<div style='width:20%; float:left;'>";
//                  echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/orange.png" width=30 height=30"/>';
                  echo '<i class="itilstatus fas fa-circle test"></i>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:30px; float:right;'>";
                  echo "<div style='vertical-align:middle; display: inline-block;'>";
                  echo '<b>' . __("Late") . '</b>';
                  echo '</div></div>';

                  echo "<div style='width:20%; float:left;'>";
//                  echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/red.png" width=30 height=30"/>';
                  echo '<i class="itilstatus fas fa-circle"></i>';
                  echo "</div>";
                  echo "<div style='width:80%; line-height:30px; float:right;'>";
                  echo "<div style='vertical-align:middle; display: inline-block;'>";
                  echo '<b>' . __("Default", 'releases') . '</b>';
                  echo '</div></div></div>';
               }
            }
         }
      }
   }

   static function getBubble($stepstate) {
      global $CFG_GLPI;
      switch ($stepstate) {
         case 0:
            echo '<i class="itilstatus fas fa-circle eval"></i>';
//            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/grey.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            break;
         case 1:
            echo '<i class="itilstatus fas fa-circle accepted"></i>';
            break;
         case 2:
//            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/blue.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            echo '<i class="itilstatus fas fa-circle approval"></i>';
            break;
         case 3:
            echo '<i class="itilstatus fas fa-circle assigned"></i>';
//            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/yellow.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            break;
         case 4:
//            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/orange.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            echo '<i class="itilstatus fas fa-circle test"></i>';
            break;
         case 5:
//            echo '<img src="' . $CFG_GLPI["root_doc"] . '/plugins/releases/pics/red.png" alt="' . __('Change state', 'releases') . '" width=50 height=50"/>';
            echo '<i class="itilstatus fas fa-circle"></i>';
            break;

         default:
            break;
      }
   }
}