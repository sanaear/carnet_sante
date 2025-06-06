<?php

namespace App\Entity;

use App\Repository\DoctorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctorRepository::class)]
class Doctor extends User
{
    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column(length: 100)]
    private ?string $speciality = null;

    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: Consultation::class)]
    private Collection $consultations;

    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: MedicalFile::class)]
    private Collection $medicalFiles;

    public function __construct()
    {
        parent::__construct();
        $this->consultations = new ArrayCollection();
        $this->medicalFiles = new ArrayCollection();
        $this->roles[] = 'ROLE_DOCTOR';
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getSpeciality(): ?string
    {
        return $this->speciality;
    }

    public function setSpeciality(string $speciality): static
    {
        $this->speciality = $speciality;
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
            $consultation->setDoctor($this);
        }
        return $this;
    }

    public function removeConsultation(Consultation $consultation): static
    {
        if ($this->consultations->removeElement($consultation)) {
            if ($consultation->getDoctor() === $this) {
                $consultation->setDoctor(null);
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
            $medicalFile->setDoctor($this);
        }
        return $this;
    }

    public function removeMedicalFile(MedicalFile $medicalFile): static
    {
        if ($this->medicalFiles->removeElement($medicalFile)) {
            if ($medicalFile->getDoctor() === $this) {
                $medicalFile->setDoctor(null);
            }
        }
        return $this;
    }
} 