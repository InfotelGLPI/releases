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
use CommonDBTM;
use CommonGLPI;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Release_Item Class
 *
 *  Relation between Release and Items
 **/
class Release_Item extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = Release::class;
    public static $items_id_1 = 'plugin_releases_releases_id';

    public static $itemtype_2         = 'itemtype';
    public static $items_id_2         = 'items_id';
    public static $checkItem_2_Rights = self::HAVE_VIEW_RIGHT_ON_ITEM;

    public static function getIcon()
    {
        return "ti ti-package";
    }

    /**
     * @since 0.84
     **/
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    /**
     * Clean table when item is purged
     *
     * @param CommonDBTM|Object $item Object to use
     *
     * @return void
     */
    public static function cleanForItem(CommonDBTM $item)
    {

        $temp = new self();
        $temp->deleteByCriteria(
            ['itemtype' => $item->getType(),
                'items_id' => $item->getField('id')],
        );
    }

    /**
     * @see CommonDBTM::prepareInputForAdd()
     **/
    public function prepareInputForAdd($input)
    {

        // Avoid duplicate entry
        if (countElementsInTable($this->getTable(), ['plugin_releases_releases_id' => $input['plugin_releases_releases_id'],
            'itemtype'                    => $input['itemtype'],
            'items_id'                    => $input['items_id']]) > 0) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    /**
     * Print the HTML array for Items linked to a problem
     *
     * @param $release Release object
     *
     * @return void
     **/
    public static function showForRelease(Release $release)
    {
        $instID = $release->fields['id'];

        if (!$release->can($instID, READ)) {
            return false;
        }
        $canedit = $release->canEdit($instID);
        $rand    = mt_rand();

        $types_iterator = self::getDistinctTypes($instID);

        if ($canedit) {
            $types = [];
            foreach ($release->getAllTypesForHelpdesk() as $key => $val) {
                $types[] = $key;
            }
            // Capture the itemtype selector (echoes internally) and render the add
            // mini-form through Twig instead of echoing raw HTML.
            ob_start();
            Dropdown::showSelectItemFromItemtypes([
                'itemtypes'       => $types,
                'entity_restrict' => ($release->fields['is_recursive']
                   ? getSonsOf('glpi_entities', $release->fields['entities_id'])
                   : $release->fields['entities_id']),
            ]);
            $dropdown_html = ob_get_clean();

            TemplateRenderer::getInstance()->display('@releases/form_change_release_add.html.twig', [
                'action_url'    => Toolbox::getItemTypeFormURL(self::class),
                'title'         => __('Add an item'),
                'hidden_name'   => 'plugin_releases_releases_id',
                'hidden_value'  => $instID,
                'dropdown_html' => $dropdown_html,
            ]);
        }

        // Flatten the itemtype-grouped rows into a single datatable feed. Each entry
        // carries its own itemtype+id so components/datatable.html.twig can render the
        // massive-action checkbox (name="item[Release_Item][linkid]").
        $entries = [];
        foreach ($types_iterator as $row) {
            $itemtype = $row['itemtype'];
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            if (!$item->canView()) {
                continue;
            }

            foreach (self::getTypeItems($instID, $itemtype) as $data) {
                $name = $data["name"];
                if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                    $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
                }
                $link = $itemtype::getFormURLWithID($data['id']);
                // Stored XSS: asset name/serial/otherserial come straight from the DB
                // (stored un-escaped on GLPI 10+/11). The link cell is rendered raw
                // (raw_html formatter) so escape the DB-sourced name here; the other
                // cells use the default formatter, which escapes on its own.
                $namelink = "<a href=\"" . htmlspecialchars($link) . "\">" . htmlspecialchars($name) . "</a>";
                if (isset($data['is_deleted']) && $data['is_deleted']) {
                    $namelink = "<span class='tab_bg_2_2'>" . $namelink . "</span>";
                }

                $entries[] = [
                    'itemtype'    => self::class,
                    'id'          => $data["linkid"],
                    'type'        => $item->getTypeName(1),
                    'entity'      => Dropdown::getDropdownName("glpi_entities", $data['entity']),
                    'name'        => $namelink,
                    'serial'      => $data["serial"] ?? "-",
                    'otherserial' => $data["otherserial"] ?? "-",
                ];
            }
        }

        $total = count($entries);

        $columns = [
            'type'        => __('Type'),
            'entity'      => __('Entity'),
            'name'        => __('Name'),
            'serial'      => __('Serial number'),
            'otherserial' => __('Inventory number'),
        ];

        $formatters = [
            'name' => 'raw_html',
        ];

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'super_header'        => _n('Item', 'Items', $total),
            'columns'             => $columns,
            'formatters'          => $formatters,
            'entries'             => $entries,
            'total_number'        => $total,
            'filtered_number'     => $total,
            'nofilter'            => true,
            'nosort'              => true,
            'showmassiveactions'  => $canedit && $total,
            'massiveactionparams' => [
                'num_displayed' => $total,
                // Strip namespace backslashes: the container id becomes a DOM id and a
                // JS selector, both of which break with backslashes.
                'container'     => 'mass' . str_replace('\\', '', self::class) . $rand,
            ],
        ]);
    }

    public static function countForItem(CommonDBTM $item)
    {
        $dbu = new DbUtils();

        if ($item->getType() == 'User') {
            return $dbu->countElementsInTable(
                getTableForItemType(Release_User::class),
                ["users_id" => $item->getID()],
            );
        } elseif ($item->getType() == 'Group') {
            return $dbu->countElementsInTable(
                getTableForItemType(Group_Release::class),
                ["groups_id" => $item->getID()],
            );
        } elseif ($item->getType() == 'Supplier') {
            return $dbu->countElementsInTable(
                getTableForItemType(Release_Supplier::class),
                ["suppliers_id" => $item->getID()],
            );
        } else {
            $table = getTableForItemType(Release_Item::class);
            return $dbu->countElementsInTable(
                $table,
                ["items_id" => $item->getID(),
                    "itemtype" => $item->getType()],
            );
        }
    }

    public static function countReleaseForItem(CommonDBTM $item)
    {
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable(Release_Item::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releases_id" => $item->getID()],
        );
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case Release::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countReleaseForItem($item);
                    }
                    return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);
                    break;
                case 'User':
                case 'Group':
                case 'Supplier':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(Release::getTypeName(Session::getPluralNumber()), $nb);
                    break;
                default:
                    $release = new Release();
                    $types = [];
                    foreach ($release->getAllTypesForHelpdesk() as $key => $val) {
                        $types[] = $key;
                    }
                    if (in_array($item->getType(), $types)
                    && Session::haveRight("plugin_releases_releases", READ)) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            // Direct one
                            $nb = self::countForItem($item);
                            // Linked items
                            $linkeditems = $item->getLinkedItems();

                            if (count($linkeditems)) {
                                foreach ($linkeditems as $type => $tab) {
                                    $typeitem = new $type();
                                    foreach ($tab as $ID) {
                                        $typeitem->getFromDB($ID);
                                        $nb += self::countForItem($typeitem);
                                    }
                                }
                            }
                        }
                        return self::createTabEntry(Release::getTypeName(Session::getPluralNumber()), $nb);
                    }
                    break;
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case Release::class:
                self::showForRelease($item);
                break;

            default:
                Release::showListForItem($item, $withtemplate);
        }
        return true;
    }

}
