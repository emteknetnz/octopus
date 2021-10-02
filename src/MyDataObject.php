<?php

namespace emteknetnz\Octopus;

use SilverStripe\ORM\DataObject;

class MyDataObject extends DataObject
{
    private static $db = [
        'Title' => 'Varchar'
    ];
}
