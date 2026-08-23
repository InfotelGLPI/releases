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
use Supplier;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Release_Supplier Class
 *
 * Relation between Releases and Suppliers
 *
 * @since 0.84
 **/
class Release_Supplier extends CommonITILActor
{
    // From CommonDBRelation
    public static $itemtype_1 = Release::class;
    public static $items_id_1 = 'plugin_releases_releases_id';
    public static $itemtype_2 = 'Supplier';
    public static $items_id_2 = 'suppliers_id';

    /**
     * Print the object user form for notification
     *
     * @param $ID              integer ID of the item
     * @param $options   array
     *
     * @return false
     **@since 0.85
     *
     */
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
