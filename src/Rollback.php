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

use CommonDBTM;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Session;

/**
 * Class Rollback
 */
class Rollback extends CommonDBTM
{
    public static $rightname = 'plugin_releases_rollbacks';
    public const TODO = 1; // todo
    public const DONE = 2; // done

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {

        return _n('Rollback', 'Rollbacks', $nb, 'releases');
    }

    /**
     *
     * @return css class
     */
    public static function getCssClass()
    {
        return "rollback";
    }

    /**
     * @param \CommonDBTM $item
     *
     * @return int
     */
    public static function countForItem(CommonDBTM $item)
    {
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable(self::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releases_id" => $item->getID()],
        );
    }

    /**
     * @param \CommonDBTM $item
     *
     * @return int
     */
    public static function countDoneForItem(CommonDBTM $item)
    {
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable(self::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releases_id" => $item->getID(),
                "state"                       => self::DONE],
        );
    }

    /**
     * Prepare input datas for adding the item. If false, add is canceled.
     *
     * @param array $input datas used to add the item
     *
     * @return false|array the modified $input array
     **/
    public function prepareInputForAdd($input)
    {

        $input = parent::prepareInputForAdd($input);

        $input["users_id"] = Session::getLoginUserID();

        // The parent release decides both the entity and the ownership of this
        // sub-item, so the posted id has to be resolved and checked before being
        // trusted: a crafted items_id would otherwise drop the sub-item into
        // another entity's release. Fail closed here rather than in the front
        // controller only, so every entry point (form, AJAX, massive actions,
        // template instantiation) shares the very same guard.
        $release = new Release();
        if (!$release->getFromDB((int) ($input["items_id"] ?? 0))
            || !Session::haveAccessToEntity($release->fields["entities_id"], $release->isRecursive())) {
            Session::addMessageAfterRedirect(
                __('The action you have requested is not allowed.'),
                false,
                ERROR,
            );
            return false;
        }
        $input["plugin_releases_releases_id"] = $release->getID();
        $input["entities_id"]                 = $release->fields["entities_id"];

        return $input;
    }

    public function post_addItem()
    {
        parent::post_addItem();

    }

    /**
     * Prepare input datas for updating the item
     *
     * @param array $input data used to update the item
     *
     * @return array the modified $input array
     **/
    public function prepareInputForUpdate($input)
    {
        // Never let an update re-parent the sub-item: entities_id and the parent keys
        // are settled at creation time, from a release that was checked back then.
        // Dropping them from the update payload closes the cross-entity move through a
        // crafted POST and costs nothing to the legitimate callers, none of which ever
        // changes them (ajax/timeline.php only re-sends the row's own release id).
        unset($input["entities_id"], $input["plugin_releases_releases_id"], $input["items_id"]);

        // update last editor if content change
        $input['users_id_editor'] = Session::getLoginUserID();
        if (isset($input['update'])
            && ($uid = Session::getLoginUserID())) { // Change from task form
            $input["users_id_editor"] = $uid;
        }
        $this->fields['date_mod'] = $_SESSION["glpi_currenttime"];
        $input['date_mod']        = $_SESSION["glpi_currenttime"];
        $input                    = parent::prepareInputForUpdate($input);
        return $input;
    }

    public function post_updateItem($history = 1)
    {

        //      parent::post_updateItem($history);
    }

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool
     */
    public function showForm($ID, $options = [])
    {

        if ($this->isNewItem()) {
            $this->getEmpty();
        }
        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $this->check(-1, CREATE, $options);
        }

        TemplateRenderer::getInstance()->display('@releases/form_rollback.html.twig', [
            'item'      => $options['parent'],
            'subitem'   => $this,
        ]);
    }
}
