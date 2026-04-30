<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RamModuleDto;
use App\DTO\StorageDeviceDto;
use App\DTO\SystemInfoDto;
use Symfony\Component\DomCrawler\Crawler;

use function in_array;

final class Aida64Parser
{
    private const SECTION_AIDA = 'AIDA64 Extreme';

    private const SECTION_SUMMARY = 'Суммарная информация';

    private const SECTION_PHYSICAL_DRIVES = 'Физические диски';

    private const SECTION_ATA = 'ATA';

    private const SECTION_SMART = 'SMART';

    /**
     * Парсит HTML-отчёт AIDA64 и возвращает DTO с данными.
     *
     * @param string $htmlContent Содержимое HTML-файла
     */
    public function parse(string $htmlContent): SystemInfoDto
    {
        $crawler = new Crawler($htmlContent);
        $dto     = new SystemInfoDto();

        // 1. Операционная система
        $dto->os = $this->extractOs($crawler);

        // 2. Материнская плата
        $dto->motherboard = $this->extractMotherboard($crawler);

        // 3. Процессор
        $dto->cpu = $this->extractCpu($crawler);

        // 4. Оперативная память
        $dto->ram = $this->extractRam($crawler);

        $dto->ramModules = $this->extractRamModules($crawler);

        // 5. Видеокарта (дискретная или встроенная – берём дискретную, если есть)
        $dto->gpu = $this->extractGpu($crawler);

        // 6. Накопитель (системный диск, с которого загружена ОС)
        $dto->storage = $this->extractSystemDrive($crawler);

        $dto->storageDevices = $this->extractStorageDevices($crawler);

        // 7. Сетевой адрес (MAC-адрес)
        $dto->networkAddress = $this->extractMacAddress($crawler);

        return $dto;
    }// end parse()

    /**
     * Извлечение ОС из раздела "Суммарная информация".
     */
    private function extractOs(Crawler $crawler): string
    {
        $summaryTable = $this->getSectionTable($crawler, self::SECTION_AIDA);
        if (! $summaryTable) {
            return '';
        }

        $value = $this->extractValueFromTable($summaryTable, 'Операционная система');

        return mb_trim($value);
    }// end extractOs()

    /**
     * Извлечение материнской платы.
     */
    private function extractMotherboard(Crawler $crawler): string
    {
        $summaryTable = $this->getSectionTable($crawler, self::SECTION_SUMMARY);
        if (! $summaryTable) {
            return '';
        }

        $value = $this->extractValueFromTable($summaryTable, 'Системная плата');

        return mb_trim($value);
    }// end extractMotherboard()

    /**
     * Извлечение процессора.
     */
    private function extractCpu(Crawler $crawler): string
    {
        $summaryTable = $this->getSectionTable($crawler, self::SECTION_SUMMARY);
        if (! $summaryTable) {
            return '';
        }

        $value = $this->extractValueFromTable($summaryTable, 'Тип ЦП');

        return mb_trim($value);
    }// end extractCpu()

    /**
     * Извлечение объёма и типа оперативной памяти.
     */
    private function extractRam(Crawler $crawler): string
    {
        $summaryTable = $this->getSectionTable($crawler, self::SECTION_SUMMARY);
        if (! $summaryTable) {
            return '';
        }

        $value = $this->extractValueFromTable($summaryTable, 'Системная память');

        return mb_trim($value);
    }// end extractRam()

