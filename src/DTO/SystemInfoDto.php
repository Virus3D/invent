<?php

declare(strict_types=1);

namespace App\DTO;

final class SystemInfoDto
{
    public string $cpu = '';

    public string $ram = '';

    /**
     * List RAM.
     *
     * @var array<RamModuleDto>
     */
    public array $ramModules = [];

    public string $storage = '';

    /**
     * List StorageDevice.
     *
     * @var array<StorageDeviceDto>
     */
    public array $storageDevices = [];

    public string $gpu = '';

    public string $motherboard = '';

    public string $os = '';

    public string $networkAddress = '';

    /**
     * Summary of toArray
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cpu'             => $this->cpu,
            'ram'             => $this->ram,
            'ram_modules'     => array_map(static fn ($m) => $m->toArray(), $this->ramModules),
            'storage'         => $this->storage,
            'storage_devices' => array_map(fn($d) => $d->toArray(), $this->storageDevices),
            'gpu'             => $this->gpu,
            'motherboard'     => $this->motherboard,
            'os'              => $this->os,
            'network_address' => $this->networkAddress,
        ];
    }// end toArray()
}// end class
