<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use MengBao\MEBMobAI\Entity\FlyingEntity;

/**
 * 悬停动作
 */
class HoverAction extends Action
{
    private int $duration;

    public function __construct(int $duration = 20)
    {
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

        $entity->hover();
        $this->ticksRemaining--;
        return false;
    }

    public function getName(): string
    {
        return "Hover";
    }
}
