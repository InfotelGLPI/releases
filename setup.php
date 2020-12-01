<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 releases plugin for GLPI
 Copyright (C) 2018 by the releases Development Team.

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

// Init the hooks of the plugins -Needed
function plugin_init_releases() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['releases']   = true;
   $PLUGIN_HOOKS['change_profile']['releases']   = ['PluginReleasesProfile', 'initProfile'];
   $PLUGIN_HOOKS['assign_to_ticket']['releases'] = true;

   $PLUGIN_HOOKS["javascript"]['releases']     = ["/plugins/releases/js/releases.js"];
   $PLUGIN_HOOKS['add_javascript']['releases'] = 'js/releases.js';
   $PLUGIN_HOOKS['add_css']['releases'][]      = "css/styles.css";

   Html::requireJs('tinymce');

   if (Session::getLoginUserID()) {

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
   $plugin                                        = new Plugin();
   if ($plugin->isInstalled("mydashboard")) {
      if ($plugin->isActivated("mydashboard")) {
         Plugin::registerClass('PluginMydashboardAlert',
                               ['addtabon' => ['PluginReleasesRelease']]);
      }
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
      'version'        => '1.0.1',
      'license'        => 'GPLv2+',
      'author'         => "<a href='http://infotel.com/services/expertise-technique/glpi/'>Infotel</a>, Alban Lesellier",
      'homepage'       => 'https://github.com/InfotelGLPI/releases',
      'minGlpiVersion' => '9.5',// For compatibility / no install in version < 9.3
      'requirements'   => [
         'glpi' => [
            'min' => '9.5',
            'max' => '9.6'
         ]
      ]
   ];

}

// Optional : check prerequisites before install : may print errors or add to message after redirect
/**
 * @return bool
 */
function plugin_releases_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.5', 'lt')
       || version_compare(GLPI_VERSION, '9.6', 'ge')) {
      echo __('This plugin requires GLPI >= 9.5');
      return false;
   }

   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
/**
 * @return bool
 */
function plugin_releases_check_config() {
   return true;
}
