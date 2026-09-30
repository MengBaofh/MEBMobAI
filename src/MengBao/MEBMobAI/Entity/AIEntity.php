<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Entity;

use MengBao\MEBMobAI\Action\Action;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

/**
 * AI实体基类
 * 提供基于动作序列的智能体系统
 */
abstract class AIEntity extends Living
{
    /** @var Action[] 动作队列 */
    private array $actionQueue = [];

    /** 动作队列最大容量 */
    private int $maxActionQueueSize = 10;

    /** 当前正在执行的动作 */
    private ?Action $currentAction = null;

    /** 目标实体 */
    private ?Entity $targetEntity = null;

    /** 持续行为（每tick检查并更新） */
    private $behavior = null;

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        // 优先从NBT恢复自定义属性（服务器重启后的恢复）
        $hasNBTData = false;

        if ($nbt->getTag("MaxHealth") !== null) {
            $this->setMaxHealth((int)$nbt->getFloat("MaxHealth", $this->getMaxHealth()));
            $hasNBTData = true;
        }

        if ($nbt->getTag("Health") !== null) {
            $health = $nbt->getFloat("Health", $this->getHealth());
            $this->setHealth(min($health, $this->getMaxHealth()));
            $hasNBTData = true;
        }

        if ($nbt->getTag("MovementSpeed") !== null) {
            $this->setMovementSpeed($nbt->getFloat("MovementSpeed", $this->getMovementSpeed()));
            $hasNBTData = true;
        }

        if ($nbt->getTag("AttackDamage") !== null) {
            $attackDamage = $nbt->getFloat("AttackDamage", 0.0);
            $this->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->setValue($attackDamage);
            $hasNBTData = true;
        }

