<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ItemType: string implements TranslatableInterface
{
    // Основное средство.
    case FIXED_ASSET = 'fixed_asset';
    // Инструмент.
    case TOOL = 'tool';
    // Материал.
    case MATERIAL = 'material';

    /**
     * {@inheritDoc}
     */
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(
            "inventory_item.type.{$this->value}",
            domain: 'inventory',
            locale: $locale
        );
    }// end trans()

    /**
     * Для основных средств требуются дополнительные поля.
     */
    public function requiresFixedAssetFields(): bool
    {
        return $this === self::FIXED_ASSET;
    }// end requiresFixedAssetFields()

    /**
     * Можно ли списывать данный тип.
     */
    public function canBeWrittenOff(): bool
    {
        return $this === self::FIXED_ASSET || $this === self::TOOL;
    }// end canBeWrittenOff()
}// end enum
