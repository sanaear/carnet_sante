<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\MedecinRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedecinRepository::class)]
#[ORM\Table(name: 'medecin')]
class Medecin extends User
{

    #[ORM\Column(length: 50)]
    private ?string $specialite = null;

    #[ORM\Column(length: 50)]
    private ?string $numRegistration = null;

  

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(string $specialite): static
    {
        $this->specialite = $specialite;

        return $this;
    }

    public function getNumRegistration(): ?string
    {
        return $this->numRegistration;
    }

    public function setNumRegistration(string $numRegistration): static
    {
        $this->numRegistration = $numRegistration;

        return $this;
    }
    public function __construct()
    {
        $this->role = 'medecin';
        $this->consultations = new ArrayCollection();
    }
}
