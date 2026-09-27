<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Action;

use MengBao\MEBMobAI\Entity\AIEntity;
use pocketmine\math\Vector3;

/**
 * 停止移动动作
 */
class StopAction extends Action
{
    public function execute(AIEntity $entity): bool
    {
        $motion = $entity->getMotion();
        $entity->setMotion(new Vector3(0, $motion->y, 0));
        $this->completed = true;
        return true;
    }

    public function getName(): string
    {
        return "Stop";
    }
}
