<?php

declare(strict_types=1);

namespace App\DTO;

final class RamModuleDto
{
    public string $slot = '';

    public string $manufacturer = '';

    public string $model = '';

    public string $size = '';

    public string $type = '';

    public string $speed = '';

    public string $timings = '';

    public string $voltage = '';

    /**
     * Summary of toArray
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slot'         => $this->slot,
            'manufacturer' => $this->manufacturer,
            'model'        => $this->model,
            'size'         => $this->size,
            'type'         => $this->type,
            'speed'        => $this->speed,
            'timings'      => $this->timings,
            'voltage'      => $this->voltage,
        ];
    }// end toArray()
}// end class
