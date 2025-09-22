<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

namespace GlpiPlugin\Releases;

use CommonITILActor;
use Dropdown;
use Html;
use NotificationMailing;
use User;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class Release_User
class Release_User extends CommonITILActor {

   // From CommonDBRelation
   static public $itemtype_1 = Release::class;
   static public $items_id_1 = 'plugin_releases_releases_id';
   static public $itemtype_2 = 'User';
   static public $items_id_2 = 'users_id';

   function post_addItem() {

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

        echo "<br><form method='post' action='" . $_SERVER['PHP_SELF'] . "'>";
        echo "<div class='center'>";
        echo "<table class='tab_cadre' width='80%'>";
        echo "<tr class='tab_bg_2'><td>" . $item->getTypeName(1) . "</td>";
        echo "<td>";
        if ($item->getFromDB($this->fields[static::getItilObjectForeignKey()])) {
            echo $item->getField('name');
        }
        echo "</td></tr>";

        $user          = new User();
        $default_email = "";
        $emails = [];
        if ($user->getFromDB($this->fields["users_id"])) {
            $default_email = $user->getDefaultEmail();
            $emails        = $user->getAllEmails();
        }

        echo "<tr class='tab_bg_2'><td>" . User::getTypeName(1) . "</td>";
        echo "<td>" . $user->getName() . "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Email Followup') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('use_notification', $this->fields['use_notification']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . _n('Email', 'Emails', 1) . "</td>";
        echo "<td>";
        if (
            (count($emails) ==  1)
            && !empty($default_email)
            && NotificationMailing::isUserAddressValid($default_email)
        ) {
            echo $default_email;
        } else if (count($emails) > 1) {
            // Several emails : select in the list
            $emailtab = [];
            foreach ($emails as $new_email) {
                $emailtab[$new_email] = $new_email;
            }
            Dropdown::showFromArray(
                "alternative_email",
                $emailtab,
                ['value'   => $this->fields['alternative_email']]
            );
        } else {
            echo "<input type='text' size='40' name='alternative_email' value='" .
                $this->fields['alternative_email'] . "'>";
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='center' colspan='2'>";
        echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary'>";
        echo "<input type='hidden' name='id' value='$ID'>";
        echo "</td></tr>";

        echo "</table></div>";
        Html::closeForm();
    }

}
