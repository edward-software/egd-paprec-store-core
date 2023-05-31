<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MissionSheetLine
 *
 * @ORM\Table(name="missionSheetLines")
 * @ORM\Entity(repositoryClass="App\Repository\MissionSheetLineRepository")
 */
class MissionSheetLine
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

    /**************************************************************************************************
     * RELATIONS
     */

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MissionSheetProduct")
     * @ORM\JoinColumn(name="missionSheetProductId", referencedColumnName="id", nullable=false)
     */
    private $missionSheetProduct;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Agency")
     * @ORM\JoinColumn(name="agencyId", referencedColumnName="id", nullable=true)
     */
    private $agency;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\MissionSheet", inversedBy="missionSheetLines")
     * @ORM\JoinColumn(name="missionSheetId", referencedColumnName="id", nullable=false)
     */
    private $missionSheet;

    /**
     * MissionSheetLine constructor.
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
     * @return MissionSheetLine
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
     * @return MissionSheetLine
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
     * @return MissionSheetLine
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
     * Set missionSheetProduct.
     *
     * @param MissionSheetProduct $missionSheetProduct
     *
     * @return MissionSheetLine
     */
    public function setMissionSheetProduct(MissionSheetProduct $missionSheetProduct)
    {
        $this->missionSheetProduct = $missionSheetProduct;

        return $this;
    }

    /**
     * Get missionSheetProduct.
     *
     * @return MissionSheetProduct
     */
    public function getMissionSheetProduct()
    {
        return $this->missionSheetProduct;
    }

    /**
     * Set agency.
     *
     * @param Agency $agency
     *
     * @return MissionSheetLine
     */
    public function setAgency(Agency $agency)
    {
        $this->agency = $agency;

        return $this;
    }

    /**
     * Get missionSheetProduct.
     *
     * @return Agency
     */
    public function getAgency()
    {
        return $this->agency;
    }

    /**
     * Set missionSheet.
     *
     * @param MissionSheet $missionSheet
     *
     * @return MissionSheetLine
     */
    public function setMissionSheet(MissionSheet $missionSheet)
    {
        $this->missionSheet = $missionSheet;

        return $this;
    }

    /**
     * Get missionSheet.
     *
     * @return MissionSheet
     */
    public function getMissionSheet()
    {
        return $this->missionSheet;
    }
}
