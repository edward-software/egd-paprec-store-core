<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Product
 *
 * @ORM\Table(name="products")
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 */
class Product
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
     * @var int
     *
     * @ORM\Column(name="rentalUnitPrice", type="integer", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^\d{1,6}((\.|\,)\d{1,2})?$/",
     *     match=true,
     *     message="la valeur doit être un nombre entre 0 et 999 999,99 ('.' autorisé)"
     * )
     */
    private $rentalUnitPrice;

    /**
     * @var int
     *
     * @ORM\Column(name="transportUnitPrice", type="integer", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^\d{1,6}((\.|\,)\d{1,2})?$/",
     *     match=true,
     *     message="la valeur doit être un nombre entre 0 et 999 999,99 ('.' autorisé)"
     * )
     */
    private $transportUnitPrice;

    /**
     * @var int
     *
     * @ORM\Column(name="treatmentUnitPrice", type="integer", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^\d{1,6}((\.|\,)\d{1,2})?$/",
     *     match=true,
     *     message="la valeur doit être un nombre entre 0 et 999 999,99 ('.' autorisé)"
     * )
     */
    private $treatmentUnitPrice;

    /**
     * @var int
     *
     * @ORM\Column(name="traceabilityUnitPrice", type="integer", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^\d{1,6}((\.|\,)\d{1,2})?$/",
     *     match=true,
     *     message="la valeur doit être un nombre entre 0 et 999 999,99 ('.' autorisé)"
     * )
     */
    private $traceabilityUnitPrice;

    /**
     * @var string
     * Le coefficient de transport à utiliser
     * @ORM\Column(name="transportType", type="string", length=255)
     * @Assert\NotBlank()
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
     * @ORM\Column(name="frequency", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $frequency;

    /**
     * @var string
     *
     * @ORM\Column(name="frequencyTimes", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $frequencyTimes;

    /**
     * @var string
     *
     * @ORM\Column(name="frequencyInterval", type="string", length=255)
     * @Assert\NotBlank()
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

//    /**
//     * @var string
//     *
//     * @ORM\Column(name="billingUnit", type="string", length=255)
//     * @Assert\NotBlank()
//     */
//    private $billingUnit;

    /**
     * @var int
     *
     * @ORM\Column(name="materialUnitPrice", type="integer", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^\d{1,6}((\.|\,)\d{1,2})?$/",
     *     match=true,
     *     message="la valeur doit être un nombre entre 0 et 999 999,99 ('.' autorisé)"
     * )
     */
    private $materialUnitPrice;


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
     * @ORM\OneToMany(targetEntity="App\Entity\FollowUp", mappedBy="product", cascade={"all"})
     */
    private $followUps;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Picture", mappedBy="product", cascade={"all"})
     */
    private $pictures;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ProductLabel", mappedBy="product", cascade={"all"})
     */
    private $productLabels;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Range", inversedBy="products")
     * @ORM\JoinColumn(name="rangeId")
     */
    private $range;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\BillingUnit", inversedBy="products")
     * @ORM\JoinColumn(name="billingUnitId")
     */
    private $billingUnit;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->pictures = new ArrayCollection();
        $this->followUps = new ArrayCollection();
        $this->productLabels = new ArrayCollection();
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * Add productLabel.
     *
     * @param ProductLabel $productLabel
     *
     * @return Product
     */
    public function addProductLabel(ProductLabel $productLabel)
    {
        $this->productLabels[] = $productLabel;

        return $this;
    }

    /**
     * Remove productLabel.
     *
     * @param ProductLabel $productLabel
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeProductLabel(ProductLabel $productLabel)
    {
        return $this->productLabels->removeElement($productLabel);
    }

    /**
     * Get productLabels.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductLabels()
    {
        return $this->productLabels;
    }


    /**
     * Set capacity.
     *
     * @param string $capacity
     *
     * @return Product
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
     * Set rentalUnitPrice.
     *
     * @param int|null $rentalUnitPrice
     *
     * @return Product
     */
    public function setRentalUnitPrice($rentalUnitPrice = null)
    {
        $this->rentalUnitPrice = $rentalUnitPrice;

        return $this;
    }

    /**
     * Get rentalUnitPrice.
     *
     * @return int|null
     */
    public function getRentalUnitPrice()
    {
        return $this->rentalUnitPrice;
    }

    /**
     * Set transportUnitPrice.
     *
     * @param int|null $transportUnitPrice
     *
     * @return Product
     */
    public function setTransportUnitPrice($transportUnitPrice = null)
    {
        $this->transportUnitPrice = $transportUnitPrice;

        return $this;
    }

    /**
     * Get transportUnitPrice.
     *
     * @return int|null
     */
    public function getTransportUnitPrice()
    {
        return $this->transportUnitPrice;
    }

    /**
     * Set treatmentUnitPrice.
     *
     * @param int|null $treatmentUnitPrice
     *
     * @return Product
     */
    public function setTreatmentUnitPrice($treatmentUnitPrice = null)
    {
        $this->treatmentUnitPrice = $treatmentUnitPrice;

        return $this;
    }

    /**
     * Get treatmentUnitPrice.
     *
     * @return int|null
     */
    public function getTreatmentUnitPrice()
    {
        return $this->treatmentUnitPrice;
    }

    /**
     * Set traceabilityUnitPrice.
     *
     * @param int|null $traceabilityUnitPrice
     *
     * @return Product
     */
    public function setTraceabilityUnitPrice($traceabilityUnitPrice = null)
    {
        $this->traceabilityUnitPrice = $traceabilityUnitPrice;

        return $this;
    }

    /**
     * Get traceabilityUnitPrice.
     *
     * @return int|null
     */
    public function getTraceabilityUnitPrice()
    {
        return $this->traceabilityUnitPrice;
    }

    /**
     * @return null|string
     */
    public function getTransportType(): ?string
    {
        return $this->transportType;
    }

    /**
     * @param string $transportType
     * @return Product
     */
    public function setTransportType(string $transportType): self
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
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
     * @return Product
     */
    public function setCode($code): self
    {
        $this->code = $code;
        return $this;
    }

//    /**
//     * @return mixed
//     */
//    public function getBillingUnit()
//    {
//        return $this->billingUnit;
//    }
//
//    /**
//     * @param mixed $billingUnit
//     * @return Product
//     */
//    public function setBillingUnit($billingUnit): self
//    {
//        $this->billingUnit = $billingUnit;
//        return $this;
//    }

    /**
     * @return mixed
     */
    public function getMaterialUnitPrice()
    {
        return $this->materialUnitPrice;
    }

    /**
     * @param mixed $materialUnitPrice
     * @return Product
     */
    public function setMaterialUnitPrice($materialUnitPrice): self
    {
        $this->materialUnitPrice = $materialUnitPrice;
        return $this;
    }


    /**
     * Add followUp.
     *
     * @param FollowUp $followUp
     *
     * @return Product
     */
    public function addFollowUp(FollowUp $followUp)
    {
        $this->followUps[] = $followUp;

        return $this;
    }

    /**
     * Remove followUp.
     *
     * @param FollowUp $followUp
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeFollowUp(FollowUp $followUp)
    {
        return $this->followUps->removeElement($followUp);
    }

    /**
     * Get followUps.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFollowUps()
    {
        return $this->followUps;
    }


}
