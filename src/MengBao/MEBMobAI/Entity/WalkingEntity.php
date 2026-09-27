<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Entity;

use pocketmine\math\Vector3;

/**
 * 地面行走生物实体
 * 用于地面移动的生物，受重力影响，支持跳跃
 */
abstract class WalkingEntity extends AIEntity
{
    /**
     * 实体tick更新
     */
    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        // 地面行走生物的额外逻辑
        if ($this->isAlive()) {
            // 检查是否需要自动跳跃
            $hasUpdate = $this->checkAutoJump() || $hasUpdate;
        }

        return $hasUpdate;
    }

    /**
     * 检查是否需要自动跳跃
     */
    protected function checkAutoJump(): bool
    {
        $motion = $this->getMotion();

        // 只有在地面上且有水平移动时才检查
        if (!$this->onGround || (abs($motion->x) < 0.01 && abs($motion->z) < 0.01)) {
            return false;
        }

        if ($this->needsJump($motion->x, $motion->z)) {
            $this->performJump();
            return true;
        }

        return false;
    }

    /**
     * 移动到指定位置（地面移动）
     */
    public function moveToPosition(Vector3 $target, float $speed = 1.0): void
    {
        $entityPos = $this->getLocation();
        $dx = $target->x - $entityPos->x;
        $dz = $target->z - $entityPos->z;
        $distance = sqrt($dx * $dx + $dz * $dz);

        if ($distance > 0) {
            $motion = $this->getMotion();
            $motionX = ($dx / $distance) * $speed * 0.15;
            $motionZ = ($dz / $distance) * $speed * 0.15;

            $this->setMotion(new Vector3($motionX, $motion->y, $motionZ));

            // 转向目标
            $yaw = atan2($dz, $dx) * 180 / M_PI - 90;
            $this->setRotation($yaw, $entityPos->pitch);
        }
    }
}
