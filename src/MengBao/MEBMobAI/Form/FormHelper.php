<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Main;
use pocketmine\entity\Living;
use pocketmine\player\Player;

/**
 * GUI工具类
 */
final class FormHelper
{
    private function __construct()
    {
    }

    /**
     * 成功消息
     */
    public static function success(Main $plugin, Player $player, string $message): void
    {
        $player->sendMessage("§a[MEBMobAI] " . $message);
    }

    /**
     * 错误消息
     */
    public static function error(Main $plugin, Player $player, string $message): void
    {
        $player->sendMessage("§c[MEBMobAI] " . $message);
    }

    /**
     * 获取玩家视线内的生物
     */
    public static function getTargetEntity(Player $player, float $maxDistance = 8.0): ?Living
    {
        $direction = $player->getDirectionVector();
        $start = $player->getEyePos();

        $nearbyEntities = $player->getWorld()->getNearbyEntities(
            $player->getBoundingBox()->expandedCopy($maxDistance, $maxDistance, $maxDistance)
        );

        $closestEntity = null;
        $closestDistance = $maxDistance;

        foreach ($nearbyEntities as $entity) {
            if ($entity === $player || !($entity instanceof Living)) {
                continue;
            }

            $entityPos = $entity->getPosition()->add(0, $entity->getSize()->getHeight() / 2, 0);
            $toEntity = $entityPos->subtractVector($start);
            $distance = $toEntity->length();

            if ($distance > $maxDistance) {
                continue;
            }

            $toEntity = $toEntity->normalize();
            $dot = $direction->dot($toEntity);

            if ($dot > 0.9 && $distance < $closestDistance) {
                $closestDistance = $distance;
                $closestEntity = $entity;
            }
        }

        return $closestEntity;
    }

    /**
     * 格式化实体名称
     */
    public static function formatEntityName(Living $entity): string
    {
        $name = $entity->getName();
        $id = $entity->getId();
        return "{$name} (#{$id})";
    }
}
