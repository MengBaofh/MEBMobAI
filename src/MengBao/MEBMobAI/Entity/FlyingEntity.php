<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Entity;

use pocketmine\math\Vector3;

/**
 * 飞行生物实体
 * 用于飞行的生物，不受重力影响，可以在Y轴自由移动
 */
abstract class FlyingEntity extends AIEntity
{
    /**
     * 获取初始重力（飞行生物无重力或轻微重力）
     */
    protected function getInitialGravity(): float
    {
        return 0.0; // 飞行生物不受重力影响
    }

    /**
     * 获取初始拖拽力（飞行生物拖拽更小）
     */
    protected function getInitialDragMultiplier(): float
    {
        return 0.01;
    }

    /**
     * 向上飞行
     */
    public function flyUp(float $speed = 1.0): void
    {
        $motion = $this->getMotion();
        $motionY = $speed * 0.1;
        $this->setMotion(new Vector3($motion->x, $motionY, $motion->z));
    }

    /**
     * 向下飞行
     */
    public function flyDown(float $speed = 1.0): void
    {
        $motion = $this->getMotion();
        $motionY = -$speed * 0.1;
        $this->setMotion(new Vector3($motion->x, $motionY, $motion->z));
    }

    /**
     * 悬停（保持当前高度）
     */
    public function hover(): void
    {
        $motion = $this->getMotion();
        $this->setMotion(new Vector3($motion->x, 0, $motion->z));
    }

    /**
     * 移动到指定位置（飞行移动，包含Y轴）
     */
    public function flyToPosition(Vector3 $target, float $speed = 1.0): void
    {
        $entityPos = $this->getLocation();
        $dx = $target->x - $entityPos->x;
        $dy = $target->y - $entityPos->y;
        $dz = $target->z - $entityPos->z;
        $distance = sqrt($dx * $dx + $dy * $dy + $dz * $dz);

        if ($distance > 0) {
            $motionX = ($dx / $distance) * $speed * 0.15;
            $motionY = ($dy / $distance) * $speed * 0.15;
            $motionZ = ($dz / $distance) * $speed * 0.15;

            $this->setMotion(new Vector3($motionX, $motionY, $motionZ));

            // 转向目标（水平方向）
            $yaw = atan2($dz, $dx) * 180 / M_PI - 90;

            // 计算俯仰角
            $horizontalDist = sqrt($dx * $dx + $dz * $dz);
            $pitch = -atan2($dy, $horizontalDist) * 180 / M_PI;

            $this->setRotation($yaw, $pitch);
        }
    }

    /**
     * 前进（飞行生物的前进包含俯仰角）
     */
    public function moveForward(float $speed = 1.0): void
    {
        $yaw = $this->getLocation()->yaw;
        $pitch = $this->getLocation()->pitch;

        $yawRad = deg2rad($yaw + 90);
        $pitchRad = deg2rad($pitch);

        $motionX = cos($yawRad) * cos($pitchRad) * $speed * 0.15;
        $motionY = -sin($pitchRad) * $speed * 0.15;
        $motionZ = sin($yawRad) * cos($pitchRad) * $speed * 0.15;

        $this->setMotion(new Vector3($motionX, $motionY, $motionZ));
    }

    /**
     * 后退（飞行生物的后退包含俯仰角）
     */
    public function moveBackward(float $speed = 1.0): void
    {
        $yaw = $this->getLocation()->yaw;
        $pitch = $this->getLocation()->pitch;

        $yawRad = deg2rad($yaw + 90);
        $pitchRad = deg2rad($pitch);

        $motionX = -cos($yawRad) * cos($pitchRad) * $speed * 0.15;
        $motionY = sin($pitchRad) * $speed * 0.15;
        $motionZ = -sin($yawRad) * cos($pitchRad) * $speed * 0.15;

        $this->setMotion(new Vector3($motionX, $motionY, $motionZ));
    }

    /**
     * 停止移动（包括垂直移动）
     */
    public function stopMovement(): void
    {
        $this->setMotion(new Vector3(0, 0, 0));
    }
}
