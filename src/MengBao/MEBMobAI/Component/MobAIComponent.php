<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Component;

use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\math\Vector3;

use MengBao\MEBMobAI\Main;
use MengBao\MEBMobAI\Entity\AIEntity;
use MengBao\MEBMobAI\Entity\FlyingEntity;
use MengBao\MEBMobAI\Entity\WalkingEntity;

class MobAIComponent
{
    private Living $entity;
    private bool $hostile = false;
    private bool $friendly = true;
    private array $targetTypes = [];
    private ?Entity $target = null;
    private int $jumpCooldown = 0;
    private int $attackCooldown = 0;
    private int $targetCheckCooldown = 0;
    private int $randomWalkTimer = 0;
    private float $randomWalkYaw = 0.0;
    private int $fleeTimer = 0;
    private float $jumpHeight = 0.5;
    private bool $isAngry = false;  // 是否处于愤怒状态（中立生物反击）
    private int $angerTimer = 0;    // 愤怒计时器（tick）
    private int $knockbackCooldown = 0;  // 击退冷却，防止AI立即覆盖击退效果

    public function __construct(Living $entity, bool $hostile, float $speed, float $attackDamage, array $targetTypes = [], float $jumpHeight = 0.5)
    {
        $this->entity = $entity;
        $this->hostile = $hostile;
        $this->jumpHeight = $jumpHeight;

        // 使用Living核心的Attribute系统设置速度和攻击伤害
        $this->entity->setMovementSpeed($speed);
        $this->entity->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->setValue($attackDamage);

        if (empty($targetTypes)) {
            $this->targetTypes = [Player::class];
        } else {
            $this->targetTypes = $targetTypes;
        }

        // 初始化时保存AI数据
        $this->saveAIData();
    }

    public function getEntity(): Living
    {
        return $this->entity;
    }

    // 使用Living核心的Attribute系统获取速度
    private function getSpeed(): float
    {
        return $this->entity->getMovementSpeed();
    }

    // 使用Living核心的Attribute系统获取攻击伤害
    private function getAttackDamage(): float
    {
        return $this->entity->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->getValue() ?? 0.0;
    }

    // 使用Living核心的Attribute系统获取跟随范围
    private function getFollowRange(): float
    {
        return $this->entity->getAttributeMap()->get(Attribute::FOLLOW_RANGE)?->getValue() ?? 16.0;
    }

    public function update(): void
    {
        $entity = $this->entity;
        if ($entity->isClosed() || !$entity->isAlive()) return;

        if (!$entity instanceof Living) return;

        // 应用原版生物特性
        \MengBao\MEBMobAI\Trait\VanillaMobTraits::applyVanillaTraits($entity);

        $this->updateTargetCheck();
        $this->jumpCooldown--;
        $this->attackCooldown--;

        // 击退冷却，避免AI立即覆盖击退效果
        if ($this->knockbackCooldown > 0) {
            $this->knockbackCooldown--;
            return;
        }

        // 更新愤怒计时器
        if ($this->isAngry) {
            $this->angerTimer--;
            if ($this->angerTimer <= 0) {
                $this->isAngry = false;
                $this->target = null;  // 愤怒结束，清除目标
            }
        }

        if ($this->fleeTimer > 0) {
            $this->fleeTimer--;
            if ($this->fleeTimer <= 0) {
                // 逃跑结束，恢复正常速度
                $this->entity->setMovementSpeed($this->entity->getMovementSpeed() / 1.5);
            } else {
                $this->fleeFromAttacker();
            }
            return;
        }

        // 中立生物在愤怒状态下会追击攻击者
        // 敌对生物会主动搜索并攻击目标
        if ($this->target !== null && $this->target->isAlive() && !$this->target->isClosed()) {
            $this->updateMove();
            $this->checkAttack();
        } else {
            $this->target = null;
            $this->randomWalk();
        }
    }

