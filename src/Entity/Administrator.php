<?php

namespace App\Entity;

use App\Repository\AdministratorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministratorRepository::class)]
#[ORM\Table(name: 'administrator')]
class Administrator extends User
{
    
    public function __construct()
    {
        $this->role = 'admin';
    }
}
