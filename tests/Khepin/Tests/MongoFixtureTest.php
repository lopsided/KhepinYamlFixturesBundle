<?php

namespace Khepin\Tests;

use \Mockery as m;
use Khepin\YamlFixturesBundle\Loader\YamlLoader;
use Khepin\Utils\BaseTestCaseMongo;

class MongoFixtureTest extends BaseTestCaseMongo {

    protected $kernel = null;

    public function setUp() {
        $this->getDoctrine();
        $container = m::mock('Container', array(
                'get' => $this->doctrine,
                )
        );
        $this->kernel = m::mock(
                'AppKernel', array(
                        'locateResource' => __DIR__ . '/mongo/',
                        'getContainer'   => $container
                )
        );
    }

    public function tearDown(){
        $loader = new YamlLoader($this->kernel, array('SomeBundle'));
        $loader->purgeDatabase('mongodb');
    }

    public function testSimpleLoading() {
        $loader = new YamlLoader($this->kernel, array('SomeBundle'));
        $loader->loadFixtures();

        $dm = $this->doctrine->getManager();
        $cars = $dm->getRepository('Khepin\Fixture\Document\Car')->findAll();
        $this->assertEquals(2, count($cars));
        $car = $dm->getRepository('Khepin\Fixture\Document\Car')->findOneBy(array('name' => 'Mercedes'));
        $this->assertEquals('Mercedes', $car->getName());
        $date = new \DateTime('2012-01-01');
        $this->assertEquals($date, $car->getDatePurchased());
        $this->assertEquals(get_class($date), get_class($car->getDatePurchased()));
    }

    public function testPurge(){
        $loader = new YamlLoader($this->kernel, array('SomeBundle'));
        $loader->loadFixtures();
        $loader->purgeDatabase('mongodb');
        
        $cars = $this->doctrine->getManager()->getRepository('Khepin\Fixture\Document\Car')->findAll();
        $this->assertEquals($cars->count(), 0);
        $drivers = $this->doctrine->getManager()->getRepository('Khepin\Fixture\Document\Driver')->findAll();
        $this->assertEquals($drivers->count(), 0);
    }

    public function testContext() {
        $loader = new YamlLoader($this->kernel, array('SomeBundle'));
        $loader->loadFixtures('french_cars');

        $repo = $this->doctrine->getManager()->getRepository('Khepin\Fixture\Document\Car');
        $cars = $repo->findAll();
        $this->assertEquals(5, count($cars));

        $car = $repo->findOneBy(array('name' => 'Peugeot'));
        $this->assertEquals('Peugeot', $car->getName());

        $car = $repo->findOneBy(array('name' => 'BMW'));
        $this->assertEquals('BMW', $car->getName());
    }
    
    public function testReferenceMany(){
        $loader = new YamlLoader($this->kernel, array('SomeBundle'));
        $loader->loadFixtures('with_drivers', 'family_cars');
        
        $repo = $this->doctrine->getManager()->getRepository('Khepin\Fixture\Document\Driver');
        $driver = $repo->findOneBy(array('name' => 'Mom'));
        $this->assertEquals($driver->getCars()->count(), 2);
        $cars = $driver->getCars();
        $car = $cars[0];
        $this->assertEquals('Mercedes', $car->getName());
        $driver = $repo->findOneBy(array('name' => 'Dad'));
        $this->assertEquals($driver->getCars()->count(), 3);
        
        // $this->assertEquals(get_class($driver->getCar()), 'Khepin\Fixture\Entity\Car');
    }
}