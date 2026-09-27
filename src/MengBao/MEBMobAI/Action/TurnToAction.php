<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

/**
 * 转向动作
 */
class TurnToAction extends Action
{
    private float $targetYaw;
    private ?Vector3 $targetPos;
    private ?Entity $targetEntity;
    private float $turnSpeed;

    public function __construct($target, float $turnSpeed = 10.0)
    {
        $this->turnSpeed = $turnSpeed;

        if ($target instanceof Entity) {
            $this->targetEntity = $target;
            $this->targetPos = null;
            $this->targetYaw = 0;
        } elseif ($target instanceof Vector3) {
            $this->targetPos = $target;
            $this->targetEntity = null;
            $this->targetYaw = 0;
        } elseif (is_float($target) || is_int($target)) {
            $this->targetYaw = (float)$target;
            $this->targetPos = null;
            $this->targetEntity = null;
        }
    }

    public function execute(AIEntity $entity): bool
    {
        $targetYaw = $this->targetYaw;

        if ($this->targetEntity !== null && !$this->targetEntity->isClosed()) {
            $targetYaw = $this->calculateYaw($entity->getLocation(), $this->targetEntity->getLocation());
        } elseif ($this->targetPos !== null) {
            $targetYaw = $this->calculateYaw($entity->getLocation(), $this->targetPos);
        }

        $currentYaw = $entity->getLocation()->yaw;
        $diff = $targetYaw - $currentYaw;

        // 标准化角度差到 -180 到 180
        while ($diff > 180) $diff -= 360;
        while ($diff < -180) $diff += 360;

        if (abs($diff) < $this->turnSpeed) {
            $entity->setRotation($targetYaw, $entity->getLocation()->pitch);
            $this->completed = true;
            return true;
        }

        $newYaw = $currentYaw + ($diff > 0 ? $this->turnSpeed : -$this->turnSpeed);
        $entity->setRotation($newYaw, $entity->getLocation()->pitch);

        return false;
    }

    private function calculateYaw(Vector3 $from, Vector3 $to): float
    {
        $dx = $to->x - $from->x;
        $dz = $to->z - $from->z;
        return atan2($dz, $dx) * 180 / M_PI - 90;
    }

    public function getName(): string
    {
        return "TurnTo";
    }
}
