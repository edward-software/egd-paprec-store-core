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
     * TODO revoir l'annotation @Assert\NotBlank(groups={"public"}) pour isSameAddress
     */
    /**
     * @var boolean
     *
     * @ORM\Column(name="isSameAddress", type="boolean")
     */
    private $isSameAddress;

    /**
     * Date de collecte souhaitée
     *
     * @var \DateTime|null
     *
     * @ORM\Column(name="ponctualDate", type="datetime", nullable=true)
     */
    private $ponctualDate;

    /**
     * @var string
     *
     * @ORM\Column(name="staff", type="text", nullable=true)
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
     * @ORM\Column(name="addressDetail", type="text", nullable=true)
     */
    private $addressDetail;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="billingAddress", type="text", nullable=true)
     */
    private $billingAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="billingCity", type="string", length=255, nullable=true)
     */
    private $billingCity;

    /**
     * @ORM\Column(name="billingPostalCode", type="string", length=255, nullable=true)
     */
    private $billingPostalCode;

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
     * @ORM\Column(name="totalAmount", type="bigint", nullable=true)
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
     * @ORM\Column(name="catalog", type="string", length=255, nullable=true)
     */
    private $catalog;

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
     * @var int
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startDate", type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="depositDate", type="datetime", nullable=true)
     */
    private $depositDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="resumptionDate", type="datetime", nullable=true)
     */
    private $resumptionDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="serviceEndDate", type="datetime", nullable=true)
     */
    private $serviceEndDate;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeCode1", type="string", length=255, nullable=true)
     */
    private $managementFeeCode1;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeCode2", type="string", length=255, nullable=true)
     */
    private $managementFeeCode2;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeCode3", type="string", length=255, nullable=true)
     */
    private $managementFeeCode3;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeCode4", type="string", length=255, nullable=true)
     */
    private $managementFeeCode4;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeDescription1", type="string", length=255, nullable=true)
     */
    private $managementFeeDescription1;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeDescription2", type="string", length=255, nullable=true)
     */
    private $managementFeeDescription2;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeDescription3", type="string", length=255, nullable=true)
     */
    private $managementFeeDescription3;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeDescription4", type="string", length=255, nullable=true)
     */
    private $managementFeeDescription4;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeFrequency1", type="string", length=255, nullable=true)
     */
    private $managementFeeFrequency1;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeFrequency2", type="string", length=255, nullable=true)
     */
    private $managementFeeFrequency2;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeFrequency3", type="string", length=255, nullable=true)
     */
    private $managementFeeFrequency3;

    /**
     * @var string
     *
     * @ORM\Column(name="managementFeeFrequency4", type="string", length=255, nullable=true)
     */
    private $managementFeeFrequency4;

    /**
     * @var int
     *
     * @ORM\Column(name="managementFeeAmount1", type="bigint", nullable=true)
     */
    private $managementFeeAmount1;

    /**
     * @var int
     *
     * @ORM\Column(name="managementFeeAmount2", type="bigint", nullable=true)
     */
    private $managementFeeAmount2;

    /**
     * @var int
     *
     * @ORM\Column(name="managementFeeAmount3", type="bigint", nullable=true)
     */
    private $managementFeeAmount3;

    /**
     * @var int
     *
     * @ORM\Column(name="managementFeeAmount4", type="bigint", nullable=true)
     */
    private $managementFeeAmount4;


    /**
     * #################################
     *              Relations
     * #################################
     */

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\FollowUp", mappedBy="quoteRequest", cascade={"all"})
     */
    private $followUps;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="quoteRequests")
     * @ORM\JoinColumn(name="userInChargeId")
     */
    private $userInCharge;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\QuoteRequest", inversedBy="children")
     * @ORM\JoinColumn(name="parentId", nullable=true)
     */
    private $parent;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MissionSheet", inversedBy="quoteRequests")
     * @ORM\JoinColumn(name="missionSheetId")
     */
    private $missionSheet;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PostalCode", inversedBy="quoteRequests")
     * @ORM\JoinColumn(name="postalCodeId")
     * @Assert\NotBlank(groups={"public_multisite"})
     */
    private $postalCode;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\QuoteRequest", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\QuoteRequestLine", mappedBy="quoteRequest")
     */
    private $quoteRequestLines;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\OtherNeed", mappedBy="quoteRequests")
     */
    private $otherNeeds;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\QuoteRequestFile", mappedBy="quoteRequest")
     */
    private $quoteRequestFiles;

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
        $this->children = new ArrayCollection();
        $this->otherNeeds = new ArrayCollection();
        $this->overallDiscount = 0;
        $this->isSameSignatory = false;
        $this->isSameAddress = false;
        $this->duration = 0;
        $this->quoteRequestFiles = new ArrayCollection();
        $this->followUps = new ArrayCollection();

        /**
         * Valeur par défaut des frais de gestion
         */
        $this->managementFeeCode1 = 'FGEST';
        $this->managementFeeCode2 = 'PASBAC';
        $this->managementFeeCode3 = 'FORFAIT';
        $this->managementFeeCode4 = null;
        $this->managementFeeDescription1 = 'Frais de gestion';
        $this->managementFeeDescription2 = 'Passage à vide';
        $this->managementFeeDescription3 = 'Facturation forfaitaire mensuelle';
        $this->managementFeeDescription4 = null;
        $this->managementFeeFrequency1 = 'MONTH';
        $this->managementFeeFrequency2 = null;
        $this->managementFeeFrequency3 = 'MONTH';
        $this->managementFeeFrequency4 = null;
        $this->managementFeeAmount1 = 35* 100;
        $this->managementFeeAmount2 = 30* 100;
        $this->managementFeeAmount3 = null;
        $this->managementFeeAmount4 = null;
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
     * @return bool
     */
    public function getIsSameAddress(): bool
    {
        return $this->isSameAddress;
    }

    /**
     * @param bool $isSameAddress
     * @return QuoteRequest
     */
    public function setIsSameAddress(bool $isSameAddress): self
    {
        $this->isSameAddress = $isSameAddress;
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
     * @return string|null
     */
    public function getAddressDetail()
    {
        return $this->addressDetail;
    }

    /**
     * @param string|null $addressDetail
     *
     * @return QuoteRequest
     */
    public function setAddressDetail($addressDetail = null)
    {
        $this->addressDetail = $addressDetail;

        return $this;
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
     * @return ?string
     */
    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    /**
     * @param string $billingAddress
     * @return QuoteRequest
     */
    public function setBillingAddress(string $billingAddress): self
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    /**
     * @return ?string
     */
    public function getBillingCity(): ?string
    {
        return $this->billingCity;
    }

    /**
     * @param string $billingCity
     * @return QuoteRequest
     */
    public function setBillingCity(string $billingCity): self
    {
        $this->billingCity = $billingCity;
        return $this;
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
     * Set parent.
     *
     * @param QuoteRequest|null $parent
     *
     * @return QuoteRequest
     */
    public function setParent(QuoteRequest $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return User|null
     */
    public function getParent()
    {
        return $this->parent;
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
     * Add child.
     *
     * @param QuoteRequest $child
     *
     * @return QuoteRequest
     */
    public function addChild(QuoteRequest $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     *
     * @param QuoteRequest $child
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeChild(QuoteRequest $child)
    {
        return $this->children->removeElement($child);
    }

    /**
     * Get children.
     *
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
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
     * @return string
     */
    public function getCatalog()
    {
        return $this->catalog;
    }

    /**
     * @param string $catalog
     * @return QuoteRequest
     */
    public function setCatalog($catalog)
    {
        $this->catalog = $catalog;
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
     * @return mixed
     */
    public function getBillingPostalCode()
    {
        return $this->billingPostalCode;
    }

    /**
     * @param mixed $billingPostalCode
     * @return QuoteRequest
     */
    public function setBillingPostalCode($billingPostalCode): self
    {
        $this->billingPostalCode = $billingPostalCode;
        return $this;
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

    /**
     * @return \DateTime|null
     */
    public function getPonctualDate(): ?\DateTime
    {
        return $this->ponctualDate;
    }

    public function setPonctualDate($ponctualDate): self
    {
        $this->ponctualDate = $ponctualDate;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate($startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getDepositDate(): ?\DateTime
    {
        return $this->depositDate;
    }

    public function setDepositDate($depositDate): self
    {
        $this->depositDate = $depositDate;

        return $this;
    }



    public function getResumptionDate(): ?\DateTime
    {
        return $this->resumptionDate;
    }

    public function setResumptionDate($resumptionDate): self
    {
        $this->resumptionDate = $resumptionDate;

        return $this;
    }

    public function getServiceEndDate(): ?\DateTime
    {
        return $this->serviceEndDate;
    }

    public function setServiceEndDate($serviceEndDate): self
    {
        $this->serviceEndDate = $serviceEndDate;

        return $this;
    }

    /**
     * @return Collection|QuoteRequestFile[]
     */
    public function getQuoteRequestFiles(): Collection
    {
        return $this->quoteRequestFiles;
    }

    public function addQuoteRequestFile(QuoteRequestFile $quoteRequestFile): self
    {
        if (!$this->quoteRequestFiles->contains($quoteRequestFile)) {
            $this->quoteRequestFiles[] = $quoteRequestFile;
            $quoteRequestFile->setQuoteRequest($this);
        }

        return $this;
    }

    public function removeQuoteRequestFile(QuoteRequestFile $quoteRequestFile): self
    {
        if ($this->quoteRequestFiles->removeElement($quoteRequestFile)) {
            // set the owning side to null (unless already changed)
            if ($quoteRequestFile->getQuoteRequest() === $this) {
                $quoteRequestFile->setQuoteRequest(null);
            }
        }

        return $this;
    }

    /**
     * Add followUp.
     *
     * @param FollowUp $followUp
     *
     * @return QuoteRequest
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

    /**
     * @return mixed
     */
    public function getMissionSheet()
    {
        return $this->missionSheet;
    }

    /**
     * @param mixed $missionSheet
     */
    public function setMissionSheet($missionSheet)
    {
        $this->missionSheet = $missionSheet;
    }

    /**
     * @return string
     */
    public function getManagementFeeCode1()
    {
        return $this->managementFeeCode1;
    }

    /**
     * @param string $managementFeeCode1
     * @return QuoteRequest
     */
    public function setManagementFeeCode1($managementFeeCode1 = null)
    {
        $this->managementFeeCode1 = $managementFeeCode1;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeCode2()
    {
        return $this->managementFeeCode2;
    }

    /**
     * @param string $managementFeeCode2
     * @return QuoteRequest
     */
    public function setManagementFeeCode2($managementFeeCode2 = null)
    {
        $this->managementFeeCode2 = $managementFeeCode2;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeCode3()
    {
        return $this->managementFeeCode3;
    }

    /**
     * @param string $managementFeeCode3
     * @return QuoteRequest
     */
    public function setManagementFeeCode3($managementFeeCode3 = null)
    {
        $this->managementFeeCode3 = $managementFeeCode3;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeCode4()
    {
        return $this->managementFeeCode4;
    }

    /**
     * @param string $managementFeeCode4
     * @return QuoteRequest
     */
    public function setManagementFeeCode4($managementFeeCode4 = null)
    {
        $this->managementFeeCode4 = $managementFeeCode4;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeDescription1()
    {
        return $this->managementFeeDescription1;
    }

    /**
     * @param string $managementFeeDescription1
     * @return QuoteRequest
     */
    public function setManagementFeeDescription1($managementFeeDescription1 = null)
    {
        $this->managementFeeDescription1 = $managementFeeDescription1;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeDescription2()
    {
        return $this->managementFeeDescription2;
    }

    /**
     * @param string $managementFeeDescription2
     * @return QuoteRequest
     */
    public function setManagementFeeDescription2($managementFeeDescription2 = null)
    {
        $this->managementFeeDescription2 = $managementFeeDescription2;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeDescription3()
    {
        return $this->managementFeeDescription3;
    }

    /**
     * @param string $managementFeeDescription3
     * @return QuoteRequest
     */
    public function setManagementFeeDescription3($managementFeeDescription3 = null)
    {
        $this->managementFeeDescription3 = $managementFeeDescription3;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeDescription4()
    {
        return $this->managementFeeDescription4;
    }

    /**
     * @param string $managementFeeDescription4
     * @return QuoteRequest
     */
    public function setManagementFeeDescription4($managementFeeDescription4 = null)
    {
        $this->managementFeeDescription4 = $managementFeeDescription4;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeFrequency1()
    {
        return $this->managementFeeFrequency1;
    }

    /**
     * @param string $managementFeeFrequency1
     * @return QuoteRequest
     */
    public function setManagementFeeFrequency1($managementFeeFrequency1 = null)
    {
        $this->managementFeeFrequency1 = $managementFeeFrequency1;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeFrequency2()
    {
        return $this->managementFeeFrequency2;
    }

    /**
     * @param string $managementFeeFrequency2
     * @return QuoteRequest
     */
    public function setManagementFeeFrequency2($managementFeeFrequency2 = null)
    {
        $this->managementFeeFrequency2 = $managementFeeFrequency2;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeFrequency3()
    {
        return $this->managementFeeFrequency3;
    }

    /**
     * @param string $managementFeeFrequency3
     * @return QuoteRequest
     */
    public function setManagementFeeFrequency3($managementFeeFrequency3 = null)
    {
        $this->managementFeeFrequency3 = $managementFeeFrequency3;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeFrequency4()
    {
        return $this->managementFeeFrequency4;
    }

    /**
     * @param string $managementFeeFrequency4
     * @return QuoteRequest
     */
    public function setManagementFeeFrequency4($managementFeeFrequency4 = null)
    {
        $this->managementFeeFrequency4 = $managementFeeFrequency4;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeAmount1()
    {
        return $this->managementFeeAmount1;
    }

    /**
     * @param string $managementFeeAmount1
     * @return QuoteRequest
     */
    public function setManagementFeeAmount1($managementFeeAmount1 = null)
    {
        $this->managementFeeAmount1 = $managementFeeAmount1;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeAmount2()
    {
        return $this->managementFeeAmount2;
    }

    /**
     * @param string $managementFeeAmount2
     * @return QuoteRequest
     */
    public function setManagementFeeAmount2($managementFeeAmount2 = null)
    {
        $this->managementFeeAmount2 = $managementFeeAmount2;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeAmount3()
    {
        return $this->managementFeeAmount3;
    }

    /**
     * @param string $managementFeeAmount3
     * @return QuoteRequest
     */
    public function setManagementFeeAmount3($managementFeeAmount3 = null)
    {
        $this->managementFeeAmount3 = $managementFeeAmount3;
        return $this;
    }

    /**
     * @return string
     */
    public function getManagementFeeAmount4()
    {
        return $this->managementFeeAmount4;
    }

    /**
     * @param string $managementFeeAmount4
     * @return QuoteRequest
     */
    public function setManagementFeeAmount4($managementFeeAmount4 = null)
    {
        $this->managementFeeAmount4 = $managementFeeAmount4;
        return $this;
    }

    



}
