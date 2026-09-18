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
use GlpiPlugin\Releases\Group_ReleaseTemplate;
use GlpiPlugin\Releases\ReleaseTemplate;

$link = new Group_ReleaseTemplate();
$item = new ReleaseTemplate();

if (isset($_POST['delete'])) {
    $link->check($_POST['id'], DELETE);
    $link->delete($_POST);

    Event::log(
        $link->fields['plugin_releases_releasetemplates_id'],
        "plugin_releases",
        4,
        "maintain",
        sprintf(__('%s deletes an actor'), $_SESSION["glpiname"]),
    );

    // Both targets were dead: ReleaseTemplate has no front controller — neither
    // front/releasetemplate.php nor front/releasetemplate.form.php exists, and its showForm()
    // posts to Release::getFormURL() — so getFormURLWithID() resolves to a 404 just like the
    // list below. Go back to the opener, keeping the message when the template is no longer
    // readable.
    if (!$item->can($link->fields["plugin_releases_releasetemplates_id"], READ)) {
        Session::addMessageAfterRedirect(
            __('You have been redirected because you no longer have access to this item'),
            true,
            ERROR,
        );
    }

    Html::back();
}

throw new BadRequestHttpException('Lost');
