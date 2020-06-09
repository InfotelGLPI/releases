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
if(Session::haveRight('plugin_releases_use',1)){
   $PLUGIN_HOOKS['csrf_compliant']['releases'] = true;
   $PLUGIN_HOOKS['change_profile']['releases'] = ['PluginReleasesProfile', 'initProfile'];

   $PLUGIN_HOOKS['use_rules']['releases'] = ['RuleMailCollector'];
   $PLUGIN_HOOKS['add_css']['releases'][] = "css/styles.css";
   Html::requireJs('tinymce');
//   $PLUGIN_HOOKS['add_css']['release'][] = "css/style_bootstrap_ticket.css";
//   $PLUGIN_HOOKS['add_css']['release'][] = "css/style_bootstrap_main.css";

   if (Session::getLoginUserID()) {

      $PLUGIN_HOOKS['menu_toadd']['releases'] = ['helpdesk' => 'PluginReleasesRelease'];

      Plugin::registerClass('PluginReleasesProfile',
         ['addtabon' => 'Profile']);
   }
   Plugin::registerClass('PluginReleasesRelease',
      ['addtabon' => ['Change']]);
   Plugin::registerClass(PluginReleasesDeployTask::class, [
      'planning_types' => true
   ]);
}
   $PLUGIN_HOOKS['planning_populate']['releases'] = ['PluginReleasesDeployTask', 'populatePlanning'];
   $PLUGIN_HOOKS['display_planning']['releases']  = ['PluginReleasesDeployTask', 'displayPlanningItem'];
   $plugin = new Plugin();
   if($plugin->isInstalled("mydashboard")){
      if($plugin->isActivated("mydashboard")){
         Plugin::registerClass('PluginMydashboardAlert',
            ['addtabon' => ['PluginReleasesRelease']]);
      }
   }
}

// Get the name and the version of the plugin - Needed
/**
 * @return array
 */
function plugin_version_releases() {

   return [
      'name'           => __('Releases', 'releases'),
      'version'        => '1.0.0',
      'license'        => 'GPLv2+',
      'author'         => "<a href='http://infotel.com/services/expertise-technique/glpi/'>Infotel</a>, Alban Lesellier",
      'homepage'       => 'https://github.com/InfotelGLPI/releases',
      'minGlpiVersion' => '9.5',// For compatibility / no install in version < 9.3
      'requirements'   => [
         'glpi'   => [
            'min' => '9.5',
            'max' => '9.6.'
//            'plugins' => ['manageentities']
         ]
      ]
   ];

}

// Optional : check prerequisites before install : may print errors or add to message after redirect
/**
 * @return bool
 */
function plugin_releases_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.3', 'lt') || version_compare(GLPI_VERSION, '9.6', 'ge')) {
      echo __('This plugin requires GLPI >= 9.3');
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
