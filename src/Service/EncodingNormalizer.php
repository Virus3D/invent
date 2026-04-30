<?php

declare(strict_types=1);

namespace App\Service;

class EncodingNormalizer
{
    /**
     * Приводит содержимое файла к UTF-8 (без BOM).
     * Поддерживает UTF-8, UTF-16LE, UTF-16BE, Windows-1251 и другие кодировки.
     *
     * @param string $content Исходное содержимое
     *
     * @return string Содержимое в UTF-8
     */
    public function normalizeToUtf8(string $content): string
    {
        // 1. Определяем кодировку по BOM
        $encoding = $this->detectEncodingByBom($content);

        // 2. Если BOM не помог, пробуем mb_detect_encoding с расширенным списком
        if (!$encoding) {
            $encoding = mb_detect_encoding(
                $content,
                [
                    'UTF-8',
                    'UTF-16LE',
                    'UTF-16BE',
                    'UTF-32LE',
                    'UTF-32BE',
                    'Windows-1251',
                    'CP1251',
                    'ISO-8859-1',
                    'ASCII',
                ],
                true
            );
        }

        // 3. Убираем BOM (если есть) для UTF-8
        if ($encoding === 'UTF-8') {
            $content = $this->removeBom($content);
            return $content;
        }

        // 4. Преобразуем в UTF-8
        if ($encoding && $encoding !== 'UTF-8') {
            $converted = mb_convert_encoding($content, 'UTF-8', $encoding);
            if ($converted !== false) {
                return $this->removeBom($converted);
            }
        }

        // 5. Если всё остальное не помогло, возвращаем исходник (или можно выбросить исключение)
        return $content;
    }// end normalizeToUtf8()

    /**
     * Определяет кодировку по BOM (Byte Order Mark).
     *
     * @return string|null Кодировка или null
     */
    private function detectEncodingByBom(string $content): ?string
    {
        $bom = substr($content, 0, 4);

        // UTF-32 BE.
        if ($bom === "\x00\x00\xFE\xFF") {
            return 'UTF-32BE';
        }
        // UTF-32 LE.
        if ($bom === "\xFF\xFE\x00\x00") {
            return 'UTF-32LE';
        }

        $bom2 = substr($content, 0, 2);
        // UTF-16 BE.
        if ($bom2 === "\xFE\xFF") {
            return 'UTF-16BE';
        }
        // UTF-16 LE.
        if ($bom2 === "\xFF\xFE") {
            return 'UTF-16LE';
        }

        // UTF-8 BOM.
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            return 'UTF-8';
        }

        return null;
    }// end detectEncodingByBom()

    /**
     * Удаляет BOM (Byte Order Mark) из строки.
     */
    private function removeBom(string $content): string
    {
        $boms = [
            // UTF-8.
            "\xEF\xBB\xBF",
            // UTF-16BE.
            "\xFE\xFF",
            // UTF-16LE.
            "\xFF\xFE",
            // UTF-32BE.
            "\x00\x00\xFE\xFF",
            // UTF-32LE.
            "\xFF\xFE\x00\x00",
        ];
        foreach ($boms as $bom) {
            if (str_starts_with($content, $bom)) {
                $content = substr($content, strlen($bom));
                break;
            }
        }
        return $content;
    }// end removeBom()
}// end class
