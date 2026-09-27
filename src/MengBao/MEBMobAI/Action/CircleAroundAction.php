<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\entity\Entity;

/**
 * 环绕目标动作
 */
class CircleAroundAction extends Action
{
    private Entity $target;
    private float $speed;
    private float $radius;
    private int $duration;
    private float $angleOffset = 0.0;

    public function __construct(Entity $target, float $radius = 5.0, float $speed = 1.0, int $duration = 200)
    {
        $this->target = $target;
        $this->radius = $radius;
        $this->speed = $speed;
        $this->duration = $duration;
        $this->ticksRemaining = $duration;
    }

    public function execute(AIEntity $entity): bool
    {
        if ($this->target->isClosed() || $this->ticksRemaining <= 0) {
            $this->completed = true;
            return true;
        }

        $targetPos = $this->target->getPosition();

        // 计算环绕位置
        $this->angleOffset += 0.05 * $this->speed; // 每tick旋转的角度
        $x = $targetPos->x + cos($this->angleOffset) * $this->radius;
        $z = $targetPos->z + sin($this->angleOffset) * $this->radius;

        $entityPos = $entity->getPosition();
        $dx = $x - $entityPos->x;
        $dz = $z - $entityPos->z;
        $distance = sqrt($dx * $dx + $dz * $dz);

        if ($distance > 0.5) {
            $motion = $entity->getMotion();
            $motionX = ($dx / $distance) * $this->speed * 0.15;
            $motionZ = ($dz / $distance) * $this->speed * 0.15;

            $entity->setMotion(new \pocketmine\math\Vector3($motionX, $motion->y, $motionZ));

            // 始终看向目标
            $entity->lookAt($this->target->getPosition()->add(0, 1, 0));
        }

        $this->ticksRemaining--;
        return false;
    }

    public function getName(): string
    {
        return "CircleAround";
    }
}
