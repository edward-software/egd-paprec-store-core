<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * @ORM\Table(name="followUps")
 * @ORM\Entity(repositoryClass="App\Repository\FollowUpRepository")
 */
class FollowUp
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
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=false)
     * @Assert\NotBlank()
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

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

    /**************************************************************************************************
     * RELATIONS
     */
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\QuoteRequest", inversedBy="followUps")
     * @ORM\JoinColumn(name="quoteRequestId", referencedColumnName="id", nullable=false)
     * @Assert\NotBlank()
     */
    private $quoteRequest;

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
     * @ORM\OneToMany(targetEntity="App\Entity\FollowUpFile", mappedBy="followUp")
     */
    private $followUpFiles;

    public function __construct()
    {
        $this->dateCreation = new \DateTime;
        $this->followUpFiles = new ArrayCollection();
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }


    /**
     * @param $dateCreation
     * @return $this
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * @param $dateUpdate
     * @return $this
     */
    public function setDateUpdate($dateUpdate)
    {
        $this->dateUpdate = $dateUpdate;

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
     * @param $deleted
     * @return $this
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

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
     * @param User|null $userCreation
     * @return $this
     */
    public function setUserCreation(User $userCreation = null)
    {
        $this->userCreation = $userCreation;

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
     * @param User|null $userUpdate
     * @return $this
     */
    public function setUserUpdate(User $userUpdate = null)
    {
        $this->userUpdate = $userUpdate;

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
     * @param $date
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
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
     * @param QuoteRequest $quoteRequest
     * @return $this
     */
    public function setQuoteRequest(QuoteRequest $quoteRequest)
    {
        $this->quoteRequest = $quoteRequest;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuoteRequest()
    {
        return $this->quoteRequest;
    }

    /**
     * @return Collection|FollowUpFile[]
     */
    public function getFollowUpFiles(): Collection
    {
        return $this->followUpFiles;
    }

    public function addFollowUpFile(FollowUpFile $followUpFile): self
    {
        if (!$this->followUpFiles->contains($followUpFile)) {
            $this->followUpFiles[] = $followUpFile;
            $followUpFile->setFollowUp($this);
        }

        return $this;
    }

    public function removeFollowUpFile(FollowUpFile $followUpFile): self
    {
        if ($this->followUpFiles->removeElement($followUpFile)) {
            // set the owning side to null (unless already changed)
            if ($followUpFile->getFollowUp() === $this) {
                $followUpFile->setFollowUp(null);
            }
        }

        return $this;
    }
}
