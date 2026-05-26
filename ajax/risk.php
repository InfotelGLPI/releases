<?php

/*
 -------------------------------------------------------------------------
 releases plugin for GLPI
 Copyright (C) 2020-2026 by the releases Development Team.

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

use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\RichText\RichText;
use GlpiPlugin\Releases\Release;
use GlpiPlugin\Releases\Risktemplate;

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();
Session::checkRight('plugin_releases_releases', UPDATE);

$_POST['itemtype'] = Release::class;

// Mandatory parameter: risktemplates_id
$risktemplates_id = $_POST['risktemplates_id'] ?? null;
if ($risktemplates_id === null) {
    throw new BadRequestHttpException("Missing or invalid parameter: 'risktemplates_id'");
} else if ($risktemplates_id == 0) {
   // Reset form
    echo json_encode([
      'content' => ""
    ]);
    die;
}

// Mandatory parameter: items_id
$parents_id = $_POST['items_id'] ?? 0;
if (!$parents_id) {
    throw new BadRequestHttpException("Missing or invalid parameter: 'items_id'");
}

// Mandatory parameter: itemtype
$parents_itemtype = $_POST['itemtype'] ?? '';
if (empty($parents_itemtype) || !is_subclass_of($parents_itemtype, CommonITILObject::class)) {
    throw new BadRequestHttpException("Missing or invalid parameter: 'itemtype'");
}

// Load Risktemplate template
$template = new Risktemplate();
if (!$template->getFromDB($risktemplates_id)) {
    throw new BadRequestHttpException("Unable to load template: $risktemplates_id");
}

// Load parent item
$parent = new $parents_itemtype();
if (!$parent->getFromDB($parents_id)) {
    throw new BadRequestHttpException("Unable to load parent item: $parents_itemtype $parents_id");
}

// Render template content using
$template->fields['content'] = RichText::getSafeHtml($template->fields['content']);

// Return json response with the template fields
echo json_encode($template->fields);
