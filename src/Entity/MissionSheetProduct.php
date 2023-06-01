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
     * @ORM\Column(name="capacity", type="string", length=10, nullable=true)
     */
    private $capacity;

    /**
     * @var string
     * L'unité du volume du produit (litre, m²,..)
     * @ORM\Column(name="capacityUnit", type="string", length=255, nullable=true)
     */
    private $capacityUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="dimensions", type="string", length=500, nullable=true)
     */
    private $dimensions;

    /**
     * @var string
     *
     * @ORM\Column(name="frequency", type="string", length=255, nullable=true)
     */
    private $frequency;

    /**
     * @var string
     *
     * @ORM\Column(name="wasteClassification", type="string", length=10, nullable=true)
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
     * @ORM\OneToMany(targetEntity="App\Entity\MissionSheetProductLabel", mappedBy="missionSheetProduct", cascade={"all"})
     */
    private $missionSheetProductLabels;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->missionSheetProductLabels = new ArrayCollection();
        $this->frequency = 'PONCTUAL';
        $this->comment = 'Produit à usage exclusif pour les feuilles de mission (dépose, reprise, etc…)';
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
