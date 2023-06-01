<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MissionSheet
 *
 * @ORM\Table(name="missionSheets")
 * @ORM\Entity(repositoryClass="App\Repository\MissionSheetRepository")
 */
class MissionSheet
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
     *
     * @ORM\Column(name="contractType", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $contractType;

    /**
     * @var string
     *
     * @ORM\Column(name="mnemonicNumber", type="string", length=255, nullable=true)
     */
    private $mnemonicNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="contractNumber", type="string", length=255, nullable=true)
     */
    private $contractNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="orderNumber", type="string", length=255, nullable=true)
     */
    private $orderNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="billingType", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $billingType;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=500, nullable=true)
     */
    private $comment;

    /**
     * @var boolean
     *
     * @ORM\Column(name="myPaprecAccess", type="boolean")
     * @Assert\NotNull()
     */
    private $myPaprecAccess;

    /**
     * @var boolean
     *
     * @ORM\Column(name="wasteTrackingRegisterAccess", type="boolean")
     * @Assert\NotNull()
     */
    private $wasteTrackingRegisterAccess;

    /**
     * @var boolean
     *
     * @ORM\Column(name="reportingAccess", type="boolean")
     * @Assert\NotNull()
     */
    private $reportingAccess;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="quoteRequests")
     * @ORM\JoinColumn(name="secondUserInChargeId")
     */
    private $secondUserInCharge;


    /**
     * @ORM\OneToMany(targetEntity="App\Entity\QuoteRequest", mappedBy="missionSheet")
     */
    private $quoteRequests;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\MissionSheetLine", mappedBy="missionSheet")
     */
    private $missionSheetLines;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->quoteRequests = new ArrayCollection();
        $this->missionSheetLines = new ArrayCollection();
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
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * @param \DateTime $dateCreation
     * @return MissionSheet
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateUpdate()
    {
        return $this->dateUpdate;
    }

    /**
     * @param \DateTime $dateUpdate
     * @return MissionSheet
     */
    public function setDateUpdate($dateUpdate)
    {
        $this->dateUpdate = $dateUpdate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserCreation()
    {
        return $this->userCreation;
    }

    /**
     * @param mixed $userCreation
     * @return MissionSheet
     */
    public function setUserCreation($userCreation)
    {
        $this->userCreation = $userCreation;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserUpdate()
    {
        return $this->userUpdate;
    }

    /**
     * @param mixed $userUpdate
     * @return MissionSheet
     */
    public function setUserUpdate($userUpdate)
    {
        $this->userUpdate = $userUpdate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param \DateTime $deleted
     * @return MissionSheet
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * Set secondUserInCharge.
     *
     * @param User|null $secondUserInCharge
     *
     * @return MissionSheet
     */
    public function setSecondUserInCharge(User $secondUserInCharge = null)
    {
        $this->secondUserInCharge = $secondUserInCharge;

        return $this;
    }

    /**
     * Get secondUserInCharge.
     *
     * @return User|null
     */
    public function getSecondUserInCharge()
    {
        return $this->secondUserInCharge;
    }

    /**
     * @return string
     */
    public function getContractType()
    {
        return $this->contractType;
    }

    /**
     * @param string $contractType
     */
    public function setContractType(string $contractType)
    {
        $this->contractType = $contractType;
    }

    /**
     * @return string
     */
    public function getMnemonicNumber()
    {
        return $this->mnemonicNumber;
    }

    /**
     * @param string $mnemonicNumber
     */
    public function setMnemonicNumber($mnemonicNumber)
    {
        $this->mnemonicNumber = $mnemonicNumber;
    }

    /**
     * @return string
     */
    public function getContractNumber()
    {
        return $this->contractNumber;
    }

    /**
     * @param string $contractNumber
     */
    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;
    }

    /**
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @param string $orderNumber
     */
    public function setOrderNumber(string $orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * @return string
     */
    public function getBillingType()
    {
        return $this->billingType;
    }

    /**
     * @param string $billingType
     */
    public function setBillingType(string $billingType)
    {
        $this->billingType = $billingType;
    }

    /**
     * @return bool
     */
    public function isMyPaprecAccess()
    {
        return $this->myPaprecAccess;
    }

    /**
     * @param bool $myPaprecAccess
     */
    public function setMyPaprecAccess(bool $myPaprecAccess)
    {
        $this->myPaprecAccess = $myPaprecAccess;
    }

    /**
     * @return bool
     */
    public function isWasteTrackingRegisterAccess()
    {
        return $this->wasteTrackingRegisterAccess;
    }

    /**
     * @param bool $wasteTrackingRegisterAccess
     */
    public function setWasteTrackingRegisterAccess(bool $wasteTrackingRegisterAccess)
    {
        $this->wasteTrackingRegisterAccess = $wasteTrackingRegisterAccess;
    }

    /**
     * @return bool
     */
    public function isReportingAccess()
    {
        return $this->reportingAccess;
    }

    /**
     * @param bool $reportingAccess
     */
    public function setReportingAccess(bool $reportingAccess)
    {
        $this->reportingAccess = $reportingAccess;
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
     * @return Collection|QuoteRequest[]
     */
    public function getQuoteRequests(): Collection
    {
        return $this->quoteRequests;
    }

    public function addQuoteRequest(QuoteRequest $quoteRequest): self
    {
        if (!$this->quoteRequests->contains($quoteRequest)) {
            $this->quoteRequests[] = $quoteRequest;
            $quoteRequest->setMissionSheet($this);
        }

        return $this;
    }

    public function removeQuoteRequest(QuoteRequest $quoteRequest): self
    {
        if ($this->quoteRequests->removeElement($quoteRequest)) {
            // set the owning side to null (unless already changed)
            if ($quoteRequest->getMissionSheet() === $this) {
                $quoteRequest->setMissionSheet(null);
            }
        }

        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Add missionSheetLine.
     *
     * @param MissionSheetLine $missionSheetLine
     *
     * @return MissionSheet
     */
    public function addMissionSheetLine(MissionSheetLine $missionSheetLine)
    {
        $this->missionSheetLines[] = $missionSheetLine;

        return $this;
    }

    /**
     * Remove missionSheetLine.
     *
     * @param MissionSheetLine $missionSheetLine
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeMissionSheetLine(MissionSheetLine $missionSheetLine)
    {
        return $this->missionSheetLines->removeElement($missionSheetLine);
    }

    /**
     * Get missionSheetLines.
     *
     * @return Collection
     */
    public function getMissionSheetLines()
    {
        return $this->missionSheetLines;
    }
}
