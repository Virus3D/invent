<?php

declare(strict_types=1);

namespace App\Enum;

enum BalanceType: string
{
    case ON_BALANCE = 'on_balance';
    case OFF_BALANCE = 'off_balance';

    /**
     * Determine if the balance type is ON_BALANCE.
     *
     * @return bool True if ON_BALANCE, false otherwise.
     */
    public function isOnBalance(): bool
    {
        return $this === self::ON_BALANCE;
    }// end isOnBalance()

    /**
     * Determine if the balance type is OFF_BALANCE.
     *
     * @return bool True if OFF_BALANCE, false otherwise.
     */
    public function isOffBalance(): bool
    {
        return $this === self::OFF_BALANCE;
    }// end isOffBalance()

    /**
     * Create a BalanceType from a boolean value.
     *
     * @param bool $isOnBalance True for ON_BALANCE, false for OFF_BALANCE.
     */
    public static function fromBool(bool $isOnBalance): self
    {
        return $isOnBalance ? self::ON_BALANCE : self::OFF_BALANCE;
    }// end fromBool()

    /**
     * Convert the BalanceType to a boolean.
     *
     * @return bool True if ON_BALANCE, false if OFF_BALANCE.
     */
    public function toBool(): bool
    {
        return $this->isOnBalance();
    }// end toBool()
}// end enum
