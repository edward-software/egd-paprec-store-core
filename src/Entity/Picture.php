<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Picture
 *
 * @ORM\Table(name="pictures")
 * @ORM\Entity(repositoryClass="App\Repository\PictureRepository")
 */
class Picture
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
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
     * #################################
     *              Relations
     * #################################
     */

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="pictures")
     */
    private $product;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Range", inversedBy="pictures")
     */
    private $range;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CustomArea", inversedBy="pictures")
     */
    private $customArea;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OtherNeed", inversedBy="pictures")
     */
    private $otherNeed;

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
     * Set path.
     *
     * @param string $path
     *
     * @return Picture
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Picture
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set product.
     *
     * @param Product|null $product
     *
     * @return Picture
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product.
     *
     * @return Product|null
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set Range.
     *
     * @param Range|null $range
     *
     * @return Picture
     */
    public function setRange(Range $range = null)
    {
        $this->range = $range;

        return $this;
    }

    /**
     * Get range.
     *
     * @return Range|null
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * Set customArea.
     *
     * @param CustomArea|null $customArea
     *
     * @return Picture
     */
    public function setCustomArea(CustomArea $customArea = null)
    {
        $this->customArea = $customArea;

        return $this;
    }

    /**
     * Get customArea.
     *
     * @return CustomArea|null
     */
    public function getCustomArea()
    {
        return $this->customArea;
    }

    /**
     * Set otherNeed.
     *
     * @param OtherNeed|null $otherNeed
     *
     * @return Picture
     */
    public function setOtherNeed(OtherNeed $otherNeed = null)
    {
        $this->otherNeed = $otherNeed;

        return $this;
    }

    /**
     * Get otherNeed.
     *
     * @return OtherNeed|null
     */
    public function getOtherNeed()
    {
        return $this->otherNeed;
    }
}
