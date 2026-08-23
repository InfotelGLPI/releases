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
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Finalization
 */
class Finalization extends CommonDBTM
{
    public $dohistory = true;
    public static $rightname = 'plugin_releases_releases';
    public const TODO = 1; // todo
    public const DONE = 2; // done
    public const FAIL = 3; // Failed

    public static function getIcon()
    {
        return "ti ti-check";
    }
    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {

        return __('Finalization', 'releases');
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case Release::getType():
                $self = new self();
                $self->showForm($item->getID());
                break;
        }
        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (static::canView()) {
            switch ($item->getType()) {
                case Release::getType():
                    return self::createTabEntry(self::getTypeName(2));
            }
        }
        return '';
    }

    /**
     * @param $state
     *
     * @return string
     */
    public static function getStateItem($state)
    {
        switch ($state) {
            case self::TODO:
                return "<span><i class=\"ti ti-hourglass\" style=\"font-size:3em;\"></i></span>";
            case self::DONE:
                return "<span><i class=\"ti ti-check\" style=\"font-size:3em;\"></i></span>";
            case self::FAIL:
                return "<span><i class=\"ti ti-x\" style=\"font-size:3em;\"></i></span>";
        }
        return '';
    }

    public function showForm($ID, $options = [])
    {
        global $CFG_GLPI;

        if (!Session::haveRight(self::$rightname, READ)) {
            return;
        }

        $release = new Release();
        if (!$release->getFromDB($ID)) {
            return;
        }

        $risk_state = (Risk::countForItem($release) == Risk::countDoneForItem($release))
            ? Risk::DONE : Risk::TODO;

        $rollback_state = (Rollback::countForItem($release) == Rollback::countDoneForItem($release))
            ? Rollback::DONE : Rollback::TODO;

        $deployTaskDone  = Release::countForItem($ID, Deploytask::class, Deploytask::DONE);
        $deployTaskTotal = Release::countForItem($ID, Deploytask::class);
        $deployTaskFail  = Deploytask::countFailForItem($release);
        $taskfailed      = "";
        $task_state      = Deploytask::TODO;
        if ($deployTaskFail != 0) {
            $taskfailed = "bulleFailed";
            $task_state = Deploytask::FAIL;
        }
        $pourcentageTask = 0;
        if ($deployTaskTotal != 0) {
            $pourcentageTask = $deployTaskDone / $deployTaskTotal * 100;
        }
        if ($deployTaskDone == $deployTaskTotal) {
            $pourcentageTask = 100;
            $task_state      = Deploytask::DONE;
        }

        $test_state = Test::TODO;
        $testDone   = Release::countForItem($ID, Test::class, Test::DONE);
        $testTotal  = Release::countForItem($ID, Test::class);
        $testFail   = Test::countFailForItem($release);
        $testfailed = "";
        if ($testFail != 0) {
            $testfailed = "bulleFailed";
            $test_state = Test::FAIL;
        }
        $pourcentageTest = 0;
        if ($testTotal != 0) {
            $pourcentageTest = $testDone / $testTotal * 100;
        }
        if ($testDone == $testTotal) {
            $pourcentageTest = 100;
            $test_state      = Test::DONE;
        }

        $riskDone      = Release::countForItem($ID, Risk::class, Risk::DONE);
        $riskTotal     = Release::countForItem($ID, Risk::class);
        $rollbackDone  = Release::countForItem($ID, Rollback::class, Rollback::DONE);
        $rollbackTotal = Release::countForItem($ID, Rollback::class);

        $dateEnd = (!empty($release->fields["date_end"]))
            ? Html::convDateTime($release->fields["date_end"])
            : __("Not yet completed", 'releases');

        $can_finalize = (empty($release->fields["date_end"])
                || $release->fields["status"] < Release::REVIEW)
            && $this->canUpdate();
        $is_failed = ($deployTaskFail != 0 || $testFail != 0);

        $confirm_url = '';
        if ($can_finalize) {
            $confirm_url = $CFG_GLPI['root_doc'] . "/plugins/releases/front/finalization.php?release_id="
                . $release->fields['id'] . ($is_failed ? "&failed=1" : "&confirm=1");
        }

        TemplateRenderer::getInstance()->display('@releases/form_finalization.html.twig', [
            'creation_date'     => Html::convDateTime($release->fields["date_creation"]),
            'risk_icon'         => self::getStateItem($risk_state),
            'risk_done'         => $riskDone,
            'risk_total'        => $riskTotal,
            'rollback_icon'     => self::getStateItem($rollback_state),
            'rollback_done'     => $rollbackDone,
            'rollback_total'    => $rollbackTotal,
            'task_icon'         => self::getStateItem($task_state),
            'task_failed_class' => $taskfailed,
            'deploy_task_done'  => $deployTaskDone,
            'deploy_task_total' => $deployTaskTotal,
            'deploy_task_fail'  => $deployTaskFail,
            'pourcentage_task'  => Html::formatNumber($pourcentageTask),
            'test_icon'         => self::getStateItem($test_state),
            'test_failed_class' => $testfailed,
            'test_done'         => $testDone,
            'test_total'        => $testTotal,
            'test_fail'         => $testFail,
            'pourcentage_test'  => Html::formatNumber($pourcentageTest),
            'date_end'          => $dateEnd,
            'can_finalize'      => $can_finalize,
            'is_failed'         => $is_failed,
            'confirm_url'       => $confirm_url,
        ]);
    }

    public static function showFinalizeForm($params)
    {

        global $CFG_GLPI;
        $release = new Release();
        $ID      = $params["release_id"];
        $release->getFromDB($ID);
        $deployTaskDone  = Release::countForItem($ID, Deploytask::class, Deploytask::DONE);
        $deployTaskTotal = Release::countForItem($ID, Deploytask::class);
        $testDone        = Release::countForItem($ID, Test::class, Test::DONE);
        $testTotal       = Release::countForItem($ID, Test::class);
        $testFail        = Test::countFailForItem($release);
        $deployTaskFail  = Deploytask::countFailForItem($release);

        $allfinish = (Risk::countForItem($release) == Risk::countDoneForItem($release))
                   && ($deployTaskTotal == $deployTaskDone)
                   && ($testTotal == $testDone)
                   && (Rollback::countForItem($release) == Rollback::countDoneForItem($release));

        TemplateRenderer::getInstance()->display('@releases/form_finalization_confirm.html.twig', [
            'allfinish'    => $allfinish,
            'action_url'   => $CFG_GLPI['root_doc'] . "/plugins/releases/front/finalization.php",
            'id'           => $ID,
            'is_failed'    => isset($params["failed"]),
            'failed_tasks' => $deployTaskFail,
            'failed_tests' => $testFail,
        ]);
    }
}
