<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class BasicFunctionalityTest extends TestCase
{
    private $assistantModuleID = '{BB6EF5EE-1437-4C80-A16D-DA0A6C885210}';

    public function setUp()
    {
        //Reset
        IPS\Kernel::reset();

        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');

        parent::setUp();
    }

    public function testCreate()
    {
        $previousCount = count(IPS_GetInstanceListByModuleID($this->assistantModuleID));
        IPS_CreateInstance($this->assistantModuleID);
        $this->assertEquals($previousCount + 1, count(IPS_GetInstanceListByModuleID($this->assistantModuleID)));
    }
}
