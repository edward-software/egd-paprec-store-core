<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Range
 *
 * @ORM\Table(name="ranges")
 * @ORM\Entity(repositoryClass="App\Repository\RangeRepository")
 */
class Range
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
     * @var boolean
     *
     * @ORM\Column(name="isEnabled", type="boolean")
     */
    private $isEnabled;

    /**
     * @var int
     * @Assert\NotBlank()
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * @var string
     * Le catalogue du produit (REGULAR ou PONCTUAL)
     * @ORM\Column(name="catalog", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $catalog;

    /**
     * #################################
     *     SYSTEM USER ASSOCIATION
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
     * @ORM\OneToMany(targetEntity="App\Entity\Product", mappedBy="range")
     */
    private $products;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Picture", mappedBy="range", cascade={"all"})
     */
    private $pictures;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\RangeLabel", mappedBy="range", cascade={"all"})
     */
    private $rangeLabels;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->pictures = new ArrayCollection();
        $this->products = new ArrayCollection();
        $this->rangeLabels = new ArrayCollection();
        $this->catalog = 'REGULAR';
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Range
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateCreation(): \DateTime
    {
        return $this->dateCreation;
    }

    /**
     * @param \DateTime $dateCreation
     * @return Range
     */
    public function setDateCreation(\DateTime $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
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
     * @return Range
     */
    public function setUserCreation($userCreation): self
    {
        $this->userCreation = $userCreation;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateUpdate(): ?\DateTime
    {
        return $this->dateUpdate;
    }

    /**
     * @param \DateTime $dateUpdate
     * @return Range
     */
    public function setDateUpdate(\DateTime $dateUpdate): self
    {
        $this->dateUpdate = $dateUpdate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDeleted(): ?\DateTime
    {
        return $this->deleted;
    }

    /**
     * @param \DateTime $deleted
     * @return Range
     */
    public function setDeleted(\DateTime $deleted): self
    {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * @return null|bool
     */
    public function getIsEnabled(): ?bool
    {
        return $this->isEnabled;
    }

    /**
     * @param bool $isEnabled
     * @return Range
     */
    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * @param int $position
     * @return Range
     */
    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return string
     */
    public function getCatalog(): string
    {
        return $this->catalog;
    }

    /**
     * @param string $catalog
     * @return Range
     */
    public function setCatalog(string $catalog): self
    {
        $this->catalog = $catalog;
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
     * @return Range
     */
    public function setUserUpdate($userUpdate): self
    {
        $this->userUpdate = $userUpdate;
        return $this;
    }

    /**
     * Add quoteRequestLine.
     *
     * @param Product $product
     *
     * @return Range
     */
    public function addProduct(Product $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product.
     *
     * @param Product $product
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeProduct(Product $product)
    {
        return $this->products->removeElement($product);
    }

    /**
     * Get products.
     *
     * @return Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Add picture.
     *
     * @param Picture $picture
     *
     * @return Range
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
            if ($picture->getType() === 'PILOTPICTURE') {
                $pilotPictures[] = $picture;
            }
        }
        return $pilotPictures;
    }

    public function getPictos()
    {
        $pictos = array();
        foreach ($this->pictures as $picture) {
            if ($picture->getType() === 'PICTO') {
                $pictos[] = $picture;
            }
        }
        return $pictos;
    }

    public function getPicturesPictures()
    {
        $pictures = array();
        foreach ($this->pictures as $picture) {
            if ($picture->getType() === 'PICTURE') {
                $pictures[] = $picture;
            }
        }
        return $pictures;
    }

    /**
     * Add rangeLabel.
     *
     * @param RangeLabel $rangeLabel
     *
     * @return Range
     */
    public function addRangeLabel(RangeLabel $rangeLabel)
    {
        $this->rangeLabels[] = $rangeLabel;

        return $this;
    }

    /**
     * Remove rangeLabel.
     *
     * @param RangeLabel $rangeLabel
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeRangeLabel(RangeLabel $rangeLabel)
    {
        return $this->rangeLabels->removeElement($rangeLabel);
    }

    /**
     * Get rangeLabels.
     *
     * @return Collection
     */
    public function getRangeLabels()
    {
        return $this->rangeLabels;
    }

}
