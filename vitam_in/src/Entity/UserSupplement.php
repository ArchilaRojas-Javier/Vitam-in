<?php

namespace App\Entity;

use App\Repository\UserSupplementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSupplementRepository::class)]
class UserSupplement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private array $dosage_schedule = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $start_date = null;

    #[ORM\Column]
    private ?int $duration_days = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $precautions = null;

    #[ORM\ManyToOne(inversedBy: 'userSupplements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'usersupplement')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Supplement $supplement = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'userSupplement', orphanRemoval: true)]
    private Collection $notes;

    /**
     * @var Collection<int, Reminder>
     */
    #[ORM\OneToMany(targetEntity: Reminder::class, mappedBy: 'userSupplement', orphanRemoval: true)]
    private Collection $reminders;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->reminders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDosageSchedule(): array
    {
        return $this->dosage_schedule;
    }

    public function setDosageSchedule(array $dosage_schedule): static
    {
        $this->dosage_schedule = $dosage_schedule;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->start_date;
    }

    public function setStartDate(\DateTimeImmutable $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getDurationDays(): ?int
    {
        return $this->duration_days;
    }

    public function setDurationDays(int $duration_days): static
    {
        $this->duration_days = $duration_days;

        return $this;
    }

    public function getPrecautions(): ?string
    {
        return $this->precautions;
    }

    public function setPrecautions(?string $precautions): static
    {
        $this->precautions = $precautions;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSupplement(): ?Supplement
    {
        return $this->supplement;
    }

    public function setSupplement(?Supplement $supplement): static
    {
        $this->supplement = $supplement;

        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setUserSupplement($this);
        }

        return $this;
    }

    public function removeNote(Note $note): static
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getUserSupplement() === $this) {
                $note->setUserSupplement(null);
            }
        }

        return $this;
    }
    /**
     * @return Collection<int, Reminder>
     */
    public function getReminders(): Collection
    {
        return $this->reminders;
    }

    public function addReminder(Reminder $reminder): static
    {
        if (!$this->reminders->contains($reminder)) {
            $this->reminders->add($reminder);
            $reminder->setUserSupplement($this);
        }
        return $this;
    }

    public function removeReminder(Reminder $reminder): static
    {
        if ($this->reminders->removeElement($reminder)) {
            if ($reminder->getUserSupplement() === $this) {
                $reminder->setUserSupplement(null);
            }
        }
        return $this;
    }
    public function getEndDate(): ?\DateTimeImmutable
    {
        if ($this->start_date === null || $this->duration_days === null) {
            return null;
        }
        return $this->start_date->modify(sprintf('+%d days', $this->duration_days));
    }
}
