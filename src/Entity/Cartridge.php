<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\CartridgeInstallation;
use App\Repository\CartridgeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CartridgeRepository::class)]
#[ORM\Table(name: 'cartridges')]
class Cartridge
{
    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $color = null;

    #[Assert\PositiveOrZero]
    #[ORM\Column(nullable: true)]
    private ?int $yieldPages = null;

    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $stockQuantity = 0;

    /**
 * Какие принтеры совместимы с этим типом картриджа
*/
    #[ORM\JoinTable(name: 'cartridge_printer_compatibility')]
    #[ORM\ManyToMany(targetEntity: InventoryItem::class, inversedBy: 'compatibleCartridges')]
    private Collection $printers;

    #[ORM\OneToMany(targetEntity: CartridgeInstallation::class, mappedBy: 'cartridge', orphanRemoval: true)]
    private Collection $installations;

    public function __construct()
    {
        $this->printers      = new ArrayCollection();
        $this->installations = new ArrayCollection();
    }// end __construct()

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

    public function getColor(): ?string
    {
        return $this->color;
    }// end getColor()

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }// end setColor()

    public function getYieldPages(): ?int
    {
        return $this->yieldPages;
    }// end getYieldPages()

    public function setYieldPages(?int $yieldPages): static
    {
        $this->yieldPages = $yieldPages;

        return $this;
    }// end setYieldPages()

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }// end getStockQuantity()

    public function setStockQuantity(int $stockQuantity): static
    {
        $this->stockQuantity = max(0, $stockQuantity);

        return $this;
    }// end setStockQuantity()

    public function decreaseStock(int $amount = 1): static
    {
        $this->stockQuantity = max(0, $this->stockQuantity - $amount);

        return $this;
    }// end decreaseStock()

    public function increaseStock(int $amount = 1): static
    {
        $this->stockQuantity += $amount;

        return $this;
    }// end increaseStock()

    public function getPrinters(): Collection
    {
        return $this->printers;
    }// end getPrinters()

    public function addPrinter(InventoryItem $printer): static
    {
        if (! $this->printers->contains($printer)) {
            $this->printers->add($printer);
        }

        return $this;
    }// end addPrinter()

    public function removePrinter(InventoryItem $printer): static
    {
        $this->printers->removeElement($printer);

        return $this;
    }// end removePrinter()

    public function getInstallations(): Collection
    {
        return $this->installations;
    }// end getInstallations()

    /**
     * Получить активную установку на конкретном принтере.
     */
    public function getActiveInstallationOnPrinter(InventoryItem $printer): ?CartridgeInstallation
    {
        foreach ($this->installations as $installation) {
            if ($installation->getPrinter() === $printer && $installation->isInstalled()) {
                return $installation;
            }
        }

        return null;
    }// end getActiveInstallationOnPrinter()
}// end class
