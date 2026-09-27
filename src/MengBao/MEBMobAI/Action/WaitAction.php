<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;

/**
 * 等待动作
 */
class WaitAction extends Action
{
    private int $duration;

    public function __construct(int $ticks)
    {
        $this->duration = $ticks;
        $this->ticksRemaining = $ticks;
    }

    public function execute(AIEntity $entity): bool
    {
        if ($this->ticksRemaining <= 0) {
            $this->completed = true;
            return true;
        }

        $this->ticksRemaining--;
        return false;
    }

    public function getName(): string
    {
        return "Wait";
    }
}
