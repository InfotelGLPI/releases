<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018 by the Releases Development Team.

 https://github.com/InfotelGLPI/releases
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

/**
 * Class PluginReleasesCreationRelease
 */
class PluginReleasesCreationRelease extends CommonDBTM {

   public $dohistory = true;
   static $rightname = 'ticket';
   protected $usenotepad = true;
   static $types = [];


   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Release', 'Releases', $nb, 'releases');
   }
   static function getMenuName($nb = 1) {
      return __('Release', 'releases');
   }










   function initShowForm($ID, $options = []){


      $this->initForm($ID, $options);
      $this->showFormHeader($options);

   }

   function closeShowForm($options){
      $this->showFormButtons($options);
   }

   function showForm($ID, $options = []) {

      $this->initShowForm($ID,$options);

      $this->coreShowForm($ID,$options);
      $this->closeShowForm($options);

      return true;
   }

   function displayMenu($ID, $options = []) {
      echo "<div class='center'>";
      echo "<table class='tab_cadre'>";
      echo "<tr  class='tab_bg_1'>";
      echo "<th>" . __("Release","releases") . "</th>";
      echo "</tr>";
      echo "<tr  class='tab_bg_1'>";
      echo "<td class='center b' >";
      $item = new PluginReleasesReleasetemplate();
      $dbu = new DbUtils();
      $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
      PluginReleasesReleasetemplate::dropdown(["name"=>"releasetemplates_id"]+$condition);
      $url = PluginReleasesRelease::getFormURL();
      echo "<a  id='link' href='$url'>";
      $url = $url."?template_id=";
      $script = "
      var link = function (id,linkurl) {
         var link = linkurl+id;
         $(\"a#link\").attr(\"href\", link);
      };
      $(\"select[name='releasetemplates_id']\").change(function() {
         link($(\"select[name='releasetemplates_id']\").val(),'$url');
         });";


      echo Html::scriptBlock('$(document).ready(function() {'.$script.'});');
            echo "<br/><br/>";
      echo __("Create a release", 'releases');
      echo "</a>";

      //      echo "<tr class='tab_bg_1'>";
      //      $this->displayItemMenuCMDB(__("Configure links", 'cmdb'), "typelink.php", "iconTypelink.png");
      //      $this->displayItemMenuCMDB(__("Display Baseline", 'cmdb'), "baseline.php", "iconBaseline.png");
      //      echo "</tr>";
      echo "</table>";
      echo "</div>";
   }

}