        // 如果没有NBT数据，从配置文件加载生物属性
        if (!$hasNBTData) {
            $plugin = \MengBao\MEBMobAI\Main::getInstance();
            if ($plugin !== null) {
                $config = $plugin->getCustomAIConfig();
                $mobKey = $this->getConfigKey();

                // 获取生物配置，如果不存在则使用默认值
                $speed = $config->getNested("{$mobKey}.speed", $config->get("default.speed", 1.0));
                $attackDamage = $config->getNested("{$mobKey}.attack_damage", $config->get("default.attack_damage", 0.0));
                $maxHealth = $config->getNested("{$mobKey}.max_health", $config->get("default.max_health", 20));
                $jumpHeight = $config->getNested("{$mobKey}.jump_height", $config->get("default.jump_height", 0.5));

                // 应用配置
                $this->setMovementSpeed($speed);
                $this->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->setValue($attackDamage);
                $this->setMaxHealth((int)$maxHealth);
                $this->setHealth($this->getMaxHealth());
                $this->jumpVelocity = (float)$jumpHeight;
            } else {
                // 如果插件未加载，使用默认方法
                $this->setMovementSpeed($this->getDefaultSpeed());
                $this->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->setValue($this->getDefaultAttackDamage());
                $this->setMaxHealth($this->getDefaultMaxHealth());
                $this->setHealth($this->getMaxHealth());
            }
        }
    }

    /**
     * 应用碰撞体积
     */
    protected function applyCollisionSize(float $widthMultiplier, float $heightMultiplier): void
    {
        try {
            $originalSize = $this->getInitialSizeInfo();
            $originalWidth = $originalSize->getWidth();
            $originalHeight = $originalSize->getHeight();
            $originalEyeHeight = $originalSize->getEyeHeight();

            $newWidth = $originalWidth * $widthMultiplier;
            $newHeight = $originalHeight * $heightMultiplier;
            $newEyeHeight = $originalEyeHeight * $heightMultiplier;

            // 参数顺序：height, width, eyeHeight
            $this->setSize(new \pocketmine\entity\EntitySizeInfo($newHeight, $newWidth, $newEyeHeight));

            \pocketmine\Server::getInstance()->getLogger()->info(
                "应用碰撞体积: " . get_class($this) .
                " 宽度: {$originalWidth} -> {$newWidth}" .
                " 高度: {$originalHeight} -> {$newHeight}"
            );
        } catch (\Throwable $e) {
            \pocketmine\Server::getInstance()->getLogger()->error(
                "设置碰撞体积失败: " . $e->getMessage()
            );
        }
    }

    /**
     * 获取配置文件中的键名
     * 默认返回类名转小写，子类可以重写
     */
    protected function getConfigKey(): string
    {
        $className = basename(str_replace('\\', '/', get_class($this)));
        // 移除 MEB 前缀
        $className = preg_replace('/^MEB/', '', $className);
        return strtolower($className);
    }

    /**
     * 获取默认速度
     */
    abstract protected function getDefaultSpeed(): float;

    /**
     * 获取默认攻击伤害
     */
    abstract protected function getDefaultAttackDamage(): float;

    /**
     * 获取默认最大生命值
     */
    protected function getDefaultMaxHealth(): int
    {
        return 20;
    }

    /**
     * 添加动作到队列
     */
    public function addAction(Action $action): bool
    {
        if (count($this->actionQueue) >= $this->maxActionQueueSize) {
            return false;
        }

        $this->actionQueue[] = $action;
        return true;
    }

    /**
     * 添加多个动作到队列
     * @param Action[] $actions
     */
    public function addActions(array $actions): void
    {
        foreach ($actions as $action) {
            if (!$this->addAction($action)) {
                break;
            }
        }
    }

    /**
     * 清空动作队列
     */
    public function clearActions(): void
    {
        $this->actionQueue = [];
        $this->currentAction = null;
    }

    /**
     * 获取当前动作队列数量
     */
    public function getActionQueueSize(): int
    {
        return count($this->actionQueue);
    }

    /**
     * 设置动作队列最大容量
     */
    public function setMaxActionQueueSize(int $size): void
    {
        $this->maxActionQueueSize = max(1, $size);
    }

    /**
     * 获取当前正在执行的动作
     */
    public function getCurrentAction(): ?Action
    {
        return $this->currentAction;
    }

    /**
     * 设置目标实体
     */
    public function setTargetEntity(?Entity $target): void
    {
        $this->targetEntity = $target;
    }

    /**
     * 获取目标实体
     */
    public function getTargetEntity(): ?Entity
    {
        return $this->targetEntity;
    }

    /**
     * 设置持续行为
     */
    public function setBehavior($behavior): void
    {
        $this->behavior = $behavior;
    }

    /**
     * 获取持续行为
     */
    public function getBehavior()
    {
        return $this->behavior;
    }

    /**
     * 清除持续行为
     */
    public function clearBehavior(): void
    {
        $this->behavior = null;
    }

    /**
     * 保存实体数据到NBT
     */
    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        // 保存自定义属性
        $nbt->setFloat("MaxHealth", $this->getMaxHealth());
        $nbt->setFloat("Health", $this->getHealth());
        $nbt->setFloat("MovementSpeed", $this->getMovementSpeed());

        $attackDamage = $this->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->getValue() ?? 0.0;
        $nbt->setFloat("AttackDamage", $attackDamage);

        // 保存动作队列大小（仅统计）
        $nbt->setInt("ActionQueueSize", count($this->actionQueue));

        return $nbt;
    }

    /**
     * 实体tick更新
     */
    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($this->isAlive()) {
            // 清除原版可能施加的运动，确保只受动作队列控制
            // 如果没有动作队列在执行，才允许自然运动
            if ($this->currentAction === null && count($this->actionQueue) === 0) {
                // 没有动作时，允许重力和摩擦力
            } else {
                // 有动作时，防止原版AI干扰
                // 保持垂直方向的重力，但清除可能的水平干扰
            }

            // 更新持续行为
            if ($this->behavior !== null && method_exists($this->behavior, 'update')) {
                $this->behavior->update();
            }

            $hasUpdate = $this->updateActions() || $hasUpdate;
        }

        return $hasUpdate;
    }

    /**
     * 更新动作执行
     */
    protected function updateActions(): bool
    {
        // 如果当前没有正在执行的动作，从队列中取出下一个
        if ($this->currentAction === null || $this->currentAction->isCompleted()) {
            if (count($this->actionQueue) > 0) {
                $this->currentAction = array_shift($this->actionQueue);
                $this->currentAction->reset();
            } else {
                $this->currentAction = null;
                return false;
            }
        }

        // 执行当前动作
        if ($this->currentAction !== null) {
            $this->currentAction->execute($this);
            return true;
        }

        return false;
    }

    /**
     * 检查前方是否需要跳跃
     */
    protected function needsJump(float $motionX, float $motionZ): bool
    {
        if (!$this->onGround) {
            return false;
        }

        $pos = $this->getPosition();
        $world = $this->getWorld();

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

            return $blockAbove->isTransparent() || $blockAbove2->isTransparent();
        }

        return false;
    }

    /**
     * 转向指定角度
     */
    public function turnTo(float $yaw): void
    {
        $this->setRotation($yaw, $this->getLocation()->pitch);
    }

    /**
     * 转向指定位置
     */
    public function lookAt(Vector3 $target): void
    {
        parent::lookAt($target);
    }

    /**
     * 前进
     */
    public function moveForward(float $speed = 1.0): void
    {
        $yaw = $this->getLocation()->yaw;
        $rad = deg2rad($yaw + 90);

        $motion = $this->getMotion();
        $motionX = cos($rad) * $speed * 0.15;
        $motionZ = sin($rad) * $speed * 0.15;

        $this->setMotion(new Vector3($motionX, $motion->y, $motionZ));
    }

    /**
     * 后退
     */
    public function moveBackward(float $speed = 1.0): void
    {
        $yaw = $this->getLocation()->yaw;
        $rad = deg2rad($yaw + 90);

        $motion = $this->getMotion();
        $motionX = -cos($rad) * $speed * 0.15;
        $motionZ = -sin($rad) * $speed * 0.15;

        $this->setMotion(new Vector3($motionX, $motion->y, $motionZ));
    }

    /**
     * 停止移动（仅水平方向）
     */
    public function stopMovement(): void
    {
        $motion = $this->getMotion();
        $this->setMotion(new Vector3(0, $motion->y, 0));
    }

    /**
     * 跳跃
     */
    public function performJump(): void
    {
        if ($this->onGround) {
            $this->jump();
        }
    }
}
