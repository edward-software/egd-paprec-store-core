<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * OtherNeed
 *
 * @ORM\Table(name="otherNeeds")
 * @ORM\Entity(repositoryClass="App\Repository\OtherNeedRepository")
 */
class OtherNeed
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
     * @ORM\JoinColumn(name="userCreationId", referencedColumnName="id", nullable=false)
     */
    private $userCreation;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="userUpdateId", referencedColumnName="id", nullable=true)
     */
    private $userUpdate;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var bool
     *
     * @ORM\Column(name="isDisplayed", type="boolean")
     * @Assert\NotBlank()
     */
    private $isDisplayed;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $language;

    /**
     * @var string
     * Le catalogue du produit (REGULAR ou PONCTUAL)
     * @ORM\Column(name="catalog", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $catalog;


    /**
     * #################################
     *              Relations
     * #################################
     */

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Picture", mappedBy="otherNeed")
     */
    private $pictures;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Cart", inversedBy="otherNeeds")
     * @ORM\JoinTable(name="otherNeeds_carts",
     *      joinColumns={@ORM\JoinColumn(name="otherNeedId", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="cartId", referencedColumnName="id")}
     *      )
     */
    private $carts;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\QuoteRequest", inversedBy="otherNeeds")
     * @ORM\JoinTable(name="otherNeeds_quoteRequests",
     *      joinColumns={@ORM\JoinColumn(name="otherNeedId", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="quoteRequestId", referencedColumnName="id")}
     *      )
     */
    private $quoteRequests;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->pictures = new ArrayCollection();
        $this->carts = new ArrayCollection();
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
     * @return OtherNeed
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
     * @return OtherNeed
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
     * @return OtherNeed
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
     * Set isDisplayed.
     *
     * @param bool $isDisplayed
     *
     * @return OtherNeed
     */
    public function setIsDisplayed($isDisplayed)
    {
        $this->isDisplayed = $isDisplayed;

        return $this;
    }

    /**
     * Get isDisplayed.
     *
     * @return bool
     */
    public function getIsDisplayed()
    {
        return $this->isDisplayed;
    }

    /**
     * Set userCreation.
     *
     * @param User $userCreation
     *
     * @return OtherNeed
     */
    public function setUserCreation( User $userCreation)
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
     * @return OtherNeed
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
     * Set picture.
     *
     * @param Picture|null $picture
     *
     * @return OtherNeed
     */
    public function setPicture(Picture $picture = null)
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * Get picture.
     *
     * @return Picture|null
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return OtherNeed
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set language.
     *
     * @param string $language
     *
     * @return OtherNeed
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Add picture.
     *
     * @param Picture $picture
     *
     * @return OtherNeed
     */
    public function addPicture(Picture $picture)
    {
        $this->picture[] = $picture;

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
        return $this->picture->removeElement($picture);
    }

    /**
     * Get pictures.
     *
     * @return Collection
     */
    public function getPictures()
    {
        return $this->pictures;
    }

    /**
     * Add cart.
     *
     * @param Cart $cart
     *
     * @return OtherNeed
     */
    public function addCart(Cart $cart)
    {
        $this->carts[] = $cart;

        return $this;
    }

    /**
     * Remove cart.
     *
     * @param Cart $cart
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCart(Cart $cart)
    {
        return $this->carts->removeElement($cart);
    }

    /**
     * Get carts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCarts()
    {
        return $this->carts;
    }

    /**
     * Add quoteRequest.
     *
     * @param QuoteRequest $quoteRequest
     *
     * @return OtherNeed
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
     * @return Collection
     */
    public function getQuoteRequests()
    {
        return $this->quoteRequests;
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
     * @return OtherNeed
     */
    public function setCatalog(string $catalog): self
    {
        $this->catalog = $catalog;
        return $this;
    }

}
