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
 the Free Software Foundation; either version 3 of the License, or
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
