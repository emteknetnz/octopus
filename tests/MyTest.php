<?php

namespace emteknetnz\Octopus;

use SilverStripe\Dev\SapphireTest;
use emteknetnz\Octopus\MyDataObject;

class MyTest extends SapphireTest
{
    public function testItWorks()
    {
        $obj = MyDataObject::create(['Title' => 'MyTitle'])->write();
        assertSame('MyTitle', MyDataObject::get()->first()->Title);
    }
}
