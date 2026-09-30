<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI;

use MengBao\MEBMobAI\Command\MEBMACommand;
use MengBao\MEBMobAI\Component\MobAIManager;
use MengBao\MEBMobAI\Entity\MobRegistry;
use MengBao\MEBMobAI\Entity\MEBZombie;
use MengBao\MEBMobAI\Form\FormFactory;
use MengBao\MEBMobAI\Listener\EntityListener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\World;

class Main extends PluginBase
{
    private static ?Main $instance = null;
    private MobRegistry $mobRegistry;
    private Config $vanillaAIConfig;
    private Config $customAIConfig;
    private MEBMACommand $command;
    private FormFactory $formFactory;

    public function onEnable(): void
    {
        self::$instance = $this;

        $this->saveDefaultConfig();
        $this->saveResource("vanilla_mob_ai.yml");
        $this->saveResource("custom_mob_ai.yml");

        $this->vanillaAIConfig = new Config($this->getDataFolder() . "vanilla_mob_ai.yml", Config::YAML);
        $this->customAIConfig = new Config($this->getDataFolder() . "custom_mob_ai.yml", Config::YAML);

        $this->mobRegistry = new MobRegistry();
        $this->command = new MEBMACommand($this);
        $this->formFactory = new FormFactory($this);

        // 注册命令
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register("mebmobai", new \MengBao\MEBMobAI\Command\AITestCommand());

        // 注册生物监听器
        $this->getServer()->getPluginManager()->registerEvents(new EntityListener(), $this);

        // 注册默认生物
        $this->registerDefaultMobs();

        // 注册定时任务更新所有AI
        $this->getScheduler()->scheduleRepeatingTask(new \pocketmine\scheduler\ClosureTask(
            function(): void {
                MobAIManager::updateAll();
            }
        ), 1); // 每tick更新
    }

    public function onDisable(): void
    {
        MobAIManager::clearAll();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ($command->getName()) {
            case "mebma":
                // 检查是否是GUI子命令
                if (isset($args[0]) && strtolower($args[0]) === "gui") {
                    if (!($sender instanceof Player)) {
                        $sender->sendMessage("§c该命令只能由玩家执行");
                        return true;
                    }
                    $this->formFactory->openMain($sender);
                    return true;
                }
                return $this->command->execute($sender, $label, $args);
            case "aitest":
                $testCommand = new \MengBao\MEBMobAI\Command\AITestCommand();
                return $testCommand->execute($sender, $label, $args);
            default:
                return false;
        }
    }

    /**
     * 注册自定义生物
     */
    private function registerDefaultMobs(): void
    {
        // 注册僵尸
        EntityFactory::getInstance()->register(MEBZombie::class, function(World $world, CompoundTag $nbt): MEBZombie {
            return new MEBZombie(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["MEBZombie"]);

        $this->mobRegistry->registerMob("zombie", MEBZombie::class, [
            "hostile" => true,
            "speed" => 1.0,
            "attack_damage" => 3.0
        ]);

        // 注册蝙蝠
        EntityFactory::getInstance()->register(\MengBao\MEBMobAI\Entity\MEBBat::class, function(World $world, CompoundTag $nbt): \MengBao\MEBMobAI\Entity\MEBBat {
            return new \MengBao\MEBMobAI\Entity\MEBBat(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["MEBBat"]);

        $this->mobRegistry->registerMob("bat", \MengBao\MEBMobAI\Entity\MEBBat::class, [
            "hostile" => false,
            "speed" => 1.2,
            "attack_damage" => 1.0
        ]);

        // 注册村民
        EntityFactory::getInstance()->register(\MengBao\MEBMobAI\Entity\MEBVillager::class, function(World $world, CompoundTag $nbt): \MengBao\MEBMobAI\Entity\MEBVillager {
            return new \MengBao\MEBMobAI\Entity\MEBVillager(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["MEBVillager"]);

        $this->mobRegistry->registerMob("villager", \MengBao\MEBMobAI\Entity\MEBVillager::class, [
            "hostile" => false,
            "speed" => 0.8,
            "attack_damage" => 0.0
        ]);

        $this->getLogger()->info("已注册 " . count($this->mobRegistry->getRegisteredMobs()) . " 个自定义生物");
    }

    public static function getInstance(): ?Main
    {
        return self::$instance;
    }

    public function getMobRegistry(): MobRegistry
    {
        return $this->mobRegistry;
    }

    /**
     * 获取原版AI配置
     */
    public function getVanillaAIConfig(): Config
    {
        return $this->vanillaAIConfig;
    }

    /**
     * 获取自定义AI配置
     */
    public function getCustomAIConfig(): Config
    {
        return $this->customAIConfig;
    }

    /**
     * 获取表单工厂
     */
    public function getFormFactory(): FormFactory
    {
        return $this->formFactory;
    }
}
