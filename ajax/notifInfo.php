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

include ('../../../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

global $DB;

if (isset($_POST['id'])) {

    $info = new PluginReleasesInformation();
    $info->getFromDB($_POST['id']);
    $alert = new PluginMydashboardAlert();
    $alert->getFromDB($_POST['alerts_id']);
    
    if ($_POST['action']=='start'){
        $info->update(array('id'=>$_POST['id'], 'is_active'=>'1'));
        $alert->update(array('id'=>$_POST['alerts_id'], 'type' => 1));
    }
    else if ($_POST['action']=='stop' && $_POST['is_active']=='1' && $_POST['reminders_id']>0){
        $info->update(array('id'=>$_POST['id'], 'is_active'=>'2'));
        $query = "DELETE FROM `glpi_reminders_users`
                  WHERE `reminders_id` = ".$_POST['reminders_id'];
        $DB->query($query);
        $alert->update(array('id'=>$_POST['alerts_id'], 'type' => 2));
        
        
    }
    
    
    echo '<script>location.reload();</script>';
    
    
}