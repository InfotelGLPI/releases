<?php

/*
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.
 -------------------------------------------------------------------------
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
