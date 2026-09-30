<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\math\Vector3;

/**
 * 随机移动动作
 */
class RandomMoveAction extends Action
{
    private int $duration;
    private float $speed;
    private ?float $targetYaw = null;

    public function __construct(int $duration = 60, float $speed = 1.0)
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

        // 第一次执行时选择随机方向
        if ($this->targetYaw === null) {
            $this->targetYaw = mt_rand(0, 360);
        }

        // 设置朝向
        $entity->setRotation($this->targetYaw, $entity->getLocation()->pitch);

        // 前进
        $entity->moveForward($this->speed);

        $this->ticksRemaining--;
        return false;
    }

    public function reset(): void
    {
        parent::reset();
        $this->targetYaw = null;
    }

    public function getName(): string
    {
        return "RandomMove";
    }
}
