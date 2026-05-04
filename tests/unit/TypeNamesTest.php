<?php

/*
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.
 -------------------------------------------------------------------------
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
