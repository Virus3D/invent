<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Furniture;
use App\Entity\Location;
use App\Enum\BalanceType;
use App\Enum\FurnitureCategory;
use App\Enum\ItemStatus;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

final class FurnitureImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }// end __construct()

    /**
     * Import furniture from an uploaded XLS/XLSX file.
     *
     * Column mapping:
     *   A - Name (with optional quantity anywhere: "14шт Стол", "Поднос - 2шт.", "5 штук Стул")
     *   B - Inventory number (or "забаланс" for off-balance)
     *   C - Location name
     *
     * @param int $batchSize Number of entities to persist before each flush (default 50).
     */
    public function importFromExcel(UploadedFile $file, ?Location $defaultLocation, int $batchSize = 50): ImportResult
    {
        $created = 0;
        $skipped = 0;
        $errors  = [];

        // Cache for location lookups to avoid repeated DB queries.
        $locationCache = [];

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
        } catch (Throwable $e) {
            return new ImportResult(0, 0, [sprintf('Ошибка чтения файла: %s', $e->getMessage())]);
        }

        $worksheet = $spreadsheet->getActiveSheet();
        $rows      = $worksheet->toArray(null, true, true, true);

        // Skip header row.
        $dataRows = array_slice($rows, 1);

        foreach ($dataRows as $row) {
            // Skip empty rows.
            if (empty($row['A']) && empty($row['B']) && empty($row['C'])) {
                continue;
            }

            $nameRaw     = trim((string) ($row['A'] ?? ''));
            $inventoryRaw = trim((string) ($row['B'] ?? ''));
            $locationRaw = trim((string) ($row['C'] ?? ''));

            if (empty($nameRaw)) {
                $skipped++;
                continue;
            }

            try {
                // Parse quantity from name.
                [
                    $quantity,
                    $cleanName,
                ] = $this->parseQuantity($nameRaw);
                if ($quantity < 1) {
                    $quantity = 1;
                }

                // Determine balance type and inventory number.
                $balanceType = $this->determineBalanceType($inventoryRaw);
                $inventoryNumber = $this->extractInventoryNumber($inventoryRaw, $balanceType);

                // Detect category from name.
                $category = $this->detectCategory($cleanName);

                // Look up or create location (with cache).
                $location = $this->resolveLocation($locationRaw, $defaultLocation, $locationCache);

                // Create furniture entities.
                for ($i = 0; $i < $quantity; $i++) {
                    $furniture = new Furniture();
                    $furniture->setName($cleanName);
                    $furniture->setInventoryNumber($inventoryNumber);
                    $furniture->setCategory($category);
                    $furniture->setBalanceType($balanceType);
                    $furniture->setStatus(ItemStatus::NEW);
                    $furniture->setLocation($location);

                    $this->entityManager->persist($furniture);
                    $created++;
                }

                // Batch flush to prevent memory exhaustion and timeout.
                if ($created % $batchSize === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            } catch (Throwable $e) {
                $errors[] = sprintf(
                    'Строка "%s": %s',
                    $nameRaw,
                    $e->getMessage()
                );
                $skipped++;
            }// end try
        }// end foreach

        // Flush remaining entities.
        if ($created > 0) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        return new ImportResult($created, $skipped, $errors);
    }// end importFromExcel()

    /**
     * Extract quantity from name string.
     *
     * Handles patterns like:
     *   - "14шт Стол офисный" (quantity at start)
     *   - "Кресло 3шт" (quantity at end)
     *   - "Поднос - 2шт." (quantity after dash)
     *   - "5 штук Стул" (various suffixes)
     *
     * @return array{int, string} [quantity, clean_name]
     */
    private function parseQuantity(string $name): array
    {
        // Common suffix pattern: order matters - longer variants first.
        // Matches: "штук", "штуки", "штуч.", "штуч", "шт.", "штук.", "шт", "".
        $suffix = '(?:штук\.?|штуки|штуч\.?|шт\.?|штук|шт)?';

        // Pattern 1: Quantity at the START of the string.
        // Matches: "14шт Стол", "10шт Стул", "5 штук Шкаф", "20 шт. Кресло".
        $startPattern = '/^(\d+)\s*' . $suffix . '\s*/u';
        if (preg_match($startPattern, $name, $matches)) {
            $quantity = (int) $matches[1];
            $cleanName = trim(preg_replace($startPattern, '', $name));

            // If clean name is empty, the whole string was just quantity like "10шт".
            // Return the original name as-is (will be saved as empty or generic).
            if ($cleanName === '') {
                return [
                    $quantity,
                    $name,
                ];
            }

            return [
                $quantity,
                $cleanName,
            ];
        }

        // Pattern 2: Quantity at the END or MIDDLE of the string (after separator).
        // Matches: "Стол 3шт", "Поднос - 2шт.", "Кресло – 5штук".
        $endPattern = '/[\s\-–—]+(\d+)\s*' . $suffix . '(?:\.\s*$|\s*$)/u';
        if (preg_match($endPattern, $name, $matches)) {
            $quantity = (int) $matches[1];
            $cleanName = trim(preg_replace($endPattern, '', $name));
            $cleanName = trim($cleanName, "\s\-–—");
            $cleanName = trim($cleanName);

            if (!empty($cleanName)) {
                return [
                    $quantity,
                    $cleanName,
                ];
            }
        }

        return [
            1,
            $name,
        ];
    }// end parseQuantity()

    /**
     * Detect furniture category from name keywords.
     *
     * Checks for all categories based on common Russian keywords.
     * Uses hash-based lookup for O(1) performance per keyword.
     */
    private function detectCategory(string $name): FurnitureCategory
    {
        $lower = mb_strtolower($name);

        // Dishware keywords indexed by hash for O(1) lookup.
        static $dishwareHash = null;
        if ($dishwareHash === null) {
            $dishwareHash = array_flip(
                [
                    'стакан',
                    'стакана',
                    'стаканы',
                    'тарелк',
                    'тарелка',
                    'тарелки',
                    'тарелок',
                    'чашк',
                    'чашка',
                    'чашки',
                    'кружк',
                    'кружка',
                    'кружки',
                    'ложк',
                    'ложка',
                    'ложки',
                    'ложек',
                    'вилк',
                    'вилка',
                    'вилки',
                    'нож',
                    'ножи',
                    'ножей',
                    'кастрюл',
                    'кастрюля',
                    'кастрюли',
                    'сковород',
                    'сковорода',
                    'сковороды',
                    'блюст',
                    'блюдо',
                    'блюда',
                    'поднос',
                    'подноса',
                    'подносы',
                    'графин',
                    'графина',
                    'сервиз',
                    'сервиза',
                    'чашка',
                    'пиал',
                    'пиала',
                    'бокал',
                    'бокала',
                    'бокалы',
                    'салатник',
                    'салатника',
                    'супник',
                    'супника',
                ]
            );
        }// end if

        foreach ($dishwareHash as $keyword => $_) {
            if (mb_stripos($lower, $keyword) !== false) {
                return FurnitureCategory::DISHWARE;
            }
        }

        // Tech keywords.
        static $techHash = null;
        if ($techHash === null) {
            $techHash = array_flip(
                [
                    'калькулятор',
                    'калькулятора',
                    'калькуляторы',
                    'кулер',
                    'кулера',
                    'кулеры',
                    'утюг',
                    'утюга',
                    'утюги',
                    'чайник',
                    'чайника',
                    'чайники',
                    'плита',
                    'плиты',
                    'плит',
                    'холодильник',
                    'холодильника',
                    'холодильники',
                    'кондиционер',
                    'кондиционера',
                    'кондиционеры',
                    'пылесос',
                    'пылесоса',
                    'пылесосы',
                    'фен',
                    'фена',
                    'фены',
                    'микроволновк',
                    'микроволновка',
                    'микроволновки',
                    'тостер',
                    'тостера',
                    'тостеры',
                    'блендер',
                    'блендера',
                    'блендеры',
                    'мультиварк',
                    'мультиварка',
                    'мультиварки',
                    'чайник электрическ',
                ]
            );
        }// end if

        foreach ($techHash as $keyword => $_) {
            if (mb_stripos($lower, $keyword) !== false) {
                return FurnitureCategory::TECH;
            }
        }

        // Exclude common false positives for desk (e.g., "лампа настольная").
        $excludeDesk = [
            'лампа',
            'светильн',
            'торшер',
            'бра',
        ];

        foreach ($excludeDesk as $ex) {
            if (mb_stripos($lower, $ex) !== false) {
                return FurnitureCategory::OTHER;
            }
        }

        // Desk keywords.
        static $deskHash = null;
        if ($deskHash === null) {
            $deskHash = array_flip(
                [
                    'стол',
                    'стола',
                    'столы',
                    'столов',
                    'парта',
                    'парты',
                    'парт',
                    'письм',
                    'письменный',
                    'верстак',
                    'верстака',
                    'верстаки',
                ]
            );
        }

        foreach ($deskHash as $keyword => $_) {
            if (mb_stripos($lower, $keyword) !== false) {
                return FurnitureCategory::DESK;
            }
        }

        // Cabinet keywords.
        static $cabinetHash = null;
        if ($cabinetHash === null) {
            $cabinetHash = array_flip(
                [
                    'шкаф',
                    'шкафа',
                    'шкафы',
                    'шкафов',
                    'стеллаж',
                    'стеллажа',
                    'стеллажи',
                    'комод',
                    'комода',
                    'комоды',
                    'буфет',
                    'буфета',
                    'буфеты',
                    'витрина',
                    'витрины',
                    'витрин',
                    'гардероб',
                    'гардероба',
                    'гардеробы',
                    'банкетк',
                    'банкетка',
                    'банкетки',
                ]
            );
        }// end if

        foreach ($cabinetHash as $keyword => $_) {
            if (mb_stripos($lower, $keyword) !== false) {
                return FurnitureCategory::CABINET;
            }
        }

        // Bed keywords.
        static $bedHash = null;
        if ($bedHash === null) {
            $bedHash = array_flip(
                [
                    'кровать',
                    'кровати',
                    'кроватей',
                    'диван',
                    'дивана',
                    'диваны',
                    'кровать чердак',
                    'чердакн',
                    'софа',
                    'софы',
                    'соф',
                    'книжк',
                    'книжка',
                    'книжки',
                    'оттоманк',
                    'оттоманка',
                    'оттоманки',
                ]
            );
        }// end if

        foreach ($bedHash as $keyword => $_) {
            if (mb_stripos($lower, $keyword) !== false) {
                return FurnitureCategory::BED;
            }
        }

        // Chair keywords.
        static $chairHash = null;
        if ($chairHash === null) {
            $chairHash = array_flip(
                [
                    'стул',
                    'стула',
                    'стулья',
                    'стульев',
                    'табурет',
                    'табурета',
                    'табуреты',
                    'табуреток',
                    'сиденье',
                    'сиденья',
                    'сидений',
                ]
            );
        }

        foreach ($chairHash as $keyword => $_) {
            if (mb_stripos($lower, $keyword) !== false) {
                return FurnitureCategory::CHAIR;
            }
        }

        // Armchair keywords.
        static $armchairHash = null;
        if ($armchairHash === null) {
            $armchairHash = array_flip(
                [
                    'кресл',
                    'кресло',
                    'кресла',
                    'кресел',
                    'пуф',
                    'пуфа',
                    'пуфы',
                    'банкетк',
                    'банкетка',
                    'банкетки',
                ]
            );
        }

        foreach ($armchairHash as $keyword => $_) {
            if (mb_stripos($lower, $keyword) !== false) {
                return FurnitureCategory::ARMCHAIR;
            }
        }

        // Nightstand keywords.
        static $nightstandHash = null;
        if ($nightstandHash === null) {
            $nightstandHash = array_flip(
                [
                    'тумб',
                    'тумба',
                    'тумбы',
                    'прикроватн',
                    'прикроватная',
                    'прикроватный',
                    'ночной стол',
                    'стол прислон',
                ]
            );
        }

        foreach ($nightstandHash as $keyword => $_) {
            if (mb_stripos($lower, $keyword) !== false) {
                return FurnitureCategory::NIGHTSTAND;
            }
        }

        return FurnitureCategory::OTHER;
    }// end detectCategory()

    /**
     * Determine balance type from inventory field content.
     */
    private function determineBalanceType(string $inventoryRaw): BalanceType
    {
        $normalized = mb_strtolower(trim($inventoryRaw));

        // Check for off-balance keywords.
        if (in_array($normalized, ['забаланс', 'за балансом', 'забалансовый', 'off_balance', 'offbalance'], true)) {
            return BalanceType::OFF_BALANCE;
        }

        return BalanceType::ON_BALANCE;
    }// end determineBalanceType()

    /**
     * Extract inventory number, clearing it for off-balance items.
     */
    private function extractInventoryNumber(string $inventoryRaw, BalanceType $balanceType): ?string
    {
        if ($balanceType->isOffBalance()) {
            return null;
        }

        $inventory = trim($inventoryRaw);

        // For on-balance items, if the field was empty or contained off-balance keyword, don't set inventory.
        if (empty($inventory)) {
            return null;
        }

        return $inventory;
    }// end extractInventoryNumber()

    /**
     * Resolve location from name string, creating if necessary.
     *
     * @param string            $locationRaw     The raw location string from the file.
     * @param Location|null     $defaultLocation Default location if none found.
     * @param array<string,int> $cache           Cache of location names to IDs.
     */
    private function resolveLocation(string $locationRaw, ?Location $defaultLocation, array &$cache): ?Location
    {
        if (empty($locationRaw)) {
            return $defaultLocation;
        }

        // Check cache first.
        if (isset($cache[$locationRaw])) {
            return $this->entityManager->find(Location::class, $cache[$locationRaw]);
        }

        // Try to find existing location by name.
        $location = $this->entityManager->getRepository(Location::class)
            ->findOneBy(['name' => $locationRaw]);

        if ($location) {
            $cache[$locationRaw] = $location->getId();

            return $location;
        }

        // Try to parse "Name (room)" or "Name, room" format.
        if (preg_match('/^(.+?)\s*[,(]\s*(.+?)\s*\)?$/u', $locationRaw, $matches)) {
            $name    = trim($matches[1]);
            $roomNum = trim($matches[2]);

            $location = $this->entityManager->getRepository(Location::class)
                ->findOneBy(['name' => $name, 'roomNumber' => $roomNum]);

            if ($location) {
                $cache[$locationRaw] = $location->getId();

                return $location;
            }

            // Create new location.
            $location = new Location();
            $location->setName($name);
            $location->setRoomNumber($roomNum);
            $this->entityManager->persist($location);

            // Cache will be refreshed after clear.
            return $location;
        }// end if

        // Return default if no location found.
        return $defaultLocation;
    }// end resolveLocation()

    /**
     * Generate a sample XLSX template for download.
     */
    public function generateTemplate(): \Symfony\Component\HttpFoundation\Response
    {
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet();

        $worksheet->setTitle('Мебель');

        // Headers.
        $worksheet->setCellValue('A1', 'Наименование');
        $worksheet->setCellValue('B1', 'Инвентарный номер');
        $worksheet->setCellValue('C1', 'Локация');

        // Sample data.
        $worksheet->setCellValue('A2', '14шт Стол офисный');
        $worksheet->setCellValue('B2', 'INV-000001');
        $worksheet->setCellValue('C2', 'Дирекция (101)');

        $worksheet->setCellValue('A3', 'Кресло');
        $worksheet->setCellValue('B3', 'забаланс');
        $worksheet->setCellValue('C3', 'Кабинет главбуха (205)');

        // Styling for header row.
        $worksheet->getStyle('A1:C1')->getFont()->setBold(true);
        $worksheet->getStyle('A1:C1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D3D3D3');

        // Auto-size columns.
        $worksheet->getColumnDimension('A')->setWidth(30);
        $worksheet->getColumnDimension('B')->setWidth(20);
        $worksheet->getColumnDimension('C')->setWidth(25);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $response = new \Symfony\Component\HttpFoundation\Response();
        $response->setContent($writer->save('php://output'));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="furniture_import_template.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }// end generateTemplate()
}// end class
