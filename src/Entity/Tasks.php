<?php

namespace App\Entity;

use App\Repository\TasksRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TasksRepository::class)]
class Tasks
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\ManyToOne(fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdOn = null;

    #[ORM\ManyToOne(fetch: "EAGER")]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $closedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $closedOn = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskHistory::class)]
    private ?Collection $history;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskAssignee::class)]
    private Collection $taskAssignees;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskComments::class)]
    private Collection $comments;

    public function __construct()
    {
        $this->history = new ArrayCollection();
        $this->taskAssignees = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getClosedBy(): ?User
    {
        return $this->closedBy;
    }

    public function setClosedBy(?User $closedBy): self
    {
        $this->closedBy = $closedBy;

        return $this;
    }

    public function getCreatedOn(): ?\DateTimeInterface
    {
        return $this->createdOn;
    }

    public function setCreatedOn(\DateTimeInterface $createdOn): self
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function getClosedOn(): ?\DateTimeInterface
    {
        return $this->closedOn;
    }

    public function setClosedOn(?\DateTimeInterface $closedOn): self
    {
        $this->closedOn = $closedOn;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public static function allowedTypes(string $type): bool
    {
        return in_array($type, [ 'feature', 'bugfix', 'hotfix' ]);
    }

    public static function allowedStatuses(string $status): bool
    {
        return in_array($status, [ 'open', 'closed', 'in_dev', 'blocked', 'in_qa' ]);
    }

    /**
     * @return Collection<int, TaskHistory>
     */
    public function getHistory(): ?Collection
    {
        return $this->history;
    }

    public function addHistory(TaskHistory $history): self
    {
        if (!$this->history->contains($history)) {
            $this->history->add($history);
            $history->setTask($this);
        }

        return $this;
    }

    public function removeHistory(TaskHistory $history): self
    {
        if ($this->history->removeElement($history)) {
            // set the owning side to null (unless already changed)
            if ($history->getTask() === $this) {
                $history->setTask(null);
            }
        }

        return $this;
    }

    public function hideHistory(): void
    {
        unset($this->history);
    }

    /**
     * @return Collection<int, TaskAssignee>
     */
    public function getAssignees(): Collection
    {
        return $this->taskAssignees;
    }

    public function addTaskAssignee(TaskAssignee $taskAssignee): static
    {
        if (!$this->taskAssignees->contains($taskAssignee)) {
            $this->taskAssignees->add($taskAssignee);
            $taskAssignee->setTask($this);
        }

        return $this;
    }

    public function removeTaskAssignee(TaskAssignee $taskAssignee): static
    {
        if ($this->taskAssignees->removeElement($taskAssignee)) {
            // set the owning side to null (unless already changed)
            if ($taskAssignee->getTask() === $this) {
                $taskAssignee->setTask(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TaskComments>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(TaskComments $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTask($this);
        }

        return $this;
    }

    public function removeComment(TaskComments $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getTask() === $this) {
                $comment->setTask(null);
            }
        }

        return $this;
    }
}
