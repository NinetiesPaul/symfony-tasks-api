<?php

namespace App\Entity;

use App\Repository\TaskAssigneeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: TaskAssigneeRepository::class)]
class TaskAssignee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(fetch: "EAGER")]
    private ?User $assignedBy = null;

    #[ORM\ManyToOne(fetch: "EAGER")]
    private ?User $assignedTo = null;

    #[ORM\ManyToOne(inversedBy: 'taskAssignees')]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?Tasks $task = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAssignedBy(): ?User
    {
        return $this->assignedBy;
    }

    public function setAssignedBy(?User $assignedBy): static
    {
        $this->assignedBy = $assignedBy;

        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    public function getTask(): ?tasks
    {
        return $this->task;
    }

    public function setTask(?tasks $task): static
    {
        $this->task = $task;

        return $this;
    }
}
