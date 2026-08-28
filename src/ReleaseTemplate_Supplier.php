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

use CommonDBRelation;
use CommonITILActor;
use Glpi\Application\View\TemplateRenderer;
use Supplier;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * ReleaseTemplate_Supplier Class
 *
 * Relation between Releases and Suppliers
 *
 * @since 0.84
 **/
class ReleaseTemplate_Supplier extends CommonITILActor
{
    // From CommonDBRelation
    public static $itemtype_1 = ReleaseTemplate::class;
    public static $items_id_1 = 'plugin_releases_releasetemplates_id';
    public static $itemtype_2 = 'Supplier';
    public static $items_id_2 = 'suppliers_id';

    public function post_addItem()
    {

        // Suppliers can only be assigned, not request or observe
        if ($this->input['type'] == CommonITILActor::ASSIGN) { // Value from CommonITILObject::getSearchOptionsActors()
            $this->_force_log_option = 6;
        }
        // A ReleaseTemplate is a CommonDropdown, not a CommonITILObject, so
        // CommonITILActor::post_addItem() cannot run here: it would update the
        // "take into account" delay, the status and raise an actor notification on an
        // ITIL object, and it now throws outright when the connected item is not one.
        // Only the relation history logging is relevant for a template, which is also
        // what CommonITILActor::post_deleteFromDB() falls back to in the same case.
        CommonDBRelation::post_addItem();
        $this->_force_log_option = 0;
    }

    /**
     * Print the object supplier form for notification
     *
     * @param $ID              integer ID of the item
     * @param $options   array
     *
     * @return false
     **/
    public function showSupplierNotificationForm($ID, $options = [])
    {

        $this->check($ID, UPDATE);

        if (!isset($this->fields['suppliers_id'])) {
            return false;
        }
        $item = new static::$itemtype_1();

        $parent_name = '';
        if ($item->getFromDB($this->fields[static::getItilObjectForeignKey()])) {
            $parent_name = $item->getField('name');
        }

        $supplier      = new Supplier();
        $default_email = "";
        if ($supplier->getFromDB($this->fields["suppliers_id"])) {
            $default_email = $supplier->fields['email'];
        }

        if (empty($this->fields['alternative_email'])) {
            $this->fields['alternative_email'] = $default_email;
        }

        TemplateRenderer::getInstance()->display('@releases/actor_notification.html.twig', [
            'form_action'       => static::getFormURL(),
            'parent_type_name'  => $item->getTypeName(1),
            'parent_name'       => $parent_name,
            'actor_type_name'   => Supplier::getTypeName(1),
            'actor_name'        => $supplier->getName(),
            'use_notification'  => $this->fields['use_notification'],
            'email_mode'        => 'text',
            'default_email'     => $default_email,
            'emails'            => [],
            'alternative_email' => $this->fields['alternative_email'],
            'id'                => $ID,
        ]);
    }
}
