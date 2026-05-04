<?php

/*
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.
 -------------------------------------------------------------------------
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
