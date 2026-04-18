<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MaterialRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: MaterialRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'material')]
class Material
{
    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[Assert\Length(max: 200)]
    #[Assert\NotBlank]
    #[ORM\Column(length: 200)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[Assert\PositiveOrZero]
    #[ORM\Column(options: ['default' => 0])]
    private int $quantity = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $checked = false;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(inversedBy: 'materials')]
    private ?Location $location = null;

    #[ORM\OneToMany(mappedBy: 'material', targetEntity: MaterialConsumption::class, cascade: ['persist'])]
    private Collection $consumptions;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }// end __construct()

    /**
     * Updates the 'updatedAt' timestamp before entity update.
     */
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }// end updateTimestamp()

    public function getId(): ?int
    {
        return $this->id;
    }// end getId()

    public function getName(): ?string
    {
        return $this->name;
    }// end getName()

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }// end setName()

    public function getDescription(): ?string
    {
        return $this->description;
    }// end getDescription()

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }// end setDescription()

    public function getQuantity(): int
    {
        return $this->quantity;
    }// end getQuantity()

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }// end setQuantity()

    public function isChecked(): bool
    {
        return $this->checked;
    }// end isChecked()

    public function setChecked(bool $checked): static
    {
        $this->checked = $checked;

        return $this;
    }// end setChecked()

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }// end getCreatedAt()

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }// end getUpdatedAt()

    public function getLocation(): ?Location
    {
        return $this->location;
    }// end getLocation()

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }// end setLocation()
}// end class
