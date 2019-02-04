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

include ('../../../inc/includes.php');
Html::header_nocache();

Session::checkLoginUser();

//Plugin::load('pdf', true);
//
//$change = getAllDatasFromTable('glpi_changes_items', '`changes_id`="'.$_GET['id'].'"');
//foreach ($change as $itemdata){
//    $item = new $itemdata['itemtype']();
//    $item->getFromDB($itemdata['items_id']);
//    $itempdf = new $PLUGIN_HOOKS['plugin_pdf'][$itemdata['itemtype']]($item);
//    echo '<iframe style="display:none" src="createPdf.php?idChange='.$_GET['id'].'&itemtype='.$itemdata['itemtype'].'&itemId='.$itemdata['items_id'].'"></iframe>';
//
//}
Html::back();