    /**
     * Извлекает информацию о всех модулях оперативной памяти из секции SPD.
     *
     * @return RamModuleDto[]
     */
    private function extractRamModules(Crawler $crawler): array
    {
        $modules  = [];
        $spdTable = $this->getSectionTable($crawler, 'SPD');
        if (! $spdTable) {
            return $modules;
        }

        $rows          = $spdTable->filter('tr');
        $currentModule = null;

        foreach ($rows as $row) {
            $cells = (new Crawler($row))->filter('td');
            if ($cells->count() < 2) {
                // Пропускаем строки без 2 ячеек (пустые или неполные).
                continue;
            }

            $firstCell = $cells->eq(1);
            // Заголовок модуля: "[ DIMM1: Patriot Memory PSD38G1600L2 ]".
            if ('dt' === $firstCell->attr('class') && preg_match('/\[ (DIMM\d+): (.+) \]/', $firstCell->text(), $m)) {
                // Сохраняем предыдущий модуль.
                if (null !== $currentModule) {
                    $modules[] = $currentModule;
                }

                $currentModule        = new RamModuleDto();
                $currentModule->slot  = $m[1];
                $currentModule->model = $m[2];

                continue;
            }

            // Если текущий модуль активен, извлекаем свойства.
            if (null === $currentModule) {
                continue;
            }

            if ($cells->count() < 5) {
                // Пропускаем строки без 5 ячеек (пустые или неполные).
                continue;
            }

            $label = mb_trim($cells->eq(3)->text());
            $value = mb_trim($cells->eq(4)->text());

            // Сопоставляем метки с полями DTO.
            switch ($label) {
                case 'Фирма':
                case 'Производитель':
                    $currentModule->manufacturer = $value;

                    break;

                case 'Размер модуля':
                    $currentModule->size = $value;

                    break;

                case 'Тип памяти':
                    $currentModule->type = $value;

                    break;

                case 'Скорость памяти':
                    $currentModule->speed = $value;

                    break;

                case 'Напряжение модуля':
                    $currentModule->voltage = $value;

                    break;
                default:
                    break;
            }// end switch

            // Тайминги: строка вида "@ 800 МГц   11-11-11-28 (CL-RCD-RP-RAS) ...".
            if (str_starts_with($label, '@ 800 МГц') || str_starts_with($label, '@ 800 MHz')) {
                if (preg_match('/^(\d+-\d+-\d+-\d+)/', $value, $tm)) {
                    $currentModule->timings = $tm[1];
                }
            }
        }// end foreach

        // Добавляем последний модуль.
        if (null !== $currentModule) {
            $modules[] = $currentModule;
        }

        return $modules;
    }// end extractRamModules()

    /**
     * Вспомогательный метод для извлечения значения из таблицы свойств модуля.
     *
     * @param Crawler $propTable Таблица свойств (обёртка над строками)
     * @param string  $label     Название свойства (например "Размер модуля")
     */
    private function extractFromPropertiesTable(Crawler $propTable, string $label): string
    {
        foreach ($propTable as $row) {
            $cells = (new Crawler($row))->filter('td');
            if ($cells->count() >= 5) {
                $rowLabel = mb_trim($cells->eq(3)->text());
                if ($rowLabel === $label) {
                    return mb_trim($cells->eq(4)->text());
                }
            }
        }

        return '';
    }// end extractFromPropertiesTable()

    /**
     * Извлечение видеокарты (дискретной, если есть, иначе интегрированной).
     */
    private function extractGpu(Crawler $crawler): string
    {
        $summaryTable = $this->getSectionTable($crawler, self::SECTION_SUMMARY);
        if (! $summaryTable) {
            return '';
        }

        // В таблице может быть несколько строк "Видеоадаптер".
        $rows      = $summaryTable->filter('tr');
        $gpuValues = [];
        foreach ($rows as $row) {
            $cells = (new Crawler($row))->filter('td');
            if ($cells->count() >= 5) {
                $label = mb_trim($cells->eq(3)->text());
                if ('Видеоадаптер' === $label) {
                    $value = mb_trim($cells->eq(4)->text());
                    if ($value) {
                        $gpuValues[] = $value;
                    }
                }
            }
        }

        // Приоритет: дискретные карты NVIDIA/AMD, затем Intel.
        $discrete = array_filter(
            $gpuValues,
            static fn ($v) => false !== mb_stripos($v, 'nvidia') || false !== mb_stripos($v, 'amd')
        );
        if (! empty($discrete)) {
            return reset($discrete);
        }

        // Если дискретной нет, берём первую попавшуюся (обычно Intel HD).
        return $gpuValues[0] ?? '';
    }// end extractGpu()

