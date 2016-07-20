<?php

namespace PhpSchool\WorkshopManagerTest\Repository;

use Composer\Json\JsonFile;
use PhpSchool\WorkshopManager\Entity\InstalledWorkshop;
use PhpSchool\WorkshopManager\Entity\Workshop;
use PhpSchool\WorkshopManager\Exception\WorkshopNotFoundException;
use PhpSchool\WorkshopManager\Repository\InstalledWorkshopRepository;
use PhpSchool\WorkshopManager\Repository\WorkshopRepository;
use PHPUnit_Framework_TestCase;

/**
 * Class WorkshopRepositoryTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class InstalledWorkshopRepositoryTest extends PHPUnit_Framework_TestCase
{
    public function testGetByNameThrowsExceptionIfWorkshopNotExist()
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->will(
                $this->returnValue(
                    [
                        'workshops' => []
                    ]
                )
            );

        $repo = new InstalledWorkshopRepository($json);
        $this->expectException(WorkshopNotFoundException::class);
        $repo->getByName('nope');
    }

    public function testGetByName()
    {
        $repo = $this->getRepo();
        $workshop = $repo->getByName('workshop');
        $this->assertInstanceOf(InstalledWorkshop::class, $workshop);
        $this->assertEquals('workshop', $workshop->getName());
        $this->assertEquals('workshop', $workshop->getDisplayName());
        $this->assertEquals('aydin', $workshop->getOwner());
        $this->assertEquals('repo', $workshop->getRepo());
        $this->assertEquals('workshop', $workshop->getDescription());
        $this->assertEquals('1.0.0', $workshop->getVersion());
    }

    public function testHasWorkshop()
    {
        $repo = $this->getRepo();
        $this->assertTrue($repo->hasWorkshop('workshop'));


        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->will(
                $this->returnValue(
                    [
                        'workshops' => []
                    ]
                )
            );

        $repo = new InstalledWorkshopRepository($json);
        $this->assertFalse($repo->hasWorkshop('workshop'));
    }

    public function testGetAllWorkshops()
    {
        $repo = $this->getRepo();
        $this->assertCount(1, $repo->getAll());
    }

    public function testIsEmpty()
    {
        $repo = $this->getRepo();
        $this->assertFalse($repo->isEmpty());

        $repo->remove($repo->getByName('workshop'));
        $this->assertTrue($repo->isEmpty());
    }

    public function testExceptionIsThrowIfTryingToRemoveNonExistingWorkshop()
    {
        $repo = $this->getRepo();
        $this->expectException(WorkshopNotFoundException::class);

        $workshop = new InstalledWorkshop('remove-me', 'workshop', 'aydin', 'repo', 'workshop', '1.0.0');
        $repo->remove($workshop);
    }

    public function testRemove()
    {
        $repo = $this->getRepo();
        $this->assertCount(1, $repo->getAll());

        $workshop = $repo->getByName('workshop');
        $repo->remove($workshop);
        $this->assertCount(0, $repo->getAll());
    }

    public function testAdd()
    {
        $repo = $this->getRepo([]);
        $workshop = new InstalledWorkshop('workshop', 'workshop', 'aydin', 'repo', 'workshop', '1.0.0');

        $this->assertCount(0, $repo->getAll());
        $repo->add($workshop);
        $this->assertCount(1, $repo->getAll());
    }

    public function testSave()
    {
        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->will($this->returnValue(['workshops' => []]));

        $repo = new InstalledWorkshopRepository($json);
        $repo->add(new InstalledWorkshop('workshop', 'workshop', 'aydin', 'repo', 'workshop', '1.0.0'));

        $data = [
            'workshops' => [
                [
                    'name' => 'workshop',
                    'display_name' => 'workshop',
                    'owner' => 'aydin',
                    'repo' => 'repo',
                    'description' => 'workshop',
                    'version' => '1.0.0'
                ]
            ]
        ];

        $json
            ->expects($this->once())
            ->method('write')
            ->with($data);

        $repo->save();
    }

    private function getRepo(array $workshops = null)
    {
        if (null === $workshops) {
            $workshops = [
                [
                    'name' => 'workshop',
                    'display_name' => 'workshop',
                    'owner' => 'aydin',
                    'repo' => 'repo',
                    'description' => 'workshop',
                    'version' => '1.0.0'
                ]
            ];
        }

        $data = [
            'workshops' => $workshops
        ];


        $json = $this->createMock(JsonFile::class);
        $json
            ->expects($this->once())
            ->method('read')
            ->will($this->returnValue($data));

        return new InstalledWorkshopRepository($json);
    }
}