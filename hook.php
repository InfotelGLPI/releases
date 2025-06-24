<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.

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

   if (!$DB->tableExists("glpi_plugin_releases_releases")) {

      $DB->runFile(PLUGIN_RELEASES_DIR . "/sql/empty-2.0.0.sql");
      install_notifications();
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
      PluginReleasesGroup_Release::getTable(),
      PluginReleasesGroup_Releasetemplate::getTable(),
      PluginReleasesRelease_Item::getTable(),
      PluginReleasesReleasetemplate_Supplier::getTable(),
      PluginReleasesRelease_Supplier::getTable(),
      PluginReleasesRelease_User::getTable(),
      PluginReleasesReleasetemplate_Item::getTable(),
      PluginReleasesRisktemplate::getTable(),
      PluginReleasesRollbacktemplate::getTable(),
      PluginReleasesReleasetemplate_User::getTable(),
      PluginReleasesTesttemplate::getTable(),
      PluginReleasesReleasetemplate::getTable()
   ];

   foreach ($tables as $table) {
       if ($DB->tableExists($table)) {
           $DB->dropTable($table);
       }
   }

   $tables_glpi = ["glpi_displaypreferences",
                   "glpi_savedsearches",
                   "glpi_logs",
                   "glpi_documents_items",
                   "glpi_notepads",
                   "glpi_items_tickets",
                   "glpi_knowbaseitems_items",
                   "glpi_itilfollowups"
   ];

   foreach ($tables_glpi as $table_glpi) {
       $DB->delete($table_glpi, ['itemtype' => ['LIKE' => 'PluginReleases%']]);
   }

   //TODO add drop profiles & menu in session ?
   //Delete rights associated with the plugin
   //   $profileRight = new ProfileRight();
   //   foreach (PluginReleaseProfile::getAllRights(true) as $right) {
   //      $profileRight->deleteByCriteria(['name' => $right['field']]);
   //   }

   $options = ['itemtype' => 'PluginReleasesRelease',
               'event'    => 'newRelease',
               'FIELDS'   => 'id'];

   $notif = new Notification();
    foreach ($DB->request([
        'FROM'  => 'glpi_notifications',
        'WHERE' => $options
    ]) as $data) {
        $notif->delete($data);
   }

   //templates
   $template       = new NotificationTemplate();
   $translation    = new NotificationTemplateTranslation();
   $notif_template = new Notification_NotificationTemplate();
   $options        = ['itemtype' => 'PluginReleasesRelease',
//                      'FIELDS'   => 'id'
   ];

    foreach ($DB->request([
        'FROM'  => 'glpi_notificationtemplates',
        'WHERE' => $options
    ]) as $data) {
        $options_template = ['notificationtemplates_id' => $data['id'],
                           'FIELDS'                   => 'id'];
        foreach ($DB->request([
            'FROM'  => 'glpi_notificationtemplatetranslations',
            'WHERE' => $options_template
        ]) as $data_template) {

            $translation->delete($data_template);
      }
      $template->delete($data);

        foreach ($DB->request([
            'FROM'  => 'glpi_notifications_notificationtemplates',
            'WHERE' => $options_template
        ]) as $data_template) {

            $notif_template->delete($data_template);
      }
   }


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
//function plugin_releases_getDatabaseRelations() {

