<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;

/**
 * 智能体动作接口
 * 每个动作代表生物的一个具体行为
 */
abstract class Action
{
    protected bool $completed = false;
    protected int $ticksRemaining = 0;

    /**
     * 执行动作
     * @return bool 动作是否完成
     */
    abstract public function execute(AIEntity $entity): bool;

    /**
     * 动作是否完成
     */
    public function isCompleted(): bool
    {
        return $this->completed;
    }

    /**
     * 重置动作状态
     */
    public function reset(): void
    {
        $this->completed = false;
        $this->ticksRemaining = 0;
    }

    /**
     * 获取动作名称
     */
    abstract public function getName(): string;
}
