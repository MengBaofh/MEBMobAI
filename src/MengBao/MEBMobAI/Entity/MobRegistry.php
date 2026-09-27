<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Entity;

class MobRegistry
{
    private array $mobs = [];
    private array $mobConfigs = [];

    public function registerMob(string $identifier, string $entityClass, array $config = []): bool
    {
        if (!class_exists($entityClass)) {
            return false;
        }

        $this->mobs[$identifier] = $entityClass;
        $this->mobConfigs[$identifier] = $config;

        return true;
    }

    public function unregisterMob(string $identifier): bool
    {
        if (!isset($this->mobs[$identifier])) {
            return false;
        }

        unset($this->mobs[$identifier]);
        unset($this->mobConfigs[$identifier]);

        return true;
    }

    public function isMobRegistered(string $identifier): bool
    {
        return isset($this->mobs[$identifier]);
    }

    public function getMobClass(string $identifier): ?string
    {
        return $this->mobs[$identifier] ?? null;
    }

    public function getMobConfig(string $identifier): array
    {
        return $this->mobConfigs[$identifier] ?? [];
    }

    public function getRegisteredMobs(): array
    {
        return array_keys($this->mobs);
    }

    public function clear(): void
    {
        $this->mobs = [];
        $this->mobConfigs = [];
    }
}
