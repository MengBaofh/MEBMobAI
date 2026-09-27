<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Entity;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class MEBZombie extends WalkingEntity
{
    public static function getNetworkTypeId(): string
    {
        return EntityIds::ZOMBIE;
    }

    public function getName(): string
    {
        return "Zombie";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.8, 0.6);
    }

    protected function getDefaultSpeed(): float
    {
        return 1.0;
    }

    protected function getDefaultAttackDamage(): float
    {
        return 3.0;
    }

    protected function getDefaultMaxHealth(): int
    {
        return 20;
    }
}
