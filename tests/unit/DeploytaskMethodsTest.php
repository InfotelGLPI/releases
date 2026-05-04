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
use PHPUnit\Framework\TestCase;

class DeploytaskMethodsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // getItilObjectItemType()
    // -------------------------------------------------------------------------

    public function testGetItilObjectItemTypeReturnsReleaseFqcn(): void
    {
        $task = new Deploytask();
        $this->assertSame(Release::class, $task->getItilObjectItemType());
    }

    public function testGetItilObjectItemTypeContainsRelease(): void
    {
        $task = new Deploytask();
        $this->assertStringContainsString('Release', $task->getItilObjectItemType());
    }

    public function testGetItilObjectItemTypeDoesNotContainDeploytask(): void
    {
        $task = new Deploytask();
        $this->assertStringNotContainsString('Deploytask', $task->getItilObjectItemType());
    }

    // -------------------------------------------------------------------------
    // getCssClass()
    // -------------------------------------------------------------------------

    public function testGetCssClassReturnsTask(): void
    {
        $this->assertSame('task', Deploytask::getCssClass());
    }

    // -------------------------------------------------------------------------
    // getNameField()
    // -------------------------------------------------------------------------

    public function testGetNameFieldReturnsName(): void
    {
        $this->assertSame('name', Deploytask::getNameField());
    }

    // -------------------------------------------------------------------------
    // Constantes d'état
    // -------------------------------------------------------------------------

    public function testTodoIsOne(): void
    {
        $this->assertSame(1, Deploytask::TODO);
    }

    public function testDoneIsTwo(): void
    {
        $this->assertSame(2, Deploytask::DONE);
    }

    public function testFailIsThree(): void
    {
        $this->assertSame(3, Deploytask::FAIL);
    }
}
