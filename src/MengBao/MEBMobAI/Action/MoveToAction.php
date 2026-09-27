<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

/**
 * 移动到目标位置动作
 */
class MoveToAction extends Action
{
    private ?Vector3 $targetPos;
    private ?Entity $targetEntity;
    private float $speed;
    private float $minDistance;

    public function __construct($target, float $speed = 1.0, float $minDistance = 1.0)
    {
        if ($target instanceof Entity) {
            $this->targetEntity = $target;
            $this->targetPos = null;
        } elseif ($target instanceof Vector3) {
            $this->targetPos = $target;
            $this->targetEntity = null;
        }
        $this->speed = $speed;
        $this->minDistance = $minDistance;
    }

    public function execute(AIEntity $entity): bool
    {
        $targetPos = $this->targetPos;

        if ($this->targetEntity !== null) {
            if ($this->targetEntity->isClosed()) {
                $this->completed = true;
                return true;
            }
            $targetPos = $this->targetEntity->getLocation();
        }

        if ($targetPos === null) {
            $this->completed = true;
            return true;
        }

        $entityPos = $entity->getLocation();
        $distance = $entityPos->distance($targetPos);

        if ($distance <= $this->minDistance) {
            $this->completed = true;
            return true;
        }

        $dx = $targetPos->x - $entityPos->x;
        $dz = $targetPos->z - $entityPos->z;
        $horizontalDist = sqrt($dx * $dx + $dz * $dz);

        if ($horizontalDist > 0) {
            $motion = $entity->getMotion();
            $motionX = ($dx / $horizontalDist) * $this->speed * 0.15;
            $motionZ = ($dz / $horizontalDist) * $this->speed * 0.15;

            $entity->setMotion(new Vector3($motionX, $motion->y, $motionZ));

            // 转向目标
            $yaw = atan2($dz, $dx) * 180 / M_PI - 90;
            $entity->setRotation($yaw, $entityPos->pitch);
        }

        return false;
    }

    public function getName(): string
    {
        return "MoveTo";
    }
}
