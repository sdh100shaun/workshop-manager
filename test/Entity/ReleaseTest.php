<?php

namespace PhpSchool\WorkshopManagerTest\Entity;

use PhpSchool\WorkshopManager\Entity\Release;
use PHPUnit_Framework_TestCase;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ReleaseTest extends PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $release = new Release('1.0.0', 'AAAA');
        $this->assertEquals('1.0.0', $release->getTag());
        $this->assertEquals('AAAA', $release->getSha());
    }
}
