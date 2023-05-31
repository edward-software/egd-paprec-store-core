<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MissionSheetProduct
 *
 * @ORM\Table(name="missionSheetProducts")
 * @ORM\Entity(repositoryClass="App\Repository\MissionSheetProductRepository")
 */
class MissionSheetProduct
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateCreation", type="datetime")
     */
    private $dateCreation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateUpdate", type="datetime", nullable=true)
     */
    private $dateUpdate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deleted", type="datetime", nullable=true)
     */
    private $deleted;


    /**
     * @var string
     * Le volume du produit
     * @ORM\Column(name="capacity", type="string", length=10)
     * @Assert\NotBlank()
     */
    private $capacity;

    /**
     * @var string
     * L'unité du volume du produit (litre, m²,..)
     * @ORM\Column(name="capacityUnit", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $capacityUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="dimensions", type="string", length=500)
     * @Assert\NotBlank()
     */
    private $dimensions;


    /**
     * @var boolean
     *
     * @ORM\Column(name="isEnabled", type="boolean")
     * @Assert\NotBlank()
     */
    private $isEnabled;

    /**
     * @var string
     * Le coefficient de transport à utiliser
     * @ORM\Column(name="transportType", type="string", length=255, nullable=true)
     */
    private $transportType;

    /**
     * @var string
     * Le catalogue du produit (REGULAR ou PONCTUAL)
     * @ORM\Column(name="catalog", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $catalog;

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
     * @var int
     * @Assert\NotBlank()
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * @var string
     *
     * @ORM\Column(name="wasteClassification", type="string", length=10)
     * @Assert\NotBlank()
     */
    private $wasteClassification;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $code;

    /**
     * @ORM\Column(name="referenceDate", type="string", length=7, nullable=true)
     */
    private $referenceDate;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=500, nullable=true)
     */
    private $comment;

    /**
     * #################################
     *              SYSTEM USER ASSOCIATION
     * #################################
     */

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="userCreationId", referencedColumnName="id", nullable=false)
     */
    private $userCreation;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="userUpdateId", referencedColumnName="id", nullable=true)
     */
    private $userUpdate;

    /**
     * #################################
     *              Relations
     * #################################
     */

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Picture", mappedBy="missionSheetProduct", cascade={"all"})
     */
    private $pictures;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\MissionSheetProductLabel", mappedBy="missionSheetProduct", cascade={"all"})
     */
    private $missionSheetProductLabels;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Range", inversedBy="missionSheetProducts")
     * @ORM\JoinColumn(name="rangeId")
     */
    private $range;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\BillingUnit", inversedBy="missionSheetProducts")
     * @ORM\JoinColumn(name="billingUnitId")
     */
    private $billingUnit;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Setting", inversedBy="missionSheetProducts")
     * @ORM\JoinColumn(name="mercurialId")
     */
    private $mercurial;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->pictures = new ArrayCollection();
        $this->missionSheetProductLabels = new ArrayCollection();
        $this->transportType = 'LIVRAISON';
        $this->catalog = 'REGULAR';
    }


    /**
     * ##########################################
     */

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dimensions.
     *
     * @param string $dimensions
     *
     * @return MissionSheetProduct
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    /**
     * Get dimensions.
     *
     * @return string
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Set capacityUnit.
     *
     * @param string $capacityUnit
     *
     * @return MissionSheetProduct
     */
    public function setCapacityUnit($capacityUnit)
    {
        $this->capacityUnit = $capacityUnit;

        return $this;
    }

    /**
     * Get capacityUnit.
     *
     * @return string
     */
    public function getCapacityUnit()
    {
        return $this->capacityUnit;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return MissionSheetProduct
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
     * @return MissionSheetProduct
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
     * Set deleted.
     *
     * @param \DateTime|null $deleted
     *
     * @return MissionSheetProduct
     */
    public function setDeleted($deleted = null)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted.
     *
     * @return \DateTime|null
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set IsEnabled.
     *
     * @param bool IsEnabled
     *
     * @return MissionSheetProduct
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get isEnabled.
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set userCreation
     *
     * @param User $userCreation
     *
     * @return MissionSheetProduct
     */
    public function setUserCreation(User $userCreation)
    {
        $this->userCreation = $userCreation;

        return $this;
    }

    /**
     * Get userCreation
     *
     * @return User
     */
    public function getUserCreation()
    {
        return $this->userCreation;
    }

    /**
     * Set userUpdate
     *
     * @param User $userUpdate
     *
     * @return MissionSheetProduct
     */
    public function setUserUpdate(User $userUpdate = null)
    {
        $this->userUpdate = $userUpdate;

        return $this;
    }

    /**
     * Get userUpdate
     *
     * @return User
     */
    public function getUserUpdate()
    {
        return $this->userUpdate;
    }

    /**
     * Add picture.
     *
     * @param Picture $picture
     *
     * @return MissionSheetProduct
     */
    public function addPicture(Picture $picture)
    {
        $this->pictures[] = $picture;

        return $this;
    }

    /**
     * Remove picture.
     *
     * @param Picture $picture
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePicture(Picture $picture)
    {
        return $this->pictures->removeElement($picture);
    }

    /**
     * Get pictures.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPictures()
    {
        return $this->pictures;
    }

    public function getPilotPictures()
    {
        $pilotPictures = array();
        foreach ($this->pictures as $picture) {
            if ($picture->getType() == 'PILOTPICTURE') {
                $pilotPictures[] = $picture;
            }
        }
        return $pilotPictures;
    }

    public function getPictos()
    {
        $pictos = array();
        foreach ($this->pictures as $picture) {
            if ($picture->getType() == 'PICTO') {
                $pictos[] = $picture;
            }
        }
        return $pictos;
    }

    public function getPicturesPictures()
    {
        $pictures = array();
        foreach ($this->pictures as $picture) {
            if ($picture->getType() == 'PICTURE') {
                $pictures[] = $picture;
            }
        }
        return $pictures;
    }


    /**
     * Set capacity.
     *
     * @param string $capacity
     *
     * @return MissionSheetProduct
     */
    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;

        return $this;
    }

    /**
     * Get capacity.
     *
     * @return string
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @return null|string
     */
    public function getTransportType(): ?string
    {
        return $this->transportType;
    }

    /**
     * @param string|null $transportType
     * @return MissionSheetProduct
     */
    public function setTransportType($transportType = null)
    {
        $this->transportType = $transportType;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getCatalog(): ?string
    {
        return $this->catalog;
    }

    /**
     * @param string $catalog
     * @return MissionSheetProduct
     */
    public function setCatalog(string $catalog): self
    {
        $this->catalog = $catalog;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    /**
     * @param string $frequency
     * @return MissionSheetProduct
     */
    public function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getFrequencyTimes(): ?string
    {
        return $this->frequencyTimes;
    }

    /**
     * @param string $frequencyTimes
     * @return MissionSheetProduct
     */
    public function setFrequencyTimes(string $frequencyTimes): self
    {
        $this->frequencyTimes = $frequencyTimes;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getFrequencyInterval(): ?string
    {
        return $this->frequencyInterval;
    }

    /**
     * @param string $frequencyInterval
     * @return MissionSheetProduct
     */
    public function setFrequencyInterval(string $frequencyInterval): self
    {
        $this->frequencyInterval = $frequencyInterval;
        return $this;
    }

    /**
     * Set position.
     *
     * @param int $position
     *
     * @return MissionSheetProduct
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return mixed
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * @param mixed $range
     * @return MissionSheetProduct
     */
    public function setRange($range): self
    {
        $this->range = $range;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBillingUnit()
    {
        return $this->billingUnit;
    }

    /**
     * @param mixed $billingUnit
     * @return MissionSheetProduct
     */
    public function setBillingUnit($billingUnit): self
    {
        $this->billingUnit = $billingUnit;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWasteClassification()
    {
        return $this->wasteClassification;
    }

    /**
     * @param mixed $wasteClassification
     * @return MissionSheetProduct
     */
    public function setWasteClassification($wasteClassification): self
    {
        $this->wasteClassification = $wasteClassification;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     * @return MissionSheetProduct
     */
    public function setCode($code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMercurial()
    {
        return $this->mercurial;
    }

    /**
     * @param mixed $mercurial
     * @return MissionSheetProduct
     */
    public function setMercurial($mercurial): self
    {
        $this->mercurial = $mercurial;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getReferenceDate()
    {
        return $this->referenceDate;
    }

    /**
     * @param mixed $referenceDate
     * @return MissionSheetProduct
     */
    public function setReferenceDate($referenceDate): self
    {
        $this->referenceDate = $referenceDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment(string $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Add missionSheetProductLabel.
     *
     * @param MissionSheetProductLabel $missionSheetProductLabel
     *
     * @return MissionSheetProduct
     */
    public function addMissionSheetProductLabel(MissionSheetProductLabel $missionSheetProductLabel)
    {
        $this->missionSheetProductLabels[] = $missionSheetProductLabel;

        return $this;
    }

    /**
     * Remove productLabel.
     *
     * @param MissionSheetProductLabel $productLabel
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeMissionSheetProductLabel(MissionSheetProductLabel $missionSheetProductLabel)
    {
        return $this->missionSheetProductLabels->removeElement($missionSheetProductLabel);
    }

    /**
     * Get productLabels.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMissionSheetProductLabels()
    {
        return $this->missionSheetProductLabels;
    }
}
