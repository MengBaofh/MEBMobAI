<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Component;

use MengBao\MEBMobAI\Main;
use pocketmine\entity\Living;
use pocketmine\Server;

/**
 * 生物AI管理器 - 负责为生物附加和管理AI组件
 */
class MobAIManager
{
    /** @var array<int, MobAIComponent> */
    private static array $aiComponents = [];

    /**
     * 为实体附加AI组件
     */
    public static function attachAI(Living $entity, bool $hostile, float $speed, float $attackDamage, array $targetTypes = [], float $jumpHeight = 0.5): void
    {
        $entityId = $entity->getId();

        // 避免重复附加
        if (isset(self::$aiComponents[$entityId])) {
            return;
        }

        $component = new MobAIComponent($entity, $hostile, $speed, $attackDamage, $targetTypes, $jumpHeight);
        self::$aiComponents[$entityId] = $component;
    }

    /**
     * 移除AI组件
     */
    public static function removeAI(int $entityId): void
    {
        unset(self::$aiComponents[$entityId]);
    }

    /**
     * 获取实体的AI组件
     */
    public static function getAIComponent(Living $entity): ?MobAIComponent
    {
        $entityId = $entity->getId();
        return self::$aiComponents[$entityId] ?? null;
    }

    /**
     * 更新所有AI组件
     */
    public static function updateAll(): void
    {
        foreach (self::$aiComponents as $entityId => $component) {
            // 检查实体是否还存在
            if ($component->getEntity()->isClosed() || !$component->getEntity()->isAlive()) {
                self::removeAI($entityId);
                continue;
            }

            // 检查MEBWorldProtect设置
            if (!self::canMobBehave($component->getEntity())) {
                continue; // 被保护，不更新AI
            }

            $component->update();
        }
    }

    /**
     * 清空所有AI组件
     */
    public static function clearAll(): void
    {
        self::$aiComponents = [];
    }

    /**
     * 检查生物是否可以行动（根据MEBWorldProtect设置）
     */
    public static function canMobBehave(Living $entity): bool
    {
        $world = $entity->getWorld();
        $worldName = $world->getFolderName();

        // 检查MEBWorldProtect插件是否存在
        $mebWorldProtect = Server::getInstance()->getPluginManager()->getPlugin("MEBWorldProtect");
        if ($mebWorldProtect === null || !$mebWorldProtect->isEnabled()) {
            return true; // 没有保护插件，默认允许
        }

        // 使用API检查生物行为权限
        try {
            if (method_exists($mebWorldProtect, "canMobBehave")) {
                return $mebWorldProtect->canMobBehave($worldName);
            }
        } catch (\Throwable $e) {
            // API调用失败，默认允许
            Server::getInstance()->getLogger()->warning(
                "MEBMobAI: 调用MEBWorldProtect API失败: " . $e->getMessage()
            );
        }

        return true; // 默认允许
    }

    /**
     * 获取AI组件数量
     */
    public static function getCount(): int
    {
        return count(self::$aiComponents);
    }

    /**
     * 获取实体的AI组件
     */
    public static function getAI(Living $entity): ?MobAIComponent
    {
        $entityId = $entity->getId();
        return self::$aiComponents[$entityId] ?? null;
    }
}
