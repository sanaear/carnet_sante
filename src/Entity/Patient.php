<?php

namespace App\Entity;

use App\Repository\PatientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PatientRepository::class)]
#[ORM\Table(name: 'patient')]
class Patient extends User
{
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $bloodType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $allergies = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: Consultation::class)]
    private Collection $consultations;

    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: MedicalFile::class)]
    private Collection $medicalFiles;

    public function __construct()
    {
        parent::__construct();
        $this->consultations = new ArrayCollection();
        $this->medicalFiles = new ArrayCollection();
        $this->roles[] = 'ROLE_PATIENT';
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getBloodType(): ?string
    {
        return $this->bloodType;
    }

    public function setBloodType(?string $bloodType): static
    {
        $this->bloodType = $bloodType;
        return $this;
    }

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setAllergies(?string $allergies): static
    {
        $this->allergies = $allergies;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return Collection<int, Consultation>
     */
    public function getConsultations(): Collection
    {
        return $this->consultations;
    }

    public function addConsultation(Consultation $consultation): static
    {
        if (!$this->consultations->contains($consultation)) {
            $this->consultations->add($consultation);
            $consultation->setPatient($this);
        }

        return $this;
    }

    public function removeConsultation(Consultation $consultation): static
    {
        if ($this->consultations->removeElement($consultation)) {
            // set the owning side to null (unless already changed)
            if ($consultation->getPatient() === $this) {
                $consultation->setPatient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MedicalFile>
     */
    public function getMedicalFiles(): Collection
    {
        return $this->medicalFiles;
    }

    public function addMedicalFile(MedicalFile $medicalFile): static
    {
        if (!$this->medicalFiles->contains($medicalFile)) {
            $this->medicalFiles->add($medicalFile);
            $medicalFile->setPatient($this);
        }
        return $this;
    }

    public function removeMedicalFile(MedicalFile $medicalFile): static
    {
        if ($this->medicalFiles->removeElement($medicalFile)) {
            if ($medicalFile->getPatient() === $this) {
                $medicalFile->setPatient(null);
            }
        }
        return $this;
    }
}
