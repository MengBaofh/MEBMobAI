<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Entity;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class MEBBat extends FlyingEntity
{
    public static function getNetworkTypeId(): string
    {
        return EntityIds::BAT;
    }

    public function getName(): string
    {
        return "Bat";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.9, 0.5);
    }

    protected function getDefaultSpeed(): float
    {
        return 1.2;
    }

    protected function getDefaultAttackDamage(): float
    {
        return 1.0;
    }

    protected function getDefaultMaxHealth(): int
    {
        return 6;
    }
}
