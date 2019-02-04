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
global $DB;
$item = new $_GET['itemtype']();
$item->getFromDB($_GET['itemId']);
$temp = "PluginPdf".$_GET['itemtype'];
$itempdf = new $temp();

$pref = getAllDatasFromTable("glpi_plugin_pdf_preferences");
foreach ($pref as $data){
    if($data['itemtype']==$_GET['itemtype']){
        $tab[] = $data['tabref'];
    }
}
if (empty($tab)){
    $tab[] = $_GET['itemtype']."$"."main";
}


$itempdf->generatePDF(array($_GET['itemId']), $tab,0,0,"/releases/pdf/Conf_".$_GET['itemtype'].$_GET['itemId'].".pdf");

$doc = new Document;
$doc_id = $doc->add(array('name'=>__('Document').": ".__('Change')." - ".$_GET['itemtype'].$_GET['itemId'], 'upload_file'=>"../../plugins/releases/pdf/Conf_".$_GET['itemtype'].$_GET['itemId'].".pdf", 'filepath'=>'../plugins/release/pdf/', 'mime'=>'application/pdf', 'date_creation'=>$_SESSION['glpi_currenttime']));
$query = "UPDATE `glpi_documents`
          SET `filename` = 'conf_".$_GET['itemtype'].$_GET['itemId'].".pdf'
          WHERE `id` = '".$doc_id."'";
$DB->query($query);

$doc_item = new Document_Item();
$doc_item->add(array('documents_id'=>$doc_id, 'items_id'=>$_GET['idChange'], 'itemtype'=>'Change'));


