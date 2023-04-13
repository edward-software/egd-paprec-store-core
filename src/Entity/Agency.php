<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Agency
 *
 * @ORM\Table(name="agencies")
 * @ORM\Entity(repositoryClass="App\Repository\AgencyRepository")
 */
class Agency
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
     * @ORM\Column(name="legalInfoTemplate", type="text", nullable=true)
     */
    private $legalInfoTemplate;

    /**
     * @ORM\Column(name="destinationEmailMission", type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Email(
     *     groups={"public"},
     *      message = "email_error"
     * )
     */
    private $destinationEmailMission;

    /**
     * @var string
     * @ORM\Column(name="entityName", type="string", nullable=true)
     */
    private $entityName;

    /**
     * @var string
     * Le nom de l'agence
     * @ORM\Column(name="name", type="string")
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     * La raison sociale de l'agence
     * @ORM\Column(name="businessName", type="string")
     * @Assert\NotBlank()
     */
    private $businessName;

    /**
     * @var string
     * L'identification société de l'agence
     * @ORM\Column(name="businessId", type="string")
     * @Assert\NotBlank()
     */
    private $businessId;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="text", nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="postalCode", type="string", length=255, nullable=true, nullable=true)
     */
    private $postalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="phoneNumber", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"public"})
     */
    private $phoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="faxNumber", type="string", length=255, nullable=true)
     * @Assert\NotBlank(groups={"public"})
     */
    private $faxNumber;


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
     * @ORM\OneToMany(targetEntity="App\Entity\PostalCode", mappedBy="agency")
     */
    private $postalCodes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Picture", mappedBy="agency", cascade={"all"})
     */
    private $pictures;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="agency", cascade={"all"})
     */
    private $users;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->postalCodes = new ArrayCollection();
        $this->users = new ArrayCollection();
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
     * @return Agency
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
     * @return Agency
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
     * @return Agency
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
     * @return Agency
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
     * @return Agency
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Agency
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param string $entityName
     * @return Agency
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
        return $this;
    }

    /**
     * @return string
     */
    public function getDestinationEmailMission()
    {
        return $this->destinationEmailMission;
    }

    /**
     * @param string $destinationEmailMission
     * @return Agency
     */
    public function setDestinationEmailMission($destinationEmailMission)
    {
        $this->destinationEmailMission = $destinationEmailMission;
        return $this;
    }

    /**
     * @return string
     */
    public function getLegalInfoTemplate()
    {
        return $this->legalInfoTemplate;
    }

    /**
     * @param string $legalInfoTemplate
     * @return Agency
     */
    public function setLegalInfoTemplate($legalInfoTemplate)
    {
        $this->legalInfoTemplate = $legalInfoTemplate;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    /**
     * @param string $businessName
     * @return Agency
     */
    public function setBusinessName(string $businessName): self
    {
        $this->businessName = $businessName;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getBusinessId(): ?string
    {
        return $this->businessId;
    }

    /**
     * @param string $businessId
     * @return Agency
     */
    public function setBusinessId(string $businessId): self
    {
        $this->businessId = $businessId;
        return $this;
    }

    /**
     * Add postalCode.
     *
     * @param PostalCode $postalCode
     *
     * @return PostalCode
     */
    public function addPostalCode(PostalCode $postalCode)
    {
        $this->postalCodes[] = $postalCode;

        return $this;
    }

    /**
     * Remove postalCode.
     *
     * @param PostalCode $postalCode
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePostalCode(PostalCode $postalCode)
    {
        return $this->postalCodes->removeElement($postalCode);
    }

    /**
     * Get postalCodes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPostalCodes()
    {
        return $this->postalCodes;
    }

    /**
     * @return null|string
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return Agency
     */
    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return Agency
     */
    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     * @return Agency
     */
    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    /**
     * Add picture.
     *
     * @param Picture $picture
     *
     * @return Agency
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
     * Add user.
     *
     * @param User $user
     *
     * @return User
     */
    public function addUser(User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user.
     *
     * @param User $user
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUser(User $user)
    {
        return $this->users->removeElement($user);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     * @return Agency
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     */
    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return string
     */
    public function getFaxNumber()
    {
        return $this->faxNumber;
    }

    /**
     * @param string $faxNumber
     */
    public function setFaxNumber(string $faxNumber): void
    {
        $this->faxNumber = $faxNumber;
    }



}