//   if (Plugin::isPluginActive("releases")) {
//      return [
//         "glpi_entities"                        => [
//            "glpi_plugin_releases_deploytasks"         => "entities_id",
//            "glpi_plugin_releases_deploytasktemplates" => "entities_id",
//            "glpi_plugin_releases_releases"            => "entities_id",
//            "glpi_plugin_releases_releasetemplates"    => "entities_id",
//            "glpi_plugin_releases_reviews"             => "entities_id",
//            "glpi_plugin_releases_risks"               => "entities_id",
//            "glpi_plugin_releases_risktemplates"       => "entities_id",
//            "glpi_plugin_releases_rollbacks"           => "entities_id",
//            "glpi_plugin_releases_rollbacktemplates"   => "entities_id",
//            "glpi_plugin_releases_tests"               => "entities_id",
//            "glpi_plugin_releases_testtemplates"       => "entities_id",
//            "glpi_plugin_releases_typedeploytasks"     => "entities_id",
//            "glpi_plugin_releases_typerisks"           => "entities_id",
//            "glpi_plugin_releases_typetests"           => "entities_id"
//         ],
//         "glpi_plugin_releases_releases"        => [
//            "glpi_plugin_releases_deploytasks"      => "plugin_releases_releases_id",
//            "glpi_plugin_releases_releases_items"   => "plugin_releases_releases_id",
//            "glpi_plugin_releases_changes_releases" => "plugin_releases_releases_id",
//            "glpi_plugin_releases_reviews"          => "plugin_releases_releases_id",
//            "glpi_plugin_releases_risks"            => "plugin_releases_releases_id",
//            "glpi_plugin_releases_rollbacks"        => "plugin_releases_releases_id",
//            "glpi_plugin_releases_tests"            => "plugin_releases_releases_id"
//         ],
//         "glpi_changes"                         => [
//            "glpi_plugin_releases_changes_releases" => "changes_id"

//         ],
//         "glpi_plugin_releases_risks"           => [
//            "glpi_plugin_releases_deploytasks" => "plugin_releases_risks_id",
//            "glpi_plugin_releases_tests"       => "plugin_releases_risks_id"
//         ],
//         "glpi_plugin_releases_risktemplates"   => [
//            "glpi_plugin_releases_deploytasktemplates" => "plugin_releases_risks_id",
//            "glpi_plugin_releases_testtemplates"       => "plugin_releases_risks_id"
//         ],
//         "glpi_plugin_releases_typedeploytasks" => [
//            "glpi_plugin_releases_deploytasks"         => "plugin_releases_typedeploytasks_id",
//            "glpi_plugin_releases_deploytasktemplates" => "plugin_releases_typedeploytasks_id",
//            "glpi_plugin_releases_typedeploytasks"     => "plugin_releases_typedeploytasks_id"
//         ],
//         "glpi_plugin_releases_typerisks"       => [
//            "glpi_plugin_releases_risks"         => "plugin_releases_typerisks_id",
//            "glpi_plugin_releases_typerisks"     => "plugin_releases_typerisks_id",
//            "glpi_plugin_releases_risktemplates" => "plugin_releases_typerisks_id"
//         ],
//         "glpi_plugin_releases_typetests"       => [
//            "glpi_plugin_releases_tests"         => "plugin_releases_typetests_id",
//            "glpi_plugin_releases_typetests"     => "plugin_releases_typetests_id",
//            "glpi_plugin_releases_testtemplates" => "plugin_releases_typetests_id"
//         ],
//         "glpi_users"                           => [
//            "glpi_plugin_releases_deploytasks"         => "users_id",
//            "glpi_plugin_releases_deploytasks"         => "users_id_editor",
//            "glpi_plugin_releases_deploytasks"         => "users_id_tech",
//            "glpi_plugin_releases_deploytasktemplates" => "users_id",
//            "glpi_plugin_releases_deploytasktemplates" => "users_id_editor",
//            "glpi_plugin_releases_deploytasktemplates" => "users_id_tech",
//            "glpi_plugin_releases_risks"               => "users_id",
//            "glpi_plugin_releases_risks"               => "users_id_editor",
//            "glpi_plugin_releases_rollbacks"           => "users_id",
//            "glpi_plugin_releases_rollbacks"           => "users_id_editor",
//            "glpi_plugin_releases_tests"               => "users_id",
//            "glpi_plugin_releases_tests"               => "users_id_editor",

//         ],
//      ];
//   } else {
//      return [];
//   }
//}

// Define Dropdown tables to be manage in GLPI :
/**
 * @return array
 */