    /**
     * Определение системного диска (с буквой C:) и возврат модели + объёма.
     */
    private function extractSystemDrive(Crawler $crawler): string
    {
        $physicalTable = $this->getSectionTable($crawler, self::SECTION_PHYSICAL_DRIVES);
        if (! $physicalTable) {
            return '';
        }

        // Ищем строку заголовка диска, содержащую "C:".
        $rows = $physicalTable->filter('tr');
        foreach ($rows as $row) {
            $crawlerRow = new Crawler($row);
            $headerCell = $crawlerRow->filter('td.dt');
            if ($headerCell->count() > 0) {
                $headerText = $headerCell->text();
                if (str_contains($headerText, 'C:')) {
                    // Извлекаем модель и объём из заголовка вида:
                    // "[ Диск #1 - Patriot Blast (серийник) [111 ГБ] C: ]".
                    if (preg_match('/-\s*([^\[]+)\s*\[([^\]]+)\]/', $headerText, $matches)) {
                        $model = mb_trim($matches[1]);
                        $size  = mb_trim($matches[2]);

                        return "{$model} ({$size})";
                    }

                    break;
                }
            }
        }

        return '';
    }// end extractSystemDrive()

    /**
     * Блок питания – в отчёте AIDA64 не предоставляется.
     */
    private function extractPowerSupply(Crawler $crawler): string
    {
        // В DMI есть раздел "Источники питания", но там пустые значения.
        return 'Не указан';
    }// end extractPowerSupply()

    /**
     * Извлекает MAC-адреса всех сетевых адаптеров.
     * Если адаптеров несколько, возвращает их через запятую.
     */
    private function extractMacAddress(Crawler $crawler): string
    {
        $macAddresses = [];

        // 1. Пробуем взять из раздела "Сеть Windows" – там есть таблица с сетевыми адаптерами.
        $networkTable = $this->getSectionTable($crawler, 'Сеть Windows');
        if ($networkTable) {
            // Ищем строки, содержащие "Аппаратный адрес".
            $rows = $networkTable->filter('tr');
            foreach ($rows as $row) {
                $cells = (new Crawler($row))->filter('td');
                if ($cells->count() >= 5) {
                    $label = mb_trim($cells->eq(3)->text());
                    if ('Аппаратный адрес' === $label) {
                        $mac = mb_trim($cells->eq(4)->text());
                        if (! empty($mac) && ! in_array($mac, $macAddresses)) {
                            $macAddresses[] = $mac;
                        }
                    }
                }
            }
        }

        // 2. Если в "Сеть Windows" не нашли, пробуем "Первичный адрес MAC" из суммарной информации
        if (empty($macAddresses)) {
            $summaryTable = $this->getSectionTable($crawler, self::SECTION_SUMMARY);
            if ($summaryTable) {
                $primaryMac = $this->extractValueFromTable($summaryTable, 'Первичный адрес MAC');
                if (! empty($primaryMac)) {
                    $macAddresses[] = $primaryMac;
                }
            }
        }

        return implode(', ', $macAddresses);
    }// end extractMacAddress()

