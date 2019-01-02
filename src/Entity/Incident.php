<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\IncidentRepository")
 */
class Incident
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $location;

    /**
     * @ORM\Column(type="datetime")
     */
    private $recorded_date;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Account", inversedBy="incidents")
     * @ORM\JoinColumn(nullable=true)
     */
    private $reporter;

    /**
     * @ORM\Column(type="string", length=2048, nullable=true)
     */
    private $details;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getRecordedDate(): ?\DateTimeInterface
    {
        return $this->recorded_date;
    }

    public function setRecordedDate(\DateTimeInterface $recorded_date): self
    {
        $this->recorded_date = $recorded_date;

        return $this;
    }

    public function getReporter(): ?Account
    {
        return $this->reporter;
    }

    public function setReporter(?Account $reporter): self
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
