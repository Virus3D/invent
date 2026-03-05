<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\InventoryItem;
use App\Repository\CartridgeInstallationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CartridgeInstallationRepository::class)]
#[ORM\Table(name: 'cartridge_installations')]
#[ORM\Index(columns: ['printer_id', 'removed_at'])]
// Для быстрого поиска активных
class CartridgeInstallation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'installations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Cartridge $cartridge = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?InventoryItem $printer = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $installedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $removedAt = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $printedPages = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    public function __construct()
    {
        $this->installedAt = new \DateTimeImmutable();
    }// end __construct()

    // --- Getters & Setters ---
    public function getId(): ?int
    {
        return $this->id;
    }// end getId()

    public function getCartridge(): ?Cartridge
    {
        return $this->cartridge;
    }// end getCartridge()

    public function setCartridge(?Cartridge $cartridge): static
    {
        $this->cartridge = $cartridge;
        return $this;
    }// end setCartridge()

    public function getPrinter(): ?InventoryItem
    {
        return $this->printer;
    }// end getPrinter()

    public function setPrinter(?InventoryItem $printer): static
    {
        $this->printer = $printer;
        return $this;
    }// end setPrinter()

    public function getInstalledAt(): \DateTimeImmutable
    {
        return $this->installedAt;
    }// end getInstalledAt()

    public function getRemovedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }// end getRemovedAt()

    public function setRemovedAt(?\DateTimeImmutable $removedAt): static
    {
        $this->removedAt = $removedAt;
        return $this;
    }// end setRemovedAt()

    public function isInstalled(): bool
    {
        return $this->removedAt === null;
    }// end isInstalled()

    public function getPrintedPages(): ?int
    {
        return $this->printedPages;
    }// end getPrintedPages()

    public function setPrintedPages(?int $printedPages): static
    {
        $this->printedPages = $printedPages;
        return $this;
    }// end setPrintedPages()

    public function getComment(): ?string
    {
        return $this->comment;
    }// end getComment()

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }// end setComment()

    /**
     * Длительность установки в днях (если картридж снят)
     */
    public function getLifetimeDays(): ?float
    {
        if (!$this->removedAt) {
            return null;
        }
        $diff = $this->installedAt->diff($this->removedAt);
        return (float) $diff->format('%a');
// Всего дней
    }// end getLifetimeDays()
}// end class
