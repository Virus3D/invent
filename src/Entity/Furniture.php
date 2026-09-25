<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\BalanceType;
use App\Enum\FurnitureCategory;
use App\Enum\ItemStatus;
use App\Repository\FurnitureRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: FurnitureRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'furniture')]
class Furniture
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

    #[ORM\Column(type: 'string', length: 20, enumType: FurnitureCategory::class)]
    private ?FurnitureCategory $category = null;

    #[ORM\Column(type: 'string', length: 20, enumType: BalanceType::class)]
    private ?BalanceType $balanceType = null;

    #[ORM\Column(type: 'string', length: 20, enumType: ItemStatus::class, nullable: true)]
    private ?ItemStatus $status = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    private ?string $purchasePrice = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?DateTimeInterface $purchaseDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $responsiblePerson = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $checked = false;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(inversedBy: 'furnitures')]
    private ?Location $location = null;

    public function __construct()
    {
        $this->createdAt   = new DateTimeImmutable();
        $this->updatedAt   = new DateTimeImmutable();
        $this->category    = FurnitureCategory::OTHER;
        $this->balanceType = BalanceType::ON_BALANCE;
        $this->status      = ItemStatus::NEW;
        $this->checked     = false;
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
        $this->name = trim(str_replace('  ', ' ', $name));

        return $this;
    }// end setName()

    public function getDescription(): ?string
    {
        return $this->description;
    }// end getDescription()

    public function setDescription(?string $description): static
    {
        $this->description = trim(str_replace('  ', ' ', $description));

        return $this;
    }// end setDescription()

    /**
     * Validates the inventory number before persisting or updating the entity.
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function validateInventoryNumber(): void
    {
        // Если объект на балансе и инвентарный номер не указан.
        if ($this->balanceType->isOnBalance() && empty($this->inventoryNumber)) {
            throw new LogicException('Инвентарный номер обязателен для объектов на балансе');
        }

        // Если объект за балансом, инвентарный номер должен быть пустым.
        if ($this->balanceType->isOffBalance() && ! empty($this->inventoryNumber)) {
            $this->inventoryNumber = null;
        }
    }// end validateInventoryNumber()

    /**
     * Validate that the inventory number is present for items on balance.
     */
    #[Assert\Callback]
    public function validateInventoryNumberCallback(ExecutionContextInterface $context): void
    {
        if ($this->balanceType->isOnBalance() && empty($this->inventoryNumber)) {
            $context->buildViolation('Инвентарный номер обязателен для объектов на балансе')
                ->atPath('inventoryNumber')
                ->addViolation();
        }
    }// end validateInventoryNumberCallback()

    public function getInventoryNumber(): ?string
    {
        return $this->inventoryNumber;
    }// end getInventoryNumber()

    public function setInventoryNumber(?string $inventoryNumber): static
    {
        $this->inventoryNumber = $inventoryNumber;

        return $this;
    }// end setInventoryNumber()

    public function getCategory(): FurnitureCategory
    {
        return $this->category;
    }// end getCategory()

    public function setCategory(FurnitureCategory $category): static
    {
        $this->category = $category;

        return $this;
    }// end setCategory()

    public function getBalanceType(): BalanceType
    {
        return $this->balanceType;
    }// end getBalanceType()

    public function setBalanceType(BalanceType $balanceType): static
    {
        $this->balanceType = $balanceType;

        return $this;
    }// end setBalanceType()

    /**
     * Determine if this furniture item is on balance.
     *
     * @return bool true if the item is on balance, false otherwise
     */
    public function isOnBalance(): bool
    {
        return $this->balanceType->isOnBalance();
    }// end isOnBalance()

    /**
     * Determine if this furniture item is off balance.
     *
     * @return bool true if the item is off balance, false otherwise
     */
    public function isOffBalance(): bool
    {
        return $this->balanceType->isOffBalance();
    }// end isOffBalance()

    public function getStatus(): ?ItemStatus
    {
        return $this->status;
    }// end getStatus()

    public function setStatus(?ItemStatus $status): static
    {
        $this->status = $status;

        return $this;
    }// end setStatus()

    public function getPurchasePrice(): ?string
    {
        return $this->purchasePrice;
    }// end getPurchasePrice()

    public function setPurchasePrice(?string $purchasePrice): static
    {
        $this->purchasePrice = $purchasePrice;

        return $this;
    }// end setPurchasePrice()

    public function getPurchaseDate(): ?DateTimeInterface
    {
        return $this->purchaseDate;
    }// end getPurchaseDate()

    public function setPurchaseDate(?DateTimeInterface $purchaseDate): static
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }// end setPurchaseDate()

    public function getResponsiblePerson(): ?string
    {
        return $this->responsiblePerson;
    }// end getResponsiblePerson()

    public function setResponsiblePerson(?string $responsiblePerson): static
    {
        $this->responsiblePerson = $responsiblePerson;

        return $this;
    }// end setResponsiblePerson()

    /**
     * Check if this furniture item has been checked.
     *
     * @return bool true if checked, false otherwise
     */
    public function isChecked(): bool
    {
        return $this->checked;
    }// end isChecked()

    /**
     * Set the checked status of this furniture item.
     *
     * @param bool $checked The checked status
     */
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

    /**
     * Get the icon for the category associated with this furniture item.
     *
     * @return string the icon representing the item's category
     */
    public function getCategoryIcon(): string
    {
        return $this->category->getIcon();
    }// end getCategoryIcon()

    /**
     * Get the color for the category associated with this furniture item.
     *
     * @return string the color associated with the item's category
     */
    public function getCategoryColor(): string
    {
        return $this->category->getColor();
    }// end getCategoryColor()

    /**
     * Get the badge class for the category associated with this furniture item.
     */
    public function getCategoryBadgeClass(): string
    {
        return $this->category->getBadgeClass();
    }// end getCategoryBadgeClass()

    /**
     * Returns the string representation of the furniture item.
     *
     * @return string The formatted string including name, inventory number, and location
     */
    public function __toString(): string
    {
        return sprintf(
            '%s%s%s',
            $this->getName(),
            $this->getInventoryNumber() ? " [{$this->getInventoryNumber()}]" : '',
            $this->getLocation() ? " — {$this->getLocation()}" : '',
        );
    }// end __toString()
}// end class
