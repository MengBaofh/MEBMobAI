<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Example;

use MengBao\MEBMobAI\Action\AttackAction;
use MengBao\MEBMobAI\Action\FlyToAction;
use MengBao\MEBMobAI\Action\FlyUpAction;
use MengBao\MEBMobAI\Action\HoverAction;
use MengBao\MEBMobAI\Action\JumpAction;
use MengBao\MEBMobAI\Action\MoveBackwardAction;
use MengBao\MEBMobAI\Action\MoveForwardAction;
use MengBao\MEBMobAI\Action\MoveToAction;
use MengBao\MEBMobAI\Action\StopAction;
use MengBao\MEBMobAI\Action\TurnToAction;
use MengBao\MEBMobAI\Action\WaitAction;
use MengBao\MEBMobAI\Entity\AIEntity;
use MengBao\MEBMobAI\Entity\FlyingEntity;
use MengBao\MEBMobAI\Entity\WalkingEntity;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

/**
 * AI系统使用示例
 */
class AIUsageExample
{
    /**
     * 示例1: 让地面生物巡逻
     */
    public static function patrolExample(WalkingEntity $entity, array $waypoints): void
    {
        // 清空现有动作
        $entity->clearActions();

        // 添加巡逻动作序列
        foreach ($waypoints as $waypoint) {
            $entity->addAction(new MoveToAction($waypoint, 1.0, 1.0));
            $entity->addAction(new WaitAction(40)); // 在每个点等待2秒（40 ticks）
        }
    }

    /**
     * 示例2: 让生物追击并攻击目标
     */
    public static function chaseAndAttackExample(WalkingEntity $entity, Entity $target): void
    {
        $entity->clearActions();
        $entity->setTargetEntity($target);

        // 转向目标
        $entity->addAction(new TurnToAction($target, 15.0));
        // 移动到目标附近
        $entity->addAction(new MoveToAction($target, 1.2, 2.0));
        // 攻击目标
        $entity->addAction(new AttackAction($target, 3.0, 2.5));
    }

    /**
     * 示例3: 复杂的动作序列
     */
    public static function complexSequenceExample(WalkingEntity $entity, Vector3 $target): void
    {
        $entity->clearActions();

        // 1. 前进3秒
        $entity->addAction(new MoveForwardAction(1.0, 60));
        // 2. 跳跃
        $entity->addAction(new JumpAction());
        // 3. 等待1秒
        $entity->addAction(new WaitAction(20));
        // 4. 转向180度
        $entity->addAction(new TurnToAction($entity->getLocation()->yaw + 180, 10.0));
        // 5. 后退2秒
        $entity->addAction(new MoveBackwardAction(0.8, 40));
        // 6. 停止
        $entity->addAction(new StopAction());
        // 7. 移动到目标位置
        $entity->addAction(new MoveToAction($target, 1.0, 1.0));
    }

    /**
     * 示例4: 飞行生物的飞行动作
     */
    public static function flyingExample(FlyingEntity $entity, Vector3 $destination): void
    {
        $entity->clearActions();

        // 1. 向上飞行2秒
        $entity->addAction(new FlyUpAction(1.5, 40));
        // 2. 悬停1秒
        $entity->addAction(new HoverAction(20));
        // 3. 飞向目标位置
        $entity->addAction(new FlyToAction($destination, 1.2, 1.0));
        // 4. 悬停2秒
        $entity->addAction(new HoverAction(40));
    }

    /**
     * 示例5: 动态添加动作（基于条件）
     */
    public static function conditionalActionsExample(AIEntity $entity, Entity $player): void
    {
        $entity->clearActions();

        $distance = $entity->getLocation()->distance($player->getLocation());

        if ($distance > 10) {
            // 距离远，快速接近
            $entity->addAction(new MoveToAction($player, 1.5, 3.0));
        } elseif ($distance > 3) {
            // 距离中等，正常接近
            $entity->addAction(new MoveToAction($player, 1.0, 2.0));
        } else {
            // 距离近，后退
            $entity->addAction(new MoveBackwardAction(1.0, 30));
        }

        // 最后攻击
        $entity->addAction(new AttackAction($player, 2.0, 2.5));
    }

    /**
     * 示例6: 设置最大动作队列大小
     */
    public static function setMaxQueueSizeExample(AIEntity $entity): void
    {
        // 设置最大队列容量为20
        $entity->setMaxActionQueueSize(20);

        // 现在可以添加最多20个动作
        for ($i = 0; $i < 20; $i++) {
            $entity->addAction(new MoveForwardAction(1.0, 10));
            $entity->addAction(new WaitAction(5));
        }
    }

    /**
     * 示例7: 检查动作队列状态
     */
    public static function checkQueueStatusExample(AIEntity $entity): void
    {
        // 获取当前队列中的动作数量
        $queueSize = $entity->getActionQueueSize();

        // 获取当前正在执行的动作
        $currentAction = $entity->getCurrentAction();

        if ($currentAction !== null) {
            echo "当前正在执行: " . $currentAction->getName() . "\n";
            echo "队列中还有 " . $queueSize . " 个动作\n";
        } else {
            echo "没有正在执行的动作\n";
        }
    }
}
