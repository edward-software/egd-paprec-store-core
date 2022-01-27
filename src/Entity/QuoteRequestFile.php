<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Picture
 *
 * @ORM\Table(name="quoteRequestFiles")
 * @ORM\Entity(repositoryClass="App\Repository\QuoteRequestFileRepository")
 */
class QuoteRequestFile
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
     * #################################
     *              Relations
     * #################################
     */

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\QuoteRequest", inversedBy="quoteRequestFiles")
     * @ORM\JoinColumn(name="quoteRequestId")
     */
    private $quoteRequest;

    public function getId()
    {
        return $this->id;
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getQuoteRequest(): ?QuoteRequest
    {
        return $this->quoteRequest;
    }

    public function setQuoteRequest(?QuoteRequest $quoteRequest): self
    {
        $this->quoteRequest = $quoteRequest;

        return $this;
    }
}
