<?php

namespace Eotvos\EjtvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Postalcode-city database information.
 *
 * In Hungary, each city has a four digit postal code, but it isn't unique, two cities may have the same.
 * This can be extended with another character (typically one from the alphabet, starting from 'A') to make it a
 * unique index.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Eotvos\EjtvBundle\Entity\PostalcodeRepository")
 * 
 * @author    Zsolt Parragi <zsolt.parragi@cancellar.hu> 
 * @copyright 2012 Cancellar
 * @license   MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version   Release: v0.1
 */
class Postalcode
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $code
     *
     * @ORM\Column(name="code", type="string", length=5,unique=true)
     */
    private $code;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var boolean $chieftown
     *
     * @ORM\Column(name="chieftown", type="boolean")
     */
    private $chieftown;

    /**
     * @var integer $countyId
     *
     * @ORM\Column(name="county_id", type="integer")
     */
    private $countyId;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code postsal code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name
     *
     * @param string $name name of the city
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set chieftown
     *
     * @param boolean $chieftown true if it is the chief town of the region
     */
    public function setChieftown($chieftown)
    {
        $this->chieftown = $chieftown;
    }

    /**
     * Get chieftown
     *
     * @return boolean 
     */
    public function getChieftown()
    {
        return $this->chieftown;
    }

    /**
     * Set countyÃid
     *
     * @param integer $countyId id of the region
     */
    public function setCountyId($countyId)
    {
        $this->countyId = $countyId;
    }

    /**
     * Get countyId
     *
     * @return integer 
     */
    public function getCountyId()
    {
        return $this->countyId;
    }
}
