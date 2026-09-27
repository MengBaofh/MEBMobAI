<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\math\Vector3;

/**
 * 前进动作
 */
class MoveForwardAction extends Action
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
        if ($this->ticksRemaining <= 0) {
            $this->completed = true;
            return true;
        }

        $yaw = $entity->getLocation()->yaw;
        $rad = deg2rad($yaw + 90);

        $motion = $entity->getMotion();
        $motionX = cos($rad) * $this->speed * 0.15;
        $motionZ = sin($rad) * $this->speed * 0.15;

        $entity->setMotion(new Vector3($motionX, $motion->y, $motionZ));

        $this->ticksRemaining--;
        return false;
    }

    public function getName(): string
    {
        return "MoveForward";
    }
}
