<?php

/*
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.
 -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Releases\Tests\Unit;

use GlpiPlugin\Releases\Risk;
use GlpiPlugin\Releases\Rollback;
use GlpiPlugin\Releases\Test as ReleaseTest;
use PHPUnit\Framework\TestCase;

class ItemCssClassTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Risk
    // -------------------------------------------------------------------------

    public function testRiskGetCssClassReturnsRisk(): void
    {
        $this->assertSame('risk', Risk::getCssClass());
    }

    public function testRiskTodoIsOne(): void
    {
        $this->assertSame(1, Risk::TODO);
    }

    public function testRiskDoneIsTwo(): void
    {
        $this->assertSame(2, Risk::DONE);
    }

    public function testRiskStateConstantsAreDistinct(): void
    {
        $this->assertNotSame(Risk::TODO, Risk::DONE);
    }

    // -------------------------------------------------------------------------
    // Test (plan de test)
    // -------------------------------------------------------------------------

    public function testReleaseTestGetCssClassReturnsTest(): void
    {
        $this->assertSame('test', ReleaseTest::getCssClass());
    }

    public function testReleaseTestTodoIsOne(): void
    {
        $this->assertSame(1, ReleaseTest::TODO);
    }

    public function testReleaseTestDoneIsTwo(): void
    {
        $this->assertSame(2, ReleaseTest::DONE);
    }

    public function testReleaseTestFailIsThree(): void
    {
        $this->assertSame(3, ReleaseTest::FAIL);
    }

    public function testReleaseTestStateConstantsAreDistinct(): void
    {
        $this->assertNotSame(ReleaseTest::TODO, ReleaseTest::DONE);
        $this->assertNotSame(ReleaseTest::DONE, ReleaseTest::FAIL);
        $this->assertNotSame(ReleaseTest::TODO, ReleaseTest::FAIL);
    }

    // -------------------------------------------------------------------------
    // Rollback
    // -------------------------------------------------------------------------

    public function testRollbackTodoIsOne(): void
    {
        $this->assertSame(1, Rollback::TODO);
    }

    public function testRollbackDoneIsTwo(): void
    {
        $this->assertSame(2, Rollback::DONE);
    }

    public function testRollbackStateConstantsAreDistinct(): void
    {
        $this->assertNotSame(Rollback::TODO, Rollback::DONE);
    }
}
