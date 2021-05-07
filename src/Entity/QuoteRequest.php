<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * QuoteRequest
 *
 * @ORM\Table(name="quoteRequests")
 * @ORM\Entity(repositoryClass="App\Repository\QuoteRequestRepository")
 * @UniqueEntity("number")
 */
class QuoteRequest
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
     * #################################
     *              SYSTEM USER ASSOCIATION
     * #################################
     */

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="userCreationId", referencedColumnName="id", nullable=true)
     */
    private $userCreation;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="userUpdateId", referencedColumnName="id", nullable=true)
     */
    private $userUpdate;

    /**
     * @ORM\Column(type="string", length=500)
     * @Assert\Length(max=500, payload="tokenStringLengthNotValid")
     * @Assert\NotBlank(payload="tokenNotValid")
     */
    private $token;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=255)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="origin", type="string", length=255)
     */
    private $origin;


    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", length=255, nullable=true)
     */
    private $number;

    /**
     * @var string
     *
     * @ORM\Column(name="businessName", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"public"})
     */
    private $businessName;

    /**
     * @var string
     *
     * @ORM\Column(name="civility", type="string", length=10, nullable=true)
     * @Assert\NotBlank(groups={"public"})
     */
    private $civility;

    /**
     * @var string
     *
     * @ORM\Column(name="lastName", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"public"})
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="firstName", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"public"})
     */
    private $firstName;

    /**
     * @var string
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"public"})
     * @Assert\Email(
     *     groups={"public"},
     *      message = "email_error"
     * )
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"public"})
     */
    private $phone;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isMultisite", type="boolean")
     * @Assert\NotBlank(groups={"public"})
     */
    private $isMultisite;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isSameSignatory", type="boolean")
     * @Assert\NotBlank(groups={"public"})
     */
    private $isSameSignatory;

    /**
     * @var string
     *
     * @ORM\Column(name="staff", type="text")
     * @Assert\NotBlank(groups={"public"})
     */
    private $staff;

    /**
     * @var string
     *
     * @ORM\Column(name="access", type="text")
     * @Assert\NotBlank(groups={"public"})
     */
    private $access;

    /**
     * @var int
     *
     * @ORM\Column(name="floorNumber",  type="integer", nullable=true)
     */
    private $floorNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="text", nullable=true)
     * @Assert\NotBlank(groups={"public_multisite"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true, nullable=true)
     * @Assert\NotBlank(groups={"public_multisite"})
     */
    private $city;

    /**
     * "Commentaire client" rempli par l'utilisateur Front Office
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="quoteStatus", type="string", length=255)
     */
    private $quoteStatus;

    /**
     * @var int
     *
     * @ORM\Column(name="totalAmount", type="integer", nullable=true)
     */
    private $totalAmount;


    /**
     * @var int
     *
     * @ORM\Column(name="overallDiscount", type="integer")
     */
    private $overallDiscount;

    /**
     * "Commentaire client" rempli par le commercial dans le back-office
     * @var string
     *
     * @ORM\Column(name="salesmanComment", type="text", nullable=true)
     */
    private $salesmanComment;

    /**
     * @var int
     *
     * @ORM\Column(name="annualBudget", type="integer", nullable=true)
     */
    private $annualBudget;

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
     * @var string
     *
     * @ORM\Column(name="customerId", type="string", length=255, nullable=true)
     */
    private $customerId;

    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", length=255, nullable=true)
     */
    private $reference;

    /**
     * @var string
     *
     * @ORM\Column(name="signatoryLastName1", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"public_same_signatory"})
     */
    private $signatoryLastName1;

    /**
     * @var string
     *
     * @ORM\Column(name="signatoryFirstName1", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"public_same_signatory"})
     */
    private $signatoryFirstName1;

    /**
     * @var string
     *
     * @ORM\Column(name="signatoryTitle1", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"public_same_signatory"})
     */
    private $signatoryTitle1;


    /**
     * #################################
     *              Relations
     * #################################
     */

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="quoteRequests")
     */
    private $userInCharge;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PostalCode", inversedBy="quoteRequests")
     * @Assert\NotBlank(groups={"public_multisite"})
     */
    private $postalCode;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\QuoteRequestLine", mappedBy="quoteRequest")
     */
    private $quoteRequestLines;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\OtherNeed", mappedBy="quoteRequests")
     */
    private $otherNeeds;

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
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->quoteRequestLines = new ArrayCollection();
        $this->otherNeeds = new ArrayCollection();
        $this->overallDiscount = 0;
        $this->isSameSignatory = false;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return QuoteRequest
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
     * @return QuoteRequest
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
     * @return QuoteRequest
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
     * Set businessName.
     *
     * @param string|null $businessName
     *
     * @return QuoteRequest
     */
    public function setBusinessName($businessName = null)
    {
        $this->businessName = $businessName;

        return $this;
    }

    /**
     * Get businessName.
     *
     * @return string|null
     */
    public function getBusinessName()
    {
        return $this->businessName;
    }

    /**
     * Set civility.
     *
     * @param string|null $civility
     *
     * @return QuoteRequest
     */
    public function setCivility($civility = null)
    {
        $this->civility = $civility;

        return $this;
    }

    /**
     * Get civility.
     *
     * @return string|null
     */
    public function getCivility()
    {
        return $this->civility;
    }

    /**
     * Set lastName.
     *
     * @param string|null $lastName
     *
     * @return QuoteRequest
     */
    public function setLastName($lastName = null)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName.
     *
     * @return string|null
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set firstName.
     *
     * @param string|null $firstName
     *
     * @return QuoteRequest
     */
    public function setFirstName($firstName = null)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName.
     *
     * @return string|null
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return QuoteRequest
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set phone.
     *
     * @param string|null $phone
     *
     * @return QuoteRequest
     */
    public function setPhone($phone = null)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string|null
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set isMultisite.
     *
     * @param bool $isMultisite
     *
     * @return QuoteRequest
     */
    public function setIsMultisite($isMultisite)
    {
        $this->isMultisite = $isMultisite;

        return $this;
    }

    /**
     * Get isMultisite.
     *
     * @return bool
     */
    public function getIsMultisite()
    {
        return $this->isMultisite;
    }

    /**
     * @return bool
     */
    public function getIsSameSignatory()
    {
        return $this->isSameSignatory;
    }

    /**
     * @param bool $isSameSignatory
     * @return QuoteRequest
     */
    public function setIsSameSignatory($isSameSignatory)
    {
        $this->isSameSignatory = $isSameSignatory;
        return $this;
    }

    /**
     * Set address.
     *
     * @param string|null $address
     *
     * @return QuoteRequest
     */
    public function setAddress($address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address.
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set city.
     *
     * @param string|null $city
     *
     * @return QuoteRequest
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
     * Set comment.
     *
     * @param string|null $comment
     *
     * @return QuoteRequest
     */
    public function setComment($comment = null)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string|null
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set quoteStatus.
     *
     * @param string $quoteStatus
     *
     * @return QuoteRequest
     */
    public function setQuoteStatus($quoteStatus)
    {
        $this->quoteStatus = $quoteStatus;

        return $this;
    }

    /**
     * Get quoteStatus.
     *
     * @return string
     */
    public function getQuoteStatus()
    {
        return $this->quoteStatus;
    }

    /**
     * Set overallDiscount.
     *
     * @param int|null $overallDiscount
     *
     * @return QuoteRequest
     */
    public function setOverallDiscount($overallDiscount = null)
    {
        $this->overallDiscount = $overallDiscount;

        return $this;
    }

    /**
     * Get overallDiscount.
     *
     * @return int|null
     */
    public function getOverallDiscount()
    {
        return $this->overallDiscount;
    }

    /**
     * Set salesmanComment.
     *
     * @param string|null $salesmanComment
     *
     * @return QuoteRequest
     */
    public function setSalesmanComment($salesmanComment = null)
    {
        $this->salesmanComment = $salesmanComment;

        return $this;
    }

    /**
     * Get salesmanComment.
     *
     * @return string|null
     */
    public function getSalesmanComment()
    {
        return $this->salesmanComment;
    }

    /**
     * Set frequency.
     *
     * @param string|null $frequency
     *
     * @return QuoteRequest
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
     * Set userCreation.
     *
     * @param User|null $userCreation
     *
     * @return QuoteRequest
     */
    public function setUserCreation(User $userCreation = null)
    {
        $this->userCreation = $userCreation;

        return $this;
    }

    /**
     * Get userCreation.
     *
     * @return User|null
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
     * @return QuoteRequest
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
     * Set userInCharge.
     *
     * @param User|null $userInCharge
     *
     * @return QuoteRequest
     */
    public function setUserInCharge(User $userInCharge = null)
    {
        $this->userInCharge = $userInCharge;

        return $this;
    }

    /**
     * Get userInCharge.
     *
     * @return User|null
     */
    public function getUserInCharge()
    {
        return $this->userInCharge;
    }

    /**
     * Add quoteRequestLine.
     *
     * @param QuoteRequestLine $quoteRequestLine
     *
     * @return QuoteRequest
     */
    public function addQuoteRequestLine(QuoteRequestLine $quoteRequestLine)
    {
        $this->quoteRequestLines[] = $quoteRequestLine;

        return $this;
    }

    /**
     * Remove quoteRequestLine.
     *
     * @param QuoteRequestLine $quoteRequestLine
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeQuoteRequestLine(QuoteRequestLine $quoteRequestLine)
    {
        return $this->quoteRequestLines->removeElement($quoteRequestLine);
    }

    /**
     * Get quoteRequestLines.
     *
     * @return Collection
     */
    public function getQuoteRequestLines()
    {
        return $this->quoteRequestLines;
    }

    /**
     * Set totalAmount.
     *
     * @param int|null $totalAmount
     *
     * @return QuoteRequest
     */
    public function setTotalAmount($totalAmount = null)
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * Get totalAmount.
     *
     * @return int|null
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set frequencyTimes.
     *
     * @param string|null $frequencyTimes
     *
     * @return QuoteRequest
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
     * @return QuoteRequest
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
     * @return QuoteRequest
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set locale.
     *
     * @param string|null $locale
     *
     * @return QuoteRequest
     */
    public function setLocale($locale = null)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string|null
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return null|string
     */
    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    /**
     * @param string $origin
     * @return QuoteRequest
     */
    public function setOrigin(string $origin): self
    {
        $this->origin = $origin;
        return $this;
    }

    /**
     * Set staff.
     *
     * @param string $staff
     *
     * @return QuoteRequest
     */
    public function setStaff($staff)
    {
        $this->staff = $staff;

        return $this;
    }

    /**
     * Get staff.
     *
     * @return string
     */
    public function getStaff()
    {
        return $this->staff;
    }



    /**
     * Set access.
     *
     * @param string $access
     *
     * @return QuoteRequest
     */
    public function setAccess($access)
    {
        $this->access = $access;

        return $this;
    }

    /**
     * Get access.
     *
     * @return string
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * @return int
     */
    public function getFloorNumber()
    {
        return $this->floorNumber;
    }

    /**
     * @param int $floorNumber
     * @return QuoteRequest
     */
    public function setFloorNumber($floorNumber)
    {
        $this->floorNumber = $floorNumber;
        return $this;
    }

    /**
     * Set postalCode.
     *
     * @param PostalCode|null $postalCode
     *
     * @return QuoteRequest
     */
    public function setPostalCode(PostalCode $postalCode = null)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get postalCode.
     *
     * @return PostalCode|null
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set number.
     *
     * @param string|null $number
     *
     * @return QuoteRequest
     */
    public function setNumber($number = null)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set customerId.
     *
     * @param string|null $customerId
     *
     * @return QuoteRequest
     */
    public function setCustomerId($customerId = null)
    {
        $this->customerId = $customerId;

        return $this;
    }

    /**
     * Get customerId.
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * Set annualBudget.
     *
     * @param int|null $annualBudget
     *
     * @return QuoteRequest
     */
    public function setAnnualBudget($annualBudget = null)
    {
        $this->annualBudget = $annualBudget;

        return $this;
    }

    /**
     * Get annualBudget.
     *
     * @return int|null
     */
    public function getAnnualBudget()
    {
        return $this->annualBudget;
    }

    /**
     * Set reference.
     *
     * @param string|null $reference
     *
     * @return QuoteRequest
     */
    public function setReference($reference = null)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get reference.
     *
     * @return string|null
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set signatoryLastName1.
     *
     * @param string|null $signatoryLastName1
     *
     * @return QuoteRequest
     */
    public function setSignatoryLastName1($signatoryLastName1 = null)
    {
        $this->signatoryLastName1 = $signatoryLastName1;

        return $this;
    }

    /**
     * Get signatoryLastName1.
     *
     * @return string|null
     */
    public function getSignatoryLastName1()
    {
        return $this->signatoryLastName1;
    }

    /**
     * Set signatoryFirstName1.
     *
     * @param string|null $signatoryFirstName1
     *
     * @return QuoteRequest
     */
    public function setSignatoryFirstName1($signatoryFirstName1 = null)
    {
        $this->signatoryFirstName1 = $signatoryFirstName1;

        return $this;
    }

    /**
     * Get signatoryFirstName1.
     *
     * @return string|null
     */
    public function getSignatoryFirstName1()
    {
        return $this->signatoryFirstName1;
    }

    /**
     * Set signatoryTitle1.
     *
     * @param string|null $signatoryTitle1
     *
     * @return QuoteRequest
     */
    public function setSignatoryTitle1($signatoryTitle1 = null)
    {
        $this->signatoryTitle1 = $signatoryTitle1;

        return $this;
    }

    /**
     * Get signatoryTitle1.
     *
     * @return string|null
     */
    public function getSignatoryTitle1()
    {
        return $this->signatoryTitle1;
    }


    /**
     * Retoune true si le signataires sont correctement définis, false sinon
     *
     * @return bool
     */
    public function hasValidSignatories()
    {
        return ($this->signatoryLastName1
            && $this->signatoryFirstName1
            && $this->signatoryTitle1);
    }

    /**
     * Add otherNeed.
     *
     * @param OtherNeed $otherNeed
     *
     * @return QuoteRequest
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
     * @return Collection
     */
    public function getOtherNeeds()
    {
        return $this->otherNeeds;
    }

    /**
     * Set token.
     *
     * @param string $token
     *
     * @return QuoteRequest
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

}
