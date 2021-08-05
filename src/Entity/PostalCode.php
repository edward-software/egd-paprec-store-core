<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PostalCode
 *
 * @ORM\Table(name="postalCodes")
 * @ORM\Entity(repositoryClass="App\Repository\PostalCodeRepository")
 * @UniqueEntity(fields={"code"}, repositoryMethod="isCodeUnique")
 */
class PostalCode
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
     * @var \DateTime
     *
     * @ORM\Column(name="deleted", type="datetime", nullable=true)
     */
    private $deleted;

    /**
     * @var string
     * @ORM\Column(name="code", type="string")
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^\d{2}(\*|(?:\d{3}))$/",
     *     match=true,
     *     message="Le codes postal doivent être un nombre de 5 caractères ou 2 suivis d'une *. (ex: 15*, 1530)"
     * )
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="rentalRate", type="bigint")
     * @Assert\NotBlank()
     */
    private $rentalRate;

    /**
     * @var int
     *
     * @ORM\Column(name="cBroyeurTransportRate", type="bigint")
     * @Assert\NotBlank()
     */
    private $cBroyeurTransportRate;

    /**
     * @var int
     *
     * @ORM\Column(name="fourgonPLTransportRate", type="bigint")
     * @Assert\NotBlank()
     */
    private $fourgonPLTransportRate;

    /**
     * @var int
     *
     * @ORM\Column(name="fourgonVLTransportRate", type="bigint")
     * @Assert\NotBlank()
     */
    private $fourgonVLTransportRate;

    /**
     * @var int
     *
     * @ORM\Column(name="amplirollTransportRate", type="bigint")
     * @Assert\NotBlank()
     */
    private $amplirollTransportRate;

    /**
     * @var int
     *
     * @ORM\Column(name="bomTransportRate", type="bigint")
     * @Assert\NotBlank()
     */
    private $bomTransportRate;

    /**
     * @var int
     *
     * @ORM\Column(name="livraisonTransportRate", type="bigint")
     * @Assert\NotBlank()
     */
    private $livraisonTransportRate;

    /**
     * @var int
     *
     * @ORM\Column(name="treatmentRate", type="bigint")
     * @Assert\NotBlank()
     */
    private $treatmentRate;


    /**
     * @var int
     *
     * @ORM\Column(name="traceabilityRate", type="bigint")
     * @Assert\NotBlank()
     */
    private $traceabilityRate;


    /**
     * @var text
     * @ORM\Column(name="city", type="string")
     * @Assert\NotBlank()
     */
    private $city;

    /**
     * @var text
     * @ORM\Column(name="zone", type="string")
     * @Assert\NotBlank()
     */
    private $zone;


    /**
     * #################################
     *              Relations
     * #################################
     */


    /**
     * @ORM\OneToMany(targetEntity="App\Entity\QuoteRequest", mappedBy="postalCode")
     */
    private $quoteRequests;


    /**
     * @ORM\OneToMany(targetEntity="App\Entity\QuoteRequest", mappedBy="billingPostalCode")
     */
    private $billingQuoteRequests;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Agency", inversedBy="postalCodes")
     * @Assert\NotBlank
     */
    private $agency;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="postalCodes")
     */
    private $userInCharge;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

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
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return PostalCode
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
     * @return PostalCode
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
     * @return PostalCode
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
     * Set code.
     *
     * @param string $code
     *
     * @return PostalCode
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return null|int
     */
    public function getCBroyeurTransportRate(): ?int
    {
        return $this->cBroyeurTransportRate;
    }

    /**
     * @param int $cBroyeurTransportRate
     * @return PostalCode
     */
    public function setCBroyeurTransportRate(int $cBroyeurTransportRate): self
    {
        $this->cBroyeurTransportRate = $cBroyeurTransportRate;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getFourgonPLTransportRate(): ?int
    {
        return $this->fourgonPLTransportRate;
    }

    /**
     * @param int $fourgonPLTransportRate
     * @return PostalCode
     */
    public function setFourgonPLTransportRate(int $fourgonPLTransportRate): self
    {
        $this->fourgonPLTransportRate = $fourgonPLTransportRate;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getFourgonVLTransportRate(): ?int
    {
        return $this->fourgonVLTransportRate;
    }

    /**
     * @param int $fourgonVLTransportRate
     * @return PostalCode
     */
    public function setFourgonVLTransportRate(int $fourgonVLTransportRate): self
    {
        $this->fourgonVLTransportRate = $fourgonVLTransportRate;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getAmplirollTransportRate(): ?int
    {
        return $this->amplirollTransportRate;
    }

    /**
     * @param int $amplirollTransportRate
     * @return PostalCode
     */
    public function setAmplirollTransportRate(int $amplirollTransportRate): self
    {
        $this->amplirollTransportRate = $amplirollTransportRate;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getBomTransportRate(): ?int
    {
        return $this->bomTransportRate;
    }

    /**
     * @param int $bomTransportRate
     * @return PostalCode
     */
    public function setBomTransportRate(int $bomTransportRate): self
    {
        $this->bomTransportRate = $bomTransportRate;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getLivraisonTransportRate(): ?int
    {
        return $this->livraisonTransportRate;
    }

    /**
     * @param int $livraisonTransportRate
     * @return PostalCode
     */
    public function setLivraisonTransportRate(int $livraisonTransportRate): self
    {
        $this->livraisonTransportRate = $livraisonTransportRate;
        return $this;
    }


    /**
     * Set treatmentRate.
     *
     * @param int $treatmentRate
     *
     * @return PostalCode
     */
    public function setTreatmentRate($treatmentRate)
    {
        $this->treatmentRate = $treatmentRate;

        return $this;
    }

    /**
     * Get treatmentRate.
     *
     * @return int
     */
    public function getTreatmentRate()
    {
        return $this->treatmentRate;
    }

    /**
     * Set traceabilityRate.
     *
     * @param int $traceabilityRate
     *
     * @return PostalCode
     */
    public function setTraceabilityRate($traceabilityRate)
    {
        $this->traceabilityRate = $traceabilityRate;

        return $this;
    }

    /**
     * Get traceabilityRate.
     *
     * @return int
     */
    public function getTraceabilityRate()
    {
        return $this->traceabilityRate;
    }

    /**
     * Set userCreation.
     *
     * @param User $userCreation
     *
     * @return PostalCode
     */
    public function setUserCreation(User $userCreation)
    {
        $this->userCreation = $userCreation;

        return $this;
    }

    /**
     * Get userCreation.
     *
     * @return User
     */
    public function getUserCreation()
    {
        return $this->userCreation;
    }

    /**
     * Set userUpdate.
     *
     * @param User|null $userUpdate
     *
     * @return PostalCode
     */
    public function setUserUpdate(User $userUpdate = null)
    {
        $this->userUpdate = $userUpdate;

        return $this;
    }

    /**
     * Get userUpdate.
     *
     * @return User|null
     */
    public function getUserUpdate()
    {
        return $this->userUpdate;
    }

    /**
     * Set city.
     *
     * @param string $city
     *
     * @return PostalCode
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set zone.
     *
     * @param string $zone
     *
     * @return PostalCode
     */
    public function setZone($zone)
    {
        $this->zone = $zone;

        return $this;
    }

    /**
     * Get zone.
     *
     * @return string
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * Add quoteRequest.
     *
     * @param QuoteRequest $quoteRequest
     *
     * @return PostalCode
     */
    public function addQuoteRequest(QuoteRequest $quoteRequest)
    {
        $this->quoteRequests[] = $quoteRequest;

        return $this;
    }

    /**
     * Remove quoteRequest.
     *
     * @param QuoteRequest $quoteRequest
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeQuoteRequest(QuoteRequest $quoteRequest)
    {
        return $this->quoteRequests->removeElement($quoteRequest);
    }

    /**
     * Get quoteRequests.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQuoteRequests()
    {
        return $this->quoteRequests;
    }

    /**
     * Set rentalRate.
     *
     * @param int $rentalRate
     *
     * @return PostalCode
     */
    public function setRentalRate($rentalRate)
    {
        $this->rentalRate = $rentalRate;

        return $this;
    }

    /**
     * Get rentalRate.
     *
     * @return int
     */
    public function getRentalRate()
    {
        return $this->rentalRate;
    }

    /**
     * @return mixed
     */
    public function getAgency()
    {
        return $this->agency;
    }

    /**
     * @param mixed $agency
     * @return PostalCode
     */
    public function setAgency($agency)
    {
        $this->agency = $agency;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserInCharge()
    {
        return $this->userInCharge;
    }

    /**
     * @param mixed $userInCharge
     * @return PostalCode
     */
    public function setUserInCharge($userInCharge): self
    {
        $this->userInCharge = $userInCharge;
        return $this;
    }

}
