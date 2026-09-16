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
use Html;
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
    public static $itemtype_1 = ReleaseTemplate::class;
    public static $items_id_1 = 'plugin_releases_releasetemplates_id';

    public static $itemtype_2         = 'itemtype';
    public static $items_id_2         = 'items_id';
    // Same contract as Release_Item: CommonDBRelation must apply the view right and
    // checkEntity() on the linked asset, which is an itemtype/items_id pair coming
    // straight from the client.
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
        $number         = count($types_iterator);

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='releaseitem_form$rand' id='releaseitem_form$rand' method='post'
                action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add an item') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td>";
            Dropdown::showSelectItemFromItemtypes([
                'itemtypes'       => self::getLinkableItemtypes(),
                'entity_restrict' => self::getEntityRestrict($release),
            ]);
            echo "</td><td class='center' width='30%'>";
            echo Html::submit(_sx('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
            echo Html::hidden('plugin_releases_releasetemplates_id', ['value' => $instID]);
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $number) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if ($canedit && $number) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . __('Type') . "</th>";
        $header_end .= "<th>" . __('Entity') . "</th>";
        $header_end .= "<th>" . __('Name') . "</th>";
        $header_end .= "<th>" . __('Serial number') . "</th>";
        $header_end .= "<th>" . __('Inventory number') . "</th></tr>";
        echo $header_begin . $header_top . $header_end;

        $totalnb = 0;
        foreach ($types_iterator as $row) {
            //      while ($row = $types_iterator->next()) {
            $itemtype = $row['itemtype'];
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }

            if ($item->canView()) {
                // CommonDBRelation::getTypeItems() applies no entity restriction at all,
                // and canView() above is a global right per itemtype, not a per-row check.
                // Replay the dropdown criteria so a link created before the sink check was
                // added cannot display an asset from another entity.
                $allowed  = array_map('intval', (array) self::getEntityRestrict($release));
                $rows     = [];
                foreach (self::getTypeItems($instID, $itemtype) as $row_data) {
                    $row_entity = (int) ($row_data['entity'] ?? 0);
                    if (in_array($row_entity, $allowed, true)
                        && Session::haveAccessToEntity($row_entity)) {
                        $rows[] = $row_data;
                    }
                }
                $nb = count($rows);
                if ($nb === 0) {
                    continue;
                }

                $prem = true;
                foreach ($rows as $data) {
                    $name = $data["name"];
                    if ($_SESSION["glpiis_ids_visible"]
                        || empty($data["name"])) {
                        $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
                    }
                    $link     = $itemtype::getFormURLWithID($data['id']);
                    // Stored XSS: asset name/serial/otherserial come straight from the DB
                    // (stored un-escaped on GLPI 10+/11) and are echoed into the central page.
                    // Escape every DB-sourced value before it reaches the HTML.
                    $namelink = "<a href=\"" . $link . "\">" . htmlspecialchars($name) . "</a>";

                    echo "<tr class='tab_bg_1'>";
                    if ($canedit) {
                        echo "<td width='10'>";
                        Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
                        echo "</td>";
                    }
                    if ($prem) {
                        $typename = $item->getTypeName($nb);
                        echo "<td class='center top' rowspan='$nb'>"
                             . (($nb > 1) ? sprintf(__('%1$s: %2$s'), $typename, $nb) : $typename) . "</td>";
                        $prem = false;
                    }
                    echo "<td class='center'>";
                    echo htmlescape(Dropdown::getDropdownName("glpi_entities", $data['entity'])) . "</td>";
                    echo "<td class='center"
                         . (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
                    echo ">" . $namelink . "</td>";
                    echo "<td class='center'>" . (isset($data["serial"]) ? htmlspecialchars($data["serial"]) : "-")
                         . "</td>";
                    echo "<td class='center'>"
                         . (isset($data["otherserial"]) ? htmlspecialchars($data["otherserial"]) : "-") . "</td>";
                    echo "</tr>";
                }
                $totalnb += $nb;
            }
        }

        if ($number) {
            echo $header_begin . $header_bottom . $header_end;
        }

        echo "</table>";
        if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
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
        $table = CommonDBTM::getTable(Release_Item::class);
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
                    if (Session::haveRight("release", READ)) {
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
