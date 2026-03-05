<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Cartridge;
use App\Entity\CartridgeInstallation;
use App\Entity\InventoryItem;
use App\Repository\CartridgeInstallationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CartridgeManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CartridgeInstallationRepository $installationRepo,
        private readonly LoggerInterface $logger
    ) {
    }// end __construct()

    /**
     * Установить картридж на принтер
     *
     * @throws \RuntimeException Если нет на складе или принтер не совместим
     */
    public function installCartridge(
        Cartridge $cartridge,
        InventoryItem $printer,
        ?string $comment = null
    ): CartridgeInstallation {

        // Проверка: принтер должен быть в категории PRINTER.
        if ($printer->getCategory()?->value !== 'printer') {
            throw new \InvalidArgumentException('Можно установить картридж только на принтер');
        }

        // Проверка склада.
        if ($cartridge->getStockQuantity() <= 0) {
            throw new \RuntimeException('Картридж отсутствует на складе');
        }

        // Проверка совместимости (опционально, можно сделать warning).
        if (!$cartridge->getPrinters()->contains($printer)) {
            $this->logger->warning(
                sprintf(
                    'Картридж "%s" установлен на несовместимый принтер "%s"',
                    $cartridge->getName(),
                    $printer->getName()
                )
            );
        }

        // Завершаем предыдущую активную установку для этого принтера.
        $this->closeActiveInstallation($printer);

        $installation = new CartridgeInstallation();
        $installation->setCartridge($cartridge);
        $installation->setPrinter($printer);
        $installation->setComment($comment);

        // Списание со склада.
        $cartridge->decreaseStock();

        $this->em->persist($installation);
        $this->em->flush();

        $this->logger->info(
            sprintf(
                'Картридж "%s" установлен на принтер "%s"',
                $cartridge->getName(),
                $printer->getName()
            )
        );

        return $installation;
    }// end installCartridge()

    /**
     * Снять картридж (завершить цикл использования)
     */
    public function removeCartridge(
        CartridgeInstallation $installation,
        ?int $printedPages = null,
        ?string $comment = null
    ): void {

        if (!$installation->isInstalled()) {
            return;
        }

        $installation->setRemovedAt(new \DateTimeImmutable());

        if ($printedPages !== null) {
            $installation->setPrintedPages($printedPages);
        }

        if ($comment) {
            $existing = $installation->getComment() ?? '';
            $installation->setComment($existing . ' [Снят: ' . $comment . ']');
        }

        $this->em->flush();

        $this->logger->info(
            sprintf(
                'Картридж "%s" снят с принтера "%s", отпечатано: %s стр.',
                $installation->getCartridge()?->getName(),
                $installation->getPrinter()?->getName(),
                $printedPages ?? 'н/д'
            )
        );
    }// end removeCartridge()

    /**
     * Возврат картриджа на склад (после заправки/обслуживания)
     */
    public function returnToStock(Cartridge $cartridge, int $quantity = 1): void
    {
        $cartridge->increaseStock($quantity);
        $this->em->flush();

        $this->logger->info(
            sprintf(
                'Возвращено %d картридж(ей) "%s" на склад',
                $quantity,
                $cartridge->getName()
            )
        );
    }// end returnToStock()

    public function getActiveInstallation(InventoryItem $printer): ?CartridgeInstallation
    {
        return $this->installationRepo->findActiveForPrinter($printer);
    }// end getActiveInstallation()

    /**
     * Прогноз замены для принтера
     *
     * @return array{date: ?\DateTimeImmutable, method: string, confidence: string}
     */
    public function predictReplacementDate(InventoryItem $printer, ?Cartridge $cartridge = null): array
    {
        // Если картридж не указан, берем текущий установленный.
        if (!$cartridge) {
            $active = $this->getActiveInstallation($printer);
            if (!$active) {
                return [
                    'date'       => null,
                    'method'     => 'none',
                    'confidence' => 'low',
                ];
            }
            $cartridge = $active->getCartridge();
        }

        $history = $this->installationRepo->findCompletedHistory($printer, $cartridge, 5);

        if (count($history) < 2) {
            return [
                'date'       => null,
                'method'     => 'insufficient_data',
                'confidence' => 'low',
            ];
        }

        $totalDays = 0;
        $totalPages = 0;
        $validCycles = 0;

        foreach ($history as $item) {
            $days = $item->getLifetimeDays();
            if ($days && $days > 0) {
                $totalDays += $days;
                $validCycles++;
                if ($item->getPrintedPages()) {
                    $totalPages += $item->getPrintedPages();
                }
            }
        }

        if ($validCycles === 0) {
            return [
                'date'       => null,
                'method'     => 'no_valid_data',
                'confidence' => 'low',
            ];
        }

        $avgDays = $totalDays / $validCycles;
        $active = $this->getActiveInstallation($printer);

        if (!$active || $active->getCartridge()->getId() !== $cartridge->getId()) {
            return [
                'date'       => null,
                'method'     => 'no_active_installation',
                'confidence' => 'low',
            ];
        }

        // Метод 1: По страницам (если есть данные и известен ресурс).
        if ($totalPages > 0 && $cartridge->getYieldPages() && $cartridge->getYieldPages() > 0) {
            $avgPagesPerDay = $totalPages / $totalDays;

            if ($avgPagesPerDay > 0) {
                // Сколько страниц уже отпечатано в текущем цикле?
                $currentPages = $active->getPrintedPages() ?? 0;
                $pagesLeft = $cartridge->getYieldPages() - $currentPages;

                if ($pagesLeft > 0) {
                    $daysLeft = $pagesLeft / $avgPagesPerDay;
                    $predictedDate = (new \DateTimeImmutable())->modify("+{$daysLeft} days");

                    return [
                        'date'       => $predictedDate,
                        'method'     => 'pages_based',
                        'confidence' => $validCycles >= 3 ? 'high' : 'medium',
                    ];
                }
            }
        }

        // Метод 2: По времени (фоллбэк).
        $daysSinceInstall = (float) $active->getInstalledAt()->diff(new \DateTimeImmutable())->format('%a');
        $daysLeft = $avgDays - $daysSinceInstall;

        if ($daysLeft > 0) {
            $predictedDate = (new \DateTimeImmutable())->modify("+{$daysLeft} days");
            return [
                'date'       => $predictedDate,
                'method'     => 'time_based',
                'confidence' => 'low',
            ];
        }

        // Картридж уже "просрочен" по средним показателям.
        return [
            'date'       => new \DateTimeImmutable(),
            'method'     => 'overdue',
            'confidence' => 'medium',
        ];
    }// end predictReplacementDate()

    private function closeActiveInstallation(InventoryItem $printer): void
    {
        $active = $this->getActiveInstallation($printer);
        if ($active) {
            $active->setRemovedAt(new \DateTimeImmutable());
            $active->setComment(
                ($active->getComment() ?? '') . ' [Закрыто системой при новой установке]'
            );
            // Не делаем flush здесь, чтобы можно было batch-операции.
        }
    }// end closeActiveInstallation()
}// end class
