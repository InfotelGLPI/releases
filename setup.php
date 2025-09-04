<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 releases plugin for GLPI
 Copyright (C) 2018-2022 by the releases Development Team.

 https://github.com/InfotelGLPI/releases
 -------------------------------------------------------------------------

 LICENSE

 This file is part of releases.

 releases is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 releases is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with releases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

global $CFG_GLPI;

use Glpi\Plugin\Hooks;

define('PLUGIN_RELEASES_VERSION', '2.0.4');

if (!defined("PLUGIN_RELEASES_DIR")) {
   define("PLUGIN_RELEASES_DIR", Plugin::getPhpDir("releases"));
   define("PLUGIN_RELEASES_NOTFULL_DIR", Plugin::getPhpDir("releases",false));
//   define("PLUGIN_RELEASES_WEBDIR", $CFG_GLPI['root_doc'] . '/plugins/releases');
//   define("PLUGIN_RELEASES_NOTFULL_WEBDIR", '/plugins/releases');
}

// Init the hooks of the plugins -Needed
function plugin_init_releases() {
   global $PLUGIN_HOOKS, $CFG_GLPI;
    $CFG_GLPI['glpiitemtypetables']['glpi_plugin_releases_releases'] = 'PluginReleasesRelease';
   $PLUGIN_HOOKS['csrf_compliant']['releases']   = true;
   $PLUGIN_HOOKS['change_profile']['releases']   = ['PluginReleasesProfile', 'initProfile'];
   $PLUGIN_HOOKS['assign_to_ticket']['releases'] = true;
   if (isset($_SESSION['glpiactiveprofile']['interface'])
       && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
//      $PLUGIN_HOOKS["javascript"]['releases'] = ["plugins/releases/js/releases.js"];
      $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['releases'][] = "js/releases.js";
      $PLUGIN_HOOKS[Hooks::ADD_CSS]['releases'][]      = "css/styles.css";
   }

   Html::requireJs('tinymce');

   if (Session::getLoginUserID()) {

       if (class_exists('PluginMetademandsMetademand')) {
           PluginMetademandsMetademand::registerType('PluginReleasesRelease');
       }

      Plugin::registerClass('PluginReleasesProfile',
                            ['addtabon' => 'Profile']);
      Plugin::registerClass('PluginReleasesRelease',
                            ['addtabon'                    => ['Change'],
                             'notificationtemplates_types' => true]);
      Plugin::registerClass('PluginReleasesRelease_Item',
                            ['addtabon' => ['User', 'Group', 'Supplier']]);
      Plugin::registerClass(PluginReleasesDeploytask::class, [
         'planning_types' => true
      ]);

      if (Session::haveRight("plugin_releases_releases", READ)) {
         $PLUGIN_HOOKS['menu_toadd']['releases'] = ['helpdesk' => 'PluginReleasesRelease'];
      }
   }

   $PLUGIN_HOOKS['planning_populate']['releases'] = ['PluginReleasesDeploytask', 'populatePlanning'];
   $PLUGIN_HOOKS['display_planning']['releases']  = ['PluginReleasesDeploytask', 'displayPlanningItem'];

   if (Plugin::isPluginActive("mydashboard")) {
       Plugin::registerClass('PluginMydashboardAlert',
                             ['addtabon' => ['PluginReleasesRelease']]);
   }

   // End init, when all types are registered
   $PLUGIN_HOOKS['post_init']['releases'] = 'plugin_releases_postinit';
}

// Get the name and the version of the plugin - Needed
/**
 * @return array
 */
function plugin_version_releases() {

   return [
      'name'           => _n('Release', 'Releases', 2, 'releases'),
      'version'      => PLUGIN_RELEASES_VERSION,
      'license'        => 'GPLv2+',
      'author'         => "<a href='https://blogglpi.infotel.com'>Infotel</a>, Alban Lesellier",
      'homepage'       => 'https://github.com/InfotelGLPI/releases',
      'minGlpiVersion' => '11.0',// For compatibility / no install
      'requirements'   => [
         'glpi' => [
            'min' => '11.0',
            'max' => '12.0'
         ]
      ]
   ];

}
