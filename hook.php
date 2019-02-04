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

function plugin_releases_install() {
   global $DB;

   include_once(GLPI_ROOT . "/plugins/releases/inc/profile.class.php");

   if (!$DB->tableExists("glpi_plugin_releases_releases")) {
      // table sql creation
      $DB->runFile(GLPI_ROOT . "/plugins/releases/sql/empty-1.0.0.sql");

   }

   PluginReleasesProfile::initProfile();
   PluginReleasesProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   return true;
}

// Uninstall process for plugin : need to return true if succeeded
function plugin_releases_uninstall() {
   global $DB;

   include_once(GLPI_ROOT . "/plugins/releases/inc/profile.class.php");
   include_once(GLPI_ROOT . "/plugins/release/inc/menu.class.php");

   // Plugin tables deletion
   $tables = array("glpi_plugin_releases_releases",
                   "glpi_plugin_releases_releasetests",
                   "glpi_plugin_releases_releasetasks",
                   "glpi_plugin_releases_releasephases",
                   "glpi_plugin_releases_releaseinformations",
                   "glpi_plugin_releases_releasedeployments",
                   "glpi_plugin_releases_releaseoverviews",
                   "glpi_plugin_releases_changes_releases");

   foreach ($tables as $table)
      $DB->query("DROP TABLE IF EXISTS `$table`;");

   // Plugin adding information on general table deletion
   //TODO add knowbase_item
   $tables_glpi = ["glpi_displaypreferences",
                   "glpi_documents_items",
                   "glpi_savedsearches",
                   "glpi_logs",
                   "glpi_notepads",
                   "glpi_dropdowntranslations"];

   foreach ($tables_glpi as $table_glpi)
      $DB->query("DELETE FROM `$table_glpi` WHERE `itemtype` = 'PluginReleasesRelease';");


   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();
   foreach (PluginReleasesProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(array('name' => $right['field']));
   }
   PluginReleasesMenu::removeRightsFromSession();

   PluginReleasesProfile::removeRightsFromSession();

   return true;
}


// Define dropdown relations
//TODO
//function plugin_releases_getDatabaseRelations() {
//
//   $plugin = new Plugin();
//   if ($plugin->isActivated("releases")) {
//      //TODO entities_id
//      return array("glpi_changes" => array("glpi_plugin_releases_release"      => "changes_id",
//                                           "glpi_plugin_releases_releaseoverviews"    => "changes_id",
//                                           "glpi_plugin_releases_releasetests"        => "changes_id",
//                                           "glpi_plugin_releases_releasetasks"        => "changes_id",
//                                           "glpi_plugin_releases_releaseinformations" => "changes_id",
//                                           "glpi_plugin_releases_releasedeployments"  => "changes_id"),
//                   //                    "glpi_plugin_releases_deployments" => array(
//                   //                                       "glpi_plugin_releases_phases" => "glpi_plugin_releases_deployments"),
//                   //                    "glpi_plugin_mydashboard_alerts" => array(
//                   //                                       "glpi_plugin_releases_informations" => "glpi_plugin_mydashboard_alerts")
//      );
//   } else {
//      return array();
//   }
//}


// Do special actions for dynamic report
function plugin_releases_dynamicReport($parm) {

   // Return false if no specific display is done, then use standard display
   return false;
}