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

namespace GlpiPlugin\Releases;

use CommonITILActor;
use Glpi\Application\View\TemplateRenderer;
use NotificationMailing;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/// Class Release_User
class Release_User extends CommonITILActor
{
    // From CommonDBRelation
    public static $itemtype_1 = Release::class;
    public static $items_id_1 = 'plugin_releases_releases_id';
    public static $itemtype_2 = 'User';
    public static $items_id_2 = 'users_id';

    public function post_addItem()
    {

        switch ($this->input['type']) { // Values from CommonITILObject::getSearchOptionsActors()
            case CommonITILActor::REQUESTER:
                $this->_force_log_option = 4;
                break;
            case CommonITILActor::OBSERVER:
                $this->_force_log_option = 66;
                break;
            case CommonITILActor::ASSIGN:
                $this->_force_log_option = 5;
                break;
        }
        parent::post_addItem();
        unset($this->_force_log_option);
    }

    /**
     * Print the object user form for notification
     *
     * @param $ID              integer ID of the item
     * @param $options   array
     *
     * @return false
     **/
    public function showUserNotificationForm($ID, $options = [])
    {

        $this->check($ID, UPDATE);

        if (!isset($this->fields['users_id'])) {
            return false;
        }
        $item = new static::$itemtype_1();

        $parent_name = '';
        if ($item->getFromDB($this->fields[static::getItilObjectForeignKey()])) {
            $parent_name = $item->getField('name');
        }

        $user          = new User();
        $default_email = "";
        $emails        = [];
        if ($user->getFromDB($this->fields["users_id"])) {
            $default_email = $user->getDefaultEmail();
            $emails        = $user->getAllEmails();
        }

        // Choose how the email field is rendered depending on available user emails
        if (
            (count($emails) == 1)
            && !empty($default_email)
            && NotificationMailing::isUserAddressValid($default_email)
        ) {
            $email_mode = 'single';
        } elseif (count($emails) > 1) {
            $email_mode = 'select';
        } else {
            $email_mode = 'text';
        }

        $emailtab = [];
        foreach ($emails as $new_email) {
            $emailtab[$new_email] = $new_email;
        }

        TemplateRenderer::getInstance()->display('@releases/actor_notification.html.twig', [
            'form_action'      => static::getFormURL(),
            'parent_type_name' => $item->getTypeName(1),
            'parent_name'      => $parent_name,
            'actor_type_name'  => User::getTypeName(1),
            'actor_name'       => $user->getName(),
            'use_notification' => $this->fields['use_notification'],
            'email_mode'       => $email_mode,
            'default_email'    => $default_email,
            'emails'           => $emailtab,
            'alternative_email' => $this->fields['alternative_email'],
            'id'               => $ID,
        ]);
    }

}
