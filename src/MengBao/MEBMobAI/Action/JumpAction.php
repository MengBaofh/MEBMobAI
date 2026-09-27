<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\math\Vector3;

/**
 * 跳跃动作
 */
class JumpAction extends Action
{
    private float $jumpHeight;
    private bool $jumped = false;

    public function __construct(float $jumpHeight = 0.42)
    {
        $this->jumpHeight = $jumpHeight;
    }

    public function execute(AIEntity $entity): bool
    {
        if (!$this->jumped && $entity->onGround) {
            $motion = $entity->getMotion();
            $entity->setMotion(new Vector3($motion->x, $this->jumpHeight, $motion->z));
            $this->jumped = true;
        }

        // 等待落地
        if ($this->jumped && $entity->onGround) {
            $this->completed = true;
            return true;
        }

        return false;
    }

    public function reset(): void
    {
        parent::reset();
        $this->jumped = false;
    }

    public function getName(): string
    {
        return "Jump";
    }
}
