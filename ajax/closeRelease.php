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

$AJAX_INCLUDE = 1;
global $DB;
include ('../../../inc/includes.php');

$overview = new PluginReleasesReleaseOverview();

if($overview->getFromDB($_GET['plugin_releases_releases_id'])) {
   if (isset($overview->fields['is_release'])
       && $overview->fields['is_release'] == 1) {
      $temp = 2;
   } else {
      $temp = 1;
   }
   $overview->update(array('id' => $overview->getID(), 'is_release' => $temp));
}
Html::back();

