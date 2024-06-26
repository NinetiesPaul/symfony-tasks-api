<?php

namespace App\Entity;

use App\Repository\TaskHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: TaskHistoryRepository::class)]
class TaskHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $field = null;

    #[ORM\Column(length: 255)]
    private ?string $changedFrom = null;

    #[ORM\Column(length: 255)]
    private ?string $changedTo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $changedOn = null;

    #[ORM\ManyToOne(fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $changedBy = null;

    #[ORM\ManyToOne(fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?Tasks $task = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getChangedFrom(): ?string
    {
        return $this->changedFrom;
    }

    public function setChangedFrom(string $changedFrom): self
    {
        $this->changedFrom = $changedFrom;

        return $this;
    }

    public function getChangedTo(): ?string
    {
        return $this->changedTo;
    }

    public function setChangedTo(string $changedTo): self
    {
        $this->changedTo = $changedTo;

        return $this;
    }

    public function getChangedOn(): ?\DateTimeInterface
    {
        return $this->changedOn;
    }

    public function setChangedOn(\DateTimeInterface $changedOn): self
    {
        $this->changedOn = $changedOn;

        return $this;
    }

    public function getChangedBy(): ?User
    {
        return $this->changedBy;
    }

    public function setChangedBy(?User $changedBy): self
    {
        $this->changedBy = $changedBy;

        return $this;
    }

    public function getTask(): ?Tasks
    {
        return $this->task;
    }

    public function setTask(?Tasks $task): self
    {
        $this->task = $task;

        return $this;
    }
}