    private function updateTargetCheck(): void
    {
        $entity = $this->entity;
        if ($this->targetCheckCooldown > 0) {
            $this->targetCheckCooldown--;
            return;
        }
        $this->targetCheckCooldown = 20;
        $this->checkTarget();
    }

    private function checkTarget(): void
    {
        // 如果已经有目标（愤怒状态的中立生物），不再搜索新目标
        if ($this->target !== null && $this->target->isAlive() && !$this->target->isClosed()) {
            return;
        }

        // 只有敌对生物才主动搜索目标
        if (!$this->hostile) return;

        $searchRange = $this->getFollowRange();

        $nearbyEntities = $this->entity->getWorld()->getNearbyEntities(
            $this->entity->getBoundingBox()->expandedCopy($searchRange, $searchRange, $searchRange)
        );

        $closestDistance = PHP_FLOAT_MAX;
        $closestTarget = null;

        foreach ($nearbyEntities as $entity) {
            if ($entity === $this->entity) {
                continue;
            }

            if (!($entity instanceof Living) || !$entity->isAlive() || $entity->isClosed()) {
                continue;
            }

            $isValidTarget = false;
            foreach ($this->targetTypes as $targetType) {
                if ($entity instanceof $targetType) {
                    if ($entity instanceof Player) {
                        if ($entity->getGamemode() !== GameMode::SURVIVAL()) {
                            continue 2;
                        }
                    }
                    $isValidTarget = true;
                    break;
                }
            }

            if (!$isValidTarget) {
                continue;
            }

            $distance = $this->entity->getLocation()->distance($entity->getLocation());
            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closestTarget = $entity;
            }
        }

