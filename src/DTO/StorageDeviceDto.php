<?php

declare(strict_types=1);

namespace App\DTO;

final class StorageDeviceDto
{
    public string $model = '';

    public string $serialNumber = '';

    public string $interface = '';

    public string $capacity = '';

    public string $type = '';

    public string $formFactor = '';

    public string $smartStatus = '';

    public string $powerOnHours = '';

    public string $powerCycleCount = '';

    public string $totalHostWrites = '';

    public string $totalHostReads = '';

    public string $temperature = '';

    public string $firmwareVersion = '';

    public string $controller = '';

    public string $flashType = '';

    public int $driveNumber = 0;

    public string $driveLetter = '';

    /**
     * Summary of toArray
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'model'             => $this->model,
            'serial_number'     => $this->serialNumber,
            'interface'         => $this->interface,
            'capacity'          => $this->capacity,
            'type'              => $this->type,
            'form_factor'       => $this->formFactor,
            'smart_status'      => $this->smartStatus,
            'power_on_hours'    => $this->powerOnHours,
            'power_cycle_count' => $this->powerCycleCount,
            'total_host_writes' => $this->totalHostWrites,
            'total_host_reads'  => $this->totalHostReads,
            'temperature'       => $this->temperature,
            'firmware_version'  => $this->firmwareVersion,
            'controller'        => $this->controller,
            'flash_type'        => $this->flashType,
            'drive_number'      => $this->driveNumber,
            'drive_letter'      => $this->driveLetter,
        ];
    }// end toArray()
}// end class
