<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\entity\Entity;

/**
 * 跟随目标动作
 */
class FollowAction extends Action
{
    private Entity $target;
    private float $speed;
    private float $followDistance;

    public function __construct(Entity $target, float $speed = 1.0, float $followDistance = 3.0)
    {
        $this->target = $target;
        $this->speed = $speed;
        $this->followDistance = $followDistance;
    }

    public function execute(AIEntity $entity): bool
    {
        if ($this->target->isClosed()) {
            $this->completed = true;
            return true;
        }

        $distance = $entity->getLocation()->distance($this->target->getLocation());

        // 如果距离合适，停止跟随
        if ($distance <= $this->followDistance) {
            $entity->stopMovement();
            return false; // 继续跟随，不完成
        }

        // 移动到目标
        $targetPos = $this->target->getLocation();
        $entityPos = $entity->getLocation();

        $dx = $targetPos->x - $entityPos->x;
        $dz = $targetPos->z - $entityPos->z;
        $horizontalDist = sqrt($dx * $dx + $dz * $dz);

        if ($horizontalDist > 0) {
            $motion = $entity->getMotion();
            $motionX = ($dx / $horizontalDist) * $this->speed * 0.15;
            $motionZ = ($dz / $horizontalDist) * $this->speed * 0.15;

            $entity->setMotion(new \pocketmine\math\Vector3($motionX, $motion->y, $motionZ));

            // 转向目标
            $yaw = atan2($dz, $dx) * 180 / M_PI - 90;
            $entity->setRotation($yaw, $entityPos->pitch);
        }

        return false; // 永远不完成，除非目标消失
    }

    public function getName(): string
    {
        return "Follow";
    }
}
