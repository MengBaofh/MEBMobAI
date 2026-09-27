<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

/**
 * 攻击动作
 */
class AttackAction extends Action
{
    private Entity $target;
    private float $damage;
    private float $range;

    public function __construct(Entity $target, float $damage = 1.0, float $range = 2.0)
    {
        $this->target = $target;
        $this->damage = $damage;
        $this->range = $range;
    }

    public function execute(AIEntity $entity): bool
    {
        if ($this->target->isClosed() || !$this->target->isAlive()) {
            $this->completed = true;
            return true;
        }

        $distance = $entity->getLocation()->distance($this->target->getLocation());

        if ($distance <= $this->range) {
            $ev = new EntityDamageByEntityEvent(
                $entity,
                $this->target,
                EntityDamageEvent::CAUSE_ENTITY_ATTACK,
                $this->damage
            );

            $this->target->attack($ev);
            $this->completed = true;
            return true;
        }

        return false;
    }

    public function getName(): string
    {
        return "Attack";
    }
}
