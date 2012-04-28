<?php

namespace Eotvos\EjtvBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contains information about a hungarian school.
 *
 * Normally schools are uniquelly identified by their OMID, but we need to monitor if a school's name is changed
 * during the years. Because of this, we have our own id, and omid is just a simple attribute of the entity, along
 * with it's active flag.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Eotvos\EjtvBundle\Entity\SchoolRepository")
 *
 * @todo use postalcode entity instead of strigs for postal code and city
 */
class School
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
     * @var string $omid
     *
     * @ORM\Column(name="omid", type="string", length=32, nullable=false)
     */
    private $omid;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string $postalcode
     *
     * @ORM\Column(name="postalcode", type="integer")
     */
    private $postalcode;

    /**
     * @var string $city
     *
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @var string $address
     *
     * @ORM\Column(name="address", type="string", length=255)
     */
    private $address;

    /**
     * @var string $telephone
     *
     * @ORM\Column(name="telephone", type="string", length=255, nullable=true)
     */
    private $telephone;

    /**
     * @var string $eductype
     *
     * @ORM\Column(name="eductype", type="string", length=255, nullable=true)
     */
    private $eductype;

    /**
     * @var boolean $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

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
     * Set omid
     *
     * @param string $omid unique OM identifier of the school
     */
    public function setOmid($omid)
    {
        $this->omid = $omid;
    }

    /**
     * Get omid
     *
     * @return string  unique OM identifier of the school
     */
    public function getOmid()
    {
        return $this->omid;
    }

    /**
     * Set postalcode
     *
     * @param string $postalcode postalcode of school
     */
    public function setPostalcode($postalcode)
    {
        $this->postalcode = $postalcode;
    }

    /**
     * Get postalcode
     *
     * @return string  postalcode of school
     */
    public function getPostalcode()
    {
        return $this->postalcode;
    }

    /**
     * Set city 
     *
     * @param string $city place of th school
     *
     * @todo this is redundant information, postalcode should be unique!
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set address
     *
     * @param string $address street and house number
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set telephone
     *
     * @param string $telephone telephone number
     *
     * @todo enforce format
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }

    /**
     * Get telephone
     *
     * @return string 
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set eductype
     *
     * @param string $eductype comma separated list of the education levels of the school
     */
    public function setEducationType($eductype)
    {
        $this->eductype = $eductype;
    }

    /**
     * Get eductype
     *
     * @return string 
     */
    public function getEducationType()
    {
        return $this->eductype;
    }

    /**
     * Set active
     *
     * @param boolean $active can registrants choose this school?
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return boolean  can registrants choose this school?
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set name
     *
     * @param string $name name of the school
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
     * Default constructor.
     * 
     * @return void
     */
    public function __construct()
    {
    }

}

