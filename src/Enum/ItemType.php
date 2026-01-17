<?php

declare(strict_types=1);

namespace App\Enum;

enum ItemType: string
{
    // Основное средство.
    case FIXED_ASSET = 'fixed_asset';
    // Инструмент.
    case TOOL = 'tool';
    // Материал.
    case MATERIAL = 'material';

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