        $this->target = $closestTarget;
    }

    private function updateMove(): void
    {
        if ($this->target === null) return;

        $targetPos = $this->target->getLocation();
        $entityPos = $this->entity->getLocation();

        $dx = $targetPos->x - $entityPos->x;
        $dz = $targetPos->z - $entityPos->z;
        $distance = sqrt($dx * $dx + $dz * $dz);

        if ($distance > 0.0) {
            $motion = $this->entity->getMotion();
            $speed = $this->getSpeed();
            $motionX = ($dx / $distance) * $speed * 0.15;
            $motionZ = ($dz / $distance) * $speed * 0.15;

            if ($this->entity instanceof Living) {
                if ($this->jumpCooldown <= 0 && $this->needsJump($motionX, $motionZ)) {
                    $this->entity->setMotion(new Vector3($motionX, $this->jumpHeight, $motionZ));
                    $this->jumpCooldown = 20;
                } else {
                    $this->entity->setMotion(new Vector3($motionX, $motion->y, $motionZ));
                }
            } else {
                $this->entity->setMotion(new Vector3($motionX, $motion->y, $motionZ));
            }

            $yaw = atan2($dz, $dx) * 180 / M_PI - 90;
            $this->entity->setRotation($yaw, $entityPos->pitch);
        }
    }

    private function checkAttack(): void
    {
        if ($this->target === null || $this->attackCooldown > 0) return;

        $distance = $this->entity->getLocation()->distance($this->target->getLocation());

        if ($distance <= 2.0) {
            $this->attackTarget();
            $this->attackCooldown = 20;
        }
    }

    private function attackTarget(): void
    {
        if ($this->target === null) return;

        $damage = $this->getAttackDamage();
        if ($damage <= 0) return;

        $ev = new \pocketmine\event\entity\EntityDamageByEntityEvent(
            $this->entity,
            $this->target,
            \pocketmine\event\entity\EntityDamageEvent::CAUSE_ENTITY_ATTACK,
            $damage
        );

        $this->target->attack($ev);
    }

    private function randomWalk(): void
    {
        if (!($this->entity instanceof Living)) return;
        if ($this->randomWalkTimer <= 0) {
            if (mt_rand(0, 100) < 30) {
                $this->entity->setMotion(new Vector3(0, $this->entity->getMotion()->y, 0));
                $this->randomWalkTimer = mt_rand(20, 60);
                $this->randomWalkYaw = -1;
            } else {
                $this->randomWalkYaw = mt_rand(0, 360);
                $this->randomWalkTimer = mt_rand(40, 80);
            }
        } else {
            $this->randomWalkTimer--;
        }

        if ($this->randomWalkTimer > 0 && $this->randomWalkYaw >= 0) {
            $rad = $this->randomWalkYaw * M_PI / 180;
            $motion = $this->entity->getMotion();
            $speed = $this->getSpeed();
            $motionX = -sin($rad) * $speed * 0.15;
            $motionZ = cos($rad) * $speed * 0.15;

            if ($this->entity instanceof Living) {
                if ($this->jumpCooldown <= 0 && $this->needsJump($motionX, $motionZ)) {
                    $this->entity->setMotion(new Vector3($motionX, $this->jumpHeight, $motionZ));
                    $this->jumpCooldown = 20;
                } else {
                    $this->entity->setMotion(new Vector3($motionX, $motion->y, $motionZ));
                }
            } else {
                $this->entity->setMotion(new Vector3($motionX, $motion->y, $motionZ));
            }

            $this->entity->setRotation($this->randomWalkYaw, 0);
        }
    }

    private function needsJump(float $motionX, float $motionZ): bool
    {
        if (!$this->entity->onGround) {
            return false;
        }
        $pos = $this->entity->getPosition();
        $world = $this->entity->getWorld();

        $frontX = (int)floor($pos->x + $motionX * 3);
        $frontY = (int)floor($pos->y);
        $frontZ = (int)floor($pos->z + $motionZ * 3);

        if (!$world->isInWorld($frontX, $frontY, $frontZ)) {
            return false;
        }

        $blockAhead = $world->getBlockAt($frontX, $frontY, $frontZ);
        if (!$blockAhead->isTransparent()) {
            $blockAbove = $world->getBlockAt($frontX, $frontY + 1, $frontZ);
            $blockAbove2 = $world->getBlockAt($frontX, $frontY + 2, $frontZ);

            if (!$blockAbove->isTransparent() && !$blockAbove2->isTransparent()) {
                return false;
            }

            return true;
        }

        $nearX = (int)floor($pos->x + $motionX);
        $nearZ = (int)floor($pos->z + $motionZ);
        $nearBlock = $world->getBlockAt($nearX, $frontY, $nearZ);
        if (!$nearBlock->isTransparent()) {
            $blockAbove = $world->getBlockAt($nearX, $frontY + 1, $nearZ);
            $blockAbove2 = $world->getBlockAt($nearX, $frontY + 2, $nearZ);

            if (!$blockAbove->isTransparent() && !$blockAbove2->isTransparent()) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function fleeFromAttacker(): void
    {
        if ($this->target === null) {
            return;
        }

        $from = $this->entity->getPosition();
        $to = $this->target->getPosition();

        $dx = $from->x - $to->x;
        $dz = $from->z - $to->z;
        $distance = sqrt($dx * $dx + $dz * $dz);

        if ($distance < 0.1) {
            $dx = mt_rand(-100, 100) / 100;
            $dz = mt_rand(-100, 100) / 100;
            $distance = sqrt($dx * $dx + $dz * $dz);
        }

        $speed = $this->getSpeed();
        $motion = $this->entity->getMotion();
        $motionX = ($dx / $distance) * $speed * 0.15;
        $motionZ = ($dz / $distance) * $speed * 0.15;

        if ($this->entity instanceof Living) {
            if ($this->jumpCooldown <= 0 && $this->needsJump($motionX, $motionZ)) {
                $this->entity->setMotion(new Vector3($motionX, $this->jumpHeight, $motionZ));
                $this->jumpCooldown = 20;
            } else {
                $this->entity->setMotion(new Vector3($motionX, $motion->y, $motionZ));
            }
        } else {
            $this->entity->setMotion(new Vector3($motionX, $motion->y, $motionZ));
        }

        $yaw = atan2($dz, $dx) * 180 / M_PI - 90;
        $this->entity->setRotation($yaw, 0);
    }

    public function onDamaged(\pocketmine\entity\Entity $attacker): void
    {
        if (!$this->hostile && $attacker instanceof Living && $this->entity instanceof Living) {
            $this->target = $attacker;
            $this->entity->setTargetEntity($attacker);
            $this->fleeTimer = 100;

            // 逃跑时提速（速度x1.5）
            $normalSpeed = $this->entity->getMovementSpeed();
            $this->entity->setMovementSpeed($normalSpeed * 1.5);
        }
    }

    /**
     * 获取是否为敌对生物
     */
    public function isHostile(): bool
    {
        return $this->hostile;
    }

    /**
     * 判断是否可以攻击（有攻击伤害）
     */
    public function canAttack(): bool
    {
        $damage = $this->entity->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->getValue() ?? 0.0;
        return $damage > 0.0;
    }

    /**
     * 设置目标
     */
    public function setTarget(?\pocketmine\entity\Entity $target): void
    {
        $this->target = $target;
        if ($target !== null && $this->entity instanceof Living) {
            $this->entity->setTargetEntity($target);
        }
    }

    /**
     * 设置愤怒状态（中立生物反击）
     */
    public function setAngry(bool $angry, int $duration = 400): void
    {
        $this->isAngry = $angry;
        if ($angry) {
            $this->angerTimer = $duration;  // 默认20秒（400 tick）
        } else {
            $this->angerTimer = 0;
        }
    }

    /**
     * 获取是否愤怒
     */
    public function isAngry(): bool
    {
        return $this->isAngry;
    }

    /**
     * 设置击退冷却
     */
    public function setKnockbackCooldown(int $ticks): void
    {
        $this->knockbackCooldown = $ticks;
    }

    /**
     * 保存AI数据到实体的 NamedTag
     */
    public function saveAIData(): void
    {
        // 使用实体内部存储保存自定义数据
        // 这些数据会在实体的 saveNBT() 时自动包含
        $reflection = new \ReflectionClass($this->entity);

        try {
            // 尝试访问实体的私有属性来存储数据
            $property = $reflection->getProperty('namedtag');
            $property->setAccessible(true);
            $nbt = $property->getValue($this->entity);

            if ($nbt instanceof \pocketmine\nbt\tag\CompoundTag) {
                $nbt->setByte("MEBMobAI_Hostile", $this->hostile ? 1 : 0);
                $nbt->setFloat("MEBMobAI_Speed", $this->entity->getMovementSpeed());
                $nbt->setFloat("MEBMobAI_AttackDamage", $this->entity->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->getValue() ?? 0.0);
                $nbt->setFloat("MEBMobAI_JumpHeight", $this->jumpHeight);
                $nbt->setFloat("MEBMobAI_MaxHealth", $this->entity->getMaxHealth());
            }
        } catch (\ReflectionException $e) {
            // 如果无法访问，静默失败
            // 原版生物的属性修改将不会持久化
        }
    }

    /**
     * 从实体NBT加载AI数据
     */
    public static function loadFromNBT(Living $entity): ?array
    {
        $reflection = new \ReflectionClass($entity);

        try {
            $property = $reflection->getProperty('namedtag');
            $property->setAccessible(true);
            $nbt = $property->getValue($entity);

            if (!($nbt instanceof \pocketmine\nbt\tag\CompoundTag)) {
                return null;
            }

            // 检查是否有保存的AI数据
            if ($nbt->getTag("MEBMobAI_Hostile") === null) {
                return null;
            }

            return [
                "hostile" => $nbt->getByte("MEBMobAI_Hostile", 0) === 1,
                "speed" => $nbt->getFloat("MEBMobAI_Speed", 1.0),
                "damage" => $nbt->getFloat("MEBMobAI_AttackDamage", 0.0),
                "jump_height" => $nbt->getFloat("MEBMobAI_JumpHeight", 0.5),
                "max_health" => $nbt->getFloat("MEBMobAI_MaxHealth", 20.0),
            ];
        } catch (\ReflectionException $e) {
            return null;
        }
    }
}