    /**
     * Извлекает детальную информацию о всех физических накопителях.
     *
     * @return StorageDeviceDto[]
     */
    private function extractStorageDevices(Crawler $crawler): array
    {
        $devices = [];

        // 1. Получаем сырой список дисков из раздела "Физические диски"
        $physicalTable = $this->getSectionTable($crawler, self::SECTION_PHYSICAL_DRIVES);
        if (! $physicalTable) {
            return $devices;
        }

        // Заголовки дисков – строки с классом "dt".
        $driveHeaders = $physicalTable->filter('tr td.dt');
        foreach ($driveHeaders as $header) {
            $headerText = (new Crawler($header))->text();
            // Формат: "[ Диск #1 - Patriot Blast (A1AE076318DF00126420) [111 ГБ] C: ]".
            $pattern = '/\[ Диск #(\d+) - ([^\(\[]+)(?:\(([^)]+)\))?\s*\[([^\]]+)\](?:\s+([A-Z]:))?/u';
            if (! preg_match($pattern, $headerText, $matches)) {
                // Альтернативный паттерн для дисков без серийного номера или буквы.
                if (! preg_match('/\[ Диск #(\d+) - ([^\[\]]+)\[([^\]]+)\](?:\s+([A-Z]:))?/u', $headerText, $matches)) {
                    continue;
                }
            }

            $dto               = new StorageDeviceDto();
            $dto->driveNumber  = (int) $matches[1];
            $dto->model        = mb_trim($matches[2]);
            $dto->serialNumber = $matches[3] ?? '';
            $dto->capacity     = mb_trim($matches[4]);
            $dto->driveLetter  = $matches[5] ?? '';

            $devices[$dto->model . $dto->serialNumber] = $dto;
        }

        if (empty($devices)) {
            return [];
        }

        // 2. Обогащаем данными из раздела "ATA"
        $ataTable = $this->getSectionTable($crawler, self::SECTION_ATA);
        if ($ataTable) {
            // В разделе ATA каждый диск представлен отдельной таблицей с dt-заголовком.
            $ataHeaders = $ataTable->filter('tr td.dt');
            foreach ($ataHeaders as $header) {
                $headerText = (new Crawler($header))->text();
                // Заголовок: "[ Patriot Blast (A1AE076318DF00126420) ]" или "[ KINGSTON SA400S37480G (50026B7382433101) ]".
                if (preg_match('/\[ (.+?) \((.+?)\) \]/', $headerText, $matches)) {
                    $model  = mb_trim($matches[1]);
                    $serial = mb_trim($matches[2]);

                    // Ищем соответствующий диск по модели и серийному номеру.
                    $found = null;
                    foreach ($devices as $device) {
                        if (str_contains($device->model, $model) || str_contains($model, $device->model)) {
                            $found = $device;

                            break;
                        }
                    }
                    if (! $found) {
                        continue;
                    }

                    // Таблица свойств находится в следующей строке.
                    $headerRow = $header->parentNode;
                    $nextRow   = $headerRow->nextSibling;
                    while ($nextRow && 'tr' !== $nextRow->nodeName) {
                        $nextRow = $nextRow->nextSibling;
                    }
                    if (! $nextRow) {
                        continue;
                    }

                    $propTable = (new Crawler($nextRow))->filter('table tr');
                    if (0 === $propTable->count()) {
                        continue;
                    }

                    // Извлекаем свойства.
                    $found->interface       = $this->extractFromPropertiesTable($propTable, 'Тип устройства');
                    $found->firmwareVersion = $this->extractFromPropertiesTable($propTable, 'Версия');
                    $found->type            = $this->extractFromPropertiesTable($propTable, 'Скорость вращения');
                    // Если скорость вращения "SSD", то тип SSD; если число, то HDD.
                    if (false !== mb_stripos($found->type, 'SSD')) {
                        $found->type = 'SSD';
                    } elseif (is_numeric($found->type)) {
                        $found->type = 'HDD';
                    } else {
                        $found->type = $this->extractFromPropertiesTable($propTable, 'Скорость вращения') ?: 'Unknown';
                    }

                    // Форм-фактор (например "2.5"").
                    $found->formFactor = $this->extractFromPropertiesTable($propTable, 'Форм-фактор');
                    // Дополнительные поля для SSD.
                    $found->controller = $this->extractFromPropertiesTable($propTable, 'Тип контроллера');
                    $found->flashType  = $this->extractFromPropertiesTable($propTable, 'Тип флэш-памяти');
                }// end if
            }// end foreach
        }// end if

        // 3. Обогащаем данными из раздела "SMART".
        $smartTable = $this->getSectionTable($crawler, self::SECTION_SMART);
        if ($smartTable) {
            // Каждый диск в SMART имеет заголовок "[ Patriot Blast (A1AE076318DF00126420) ]".
            $smartHeaders = $smartTable->filter('tr td.dt');
            foreach ($smartHeaders as $header) {
                $headerText = (new Crawler($header))->text();
                if (preg_match('/\[ (.+?) \((.+?)\) \]/', $headerText, $matches)) {
                    $model  = mb_trim($matches[1]);
                    $serial = mb_trim($matches[2]);

                    $found = null;
                    foreach ($devices as $device) {
                        if (str_contains($device->model, $model) || str_contains($model, $device->model)) {
                            $found = $device;

                            break;
                        }
                    }
                    if (! $found) {
                        continue;
                    }

                    // Таблица атрибутов SMART – следующая строка.
                    $headerRow = $header->parentNode;
                    $nextRow   = $headerRow->nextSibling;
                    while ($nextRow && 'tr' !== $nextRow->nodeName) {
                        $nextRow = $nextRow->nextSibling;
                    }
                    if (! $nextRow) {
                        continue;
                    }

                    $attrTable = (new Crawler($nextRow))->filter('table tr');
                    if (0 === $attrTable->count()) {
                        continue;
                    }

                    // Парсим строки атрибутов.
                    foreach ($attrTable as $row) {
                        $cells = (new Crawler($row))->filter('td');
                        if ($cells->count() < 6) {
                            continue;
                        }
                        $attrName = mb_trim($cells->eq(1)->text());
                        $rawData  = mb_trim($cells->eq(5)->text());

                        switch ($attrName) {
                            case 'Power-On Hours Count':
                                $found->powerOnHours = $rawData;

                                break;

                            case 'Power Cycle Count':
                                $found->powerCycleCount = $rawData;

                                break;

                            case 'Lifetime Writes (GB)':
                            case 'Lifetime Writes to Flash':
                            case 'Host Writes (Sector Unit)':
                                // Извлекаем число из строки вида "51.75 ТБ" или "452 ГБ".
                                if (preg_match('/([\d\.]+)\s*([ТГM]Б)/iu', $rawData, $sizeMatches)) {
                                    $found->totalHostWrites = $rawData;
                                } else {
                                    $found->totalHostWrites = $rawData;
                                }

                                break;

                            case 'Host Reads (Sector Unit)':
                                $found->totalHostReads = $rawData;

                                break;

                            case 'Temperature':
                                // Извлекаем температуру, например "30, 30, 30".
                                if (preg_match('/(\d+)/', $rawData, $tempMatch)) {
                                    $found->temperature = $tempMatch[1] . ' °C';
                                }

                                break;

                            case 'SSD Life Remaining':
                            case 'SSD Life Left':
                                $found->smartStatus = $rawData . '% remaining';

                                break;
                        }// end switch
                    }// end foreach

                    // Общий SMART-статус из сводки (если есть)
                    // В конце таблицы SMART может быть строка с "Статус SMART" – но в данном отчёте её нет,
                    // есть только атрибуты. Статус OK виден в "Суммарная информация" -> "SMART-статус жёстких дисков"
                    // Попробуем взять оттуда.
                    if (empty($found->smartStatus)) {
                        $summaryTable = $this->getSectionTable($crawler, self::SECTION_SUMMARY);
                        if ($summaryTable) {
                            $globalSmart = $this->extractValueFromTable($summaryTable, 'SMART-статус жёстких дисков');
                            if ($globalSmart) {
                                $found->smartStatus = $globalSmart;
                            } else {
                                $found->smartStatus = 'OK';
                            }
                        } else {
                            $found->smartStatus = 'OK';
                        }
                    }
                }// end if
            }// end foreach
        }// end if

        return array_values($devices);
    }// end extractStorageDevices()

