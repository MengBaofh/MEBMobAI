<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Entity;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class MEBVillager extends WalkingEntity
{
    public static function getNetworkTypeId(): string
    {
        return EntityIds::VILLAGER;
    }

    public function getName(): string
    {
        return "Villager";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.8, 0.6);
    }

    protected function getDefaultSpeed(): float
    {
        return 0.8;
    }

    protected function getDefaultAttackDamage(): float
    {
        return 0.0;
    }

    protected function getDefaultMaxHealth(): int
    {
        return 20;
    }
}
