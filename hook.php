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

/**
 * @return bool
 */
function plugin_releases_install() {
   global $DB;

//   include_once(GLPI_ROOT . "/plugins/release/inc/profile.class.php");

   if (!$DB->tableExists("glpi_plugin_releases_releases")) {

      $DB->runFile(GLPI_ROOT . "/plugins/releases/sql/empty-1.0.0.sql");

   }
   $rep_files_release = GLPI_PLUGIN_DOC_DIR."/releases";
   if (!is_dir($rep_files_release)) {
      mkdir($rep_files_release);
   }


   PluginReleasesProfile::initProfile();
   PluginReleasesProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
//
//   PluginReleasesNotificationTargetTask::install100();

   return true;
}

/**
 * @return bool
 */
function plugin_releases_uninstall() {
   global $DB;



   $tables = [
               PluginReleasesRelease::getTable(),
               PluginReleasesReview::getTable(),
               PluginReleasesChange_Release::getTable(),
               PluginReleasesTypeDeployTask::getTable(),
               PluginReleasesTypeRisk::getTable(),
               PluginReleasesTypeTest::getTable(),
               PluginReleasesDeployTask::getTable(),
               PluginReleasesTest::getTable(),
               PluginReleasesRisk::getTable(),
               PluginReleasesRollback::getTable(),
               PluginReleasesDeploytasktemplate::getTable(),
               'glpi_plugin_releases_globalstatues'
             ];

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   $tables_glpi = ["glpi_displaypreferences",
                   "glpi_notepads",
                   "glpi_savedsearches",
                   "glpi_logs",
                   "glpi_documents_items"];

   foreach ($tables_glpi as $table_glpi) {
      $DB->query("DELETE FROM `$table_glpi` WHERE `itemtype` LIKE 'PluginRelease%';");
   }




   //Delete rights associated with the plugin
//   $profileRight = new ProfileRight();
//   foreach (PluginReleaseProfile::getAllRights(true) as $right) {
//      $profileRight->deleteByCriteria(['name' => $right['field']]);
//   }


   return true;
}

// Define dropdown relations
/**
 * @return array
 */
function plugin_releases_getDatabaseRelations() {

   $plugin = new Plugin();

   if ($plugin->isActivated("releases")) {
      return [];
   } else {
      return [];
   }
}

// Define Dropdown tables to be manage in GLPI :
/**
 * @return array
 */
function plugin_releases_getDropdown() {

   $plugin = new Plugin();
 
   if ($plugin->isActivated("releases")) {
      return [PluginReleasesDeploytasktemplate::getType() => PluginReleasesDeploytasktemplate::getTypeName(2),
            PluginReleasesTesttemplate::getType() => PluginReleasesTesttemplate::getTypeName(2),
            PluginReleasesRisktemplate::getType() => PluginReleasesRisktemplate::getTypeName(2),
            PluginReleasesRollbacktemplate::getType() => PluginReleasesRollbacktemplate::getTypeName(2),
            PluginReleasesReleasetemplate::getType() => PluginReleasesReleasetemplate::getTypeName(2),
            PluginReleasesTypeDeployTask::getType() => PluginReleasesTypeDeployTask::getTypeName(2),
         PluginReleasesTypeTest::getType() => PluginReleasesTypeTest::getTypeName(2),
         PluginReleasesTypeRisk::getType() => PluginReleasesTypeRisk::getTypeName(2)

      ];
   } else {
      return [];
   }
}



/**
 * @param $type
 * @param $ID
 * @param $data
 * @param $num
 *
 * @return string
 */
function plugin_releases_displayConfigItem($type, $ID, $data, $num) {

   $searchopt =& Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

//   switch ($table . '.' . $field) {
//      case "glpi_plugin_releases_tasks.priority" :
//         return " style=\"background-color:" . $_SESSION["glpipriority_" . $data[$num][0]['name']] . ";\" ";
//         break;
//   }
   return "";
}


