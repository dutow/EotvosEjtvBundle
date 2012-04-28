<?php

namespace Eotvos\EjtvBundle\TestFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Eotvos\EjtvBundle\Entity\Postalcode;

/**
 * Loads sample postal codes for tests
 * 
 * @uses FixtureInterface
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu>
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class PostalcodeFixture implements FixtureInterface
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
        $pc1 = new Postalcode();
        $pc1->setCode('8900A');
        $pc1->setCountyId(9);
        $pc1->setChieftown(true);
        $pc1->setName('Zalaegerszeg');
        $om->persist($pc1);

        $pc1 = new Postalcode();
        $pc1->setCode('8901A');
        $pc1->setCountyId(9);
        $pc1->setChieftown(true);
        $pc1->setName('Zalaegerszeg');
        $om->persist($pc1);

        $pc1 = new Postalcode();
        $pc1->setCode('8901B');
        $pc1->setCountyId(9);
        $pc1->setChieftown(true);
        $pc1->setName('Somethingelse');
        $om->persist($pc1);

        $om->flush();
    }

}

