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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Risk
 */
class Risk extends CommonDBTM
{
    public static $rightname = 'plugin_releases_risks';
    public const TODO = 1; // todo
    public const DONE = 2; // done

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Risk', 'Risks', $nb, 'releases');
    }

    /**
     *
     * @return css class
     */
    public static function getCssClass()
    {
        return "risk";
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

    public function post_addItem()
    {
        parent::post_addItem();

        if (isset($this->input["create_test"])
            && $this->input["create_test"] == 1) {
            $test                                     = new Test();
            $inputTest                                = [];
            $inputTest["entities_id"]                 = $this->fields["entities_id"];
            $inputTest["plugin_releases_releases_id"] = $this->fields["plugin_releases_releases_id"];
            $inputTest["plugin_releases_risks_id"]    = $this->fields["id"];
            $inputTest["name"]                        = $this->fields["name"];
            $inputTest["users_id"]                    = $this->fields["users_id"];
            $inputTest["content"]                     = $this->fields["content"];
            $inputTest["items_id"]                    = $this->fields["plugin_releases_releases_id"];
            $test->add($inputTest);
        }

    }

    public function prepareInputForUpdate($input)
    {
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

        parent::post_updateItem($history);
    }

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

        TemplateRenderer::getInstance()->display('@releases/form_risk.html.twig', [
            'item'      => $options['parent'],
            'subitem'   => $this,
        ]);

    }
}
