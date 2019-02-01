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
 
class PluginReleasesMenu extends CommonGLPI {
   static $rightname = 'plugin_releases';

   static function getMenuName() {
      return _n('Release', 'Releases', 2, 'releases');
   }

   static function getMenuContent() {
      global $CFG_GLPI;

      $menu                                           = array();
      $menu['title']                                  = self::getMenuName();
      $menu['page']                                   = "/plugins/releases/front/release.php";
      $menu['links']['search']                        = PluginReleasesRelease::getSearchURL(false);
      if (PluginReleasesRelease::canCreate()) {
         $menu['links']['add']                        = PluginReleasesRelease::getFormURL(false);
      }

      return $menu;
   }

   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['helpdesk']['types']['PluginReleasesMenu'])) {
         unset($_SESSION['glpimenu']['helpdesk']['types']['PluginReleasesMenu']);
      }
      if (isset($_SESSION['glpimenu']['helpdesk']['content']['pluginreleasesmenu'])) {
         unset($_SESSION['glpimenu']['helpdesk']['content']['pluginreleasesmenu']);
      }
   }
}