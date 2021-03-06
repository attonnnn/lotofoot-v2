<?php

namespace Lotofootv2\Bundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Season
 *
 * @ORM\Table(name="lfv2_league")
 * @ORM\Entity
 */
class League
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="number", type="integer")
     */
    private $number;

    /**
     * @var integer
     *
     * @ORM\Column(name="currentDay", type="integer")
     */
    private $currentDay;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;
    
    /**
     * @var string
     *
     * @ORM\Column(name="opened", type="boolean")
     */
    private $opened;

    public function __construct()
    {
    	$this->currentDay = 1;
    	$this->opened = true;
    }
    
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
     * Set number
     *
     * @param integer $number
     * @return Season
     */
    public function setNumber($number)
    {
        $this->number = $number;
    
        return $this;
    }

    /**
     * Get number
     *
     * @return integer 
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set currentDay
     *
     * @param integer $currentDay
     * @return Season
     */
    public function setCurrentDay($currentDay)
    {
        $this->currentDay = $currentDay;
    
        return $this;
    }

    /**
     * Get currentDay
     *
     * @return integer 
     */
    public function getCurrentDay()
    {
        return $this->currentDay;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Season
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
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
     * Set name
     *
     * @param string $name
     * @return Season
     */
    public function setOpened($state)
    {
    	$this->opened = $state;
    
    	return $this;
    }
    
    /**
     * Get name
     *
     * @return string
     */
    public function getOpened()
    {
    	return $this->opened;
    }
    
    public function __toString() {
    	return "League : {$this->name}\n";
    }
}