    /**
     * Находит таблицу, относящуюся к указанному разделу.
     *
     * @param string $sectionTitle Название раздела (например "Суммарная информация")
     */
    private function getSectionTable(Crawler $crawler, string $sectionTitle): ?Crawler
    {
        // Ищем все TD.pt, фильтруем по тексту.
        $headerCells = $crawler->filter('TD.pt');
        foreach ($headerCells as $cell) {
            if (mb_trim($cell->textContent) !== $sectionTitle) {
                continue;
            }

            // Поднимаемся до родительской таблицы заголовка.
            $parentTable = $cell;
            while ($parentTable && 'table' !== $parentTable->nodeName) {
                $parentTable = $parentTable->parentNode;
            }
            if (! $parentTable) {
                continue;
            }

            // Ищем следующую таблицу на том же уровне (таблицу данных).
            $dataTable = $parentTable->nextElementSibling;
            while ($dataTable && 'table' !== $dataTable->nodeName) {
                $dataTable = $dataTable->nextElementSibling;
            }

            if ($dataTable) {
                return new Crawler($dataTable);
            }
        }// end foreach

        return null;
    }// end getSectionTable()

    /**
     * Извлекает значение из таблицы по метке (label).
     * Метка находится в 4-м td (индекс 3), значение – в 5-м td (индекс 4).
     */
    private function extractValueFromTable(Crawler $table, string $label): string
    {
        $rows = $table->filter('TR');
        foreach ($rows as $row) {
            $cells = (new Crawler($row))->filter('TD');
            if (4 == $cells->count()) {
                $cellLabel = mb_trim($cells->eq(2)->text());
                if ($cellLabel === $label) {
                    return mb_trim($cells->eq(3)->text());
                }
            }
            if ($cells->count() >= 5) {
                $cellLabel = mb_trim($cells->eq(3)->text());
                if ($cellLabel === $label) {
                    return mb_trim($cells->eq(4)->text());
                }
            }
        }

        return '';
    }// end extractValueFromTable()
}// end class
