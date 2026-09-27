<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Listener;

use MengBao\MEBMobAI\Component\MobAIManager;
use MengBao\MEBMobAI\Main;
use pocketmine\entity\Living;
use pocketmine\Server;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

/**
 * 生物监听器 - 自动为生物附加AI
 */
class EntityListener implements Listener
{
    private ?Config $vanillaAIConfig = null;

    public function __construct()
    {
        // 加载原版生物AI配置
        $configPath = Main::getInstance()->getDataFolder() . "vanilla_mob_ai.yml";
        if (file_exists($configPath)) {
            $this->vanillaAIConfig = new Config($configPath, Config::YAML);
        }
    }

    /**
     * 监听生物生成事件
     */
    public function onEntitySpawn(EntitySpawnEvent $event): void
    {
        $entity = $event->getEntity();

        // 处理原版生物和自定义生物
        if (!($entity instanceof Living)) {
            return;
        }
        // 排除玩家
        if ($entity instanceof \pocketmine\player\Player) {
            return;
        }

        // 排除自定义AI实体（AIEntity），它们有自己的动作系统
        if ($entity instanceof \MengBao\MEBMobAI\Entity\AIEntity) {
            return;
        }

        // 获取生物配置并附加AI
        $aiConfig = $this->getAIConfigForEntity($entity);

        if ($aiConfig !== null) {
            $targetTypes = $aiConfig["target_types"] ?? [];
            $jumpHeight = $aiConfig["jump_height"] ?? 0.5;

            MobAIManager::attachAI(
                $entity,
                $aiConfig["hostile"] ?? false,
                $aiConfig["speed"] ?? 1.0,
                $aiConfig["damage"] ?? 0.0,
                $targetTypes,
                $jumpHeight
            );
            Server::getInstance()->getLogger()->info("附加AI到生物: " . get_class($entity));
        }
    }

    /**
     * 根据实体类型获取AI配置
     */
    private function getAIConfigForEntity(Living $entity): ?array
    {
        $entityClass = get_class($entity);
        $entityName = basename(str_replace('\\', '/', $entityClass));

        // 实体名称转换为配置键名（例如：Zombie -> zombie）
        $configKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $entityName));

        // 从vanilla_mob_ai.yml读取配置
        if ($this->vanillaAIConfig !== null && $this->vanillaAIConfig->exists($configKey)) {
            return $this->vanillaAIConfig->get($configKey);
        }

        // 如果没有特定配置，使用默认配置
        return $this->getDefaultAIConfig($entityName);
    }

    /**
     * 获取默认AI配置（当没有特定配置时）
     */
    private function getDefaultAIConfig(string $entityName): ?array
    {
        // 怪物列表（敌对）
        $monsters = [
            'Zombie',
            'ZombieVillager',
            'Husk',
            'Drowned',
            'Skeleton',
            'Stray',
            'WitherSkeleton',
            'Creeper',
            'Spider',
            'CaveSpider',
            'Enderman',
            'Blaze',
            'Ghast',
            'Witch',
            'Pillager',
            'Vindicator',
            'Evoker',
            'Ravager',
            'Vex',
            'Phantom',
            'Slime',
            'MagmaCube',
            'Guardian',
            'ElderGuardian',
            'Shulker',
            'Piglin',
            'PiglinBrute',
            'Hoglin',
            'Zoglin',
            'Wither',
            'EnderDragon'
        ];

        // 和平生物列表
        $passives = [
            'Cow',
            'Pig',
            'Sheep',
            'Chicken',
            'Rabbit',
            'Horse',
            'Donkey',
            'Mule',
            'Cat',
            'Wolf',
            'Ocelot',
            'Parrot',
            'Llama',
            'Fox',
            'Panda',
            'Bee',
            'Villager',
            'IronGolem',
            'SnowGolem',
            'Squid',
            'Dolphin',
            'Turtle',
            'Cod',
            'Salmon',
            'TropicalFish',
            'Pufferfish',
            'Axolotl',
            'Goat',
            'Frog',
            'Tadpole',
            'Allay',
            'Bat',
            'Strider',
            'Mooshroom'
        ];

        // 判断生物类型
        if (in_array($entityName, $monsters)) {
            return [
                "hostile" => true,
                "speed" => 1.2,
                "damage" => 4.0,
                "jump_height" => 0.5,
                "search_range" => 16.0,
                "attack_distance" => 0.5,
                "target_types" => ["pocketmine\\player\\Player"]
            ];
        }

        if (in_array($entityName, $passives)) {
            return [
                "hostile" => false,
                "speed" => 0.8,
                "damage" => 0.0,
                "jump_height" => 0.5,
                "search_range" => 16.0,
                "attack_distance" => 0.5,
                "target_types" => []
            ];
        }

        // 使用配置文件中的默认值
        if ($this->vanillaAIConfig !== null) {
            $defaultConfig = $this->vanillaAIConfig->get("default");
            if ($defaultConfig !== null) {
                return $defaultConfig;
            }
        }

        // 最终默认值
        return [
            "hostile" => false,
            "speed" => 0.8,
            "damage" => 0.0,
            "jump_height" => 0.5,
            "search_range" => 16.0,
            "attack_distance" => 0.5,
            "target_types" => []
        ];
    }

    /**
     * 监听实体受伤事件 - 触发被动生物逃跑或中立生物反击
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        // 只处理Living实体
        if (!($entity instanceof Living)) {
            return;
        }

        // 获取该实体的AI组件
        $aiComponent = MobAIManager::getAIComponent($entity);
        if ($aiComponent === null) {
            return;
        }

        // 如果是被其他实体攻击
        if ($event instanceof EntityDamageByEntityEvent) {
            $attacker = $event->getDamager();

            // 攻击者必须是Living实体
            if (!($attacker instanceof Living)) {
                return;
            }

            // 中立生物反击逻辑：非敌对但有攻击力的生物
            if (!$aiComponent->isHostile() && $aiComponent->canAttack()) {
                $aiComponent->setTarget($attacker);
                $aiComponent->setAngry(true, 400);  // 愤怒20秒（400 tick）
                return;
            }

            // 被动生物逃跑逻辑
            $aiComponent->onDamaged($attacker);
        }
    }
}
