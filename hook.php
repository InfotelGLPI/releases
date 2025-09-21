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

use GlpiPlugin\Releases\Change_Release;
use GlpiPlugin\Releases\Deploytask;
use GlpiPlugin\Releases\Deploytasktemplate;
use GlpiPlugin\Releases\Group_Release;
use GlpiPlugin\Releases\Group_Releasetemplate;
use GlpiPlugin\Releases\Profile;
use GlpiPlugin\Releases\Release;
use GlpiPlugin\Releases\Release_Item;
use GlpiPlugin\Releases\Release_Supplier;
use GlpiPlugin\Releases\Release_User;
use GlpiPlugin\Releases\Releasetemplate;
use GlpiPlugin\Releases\Releasetemplate_Item;
use GlpiPlugin\Releases\Releasetemplate_Supplier;
use GlpiPlugin\Releases\Releasetemplate_User;
use GlpiPlugin\Releases\Review;
use GlpiPlugin\Releases\Risk;
use GlpiPlugin\Releases\Risktemplate;
use GlpiPlugin\Releases\Rollback;
use GlpiPlugin\Releases\Rollbacktemplate;
use GlpiPlugin\Releases\Test;
use GlpiPlugin\Releases\Testtemplate;
use GlpiPlugin\Releases\TypeDeployTask;
use GlpiPlugin\Releases\TypeRisk;
use GlpiPlugin\Releases\TypeTest;

/**
 * @return bool
 */
function plugin_releases_install()
{
    global $DB;

    if (!$DB->tableExists("glpi_plugin_releases_releases")) {
        $DB->runFile(PLUGIN_RELEASES_DIR . "/sql/empty-2.1.0.sql");
        install_notifications();
    } else {
        $DB->runFile(PLUGIN_RELEASES_DIR . "/sql/update-2.1.0.sql");
    }
    $rep_files_release = GLPI_PLUGIN_DOC_DIR . "/releases";
    if (!is_dir($rep_files_release)) {
        mkdir($rep_files_release);
    }

    Profile::initProfile();
    Profile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

    return true;
}


/**
 * @return bool
 */
function plugin_releases_uninstall()
{
    global $DB;


    $tables = [
      Release::getTable(),
      Review::getTable(),
      Change_Release::getTable(),
      TypeDeployTask::getTable(),
      TypeRisk::getTable(),
      TypeTest::getTable(),
      Deploytask::getTable(),
      Test::getTable(),
      Risk::getTable(),
      Rollback::getTable(),
      Deploytasktemplate::getTable(),
      Group_Release::getTable(),
      Group_Releasetemplate::getTable(),
      Release_Item::getTable(),
      Releasetemplate_Supplier::getTable(),
      Release_Supplier::getTable(),
      Release_User::getTable(),
      Releasetemplate_Item::getTable(),
      Risktemplate::getTable(),
      Rollbacktemplate::getTable(),
      Releasetemplate_User::getTable(),
      Testtemplate::getTable(),
      Releasetemplate::getTable()
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->dropTable($table, true);
        }
    }

    $itemtypes = ['Alert',
        'DisplayPreference',
        'Document_Item',
        'ImpactItem',
        'Item_Ticket',
        'Link_Itemtype',
        'Notepad',
        'SavedSearch',
        'DropdownTranslation',
        'NotificationTemplate',
        'Notification'];
    foreach ($itemtypes as $itemtype) {
        $item = new $itemtype;
        $item->deleteByCriteria(['itemtype' => Release::class]);

        $item = new $itemtype;
        $item->deleteByCriteria(['itemtype' => Releasetemplate::class]);
    }

   //TODO add drop profiles & menu in session ?
   //Delete rights associated with the plugin
   //   $profileRight = new ProfileRight();
   //   foreach (Profile::getAllRights(true) as $right) {
   //      $profileRight->deleteByCriteria(['name' => $right['field']]);
   //   }

    $options = ['itemtype' => Release::class];

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
    $options        = ['itemtype' => Release::class,
    ];

    foreach ($DB->request([
        'FROM'  => 'glpi_notificationtemplates',
        'WHERE' => $options
    ]) as $data) {
        $options_template = ['notificationtemplates_id' => $data['id']];
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

function plugin_releases_postinit()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['item_purge']['releases'] = [];
    $release                                = new Release();
    $types                                  = $release->getAllTypesForHelpdesk();
    if (isset($types) && is_array($types)) {
        foreach ($types as $type => $name) {
            $PLUGIN_HOOKS['item_purge']['releases'][$type]
            = [Release_Item::class, 'cleanForItem'];

            CommonGLPI::registerStandardTab($type, Release_Item::class);
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
function plugin_releases_getDropdown()
{

    if (Plugin::isPluginActive("releases")) {
        return [Deploytasktemplate::getType() => Deploytasktemplate::getTypeName(2),
              Testtemplate::getType()       => Testtemplate::getTypeName(2),
              Risktemplate::getType()       => Risktemplate::getTypeName(2),
              Rollbacktemplate::getType()   => Rollbacktemplate::getTypeName(2),
//              Releasetemplate::getType()    => Releasetemplate::getTypeName(2),
              TypeDeployTask::getType()     => TypeDeployTask::getTypeName(2),
              TypeTest::getType()           => TypeTest::getTypeName(2),
              TypeRisk::getType()           => TypeRisk::getTypeName(2)

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
function plugin_releases_AssignToTicket($types)
{
    if (Session::haveRight("plugin_releases_releases", "1")
       && isset($_REQUEST["_itemtype"]) && $_REQUEST["_itemtype"] == "Ticket") {
        $types[Release::class] = Release::getTypeName(2);
    }

    return $types;
}


/**
 * install_notifications
 *
 * @return bool for success (will die for most error)
 * */
function install_notifications()
{

    global $DB;

    $migration = new Migration(1.0);

   // Notification
   // Request
    $options_notif        = ['itemtype' => Release::class,
        'name' => 'New release'];
    $DB->insert(
        "glpi_notificationtemplates",
        $options_notif
    );

    foreach ($DB->request([
        'FROM' => 'glpi_notificationtemplates',
        'WHERE' => $options_notif]) as $data) {
        $templates_id = $data['id'];

        if ($templates_id) {
            $DB->insert(
                "glpi_notificationtemplatetranslations",
                [
                    'notificationtemplates_id' => $templates_id,
                    'subject' => '##lang.pluginreleasesrelease.release## : ##pluginreleasesrelease.title##',
                    'content_text' => '##lang.pluginreleasesrelease.title## : ##pluginreleasesrelease.title##
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
                                    ##ENDFOREACHtests##',
                                                        'content_html' => '##lang.pluginreleasesrelease.title## : ##pluginreleasesrelease.title##
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
                                    ##ENDFOREACHtests##',
                ]
            );

            $DB->insert(
                "glpi_notifications",
                [
                    'name' => 'New release',
                    'entities_id' => 0,
                    'itemtype' => Release::class,
                    'event' => 'newRelease',
                    'is_recursive' => 1,
                ]
            );

            $options_notif        = ['itemtype' => Release::class,
                'name' => 'New release',
                'event' => 'newRelease'];

            foreach ($DB->request([
                'FROM' => 'glpi_notifications',
                'WHERE' => $options_notif]) as $data_notif) {
                $notification = $data_notif['id'];
                if ($notification) {
                    $DB->insert(
                        "glpi_notifications_notificationtemplates",
                        [
                            'notifications_id' => $notification,
                            'mode' => 'mailing',
                            'notificationtemplates_id' => $templates_id,
                        ]
                    );
                }
            }
        }
    }

    $migration->executeMigration();

    return true;
}
