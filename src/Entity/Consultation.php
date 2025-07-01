<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
class Consultation
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_SCHEDULED => 'Planifiée',
            self::STATUS_COMPLETED => 'Terminée',
            self::STATUS_CANCELLED => 'Annulée',
        ];
    }
    
    public static function getDefaultStatus(): string
    {
        return self::STATUS_PENDING;
    }
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Doctor $doctor = null;

    #[ORM\ManyToOne(inversedBy: 'consultations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Patient $patient = null;

    #[ORM\OneToOne(mappedBy: 'consultation', targetEntity: Ordonnance::class, cascade: ['persist', 'remove'])]
    private ?Ordonnance $ordonnance = null; // Will be set via setOrdonnance
    
    #[ORM\OneToMany(mappedBy: 'consultation', targetEntity: MedicalFile::class)]
    private Collection $medicalFiles;
    
    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startTime = null;
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endTime = null;
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;
    
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->date = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->startTime = $now;
        $this->endTime = $now->modify('+30 minutes');
        $this->status = self::STATUS_SCHEDULED;
        $this->ordonnance = null;
        $this->notes = '';
        $this->medicalFiles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        if ($date instanceof \DateTime) {
            $date = \DateTimeImmutable::createFromMutable($date);
        }
        $this->date = $date;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDoctor(): ?Doctor
    {
        return $this->doctor;
    }

    public function setDoctor(?Doctor $doctor): static
    {
        $this->doctor = $doctor;
        return $this;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;
        return $this;
    }

    public function getOrdonnance(): ?Ordonnance
    {
        return $this->ordonnance;
    }

    public function setOrdonnance(?Ordonnance $ordonnance): static
    {
        // If the ordonnance is already set to this value, do nothing
        if ($this->ordonnance === $ordonnance) {
            return $this;
        }

        // If we're setting to null, handle the cleanup
        if ($ordonnance === null) {
            $oldOrdonnance = $this->ordonnance;
            $this->ordonnance = null;
            
            if ($oldOrdonnance !== null && $oldOrdonnance->getConsultation() === $this) {
                $oldOrdonnance->setConsultation(null);
            }
            
            return $this;
        }

        // If we're setting a new ordonnance
        
        // First, detach from the current ordonnance if it exists
        if ($this->ordonnance !== null) {
            $currentOrdonnance = $this->ordonnance;
            $this->ordonnance = null;
            
            if ($currentOrdonnance->getConsultation() === $this) {
                $currentOrdonnance->setConsultation(null);
            }
        }

        // Set the new ordonnance
        $this->ordonnance = $ordonnance;
        
        // Update the inverse side if needed
        if ($ordonnance->getConsultation() !== $this) {
            $ordonnance->setConsultation($this);
        }

        return $this;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function setStatus(string $status): self
    {
        if (!in_array($status, array_keys(self::getStatuses()))) {
            throw new \InvalidArgumentException(sprintf('Invalid status "%s"', $status));
        }
        $this->status = $status;
        return $this;
    }
    
    public function getNotes(): ?string
    {
        return $this->notes;
    }
    
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }
    
    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }
    
    public function setStartTime(\DateTimeInterface $startTime): self
    {
        if ($startTime instanceof \DateTime) {
            $startTime = \DateTimeImmutable::createFromMutable($startTime);
        }
        $this->startTime = $startTime;
        return $this;
    }
    
    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }
    
    public function setEndTime(\DateTimeInterface $endTime): self
    {
        if ($endTime instanceof \DateTime) {
            $endTime = \DateTimeImmutable::createFromMutable($endTime);
        }
        $this->endTime = $endTime;
        return $this;
    }
    
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    
    /**
     * @return Collection<int, MedicalFile>
     */
    public function getMedicalFiles(): Collection
    {
        return $this->medicalFiles;
    }

    public function addMedicalFile(MedicalFile $medicalFile): self
    {
        if (!$this->medicalFiles->contains($medicalFile)) {
            $this->medicalFiles[] = $medicalFile;
            $medicalFile->setConsultation($this);
        }

        return $this;
    }

    public function removeMedicalFile(MedicalFile $medicalFile): self
    {
        if ($this->medicalFiles->removeElement($medicalFile)) {
            // set the owning side to null (unless already changed)
            if ($medicalFile->getConsultation() === $this) {
                $medicalFile->setConsultation(null);
            }
        }

        return $this;
    }
} 