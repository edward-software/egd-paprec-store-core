<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CustomArea
 *
 * @ORM\Table(name="customAreas")
 * @ORM\Entity(repositoryClass="App\Repository\CustomAreaRepository")
 * @UniqueEntity(
 *     fields={"code", "language"},
 *     message="Ce code existe déjà pour cette langue",
 *     payload="codeLanguageAlreadyExists"
 * )
 */
class CustomArea
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
     * @var bool
     *
     * @ORM\Column(name="isDisplayed", type="boolean")
     * @Assert\NotBlank()
     */
    private $isDisplayed;

    /**
     * @var string
     *
     * @ORM\Column(name="leftContent", type="text")
     * @Assert\NotNull()
     */
    private $leftContent;

    /**
     * @var string
     *
     * @ORM\Column(name="rightContent", type="text", nullable=true)
     */
    private $rightContent;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=50)
     * @Assert\NotNull()
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $language;

    /*********************************
     * RELATIONS
     ********************************/

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Picture", mappedBy="customArea", cascade={"all"})
     */
    private $pictures;


    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->pictures = new ArrayCollection();
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
     * @return CustomArea
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
     * @return CustomArea
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
     * Set leftContent.
     *
     * @param string $leftContent
     *
     * @return CustomArea
     */
    public function setLeftContent($leftContent)
    {
        $this->leftContent = $leftContent;

        return $this;
    }

    /**
     * Get leftContent.
     *
     * @return string
     */
    public function getLeftContent()
    {
        return $this->leftContent;
    }

    /**
     * Set rightContent.
     *
     * @param string $rightContent
     *
     * @return CustomArea
     */
    public function setRightContent($rightContent)
    {
        $this->rightContent = $rightContent;

        return $this;
    }

    /**
     * Get rightContent.
     *
     * @return string
     */
    public function getRightContent()
    {
        return $this->rightContent;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return CustomArea
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
     * Set language.
     *
     * @param string $language
     *
     * @return CustomArea
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
     * Set userCreation.
     *
     * @param User $userCreation
     *
     * @return CustomArea
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
     * Add picture.
     *
     * @param Picture $picture
     *
     * @return CustomArea
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
     * @return Collection
     */
    public function getPictures()
    {
        return $this->pictures;
    }

    public function getLeftPictures()
    {
        $leftPictures = array();
        foreach ($this->pictures as $picture) {
            if ($picture->getType() === 'LEFT') {
                $leftPictures[] = $picture;
            }
        }
        return $leftPictures;
    }

    public function getRightPictures()
    {
        $rightPictures = array();
        foreach ($this->pictures as $picture) {
            if ($picture->getType() === 'RIGHT') {
                $rightPictures[] = $picture;
            }
        }
        return $rightPictures;
    }


    /**
     * Set deleted.
     *
     * @param \DateTime|null $deleted
     *
     * @return CustomArea
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
     * @return CustomArea
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
     * Set userUpdate.
     *
     * @param User|null $userUpdate
     *
     * @return CustomArea
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
}
