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
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Test
 */
class Test extends CommonDBTM
{
    public static $rightname = 'plugin_releases_tests';
    public const TODO = 1; // todo
    public const DONE = 2; // done
    public const FAIL = 3; // Failed

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {

        return _n('Test', 'Tests', $nb, 'releases');
    }

    /**
     *
     * @return css class
     */
    public static function getCssClass()
    {
        return "test";
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
     * @param \CommonDBTM $item
     *
     * @return int
     */
    public static function countFailForItem(CommonDBTM $item)
    {
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable(self::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releases_id" => $item->getID(),
                "state"                       => self::FAIL],
        );
    }

    /**
     * Prepare input datas for adding the item
     *
     * @param array $input datas used to add the item
     *
     * @return array the modified $input array
     **/
    public function prepareInputForAdd($input)
    {

        $input = parent::prepareInputForAdd($input);

        $input["users_id"] = Session::getLoginUserID();
        $input["plugin_releases_releases_id"] = $input["items_id"];

        $release           = new Release();
        $release->getFromDB($input["items_id"]);
        $input["entities_id"] = $release->fields["entities_id"] ?? 0;

        return $input;
    }

    /**
     *
     */
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
        // update last editor if content change
        if (isset($input['update'])
            && ($uid = Session::getLoginUserID())) { // Change from task form
            $input["users_id_editor"] = $uid;
        }
        $this->fields['date_mod'] = $_SESSION["glpi_currenttime"];
        $input['date_mod']        = $_SESSION["glpi_currenttime"];
        $input['users_id_editor'] = Session::getLoginUserID();
        $input                    = parent::prepareInputForUpdate($input);
        return $input;
    }

    public function post_updateItem($history = 1)
    {
        parent::post_updateItem($history);
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

        TemplateRenderer::getInstance()->display('@releases/form_test.html.twig', [
            'item'      => $options['parent'],
            'subitem'   => $this,
        ]);
    }

    /**
     * Dropdown of test & tests state
     *
     * @param $name   select name
     * @param $value  default value (default '')
     * @param $display  display of send string ? (true by default)
     * @param $options  options
     **/
    public static function dropdownStateTest($name, $value = '', $display = true, $options = [])
    {

        $values = [static::TODO => __('To do'),
            static::DONE => __('Done'),
            static::FAIL => __('Failed', 'releases')];

        return Dropdown::showFromArray($name, $values, array_merge(['value'   => $value,
            'display' => $display], $options));
    }

    /**
     * Get test state name
     *
     * @param $value status ID
     **/
    public static function getState($value)
    {

        switch ($value) {
            case static::FAIL:
                return __('Failed', 'releases');

            case static::TODO:
                return __('To do');

            case static::DONE:
                return __('Done');
        }
    }
}
