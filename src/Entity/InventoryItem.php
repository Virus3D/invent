<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\BalanceType;
use App\Enum\InventoryCategory;
use App\Enum\ItemStatus;
use App\Enum\ItemType;
use App\Repository\InventoryItemRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use function array_key_exists;
use function in_array;
use function sprintf;

#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
class InventoryItem
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

    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $inventoryNumber = null;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $serialNumber = null;

    #[ORM\Column(type: 'string', length: 20, enumType: InventoryCategory::class)]
    private ?InventoryCategory $category = null;

    #[ORM\Column(type: 'string', length: 20, enumType: BalanceType::class)]
    private ?BalanceType $balanceType = null;

    #[ORM\Column(type: 'string', length: 20, enumType: ItemStatus::class)]
    private ?ItemStatus $status = null;

    #[ORM\Column(type: 'string', length: 20, enumType: ItemType::class)]
    private ?ItemType $type = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    private ?string $purchasePrice = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $purchaseDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $commissioningDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $responsiblePerson = null;

    /**
     * Contains key-value pairs giving item specifications.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private array $specifications = [];

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(inversedBy: 'inventoryItems')]
    private ?Location $location = null;

    #[ORM\OneToMany(mappedBy: 'inventoryItem', targetEntity: MovementLog::class)]
    private Collection $movementLogs;

    #[ORM\OneToMany(mappedBy: 'inventoryItem', targetEntity: BalanceHistory::class, cascade: ['persist'])]
    private Collection $balanceHistories;

    public function __construct()
    {
        $this->movementLogs     = new ArrayCollection();
        $this->balanceHistories = new ArrayCollection();
        $this->createdAt        = new DateTimeImmutable();
        $this->updatedAt        = new DateTimeImmutable();
        $this->category         = InventoryCategory::OTHER;
        $this->balanceType      = BalanceType::ON_BALANCE;
        $this->status           = ItemStatus::NEW;
        $this->type             = ItemType::FIXED_ASSET;
    }// end __construct()

    /**
     * Updates the 'updatedAt' timestamp before entity update.
     *
     * This method is called automatically on pre-update events
     * to keep track of when the entity was last modified.
     *
     * @ORM\PreUpdate
     */
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }// end updateTimestamp()

    /**
     * Validates the inventory number before persisting or updating the entity.
     *
     * @throws LogicException if the inventory number is required but not provided
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function validateInventoryNumber(): void
    {
        // Если объект на балансе и категория требует инвентарного номера.
        if (
            $this->balanceType->isOnBalance()
            && empty($this->inventoryNumber)
        ) {
            throw new LogicException('Инвентарный номер обязателен для объектов на балансе');
        }

        // Если объект за балансом, инвентарный номер должен быть пустым.
        if ($this->balanceType->isOffBalance() && ! empty($this->inventoryNumber)) {
            $this->inventoryNumber = null;
        }
    }// end validateInventoryNumber()

    /**
     * Get the unique identifier of the inventory item.
     */
    public function getId(): ?int
    {
        return $this->id;
    }// end getId()

    /**
     * Get the name of the inventory item.
     */
    public function getName(): ?string
    {
        return $this->name;
    }// end getName()

    /**
     * Set the name for the inventory item.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }// end setName()

    /**
     * Get the description of the inventory item.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }// end getDescription()

    /**
     * Set the description for the inventory item.
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }// end setDescription()

    /**
     * Get the inventory number of the inventory item.
     */
    public function getInventoryNumber(): ?string
    {
        return $this->inventoryNumber;
    }// end getInventoryNumber()

    /**
     * Set the inventory number for the inventory item.
     */
    public function setInventoryNumber(?string $inventoryNumber): static
    {
        $this->inventoryNumber = $inventoryNumber;

        return $this;
    }// end setInventoryNumber()

    /**
     * Get the serial number of the inventory item.
     */
    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }// end getSerialNumber()

    /**
     * Set the serial number for the inventory item.
     */
    public function setSerialNumber(?string $serialNumber): static
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }// end setSerialNumber()

    /**
     * Get the category of the inventory item.
     */
    public function getCategory(): InventoryCategory
    {
        return $this->category;
    }// end getCategory()

    /**
     * Set the category for the inventory item.
     */
    public function setCategory(InventoryCategory $category): static
    {
        $this->category = $category;

        return $this;
    }// end setCategory()

    /**
     * Set the category for the inventory item from a string value.
     *
     * @param string $category The category name as a string
     */
    public function setCategoryFromString(string $category): static
    {
        $this->category = InventoryCategory::from($category);

        return $this;
    }// end setCategoryFromString()

    /**
     * Get the balance type for the inventory item.
     */
    public function getBalanceType(): BalanceType
    {
        return $this->balanceType;
    }// end getBalanceType()

    /**
     * Set the balance type for the inventory item.
     */
    public function setBalanceType(BalanceType $balanceType): static
    {
        $oldBalanceType    = $this->balanceType;
        $this->balanceType = $balanceType;

        // Если статус баланса изменился, добавляем запись в историю.
        if ($oldBalanceType !== $balanceType) {
            $history = new BalanceHistory();
            $history->setInventoryItem($this);
            $history->setPreviousBalanceType($oldBalanceType);
            $history->setNewBalanceType($balanceType);
            $history->setChangedAt(new DateTimeImmutable());

            $this->addBalanceHistory($history);
        }

        return $this;
    }// end setBalanceType()

    /**
     * Get the status for the inventory item.
     */
    public function getStatus(): ItemStatus
    {
        return $this->status;
    }// end getStatus()

    /**
     * Set the status for the inventory item.
     */
    public function setStatus(ItemStatus $status): static
    {
        $this->status = $status;

        return $this;
    }// end setStatus()

    /**
     * Get the type for the inventory item.
     */
    public function getType(): ItemType
    {
        return $this->type;
    }// end getType()

    /**
     * Set the type for the inventory item.
     */
    public function setType(ItemType $type): static
    {
        $this->type = $type;

        return $this;
    }// end setType()

    /**
     * Get the purchase price for this inventory item.
     *
     * @return string|null The purchase price or null if not set
     */
    public function getPurchasePrice(): ?string
    {
        return $this->purchasePrice;
    }// end getPurchasePrice()

    /**
     * Set the purchase price for this inventory item.
     *
     * @param string|null $purchasePrice The purchase price or null if not set
     */
    public function setPurchasePrice(?string $purchasePrice): static
    {
        $this->purchasePrice = $purchasePrice;

        return $this;
    }// end setPurchasePrice()

    /**
     * Get the purchase date for this inventory item.
     *
     * @return DateTimeInterface|null The purchase date or null if not set
     */
    public function getPurchaseDate(): ?DateTimeInterface
    {
        return $this->purchaseDate;
    }// end getPurchaseDate()

    /**
     * Set the purchase date for this inventory item.
     *
     * @param DateTimeInterface|null $purchaseDate The purchase date or null if not set
     */
    public function setPurchaseDate(?DateTimeInterface $purchaseDate): static
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }// end setPurchaseDate()

    /**
     * Get the commissioning date for this inventory item.
     *
     * @return DateTimeInterface|null The commissioning date or null if not set
     */
    public function getCommissioningDate(): ?DateTimeInterface
    {
        return $this->commissioningDate;
    }// end getCommissioningDate()

    /**
     * Set the commissioning date for this inventory item.
     *
     * @param DateTimeInterface|null $commissioningDate The commissioning date or null if not set
     */
    public function setCommissioningDate(?DateTimeInterface $commissioningDate): static
    {
        $this->commissioningDate = $commissioningDate;

        return $this;
    }// end setCommissioningDate()

    /**
     * Get the responsible person for this inventory item.
     *
     * @return string|null The name of the responsible person or null if not set
     */
    public function getResponsiblePerson(): ?string
    {
        return $this->responsiblePerson;
    }// end getResponsiblePerson()

    /**
     * Set the responsible person for this inventory item.
     *
     * @param string|null $responsiblePerson The name of the responsible person or null
     */
    public function setResponsiblePerson(?string $responsiblePerson): static
    {
        $this->responsiblePerson = $responsiblePerson;

        return $this;
    }// end setResponsiblePerson()

    /**
     * Get the specifications for the inventory item.
     *
     * @return array<string, mixed> associative array of specifications
     */
    public function getSpecifications(): array
    {
        return $this->specifications;
    }// end getSpecifications()

    /**
     * Set the specifications for the inventory item.
     *
     * @param array<string, mixed>|null $specifications Associative array of specifications or null to clear
     */
    public function setSpecifications(?array $specifications): static
    {
        $this->specifications = $specifications ?? [];

        return $this;
    }// end setSpecifications()

    /**
     * Get the creation date and time of the inventory item.
     *
     * @return DateTimeImmutable|null The date and time the item was created, or null if not set
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }// end getCreatedAt()

    /**
     * Get the last updated date and time of the inventory item.
     *
     * @return DateTimeImmutable|null the date and time the item was last updated, or null if not set
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }// end getUpdatedAt()

    /**
     * Get the location associated with this inventory item.
     */
    public function getLocation(): ?Location
    {
        return $this->location;
    }// end getLocation()

    /**
     * Set the location for this inventory item.
     */
    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }// end setLocation()

    /**
     * Get the movement logs associated with this inventory item.
     */
    public function getMovementLogs(): Collection
    {
        return $this->movementLogs;
    }// end getMovementLogs()

    /**
     * Get the balance histories associated with this inventory item.
     *
     * @return Collection the balance histories for this inventory item
     */
    public function getBalanceHistories(): Collection
    {
        return $this->balanceHistories;
    }// end getBalanceHistories()

    /**
     * Add a balance history to this inventory item.
     *
     * @param BalanceHistory $balanceHistory The balance history to add
     */
    public function addBalanceHistory(BalanceHistory $balanceHistory): static
    {
        if (! $this->balanceHistories->contains($balanceHistory)) {
            $this->balanceHistories->add($balanceHistory);
            $balanceHistory->setInventoryItem($this);
        }

        return $this;
    }// end addBalanceHistory()

    /**
     * Remove a balance history from this inventory item.
     *
     * @param BalanceHistory $balanceHistory The balance history to remove
     */
    public function removeBalanceHistory(BalanceHistory $balanceHistory): static
    {
        if ($this->balanceHistories->removeElement($balanceHistory)) {
            if ($balanceHistory->getInventoryItem() === $this) {
                $balanceHistory->setInventoryItem(null);
            }
        }

        return $this;
    }// end removeBalanceHistory()

    /**
     * Get a specific PC specification value by key.
     */
    public function getPcSpecification(string $key): mixed
    {
        return $this->specifications[$key] ?? null;
    }// end getPcSpecification()

    /**
     * Get the icon for the category associated with this inventory item.
     *
     * @return string the icon representing the item's category
     */
    public function getCategoryIcon(): string
    {
        return $this->category->getIcon();
    }// end getCategoryIcon()

    /**
     * Get the color for the category associated with this inventory item.
     *
     * @return string the color associated with the item's category
     */
    public function getCategoryColor(): string
    {
        return $this->category->getColor();
    }// end getCategoryColor()

    /**
     * Get the badge class for the category associated with this inventory item.
     */
    public function getCategoryBadgeClass(): string
    {
        return $this->category->getBadgeClass();
    }// end getCategoryBadgeClass()

    /**
     * Determine if this inventory item is on balance.
     *
     * @return bool true if the item is on balance, false otherwise
     */
    public function isOnBalance(): bool
    {
        return $this->balanceType->isOnBalance();
    }// end isOnBalance()

    /**
     * Determine if this inventory item is off balance.
     *
     * @return bool true if the item is off balance, false otherwise
     */
    public function isOffBalance(): bool
    {
        return $this->balanceType->isOffBalance();
    }// end isOffBalance()

    /**
     * Determine if this inventory item's category has specifications.
     */
    public function hasSpecifications(): bool
    {
        return $this->category->hasSpecifications();
    }// end hasSpecifications()

    /**
     * Validate specifications based on category requirements.
     */
    public function validateSpecificationsForCategory(ExecutionContextInterface $context): void
    {
        if (! $this->category instanceof InventoryCategory) {
            return;
        }

        $requiredSpecs  = $this->category->getRequiredSpecifications();
        $specifications = $this->getSpecifications();

        foreach ($requiredSpecs as $requiredKey) {
            if (! array_key_exists($requiredKey, $specifications) || empty($specifications[$requiredKey])) {
                $context->buildViolation(
                    sprintf(
                        'Для категории "%s" обязательно поле "%s" в характеристиках',
                        $this->category->value,
                        $requiredKey
                    )
                )
                    ->atPath('specifications')
                    ->addViolation();
            }
        }

        // Validate specifications structure (only allowed keys for category).
        $allowedSpecs = $this->category->getAllowedSpecifications();
        if (! empty($allowedSpecs)) {
            foreach (array_keys($specifications) as $specKey) {
                if (! in_array($specKey, $allowedSpecs, true)) {
                    $context->buildViolation(
                        sprintf(
                            'Ключ "%s" не разрешен для категории "%s"',
                            $specKey,
                            $this->category->value
                        )
                    )
                        ->atPath('specifications')
                        ->addViolation();
                }
            }
        }
    }// end validateSpecificationsForCategory()

    /**
     * Get all specification keys.
     *
     * @return array<string>
     */
    public function getSpecificationKeys(): array
    {
        return array_keys($this->specifications);
    }// end getSpecificationKeys()

    /**
     * Check if specification key exists.
     */
    public function hasSpecification(string $key): bool
    {
        return array_key_exists($key, $this->specifications);
    }// end hasSpecification()

    /**
     * Remove a specification by key.
     */
    public function removeSpecification(string $key): static
    {
        unset($this->specifications[$key]);

        return $this;
    }// end removeSpecification()

    /**
     * Перемещение между балансом и забалансом.
     */
    public function moveToBalance(BalanceType $newStatus, string $reason, string $changedBy): void
    {
        $this->setBalanceType($newStatus);

        // Обновляем последнюю запись в истории с причиной и автором.
        $latestHistory = $this->balanceHistories->last();
        if ($latestHistory) {
            $latestHistory->setReason($reason);
            $latestHistory->setChangedBy($changedBy);
        }
    }// end moveToBalance()

    /**
     * Validate that the inventory number is present for items on balance.
     */
    #[Assert\Callback]
    public function validateInventoryNumberCallback(ExecutionContextInterface $context): void
    {
        if ($this->isOnBalance() && empty($this->inventoryNumber)) {
            $context->buildViolation('Инвентарный номер обязателен для объектов на балансе')
                ->atPath('inventoryNumber')
                ->addViolation();
        }
    }// end validateInventoryNumberCallback()
}// end class
