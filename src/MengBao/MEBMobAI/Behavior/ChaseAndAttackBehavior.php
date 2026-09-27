<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Behavior;

use MengBao\MEBMobAI\Action\AttackAction;
use MengBao\MEBMobAI\Action\MoveToAction;
use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\entity\Entity;

/**
 * 持续追击行为
 * 当动作队列为空时自动重新添加追击动作
 */
class ChaseAndAttackBehavior
{
    private AIEntity $entity;
    private Entity $target;
    private float $speed;
    private float $damage;
    private float $chaseDistance;
    private float $attackRange;

    public function __construct(AIEntity $entity, Entity $target, float $speed = 1.2, float $damage = 3.0, float $chaseDistance = 2.0, float $attackRange = 2.5)
    {
        $this->entity = $entity;
        $this->target = $target;
        $this->speed = $speed;
        $this->damage = $damage;
        $this->chaseDistance = $chaseDistance;
        $this->attackRange = $attackRange;
    }

    /**
     * 每tick调用此方法检查并更新行为
     */
    public function update(): void
    {
        // 如果目标消失，停止
        if ($this->target->isClosed() || !$this->target->isAlive()) {
            return;
        }

        // 如果队列为空，重新添加追击动作
        if ($this->entity->getActionQueueSize() === 0) {
            $this->entity->addAction(new MoveToAction($this->target, $this->speed, $this->chaseDistance));
            $this->entity->addAction(new AttackAction($this->target, $this->damage, $this->attackRange));
        }
    }

    public function getTarget(): Entity
    {
        return $this->target;
    }

    public function setTarget(Entity $target): void
    {
        $this->target = $target;
    }
}
