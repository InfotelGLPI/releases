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
use Session;
use Toolbox;

/**
 * ReleaseTemplate_Item Class
 *
 *  Relation between ReleaseTemplates and Items
 **/
class ReleaseTemplate_Item extends CommonDBRelation
{
    // From CommonDBRelation
    public static ?string $itemtype_1 = ReleaseTemplate::class;
    public static ?string $items_id_1 = 'plugin_releases_releasetemplates_id';

    public static ?string $itemtype_2         = 'itemtype';
    public static ?string $items_id_2         = 'items_id';
    // Same contract as Release_Item: CommonDBRelation must apply the view right and
    // checkEntity() on the linked asset, which is an itemtype/items_id pair coming
    // straight from the client.
    public static int $checkItem_2_Rights = self::HAVE_VIEW_RIGHT_ON_ITEM;

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
     * Itemtypes that may be linked to a release template.
     *
     * showForRelease() builds its dropdown with them and prepareInputForAdd() replays
     * them at the sink, so the rule is written once only.
     *
     * @return array<int, string>
     */
    public static function getLinkableItemtypes()
    {
        $release = new Release();
        return array_keys($release->getAllTypesForHelpdesk());
    }

    /**
     * Entity restriction applied to the item dropdown of a release template.
     *
     * @param ReleaseTemplate $release
     *
     * @return int|array<int, int>
     */
    public static function getEntityRestrict(ReleaseTemplate $release)
    {
        return $release->fields['is_recursive']
            ? getSonsOf('glpi_entities', $release->fields['entities_id'])
            : $release->fields['entities_id'];
    }

    /**
     * @see CommonDBTM::prepareInputForAdd()
     **/
    public function prepareInputForAdd($input)
    {
        // The dropdown restricts both the itemtype and the entity, but nothing replayed
        // that restriction server-side: a crafted POST could attach — and then read back
        // through the "Items" tab — any asset of any entity. Replay here the very
        // criteria showForRelease() builds the dropdown with.
        $template = new ReleaseTemplate();
        if (!$template->getFromDB((int) ($input['plugin_releases_releasetemplates_id'] ?? 0))
            || !$template->can($template->getID(), UPDATE)) {
            return false;
        }
        if (!in_array($input['itemtype'] ?? '', self::getLinkableItemtypes(), true)) {
            return false;
        }
        $item = getItemForItemtype($input['itemtype']);
        if ($item === false || !$item->getFromDB((int) ($input['items_id'] ?? 0))) {
            return false;
        }
        if ($item->isEntityAssign()) {
            $item_entity = (int) $item->fields['entities_id'];
            $allowed     = array_map('intval', (array) self::getEntityRestrict($template));
            if (!in_array($item_entity, $allowed, true)
                || !Session::haveAccessToEntity($item_entity, $item->isRecursive())) {
                return false;
            }
        }

        // Avoid duplicate entry
        if (countElementsInTable($this->getTable(), ['plugin_releases_releasetemplates_id' => $input['plugin_releases_releasetemplates_id'],
            'itemtype'                            => $input['itemtype'],
            'items_id'                            => $input['items_id']]) > 0) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    /**
     * Print the HTML array for Items linked to a problem
     *
     * @param $release ReleaseTemplate object
     *
     * @return void
     **/
    public static function showForRelease(ReleaseTemplate $release)
    {
        $instID = $release->fields['id'];

        if (!$release->can($instID, READ)) {
            return false;
        }
        $canedit = $release->canEdit($instID);
        $rand    = mt_rand();

        $types_iterator = self::getDistinctTypes($instID);

        if ($canedit) {
            // Capture the itemtype selector (echoes internally) and render the add
            // mini-form through Twig instead of echoing raw HTML.
            ob_start();
            Dropdown::showSelectItemFromItemtypes([
                'itemtypes'       => self::getLinkableItemtypes(),
                'entity_restrict' => self::getEntityRestrict($release),
            ]);
            $dropdown_html = ob_get_clean();

            TemplateRenderer::getInstance()->display('@releases/form_change_release_add.html.twig', [
                'action_url'    => Toolbox::getItemTypeFormURL(self::class),
                'title'         => __('Add an item'),
                'hidden_name'   => 'plugin_releases_releasetemplates_id',
                'hidden_value'  => $instID,
                'dropdown_html' => $dropdown_html,
            ]);
        }

        // CommonDBRelation::getTypeItems() applies no entity restriction at all, and
        // canView() below is a global right per itemtype, not a per-row check. Replay
        // the dropdown criteria so a link created before the sink check was added
        // cannot display an asset from another entity.
        $allowed = array_map('intval', (array) self::getEntityRestrict($release));

        // Flatten the itemtype-grouped rows into a single datatable feed. Each entry
        // carries its own itemtype+id so components/datatable.html.twig can render the
        // massive-action checkbox (name="item[ReleaseTemplate_Item][linkid]").
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
                $row_entity = (int) ($data['entity'] ?? 0);
                if (!in_array($row_entity, $allowed, true)
                    || !Session::haveAccessToEntity($row_entity)) {
                    continue;
                }

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
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable(ReleaseTemplate_Item::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releasetemplates_id" => $item->getID()],
        );
    }

    public static function countReleaseForItem(CommonDBTM $item)
    {
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable(ReleaseTemplate_Item::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releasetemplates_id" => $item->getID()],
        );
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case ReleaseTemplate::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);

                case 'User':
                case 'Group':
                case 'Supplier':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countReleaseForItem($item);
                    }
                    return self::createTabEntry(ReleaseTemplate::getTypeName(Session::getPluralNumber()), $nb);

                default:
                    if (Session::haveRight(Release::$rightname, READ)) {
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
                        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
                    }
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case ReleaseTemplate::class:
                self::showForRelease($item);
                break;

            default:
                Release::showListForItem($item, $withtemplate);
        }
        return true;
    }

}
