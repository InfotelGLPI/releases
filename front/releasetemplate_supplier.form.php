<?php

/**
 * -------------------------------------------------------------------------
 * releases plugin for GLPI
 * Copyright (C) 2020-2026 by the releases Development Team.
 *
 * https://github.com/InfotelGLPI/releases
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of releases.
 *
 * releases is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * releases is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with releases. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use Glpi\Event;
use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Releases\ReleaseTemplate_Supplier;

$link = new ReleaseTemplate_Supplier();

Html::popHeader(__('Email followup'), $_SERVER['PHP_SELF']);

if (isset($_POST["update"])) {
    $link->check($_POST["id"], UPDATE);

    $link->update($_POST);
    echo "<script type='text/javascript' >\n";
    echo "window.parent.location.reload();";
    echo "</script>";

} elseif (isset($_POST['delete'])) {
    $link->check($_POST['id'], DELETE);
    $link->delete($_POST);

    Event::log(
        $link->fields['plugin_releases_releasetemplates_id'],
        "plugin_releases",
        4,
        "maintain",
        sprintf(__('%s deletes an actor'), $_SESSION["glpiname"]),
    );
    // Redirecting to Release::getFormURLWithID() sent the popup to the release form carrying a
    // template id, an id that form cannot load. ReleaseTemplate has no front controller at all
    // — neither front/releasetemplate.php nor front/releasetemplate.form.php exists, and
    // ReleaseTemplate::showForm() posts to Release::getFormURL() — so there is no template URL
    // to redirect to. Go back to the page the popup was opened from.
    Html::back();

} elseif (isset($_GET["id"])) {
    $link->showSupplierNotificationForm($_GET["id"]);
} else {
    throw new BadRequestHttpException('Lost');
}

Html::popFooter();
