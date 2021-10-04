<?php

namespace emteknetnz\Octopus;

use SilverStripe\ORM\DataObject;

class MyDataObject extends DataObject
{
    private static $table_name = 'MyDataObject';

    private static $db = [
        'Title' => 'Varchar'
    ];  // intentional space to fail phpcs 

}
