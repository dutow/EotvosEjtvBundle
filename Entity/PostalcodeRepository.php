<?php

namespace Eotvos\EjtvBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Custom repository functions for Repository objects.
 * 
 * @uses EntityRepository
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class PostalcodeRepository extends EntityRepository
{
    /**
     * Gets postal codes starting with $prefix.
     * 
     * @param mixed $prefix starting numbers/characters of the postal code.
     * 
     * @return array code and name
     */
    public function getWithPrefix($prefix)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT pc.code, pc.name FROM Eotvos\EjtvBundle\Entity\Postalcode pc WHERE pc.code LIKE :prefix')
            ->setParameter('prefix', $prefix.'%')
            ->getResult()
            ;
    }

    /**
     * Finds a postal code record with the given code. If multiple found, returns one.
     * 
     * @param mixed $code postal code
     * 
     * @return Postalcode first matching postal code
     *
     * @todo error in case of multiple records?
     */
    public function findOneByCode($code)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT pc FROM Eotvos\EjtvBundle\Entity\Postalcode pc WHERE pc.code LIKE :prefix')
            ->setParameter('prefix', $code.'%')
            ->getSingleResult()
            ;
    }

}
