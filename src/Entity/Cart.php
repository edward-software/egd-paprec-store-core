<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * Cart
 *
 * @ORM\Table(name="carts")
 * @ORM\Entity(repositoryClass="App\Repository\CartRepository")
 */
class Cart
{
    /**
     * @var \Ramsey\Uuid\UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateCreation", type="datetime")
     */
    private $dateCreation;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="dateUpdate", type="datetime", nullable=true)
     */
    private $dateUpdate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="disabled", type="datetime", nullable=true)
     */
    private $disabled;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="frequency", type="string", length=255, nullable=true)
     */
    private $frequency;
    
    /**
     * @var string
     *
     * @ORM\Column(name="frequencyTimes", type="string", length=255, nullable=true)
     */
    private $frequencyTimes;

    /**
     * @var string
     *
     * @ORM\Column(name="frequencyInterval", type="string", length=255, nullable=true)
     */
    private $frequencyInterval;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @var array|null
     *
     * @ORM\Column(name="content", type="json", nullable=true)
     */
    private $content;

    /******************************
     * RELATIONS
     */

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\OtherNeed", mappedBy="carts")
     */
    private $otherNeeds;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->content = array();
        $this->otherNeeds = new ArrayCollection();
    }



    /**
     * Get id.
     *
     * @return uuid
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Cart
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate.
     *
     * @param \DateTime|null $dateUpdate
     *
     * @return Cart
     */
    public function setDateUpdate($dateUpdate = null)
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate.
     *
     * @return \DateTime|null
     */
    public function getDateUpdate()
    {
        return $this->dateUpdate;
    }

    /**
     * Set disabled.
     *
     * @param \DateTime|null $disabled
     *
     * @return Cart
     */
    public function setDisabled($disabled = null)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return \DateTime|null
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set city.
     *
     * @param string|null $city
     *
     * @return Cart
     */
    public function setCity($city = null)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set frequency.
     *
     * @param string|null $frequency
     *
     * @return Cart
     */
    public function setFrequency($frequency = null)
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get frequency.
     *
     * @return string|null
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * Set content.
     *
     * @param json|null $content
     *
     * @return Cart
     */
    public function setContent($content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return json|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set frequencyTimes.
     *
     * @param string|null $frequencyTimes
     *
     * @return Cart
     */
    public function setFrequencyTimes($frequencyTimes = null)
    {
        $this->frequencyTimes = $frequencyTimes;

        return $this;
    }

    /**
     * Get frequencyTimes.
     *
     * @return string|null
     */
    public function getFrequencyTimes()
    {
        return $this->frequencyTimes;
    }

    /**
     * Set frequencyInterval.
     *
     * @param string|null $frequencyInterval
     *
     * @return Cart
     */
    public function setFrequencyInterval($frequencyInterval = null)
    {
        $this->frequencyInterval = $frequencyInterval;

        return $this;
    }

    /**
     * Get frequencyInterval.
     *
     * @return string|null
     */
    public function getFrequencyInterval()
    {
        return $this->frequencyInterval;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Cart
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Add otherNeed.
     *
     * @param OtherNeed $otherNeed
     *
     * @return Cart
     */
    public function addOtherNeed(OtherNeed $otherNeed)
    {
        $this->otherNeeds[] = $otherNeed;

        return $this;
    }

    /**
     * Remove otherNeed.
     *
     * @param OtherNeed $otherNeed
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOtherNeed(OtherNeed $otherNeed)
    {
        return $this->otherNeeds->removeElement($otherNeed);
    }

    /**
     * Get otherNeeds.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOtherNeeds()
    {
        return $this->otherNeeds;
    }
}
