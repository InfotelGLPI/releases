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

use GlpiPlugin\Releases\TypeDeployTask;
use GlpiPlugin\Releases\TypeRisk;
use GlpiPlugin\Releases\TypeTest;
use PHPUnit\Framework\TestCase;

class TypeNamesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // TypeRisk
    // -------------------------------------------------------------------------

    public function testTypeRiskGetTypeNameSingularReturnsRiskType(): void
    {
        $this->assertSame('Risk type', TypeRisk::getTypeName(1));
    }

    public function testTypeRiskGetTypeNamePluralReturnsRiskTypes(): void
    {
        $this->assertSame('Risk types', TypeRisk::getTypeName(2));
    }

    public function testTypeRiskRightnameIsNotEmpty(): void
    {
        $this->assertNotEmpty(TypeRisk::$rightname);
    }

    // -------------------------------------------------------------------------
    // TypeDeployTask
    // -------------------------------------------------------------------------

    public function testTypeDeployTaskGetTypeNameSingularReturnsDeployTaskType(): void
    {
        $this->assertSame('Deploy task type', TypeDeployTask::getTypeName(1));
    }

    public function testTypeDeployTaskGetTypeNamePluralReturnsDeployTaskTypes(): void
    {
        $this->assertSame('Deploy task types', TypeDeployTask::getTypeName(2));
    }

    public function testTypeDeployTaskRightnameIsNotEmpty(): void
    {
        $this->assertNotEmpty(TypeDeployTask::$rightname);
    }

    // -------------------------------------------------------------------------
    // TypeTest
    // -------------------------------------------------------------------------

    public function testTypeTestGetTypeNameSingularReturnsTestType(): void
    {
        $this->assertSame('Test type', TypeTest::getTypeName(1));
    }

    public function testTypeTestGetTypeNamePluralReturnsTestTypes(): void
    {
        $this->assertSame('Test types', TypeTest::getTypeName(2));
    }

    public function testTypeTestRightnameIsNotEmpty(): void
    {
        $this->assertNotEmpty(TypeTest::$rightname);
    }

    // -------------------------------------------------------------------------
    // Les trois types ont des rightnames différents
    // -------------------------------------------------------------------------

    public function testTypeRightnamesAreDistinct(): void
    {
        $this->assertNotSame(TypeRisk::$rightname, TypeDeployTask::$rightname);
        $this->assertNotSame(TypeDeployTask::$rightname, TypeTest::$rightname);
        $this->assertNotSame(TypeRisk::$rightname, TypeTest::$rightname);
    }
}
