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

define('PLUGIN_RELEASES_VERSION', '1.0.0');

// Init the hooks of the plugins -Needed
function plugin_init_releases() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['releases'] = true;
   $PLUGIN_HOOKS['change_profile']['releases'] = array('PluginReleasesProfile', 'initProfile');

   if (Session::getLoginUserID()) {

      Plugin::registerClass('PluginReleasesProfile',
                            array('addtabon' => 'Profile'));

      $PLUGIN_HOOKS['menu_toadd']['releases'] = ['helpdesk' => 'PluginReleasesMenu'];

      Plugin::registerClass('PluginReleases_Change_Release',
                              array('addtabon' => array('Change')));
   }
}


// Get the name and the version of the plugin - Needed
function plugin_version_releases() {

   return [
      'name'           => _n('Release', 'Releases', 2, 'releases'),
      'version'        => PLUGIN_RELEASES_VERSION,
      'author'         => "<a href='http://blogglpi.infotel.com'>Infotel</a>",
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/InfotelGLPI/releases',
      'requirements'   => [
         'glpi' => [
            'min' => '9.4',
            'dev' => false
         ]
      ]];

}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_releases_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.4', 'lt')
       || version_compare(GLPI_VERSION, '9.5', 'ge')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.4');
      }
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_releases_check_config() {
   return true;
}
