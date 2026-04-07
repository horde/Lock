<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Lock\Test\Unit;

use Horde_Lock;
use Horde_Lock_Null;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Lock::class)]
class LockConstantsTest extends TestCase
{
    public function testTypeExclusiveConstant(): void
    {
        $this->assertSame(1, Horde_Lock::TYPE_EXCLUSIVE);
    }

    public function testTypeSharedConstant(): void
    {
        $this->assertSame(2, Horde_Lock::TYPE_SHARED);
    }

    public function testPermanentConstant(): void
    {
        $this->assertSame(-1, Horde_Lock::PERMANENT);
    }

    public function testConstructorDefaultParams(): void
    {
        // Use Null driver as concrete implementation of abstract Horde_Lock
        $driver = new Horde_Lock_Null();

        $this->assertInstanceOf(Horde_Lock::class, $driver);
    }

    public function testConstructorAcceptsLogger(): void
    {
        $logger = $this->createStub(\Horde_Log_Logger::class);
        $driver = new Horde_Lock_Null(['logger' => $logger]);

        // Logger is consumed and stored — driver still works
        $this->assertInstanceOf(Horde_Lock::class, $driver);
        $this->assertTrue($driver->resetLock('test', 100));
    }
}
