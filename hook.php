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
   $rep_files_release = GLPI_PLUGIN_DOC_DIR . "/releases";
   if (!is_dir($rep_files_release)) {
      mkdir($rep_files_release);
   }

   PluginReleasesProfile::initProfile();
   PluginReleasesProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

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
      PluginReleasesDeploytask::getTable(),
      PluginReleasesTest::getTable(),
      PluginReleasesRisk::getTable(),
      PluginReleasesRollback::getTable(),
      PluginReleasesDeploytasktemplate::getTable(),
   ];

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   $tables_glpi = ["glpi_displaypreferences",
                   "glpi_savedsearches",
                   "glpi_logs",
                   "glpi_documents_items",
                   "glpi_notepads",
                   "glpi_items_tickets",
                   "glpi_knowbaseitems_items"
   ];

   foreach ($tables_glpi as $table_glpi) {
      $DB->query("DELETE FROM `$table_glpi` WHERE `itemtype` LIKE 'PluginReleases%';");
   }

   //TODO add drop profiles & menu in session ?
   //Delete rights associated with the plugin
   //   $profileRight = new ProfileRight();
   //   foreach (PluginReleaseProfile::getAllRights(true) as $right) {
   //      $profileRight->deleteByCriteria(['name' => $right['field']]);
   //   }


   return true;
}

function plugin_releases_postinit() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['item_purge']['releases'] = [];
   $release                                = new PluginReleasesRelease();
   $types                                  = $release->getAllTypesForHelpdesk();
   if (isset($types) && is_array($types)) {
      foreach ($types as $type => $name) {

         $PLUGIN_HOOKS['item_purge']['releases'][$type]
            = ['PluginReleasesRelease_Item', 'cleanForItem'];

         CommonGLPI::registerStandardTab($type, 'PluginReleasesRelease_Item');
      }
   }
}

// Define dropdown relations
/**
 * @return array
 */
function plugin_releases_getDatabaseRelations() {

   $plugin = new Plugin();

   if ($plugin->isActivated("releases")) {
      return [
         "glpi_entities"                        => [
            "glpi_plugin_releases_deploytasks"         => "entities_id",
            "glpi_plugin_releases_deploytasktemplates" => "entities_id",
            "glpi_plugin_releases_releases"            => "entities_id",
            "glpi_plugin_releases_releasetemplates"    => "entities_id",
            "glpi_plugin_releases_reviews"             => "entities_id",
            "glpi_plugin_releases_risks"               => "entities_id",
            "glpi_plugin_releases_risktemplates"       => "entities_id",
            "glpi_plugin_releases_rollbacks"           => "entities_id",
            "glpi_plugin_releases_rollbacktemplates"   => "entities_id",
            "glpi_plugin_releases_tests"               => "entities_id",
            "glpi_plugin_releases_testtemplates"       => "entities_id",
            "glpi_plugin_releases_typedeploytasks"     => "entities_id",
            "glpi_plugin_releases_typerisks"           => "entities_id",
            "glpi_plugin_releases_typetests"           => "entities_id"
         ],
         "glpi_plugin_releases_releases"        => [
            "glpi_plugin_releases_deploytasks"      => "plugin_releases_releases_id",
            "glpi_plugin_releases_releases_items"   => "plugin_releases_releases_id",
            "glpi_plugin_releases_changes_releases" => "plugin_releases_releases_id",
            "glpi_plugin_releases_reviews"          => "plugin_releases_releases_id",
            "glpi_plugin_releases_risks"            => "plugin_releases_releases_id",
            "glpi_plugin_releases_rollbacks"        => "plugin_releases_releases_id",
            "glpi_plugin_releases_tests"            => "plugin_releases_releases_id"
         ],
         "glpi_changes"                         => [
            "glpi_plugin_releases_changes_releases" => "changes_id"

         ],
         "glpi_plugin_releases_risks"           => [
            "glpi_plugin_releases_deploytasks" => "plugin_releases_risks_id",
            "glpi_plugin_releases_tests"       => "plugin_releases_risks_id"
         ],
         "glpi_plugin_releases_risktemplates"   => [
            "glpi_plugin_releases_deploytasktemplates" => "plugin_releases_risks_id",
            "glpi_plugin_releases_testtemplates"       => "plugin_releases_risks_id"
         ],
         "glpi_plugin_releases_typedeploytasks" => [
            "glpi_plugin_releases_deploytasks"         => "plugin_releases_typedeploytasks_id",
            "glpi_plugin_releases_deploytasktemplates" => "plugin_releases_typedeploytasks_id",
            "glpi_plugin_releases_typedeploytasks"     => "plugin_releases_typedeploytasks_id"
         ],
         "glpi_plugin_releases_typerisks"       => [
            "glpi_plugin_releases_risks"         => "plugin_releases_typerisks_id",
            "glpi_plugin_releases_typerisks"     => "plugin_releases_typerisks_id",
            "glpi_plugin_releases_risktemplates" => "plugin_releases_typerisks_id"
         ],
         "glpi_plugin_releases_typetests"       => [
            "glpi_plugin_releases_tests"         => "plugin_releases_typetests_id",
            "glpi_plugin_releases_typetests"     => "plugin_releases_typetests_id",
            "glpi_plugin_releases_testtemplates" => "plugin_releases_typetests_id"
         ],
         "glpi_users"                           => [
            "glpi_plugin_releases_deploytasks"         => "users_id",
            "glpi_plugin_releases_deploytasks"         => "users_id_editor",
            "glpi_plugin_releases_deploytasks"         => "users_id_tech",
            "glpi_plugin_releases_deploytasktemplates" => "users_id",
            "glpi_plugin_releases_deploytasktemplates" => "users_id_editor",
            "glpi_plugin_releases_deploytasktemplates" => "users_id_tech",
            "glpi_plugin_releases_risks"               => "users_id",
            "glpi_plugin_releases_risks"               => "users_id_editor",
            "glpi_plugin_releases_rollbacks"           => "users_id",
            "glpi_plugin_releases_rollbacks"           => "users_id_editor",
            "glpi_plugin_releases_tests"               => "users_id",
            "glpi_plugin_releases_tests"               => "users_id_editor",

         ],
      ];
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
              PluginReleasesTesttemplate::getType()       => PluginReleasesTesttemplate::getTypeName(2),
              PluginReleasesRisktemplate::getType()       => PluginReleasesRisktemplate::getTypeName(2),
              PluginReleasesRollbacktemplate::getType()   => PluginReleasesRollbacktemplate::getTypeName(2),
              PluginReleasesReleasetemplate::getType()    => PluginReleasesReleasetemplate::getTypeName(2),
              PluginReleasesTypeDeployTask::getType()     => PluginReleasesTypeDeployTask::getTypeName(2),
              PluginReleasesTypeTest::getType()           => PluginReleasesTypeTest::getTypeName(2),
              PluginReleasesTypeRisk::getType()           => PluginReleasesTypeRisk::getTypeName(2)

      ];
   } else {
      return [];
   }
}

/**
 * @param $types
 *
 * @return mixed
 */
function plugin_releases_AssignToTicket($types) {
   if (Session::haveRight("plugin_releases_releases", "1") && isset($_REQUEST["_itemtype"]) && $_REQUEST["_itemtype"] == "Ticket") {
      $types['PluginReleasesRelease'] = PluginReleasesRelease::getTypeName(2);
   }

   return $types;
}



