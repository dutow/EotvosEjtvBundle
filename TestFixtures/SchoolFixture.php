<?php

namespace Eotvos\EjtvBundle\TestFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Eotvos\EjtvBundle\Entity\School;

/**
 * Loads sample schools for tests
 * 
 * @uses FixtureInterface
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu>
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class SchoolFixture implements FixtureInterface
{
    /**
     * load
     * 
     * @param ObjectManager $om database object manager
     * 
     * @return void
     */
    public function load(ObjectManager $om)
    {
        $school = new School();
        $school->setName('Zrinyi Miklos');
        $school->setOmid('OM1111');
        $school->setTelephone('+36202020201');
        $school->setPostalcode('8900');
        $school->setCity('Zalaegerszeg');
        $school->setAddress('X Y 6');
        $school->setEducationType('gimnazium');
        $school->setActive(true);
        $om->persist($school);

        $school = new School();
        $school->setName('Zrinyi Miki');
        $school->setOmid('OM1111');
        $school->setTelephone('+36202020201');
        $school->setPostalcode('8900');
        $school->setCity('Zalaegerszeg');
        $school->setAddress('X Y 6');
        $school->setEducationType('gimnazium');
        $school->setActive(false);
        $om->persist($school);

        $school = new School();
        $school->setName('Zrinyi Ilona');
        $school->setOmid('OM1111');
        $school->setTelephone('+36202020201');
        $school->setPostalcode('1111');
        $school->setCity('Budapest');
        $school->setAddress('X Y 6');
        $school->setEducationType('gimnazium,szakkozep');
        $school->setActive(true);
        $om->persist($school);

        $om->flush();
    }
}

