<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 * @since 9.1
 */

use Glpi\Http\Response;




header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// Mandatory parameter: rollbacktemplates_id
$rollbacktemplates_id = $_POST['rollbacktemplates_id'] ?? null;
if ($rollbacktemplates_id === null) {
   Response::sendError(400, "Missing or invalid parameter: 'rollbacktemplates_id'");
} else if ($rollbacktemplates_id == 0) {
   // Reset form
   echo json_encode([
                       'name' => "",
                       'content' => ""
                    ]);
   die;
}

// Mandatory parameter: items_id
$parents_id = $_POST['items_id'] ?? 0;
if (!$parents_id) {
   Response::sendError(400, "Missing or invalid parameter: 'items_id'");
}

// Mandatory parameter: itemtype
$parents_itemtype = $_POST['itemtype'] ?? '';
if (empty($parents_itemtype) || !is_subclass_of($parents_itemtype, CommonITILObject::class)) {
   Response::sendError(400, "Missing or invalid parameter: 'itemtype'");
}

// Load PluginReleasesRollbacktemplate template
$template = new PluginReleasesRollbacktemplate();
if (!$template->getFromDB($rollbacktemplates_id)) {
   Response::sendError(400, "Unable to load template: $rollbacktemplates_id");
}

// Load parent item
$parent = new $parents_itemtype();
if (!$parent->getFromDB($parents_id)) {
   Response::sendError(400, "Unable to load parent item: $parents_itemtype $parents_id");
}

// Render template content using
$template->fields['name'] = Glpi\RichText\RichText::getTextFromHtml($template->fields['name']);

$template->fields['content'] = Glpi\RichText\RichText::getSafeHtml($template->fields['content']);

// Return json response with the template fields
echo json_encode($template->fields);
