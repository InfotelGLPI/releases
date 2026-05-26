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

use GlpiPlugin\Releases\Release;
use GlpiPlugin\Releases\ReleaseTemplate;
use PHPUnit\Framework\TestCase;

class ReleaseMethodsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Ensemble complet des constantes de statut
    // -------------------------------------------------------------------------

    public function testAllStatusConstantsAreIntegers(): void
    {
        $this->assertIsInt(Release::NEWRELEASE);
        $this->assertIsInt(Release::RELEASEDEFINITION);
        $this->assertIsInt(Release::DATEDEFINITION);
        $this->assertIsInt(Release::CHANGEDEFINITION);
        $this->assertIsInt(Release::RISKDEFINITION);
        $this->assertIsInt(Release::ROLLBACKDEFINITION);
        $this->assertIsInt(Release::TASKDEFINITION);
        $this->assertIsInt(Release::TESTDEFINITION);
        $this->assertIsInt(Release::FINALIZE);
        $this->assertIsInt(Release::REVIEW);
        $this->assertIsInt(Release::CLOSED);
        $this->assertIsInt(Release::FAIL);
    }

    public function testAllStatusConstantsAreDistinct(): void
    {
        $constants = [
            Release::NEWRELEASE,
            Release::RELEASEDEFINITION,
            Release::DATEDEFINITION,
            Release::CHANGEDEFINITION,
            Release::RISKDEFINITION,
            Release::ROLLBACKDEFINITION,
            Release::TASKDEFINITION,
            Release::TESTDEFINITION,
            Release::FINALIZE,
            Release::REVIEW,
            Release::CLOSED,
            Release::FAIL,
        ];

        $this->assertCount(12, array_unique($constants));
    }

    public function testStatusConstantsFollowAscendingOrder(): void
    {
        $this->assertLessThan(Release::RELEASEDEFINITION,  Release::NEWRELEASE);
        $this->assertLessThan(Release::DATEDEFINITION,     Release::RELEASEDEFINITION);
        $this->assertLessThan(Release::CHANGEDEFINITION,   Release::DATEDEFINITION);
        $this->assertLessThan(Release::RISKDEFINITION,     Release::CHANGEDEFINITION);
        $this->assertLessThan(Release::ROLLBACKDEFINITION, Release::RISKDEFINITION);
        $this->assertLessThan(Release::TASKDEFINITION,     Release::ROLLBACKDEFINITION);
        $this->assertLessThan(Release::TESTDEFINITION,     Release::TASKDEFINITION);
        $this->assertLessThan(Release::FINALIZE,           Release::TESTDEFINITION);
        $this->assertLessThan(Release::REVIEW,             Release::FINALIZE);
        $this->assertLessThan(Release::CLOSED,             Release::REVIEW);
        $this->assertLessThan(Release::FAIL,               Release::CLOSED);
    }

    public function testNewReleaseValueIsOne(): void
    {
        $this->assertSame(1, Release::NEWRELEASE);
    }

    public function testFailValueIsTwelve(): void
    {
        $this->assertSame(12, Release::FAIL);
    }

    // -------------------------------------------------------------------------
    // Méthodes statiques pures
    // -------------------------------------------------------------------------

    public function testGetTemplateClassReturnsReleaseTemplateFqcn(): void
    {
        $this->assertSame(ReleaseTemplate::class, Release::getTemplateClass());
    }

    public function testGetTemplateFieldNameReturnsReleasetemplatesId(): void
    {
        $release = new Release();
        $this->assertSame('releasetemplates_id', $release->getTemplateFieldName());
    }
}