function plugin_releases_getDropdown() {

   if (Plugin::isPluginActive("releases")) {
      return [PluginReleasesDeploytasktemplate::getType() => PluginReleasesDeploytasktemplate::getTypeName(2),
              PluginReleasesTesttemplate::getType()       => PluginReleasesTesttemplate::getTypeName(2),
              PluginReleasesRisktemplate::getType()       => PluginReleasesRisktemplate::getTypeName(2),
              PluginReleasesRollbacktemplate::getType()   => PluginReleasesRollbacktemplate::getTypeName(2),
//              PluginReleasesReleasetemplate::getType()    => PluginReleasesReleasetemplate::getTypeName(2),
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
   if (Session::haveRight("plugin_releases_releases", "1")
       && isset($_REQUEST["_itemtype"]) && $_REQUEST["_itemtype"] == "Ticket") {
      $types['PluginReleasesRelease'] = PluginReleasesRelease::getTypeName(2);
   }

   return $types;
}


/**
 * install_notifications
 *
 * @return bool for success (will die for most error)
 * */
function install_notifications() {

   global $DB;

   $migration = new Migration(1.0);

   // Notification
   // Request
    $query_id = "INSERT INTO `glpi_notificationtemplates`(`name`, `itemtype`, `date_mod`) VALUES ('New release','PluginReleasesRelease', NOW());";
    $result = $DB->doQuery($query_id) or die($DB->error());
    $query_id = "SELECT `id` FROM `glpi_notificationtemplates` WHERE `itemtype`='PluginReleasesRelease' AND `name` = 'New release'";
    $result = $DB->doQuery($query_id) or die($DB->error());
   $templates_id = $DB->result($result, 0, 'id');

    $query = "INSERT INTO `glpi_notificationtemplatetranslations` (`notificationtemplates_id`, `subject`, `content_text`, `content_html`)
VALUES('" . $templates_id . "',
'##lang.pluginreleasesrelease.release## : ##pluginreleasesrelease.title##',
'##lang.pluginreleasesrelease.title## : ##pluginreleasesrelease.title##
##lang.pluginreleasesrelease.url## : ##pluginreleasesrelease.url##
##lang.pluginreleasesrelease.status## : ##pluginreleasesrelease.status##

##lang.pluginreleasesrelease.description## : ##pluginreleasesrelease.description##

##lang.pluginreleasesrelease.risks##
##FOREACHrisks##

##lang.risk.name## : ##risk.name##
##lang.risk.description## : ##risk.description##

##ENDFOREACHrisks##

##lang.pluginreleasesrelease.rollbacks##
##FOREACHrollbacks##

##lang.rollback.name## : ##rollback.name##
##lang.rollback.description## : ##rollback.description##

##ENDFOREACHrollbacks##

##lang.pluginreleasesrelease.numberofrollbacks## : ##pluginreleasesrelease.numberofrollbacks##


##lang.pluginreleasesrelease.tasks##
##FOREACHtasks##

[##task.date##]
##lang.task.author## : ##task.author##
##lang.task.description## : ##task.description##
##lang.task.time## : ##task.time##
##lang.task.type## : ##task.type##
##lang.task.status## : ##task.status##

##ENDFOREACHtasks##

##lang.pluginreleasesrelease.numberoftasks## : ##pluginreleasesrelease.numberoftasks##

##lang.pluginreleasesrelease.tests##
##FOREACHtests##

##lang.test.author## ##test.author##
##lang.test.description## ##test.description##
##lang.test.type## ##test.type##
##lang.test.status## : ##test.status##
##ENDFOREACHtests##



','');";
    $DB->doQuery($query);

    $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`)
              VALUES ('New release', 0, 'PluginReleasesRelease', 'newRelease', 1);";
    $DB->doQuery($query);

   //retrieve notification id
    $query_id = "SELECT `id` FROM `glpi_notifications`
               WHERE `name` = 'New release' AND `itemtype` = 'PluginReleasesRelease' AND `event` = 'newRelease'";
    $result = $DB->doQuery($query_id) or die ($DB->error());
    $notification = $DB->result($result, 0, 'id');

    $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`) 
               VALUES (" . $notification . ", 'mailing', " . $templates_id . ");";
    $DB->doQuery($query);
    //

    //
   //      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`)
   //              VALUES ('Consumable request', 0, 'PluginConsumablesRequest', 'ConsumableRequest', 1);";
   //      $DB->query($query);
   //
   //      //retrieve notification id
   //      $query_id = "SELECT `id` FROM `glpi_notifications`
   //               WHERE `name` = 'Consumable request' AND `itemtype` = 'PluginConsumablesRequest' AND `event` = 'ConsumableRequest'";
   //      $result = $DB->query($query_id) or die ($DB->error());
   //      $notification = $DB->result($result, 0, 'id');
   //
   //      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`)
   //               VALUES (" . $notification . ", 'mailing', " . $templates_id . ");";
   //      $DB->query($query);
   //
   //      // Request validation
   //      $query_id = "INSERT INTO `glpi_notificationtemplates`(`name`, `itemtype`, `date_mod`, `comment`, `css`) VALUES ('Consumables Request Validation','PluginConsumablesRequest', NOW(),'','');";
   //      $result = $DB->query($query_id) or die($DB->error());
   //      $query_id = "SELECT `id` FROM `glpi_notificationtemplates` WHERE `itemtype`='PluginConsumablesRequest' AND `name` = 'Consumables Request Validation'";
   //      $result = $DB->query($query_id) or die($DB->error());
   //      $templates_id = $DB->result($result, 0, 'id');
   //
   //      $query = "INSERT INTO `glpi_notificationtemplatetranslations` (`notificationtemplates_id`, `subject`, `content_text`, `content_html`)
   //VALUES('" . $templates_id . "', '##consumable.action## : ##consumable.entity##',
   //'##FOREACHconsumabledatas##
   //##lang.consumable.entity## :##consumable.entity##
   //##lang.consumablerequest.requester## : ##consumablerequest.requester##
   //##lang.consumablerequest.validator## : ##consumablerequest.validator##
   //##lang.consumablerequest.consumabletype## : ##consumablerequest.consumabletype##
   //##lang.consumablerequest.consumable## : ##consumablerequest.consumable##
   //##lang.consumablerequest.number## : ##consumablerequest.number##
   //##lang.consumablerequest.requestdate## : ##consumablerequest.requestdate##
   //##lang.consumablerequest.status## : ##consumablerequest.status##
   //##ENDFOREACHconsumabledatas##
   //##lang.consumablerequest.comment## : ##consumablerequest.comment##',
   //'##FOREACHconsumabledatas##&lt;br /&gt; &lt;br /&gt;
   //&lt;p&gt;##lang.consumable.entity## :##consumable.entity##&lt;br /&gt; &lt;br /&gt;
   //##lang.consumablerequest.requester## : ##consumablerequest.requester##&lt;br /&gt;
   //##lang.consumablerequest.validator## : ##consumablerequest.validator##&lt;br /&gt;
   //##lang.consumablerequest.consumabletype## : ##consumablerequest.consumabletype##&lt;br /&gt;
   //##lang.consumablerequest.consumable## : ##consumablerequest.consumable##&lt;br /&gt;
   //##lang.consumablerequest.number## : ##consumablerequest.number##&lt;br /&gt;
   //##lang.consumablerequest.requestdate## : ##consumablerequest.requestdate##&lt;br /&gt;
   //##lang.consumablerequest.status## : ##consumablerequest.status##&lt;br /&gt;
   //##lang.consumablerequest.comment## : ##consumablerequest.comment##&lt;br /&gt;
   //##ENDFOREACHconsumabledatas##');";
   //      $DB->query($query);
   //
   //      $query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`)
   //              VALUES ('Consumable request validation', 0, 'PluginConsumablesRequest', 'ConsumableResponse', 1);";
   //      $DB->query($query);
   //
   //      //retrieve notification id
   //      $query_id = "SELECT `id` FROM `glpi_notifications`
   //               WHERE `name` = 'Consumable request validation' AND `itemtype` = 'PluginConsumablesRequest'
   //               AND `event` = 'ConsumableResponse'";
   //      $result = $DB->query($query_id) or die ($DB->error());
   //      $notification = $DB->result($result, 0, 'id');
   //
   //      $query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`)
   //               VALUES (" . $notification . ", 'mailing', " . $templates_id . ");";
   //      $DB->query($query);

   $migration->executeMigration();

   return true;


}


