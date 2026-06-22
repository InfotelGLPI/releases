<?php

/*
 -------------------------------------------------------------------------
 releases plugin for GLPI
 Copyright (C) 2020-2026 by the releases Development Team.

 https://github.com/InfotelGLPI/releases
 -------------------------------------------------------------------------

 LICENSE

 This file is part of releases.

 releases is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 releases is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with releases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Releases\Tests\Unit;

use GlpiPlugin\Releases\Deploytask;
use GlpiPlugin\Releases\Release;
use GlpiPlugin\Releases\Risk;
use GlpiPlugin\Releases\ReleaseTemplate;
use GlpiPlugin\Releases\Rollback;
use GlpiPlugin\Releases\Test as ReleaseTest;
use PHPUnit\Framework\TestCase;

class ReleaseStatusTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constantes de statut Release
    // -------------------------------------------------------------------------

    public function testReleaseStatusConstantsAreDistinctIntegers(): void
    {
        $this->assertIsInt(Release::NEWRELEASE);
        $this->assertIsInt(Release::RELEASEDEFINITION);
        $this->assertIsInt(Release::DATEDEFINITION);

        $this->assertNotSame(Release::NEWRELEASE, Release::RELEASEDEFINITION);
        $this->assertNotSame(Release::RELEASEDEFINITION, Release::DATEDEFINITION);
        $this->assertNotSame(Release::NEWRELEASE, Release::DATEDEFINITION);
    }

    public function testReleaseStatusOrder(): void
    {
        $this->assertLessThan(Release::RELEASEDEFINITION, Release::NEWRELEASE);
        $this->assertLessThan(Release::DATEDEFINITION, Release::RELEASEDEFINITION);
    }

    // -------------------------------------------------------------------------
    // Méthodes statiques (pas d'accès DB)
    // -------------------------------------------------------------------------

    public function testGetTemplateClassReturnsFqcn(): void
    {
        $this->assertSame(ReleaseTemplate::class, Release::getTemplateClass());
    }

    /**
     * The terminal-status guard added to the timeline/changeitemstate AJAX
     * controllers relies on getClosedStatusArray() listing both CLOSED and FAIL.
     * If this contract changes, those guards must be revisited.
     */
    public function testClosedStatusArrayContainsTerminalStatuses(): void
    {
        $closed = Release::getClosedStatusArray();

        $this->assertContains(Release::CLOSED, $closed);
        $this->assertContains(Release::FAIL, $closed);
    }

    // -------------------------------------------------------------------------
    // Constantes Risk
    // -------------------------------------------------------------------------

    public function testRiskStateConstants(): void
    {
        $this->assertIsInt(Risk::TODO);
        $this->assertIsInt(Risk::DONE);
        $this->assertNotSame(Risk::TODO, Risk::DONE);
    }

    // -------------------------------------------------------------------------
    // Constantes Deploytask
    // -------------------------------------------------------------------------

    public function testDeploytaskStateConstants(): void
    {
        $this->assertIsInt(Deploytask::TODO);
        $this->assertIsInt(Deploytask::DONE);
        $this->assertIsInt(Deploytask::FAIL);
        $this->assertNotSame(Deploytask::TODO, Deploytask::DONE);
        $this->assertNotSame(Deploytask::DONE, Deploytask::FAIL);
    }

    // -------------------------------------------------------------------------
    // Constantes Test de release
    // -------------------------------------------------------------------------

    public function testReleaseTestStateConstants(): void
    {
        $this->assertIsInt(ReleaseTest::TODO);
        $this->assertIsInt(ReleaseTest::DONE);
        $this->assertIsInt(ReleaseTest::FAIL);
        $this->assertNotSame(ReleaseTest::TODO, ReleaseTest::DONE);
        $this->assertNotSame(ReleaseTest::DONE, ReleaseTest::FAIL);
    }

    // -------------------------------------------------------------------------
    // Constantes Rollback
    // -------------------------------------------------------------------------

    public function testRollbackStateConstants(): void
    {
        $this->assertIsInt(Rollback::TODO);
        $this->assertIsInt(Rollback::DONE);
        $this->assertNotSame(Rollback::TODO, Rollback::DONE);
    }
}
