<?php

namespace App\Entity;

use App\Repository\OrdonnanceRepository;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrdonnanceRepository::class)]
#[Vich\Uploadable]
class Ordonnance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    private ?int $size = null;

    #[Vich\UploadableField(mapping: 'ordonnance_file', fileNameProperty: 'filePath')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['application/pdf', 'application/x-pdf'],
        mimeTypesMessage: 'Veuillez téléverser un document PDF valide',
        uploadErrorMessage: 'Une erreur est survenue lors du téléversement du fichier',
        maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). La taille maximale autorisée est {{ limit }} {{ suffix }}',
        groups: ['file_upload']
    )]
    private ?File $file = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(groups: ['generate_prescription'], message: 'Le contenu de l\'ordonnance est requis')]
    private ?string $content = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isGenerated = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(targetEntity: Consultation::class, inversedBy: 'ordonnance', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'consultation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'La consultation est requise')]
    private ?Consultation $consultation = null; // This should not be null after construction

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isGenerated = false;
        $this->title = 'Ordonnance médicale du ' . (new \DateTimeImmutable())->format('d/m/Y');
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function isGenerated(): bool
    {
        return $this->isGenerated;
    }

    public function setIsGenerated(bool $isGenerated): self
    {
        $this->isGenerated = $isGenerated;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file = null): void
    {
        $this->file = $file;

        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): self
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getFileSizeFormatted(): string
    {
        if (null === $this->size) {
            return 'N/A';
        }

        $units = ['o', 'Ko', 'Mo', 'Go'];
        $size = $this->size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }
    
    public function getTitle(): ?string
    {
        return $this->title;
    }
    
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setConsultation(?Consultation $consultation): static
    {
        // If the consultation is already set to this value, do nothing
        if ($this->consultation === $consultation) {
            return $this;
        }

        // If we're setting to null, handle the cleanup
        if ($consultation === null) {
            $oldConsultation = $this->consultation;
            $this->consultation = null;
            
            if ($oldConsultation !== null && $oldConsultation->getOrdonnance() === $this) {
                $oldConsultation->setOrdonnance(null);
            }
            
            return $this;
        }

        // If we're setting a new consultation
        
        // First, detach from the current consultation if it exists
        if ($this->consultation !== null) {
            $currentConsultation = $this->consultation;
            $this->consultation = null;
            
            if ($currentConsultation->getOrdonnance() === $this) {
                $currentConsultation->setOrdonnance(null);
            }
        }

        // Set the new consultation
        $this->consultation = $consultation;
        
        // Update the inverse side if needed
        if ($consultation->getOrdonnance() !== $this) {
            $consultation->setOrdonnance($this);
        }

        return $this;
    }
    
    public function __toString(): string
    {
        return sprintf('Ordonnance #%d - %s', $this->id, $this->createdAt->format('d/m/Y'));
    }
}