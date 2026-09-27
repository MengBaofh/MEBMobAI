<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use MengBao\MEBMobAI\Entity\FlyingEntity;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

/**
 * 飞向目标动作（支持3D移动）
 */
class FlyToAction extends Action
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
        if (!($entity instanceof FlyingEntity)) {
            $this->completed = true;
            return true;
        }

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

        $distance = $entity->getLocation()->distance($targetPos);

        if ($distance <= $this->minDistance) {
            $this->completed = true;
            return true;
        }

        $entity->flyToPosition($targetPos, $this->speed);
        return false;
    }

    public function getName(): string
    {
        return "FlyTo";
    }
}
