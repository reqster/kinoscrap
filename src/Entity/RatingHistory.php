<?php

namespace App\Entity;

use App\Repository\RatingHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=RatingHistoryRepository::class)
 *
 * @ORM\HasLifecycleCallbacks
 *
 * @UniqueEntity(
 *     fields={"movieId", "scrapeDate"},
 *     message="This movie has already been scraped at this date."
 * )
 */
class RatingHistory
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Movie")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private Movie $movie;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private $scrapeDate;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $value;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $votes;

    /**
     * @ORM\Column(type="boolean")
     */
    private $activeScrape;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function setMovie(Movie $movie): self
    {
        $this->movie = $movie;

        return $this;
    }

    public function getScrapeDate(): ?\DateTimeInterface
    {
        return $this->scrapeDate;
    }

    public function setScrapeDate(\DateTimeInterface $scrapeDate): self
    {
        $this->scrapeDate = $scrapeDate;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(?float $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getVotes(): ?int
    {
        return $this->votes;
    }

    public function setVotes(?int $votes): self
    {
        $this->votes = $votes;

        return $this;
    }

    public function isActiveScrape(): ?bool
    {
        return $this->activeScrape;
    }

    public function setActiveScrape(bool $activeScrape): self
    {
        $this->activeScrape = $activeScrape;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->scrapeDate = new \DateTime('now');
    }
}
