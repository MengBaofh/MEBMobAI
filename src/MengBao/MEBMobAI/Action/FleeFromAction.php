<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\entity\Entity;

/**
 * 逃离目标动作
 */
class FleeFromAction extends Action
{
    private Entity $target;
    private float $speed;
    private int $duration;
    private float $safeDistance;

    public function __construct(Entity $target, float $speed = 1.2, int $duration = 100, float $safeDistance = 10.0)
    {
        $this->target = $target;
        $this->speed = $speed;
        $this->duration = $duration;
        $this->safeDistance = $safeDistance;
        $this->ticksRemaining = $duration;
    }

    public function execute(AIEntity $entity): bool
    {
        if ($this->target->isClosed() || $this->ticksRemaining <= 0) {
            $this->completed = true;
            return true;
        }

        $distance = $entity->getLocation()->distance($this->target->getLocation());

        // 如果已经足够远，完成动作
        if ($distance >= $this->safeDistance) {
            $this->completed = true;
            return true;
        }

        // 计算逃离方向（与目标相反）
        $from = $entity->getPosition();
        $to = $this->target->getPosition();

        $dx = $from->x - $to->x;
        $dz = $from->z - $to->z;
        $dist = sqrt($dx * $dx + $dz * $dz);

        if ($dist < 0.1) {
            $dx = mt_rand(-100, 100) / 100;
            $dz = mt_rand(-100, 100) / 100;
            $dist = sqrt($dx * $dx + $dz * $dz);
        }

        $motion = $entity->getMotion();
        $motionX = ($dx / $dist) * $this->speed * 0.15;
        $motionZ = ($dz / $dist) * $this->speed * 0.15;

        $entity->setMotion(new \pocketmine\math\Vector3($motionX, $motion->y, $motionZ));

        // 转向逃离方向
        $yaw = atan2($dz, $dx) * 180 / M_PI - 90;
        $entity->setRotation($yaw, $entity->getLocation()->pitch);

        $this->ticksRemaining--;
        return false;
    }

    public function getName(): string
    {
        return "FleeFrom";
    }
}
