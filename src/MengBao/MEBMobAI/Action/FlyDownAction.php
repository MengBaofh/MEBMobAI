<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use MengBao\MEBMobAI\Entity\FlyingEntity;
use pocketmine\math\Vector3;

/**
 * 向下飞行动作
 */
class FlyDownAction extends Action
{
    private float $speed;
    private int $duration;

    public function __construct(float $speed = 1.0, int $duration = 20)
    {
        $this->speed = $speed;
        $this->duration = $duration;
        $this->ticksRemaining = $duration;
    }

    public function execute(AIEntity $entity): bool
    {
        if (!($entity instanceof FlyingEntity)) {
            $this->completed = true;
            return true;
        }

        if ($this->ticksRemaining <= 0) {
            $this->completed = true;
            return true;
        }

        $entity->flyDown($this->speed);
        $this->ticksRemaining--;
        return false;
    }

    public function getName(): string
    {
        return "FlyDown";
    }
}
