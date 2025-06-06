<?php

namespace App\Entity;

use App\Repository\AdminRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
class Admin extends User
{
    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    public function __construct()
    {
        parent::__construct();
        $this->roles[] = 'ROLE_ADMIN';
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
} 