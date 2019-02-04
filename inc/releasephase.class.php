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

class PluginReleasesReleasePhase extends PluginReleasesReleaseDeployment {


   static $rightname = "plugin_releases";


   /**
    * @since version 0.84
    **/
   static function getTypeName($nb = 0) {
      return _n('Phase', 'Phases', $nb, 'releases');
   }

   /** form for Task
    *
    * @param $ID        Integer : Id of the task
    * @param $options   array
    *     -  parent Object : the object
    * */
   function showForm($ID, $options = array()) {

      $rand = mt_rand();
      $this->initForm($ID, $options);
      $options["formtitle"] = "";
      $options["colspan"] = "1";
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><th colspan='2'>";
      if ($ID > 0) {
         echo __('Edit phase', 'releases');
      } else {
         echo __("New phase", 'releases');
      }
      echo '</th></tr>';

      $rowspan = 5;

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description') . "</td>";
      if (isset($this->fields["comment"])) {
         $text = $this->fields["comment"];
      } else {
         $text = "";
      }
      echo "<td id='content'>" .
           "<textarea name='comment' style='width: 95%; height: 160px' id='phase'>" . $text .
           "</textarea>";
      echo Html::scriptBlock("$(document).ready(function() { $('#content$rand').autogrow(); });");
      echo "</td>";
      echo "<input type='hidden' name='plugin_releases_deployments_id' value='" . $options['plugin_releases_deployments_id'] . "'>";
      echo "</tr>\n";


      $this->showFormButtons(array('formfooter' => false));

      return true;
   }
}