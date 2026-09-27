<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Command;

use MengBao\MEBMobAI\Entity\AIEntity;
use MengBao\MEBMobAI\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class MEBMACommand extends Command
{
    private Main $plugin;

    public function __construct(Main $plugin)
    {
        parent::__construct("mebma", "MEBMobAI指令", "/mebma <spawn|list|remove> [args...]");
        $this->setPermission("mebmobai.command");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§c此指令只能在游戏内使用");
            return true;
        }

        if (!$this->testPermission($sender)) {
            return false;
        }

        if (count($args) < 1) {
            $sender->sendMessage("§e用法: /mebma <spawn|list|remove> [args...]");
            return true;
        }

        $subCommand = array_shift($args);

        switch (strtolower($subCommand)) {
            case "spawn":
                return $this->handleSpawn($sender, $args);
            case "list":
                return $this->handleList($sender);
            case "remove":
                return $this->handleRemove($sender, $args);
            default:
                $sender->sendMessage("§c未知子指令: {$subCommand}");
                return false;
        }
    }

    private function handleSpawn(Player $player, array $args): bool
    {
        if (count($args) < 1) {
            $player->sendMessage("§e用法: /mebma spawn <生物ID>");
            return true;
        }

        $mobId = $args[0];
        $registry = $this->plugin->getMobRegistry();

        if (!$registry->isMobRegistered($mobId)) {
            $player->sendMessage("§c生物ID '{$mobId}' 未注册");
            return true;
        }

        $mobClass = $registry->getMobClass($mobId);
        $config = $registry->getMobConfig($mobId);

        $playerLoc = $player->getLocation();
        $location = new Location(
            $playerLoc->x,
            $playerLoc->y + 1,
            $playerLoc->z,
            $player->getWorld(),
            $playerLoc->yaw,
            $playerLoc->pitch
        );
        $nbt = CompoundTag::create();

        // if (isset($config["hostile"])) {
        //     $nbt->setByte("Hostile", $config["hostile"] ? 1 : 0);
        // }
        // if (isset($config["friendly"])) {
        //     $nbt->setByte("Friendly", $config["friendly"] ? 1 : 0);
        // }
        // if (isset($config["speed"])) {
        //     $nbt->setFloat("Speed", (float)$config["speed"]);
        // }
        // if (isset($config["attack_damage"])) {
        //     $nbt->setFloat("AttackDamage", (float)$config["attack_damage"]);
        // }

        $entity = new $mobClass($location, $nbt);
        $entity->spawnToAll();

        $player->sendMessage("§a已生成生物: {$mobId}");
        return true;
    }

    private function handleList(Player $player): bool
    {
        $registry = $this->plugin->getMobRegistry();
        $mobs = $registry->getRegisteredMobs();

        if (empty($mobs)) {
            $player->sendMessage("§e没有已注册的生物");
            return true;
        }

        $player->sendMessage("§a已注册的生物:");
        foreach ($mobs as $mobId) {
            $player->sendMessage("§7- §f{$mobId}");
        }

        return true;
    }

    private function handleRemove(Player $player, array $args): bool
    {
        if (count($args) < 1) {
            $player->sendMessage("§e用法: /mebma remove <范围>");
            return true;
        }

        $range = (float)$args[0];
        $count = 0;

        foreach ($player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy($range, $range, $range)) as $entity) {
            if ($entity instanceof AIEntity) {
                $entity->flagForDespawn();
                $count++;
            }
        }

        $player->sendMessage("§a已移除 {$count} 个AI生物");
        return true;
    }
}
