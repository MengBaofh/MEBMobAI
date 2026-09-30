<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Trait;

use pocketmine\entity\Living;
use pocketmine\world\World;

/**
 * 原版生物特性处理
 */
class VanillaMobTraits
{
    /**
     * 检查位置是否能看到天空
     */
    private static function canSeeSky(World $world, int $x, int $y, int $z): bool
    {
        // 使用 getRealBlockSkyLightAt 检查天空光照
        // 如果天空光照等于 15，说明能看到天空
        $skyLight = $world->getRealBlockSkyLightAt($x, $y, $z);
        return $skyLight >= 15;
    }

    /**
     * 处理僵尸类生物的白天燃烧
     */
    public static function handleUndeadBurning(Living $entity): void
    {
        if (!$entity->isAlive()) {
            return;
        }

        $world = $entity->getWorld();

        // 检查是否是白天
        if ($world->getSunAngleDegrees() >= 90 && $world->getSunAngleDegrees() < 270) {
            return; // 夜晚，不燃烧
        }

        // 检查是否在阳光下
        $pos = $entity->getPosition();
        if (!self::canSeeSky($world, (int)$pos->x, (int)($pos->y + 1), (int)$pos->z)) {
            return; // 不在阳光下
        }

        // 检查是否在水中
        if ($entity->isUnderwater()) {
            return;
        }

        // 检查是否戴头盔
        $helmet = $entity->getArmorInventory()->getHelmet();
        if (!$helmet->isNull()) {
            // 戴着头盔，头盔会损耗耐久
            if (mt_rand(0, 19) === 0) { // 5%几率损耗 (1/20)
                $damage = $helmet->getDamage() + 1;
                if ($damage >= $helmet->getMaxDurability()) {
                    $entity->getArmorInventory()->setHelmet(\pocketmine\item\VanillaItems::AIR());
                } else {
                    $helmet->setDamage($damage);
                    $entity->getArmorInventory()->setHelmet($helmet);
                }
            }
            return;
        }

        // 燃烧 - 原版僵尸燃烧时间较短
        if (!$entity->isOnFire()) {
            $entity->setOnFire(8);
        }
    }

    /**
     * 处理蜘蛛的攀爬能力
     */
    public static function handleSpiderClimbing(Living $entity): void
    {
        if (!$entity->isAlive() || !$entity->onGround) {
            return;
        }

        $motion = $entity->getMotion();

        // 检查是否贴着墙
        $world = $entity->getWorld();
        $pos = $entity->getPosition();

        $hasWall = false;
        $checkPositions = [
            [$pos->x + 0.5, $pos->y, $pos->z],
            [$pos->x - 0.5, $pos->y, $pos->z],
            [$pos->x, $pos->y, $pos->z + 0.5],
            [$pos->x, $pos->y, $pos->z - 0.5],
        ];

        foreach ($checkPositions as $checkPos) {
            $block = $world->getBlockAt((int)$checkPos[0], (int)$checkPos[1], (int)$checkPos[2]);
            if (!$block->isTransparent()) {
                $hasWall = true;
                break;
            }
        }

        if ($hasWall) {
            // 贴着墙时可以向上爬
            $entity->setMotion($motion->withComponents(null, 0.2, null));
        }
    }

    /**
     * 处理末影人的传送和水伤害
     */
    public static function handleEndermanTeleport(Living $entity): void
    {
        if (!$entity->isAlive()) {
            return;
        }

        $world = $entity->getWorld();
        $pos = $entity->getPosition();

        // 检查是否在雨中
        $inRain = false;
        if ($world->isRaining()) {
            $inRain = self::canSeeSky($world, (int)$pos->x, (int)($pos->y + 1), (int)$pos->z);
        }

        // 在水中或雨中受伤
        if ($entity->isUnderwater() || $inRain) {
            // 受到水伤害
            $ev = new \pocketmine\event\entity\EntityDamageEvent(
                $entity,
                \pocketmine\event\entity\EntityDamageEvent::CAUSE_DROWNING,
                1.0
            );
            $entity->attack($ev);

            // 随机传送
            if (mt_rand(0, 100) < 20) {
                self::randomTeleport($entity, 32);
            }
        }
    }

    /**
     * 处理苦力怕的爆炸
     */
    public static function handleCreeperExplosion(Living $entity): void
    {
        // 这个需要更复杂的状态管理，暂时保留接口
    }

    /**
     * 随机传送实体
     */
    private static function randomTeleport(Living $entity, float $maxDistance): bool
    {
        $world = $entity->getWorld();
        $pos = $entity->getPosition();

        for ($i = 0; $i < 16; $i++) {
            $x = $pos->x + (mt_rand(-100, 100) / 100) * $maxDistance;
            $y = $pos->y + mt_rand(-8, 8);
            $z = $pos->z + (mt_rand(-100, 100) / 100) * $maxDistance;

            // 检查目标位置是否安全
            if (!$world->isInWorld((int)$x, (int)$y, (int)$z)) {
                continue;
            }

            $targetBlock = $world->getBlockAt((int)$x, (int)$y, (int)$z);
            $blockAbove = $world->getBlockAt((int)$x, (int)$y + 1, (int)$z);

            if ($targetBlock->isTransparent() && $blockAbove->isTransparent()) {
                $entity->teleport(new \pocketmine\world\Position($x, $y, $z, $world));
                return true;
            }
        }

        return false;
    }

    /**
     * 根据实体类型应用原版特性
     */
    public static function applyVanillaTraits(Living $entity): void
    {
        $className = (new \ReflectionClass($entity))->getShortName();

        switch ($className) {
            case 'Zombie':
            case 'ZombieVillager':
            case 'Husk':
            case 'Skeleton':
            case 'Stray':
                self::handleUndeadBurning($entity);
                break;

            case 'Spider':
            case 'CaveSpider':
                self::handleSpiderClimbing($entity);
                break;

            case 'Enderman':
                self::handleEndermanTeleport($entity);
                break;

            case 'Creeper':
                self::handleCreeperExplosion($entity);
                break;
        }
    }
}
