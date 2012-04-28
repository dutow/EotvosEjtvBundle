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
 *
 * @todo create a county database
 */
class Postalcode
{
    /**
     * @var integer $id integer id of the record
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $code postalcode
     *
     * @ORM\Column(name="code", type="string", length=5,unique=true)
     */
    private $code;

    /**
     * @var string $name name of the associated city
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var boolean $chieftown is this the chieftown of the county?
     *
     * @ORM\Column(name="chieftown", type="boolean")
     */
    private $chieftown;

    /**
     * @var integer $countyId id of the county which it belongs to
     *
     * @ORM\Column(name="county_id", type="integer")
     */
    private $countyId;


    /**
     * Returns the id of the record
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the postal code
     *
     * @param string $code postsal code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Gets the five character postal code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Gets the classically displayed four character postal code
     *
     * @return string 
     */
    public function getCode4()
    {
        return substr($this->code, 0, 4);
    }

    /**
     * Sets the city name
     *
     * @param string $name name of the city
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the city name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets if this entry is the chieftown of a region
     *
     * @param boolean $chieftown true if it is the chief town of the region
     */
    public function setChieftown($chieftown)
    {
        $this->chieftown = $chieftown;
    }

    /**
     * Tells if this is a chieftown
     *
     * @return boolean 
     */
    public function getChieftown()
    {
        return $this->chieftown;
    }

    /**
     * Set countyId
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

