<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\BalanceType;
use App\Repository\BalanceHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BalanceHistoryRepository::class)]
#[ORM\Table(name: 'balance_history')]
#[ORM\HasLifecycleCallbacks]
class BalanceHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: InventoryItem::class, inversedBy: 'balanceHistories')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?InventoryItem $inventoryItem = null;

    #[ORM\Column(type: 'string', length: 20, enumType: BalanceType::class)]
    private ?BalanceType $previousBalanceType = null;

    #[ORM\Column(type: 'string', length: 20, enumType: BalanceType::class)]
    private ?BalanceType $newBalanceType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $changedBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $changedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $additionalData = null;

    public function __construct()
    {
        $this->changedAt = new \DateTimeImmutable();
    }// end __construct()

    public function getId(): ?int
    {
        return $this->id;
    }// end getId()

    public function getInventoryItem(): ?InventoryItem
    {
        return $this->inventoryItem;
    }// end getInventoryItem()

    public function setInventoryItem(?InventoryItem $inventoryItem): static
    {
        $this->inventoryItem = $inventoryItem;
        return $this;
    }// end setInventoryItem()

    public function getPreviousBalanceType(): ?BalanceType
    {
        return $this->previousBalanceType;
    }// end getPreviousBalanceType()

    public function setPreviousBalanceType(BalanceType $previousBalanceType): static
    {
        $this->previousBalanceType = $previousBalanceType;
        return $this;
    }// end setPreviousBalanceType()

    public function getNewBalanceType(): ?BalanceType
    {
        return $this->newBalanceType;
    }// end getNewBalanceType()

    public function setNewBalanceType(BalanceType $newBalanceType): static
    {
        $this->newBalanceType = $newBalanceType;
        return $this;
    }// end setNewBalanceType()

    public function getReason(): ?string
    {
        return $this->reason;
    }// end getReason()

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }// end setReason()

    public function getChangedBy(): ?string
    {
        return $this->changedBy;
    }// end getChangedBy()

    public function setChangedBy(?string $changedBy): static
    {
        $this->changedBy = $changedBy;
        return $this;
    }// end setChangedBy()

    public function getChangedAt(): ?\DateTimeImmutable
    {
        return $this->changedAt;
    }// end getChangedAt()

    public function setChangedAt(\DateTimeImmutable $changedAt): static
    {
        $this->changedAt = $changedAt;
        return $this;
    }// end setChangedAt()

    public function getAdditionalData(): ?array
    {
        return $this->additionalData;
    }// end getAdditionalData()

    public function setAdditionalData(?array $additionalData): static
    {
        $this->additionalData = $additionalData;
        return $this;
    }// end setAdditionalData()

    // Вспомогательные методы
    public function getChangeType(): string
    {
        if ($this->previousBalanceType->isOnBalance() && $this->newBalanceType->isOffBalance()) {
            return 'Перемещено за баланс';
        } else if ($this->previousBalanceType->isOffBalance() && $this->newBalanceType->isOnBalance()) {
            return 'Возвращено на баланс';
        }

        return 'Изменение статуса баланса';
    }// end getChangeType()

    public function getChangeIcon(): string
    {
        if ($this->previousBalanceType->isOnBalance() && $this->newBalanceType->isOffBalance()) {
            return 'fa-arrow-right';
// С баланса за баланс
        } else if ($this->previousBalanceType->isOffBalance() && $this->newBalanceType->isOnBalance()) {
            return 'fa-arrow-left';
// С забаланса на баланс
        }

        return 'fa-exchange-alt';
    }// end getChangeIcon()

    public function getChangeColor(): string
    {
        if ($this->previousBalanceType->isOnBalance() && $this->newBalanceType->isOffBalance()) {
            return 'warning';
// Предупреждение
        } else if ($this->previousBalanceType->isOffBalance() && $this->newBalanceType->isOnBalance()) {
            return 'success';
// Успех
        }

        return 'info';
    }// end getChangeColor()
}// end class
