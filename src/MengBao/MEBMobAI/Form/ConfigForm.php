<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Component\MobAIManager;
use MengBao\MEBMobAI\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\player\Player;

/**
 * 全局配置表单
 */
final class ConfigForm
{
    public static function open(Main $plugin, Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) use ($plugin): void {
            if ($data === null) {
                MainMenuForm::open($plugin, $player);
                return;
            }

            match ($data) {
                0 => self::reloadConfig($plugin, $player),
                1 => self::clearAllAI($plugin, $player),
                2 => self::removeCustomAI($plugin, $player),
                3 => self::removeVanillaMobs($plugin, $player),
                4 => self::showStats($plugin, $player),
                5 => MainMenuForm::open($plugin, $player),
                default => null,
            };
        });

        $form->setTitle("§l§3全局配置");

        $aiCount = MobAIManager::getCount();

        // 统计自定义AI数量
        $customAICount = 0;
        foreach ($player->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof \MengBao\MEBMobAI\Entity\AIEntity && $entity->isAlive()) {
                    $customAICount++;
                }
            }
        }

        $content = "§e当前已激活AI生物: §a" . ($aiCount + $customAICount) . "§e个\n";
        $content .= "§7原版AI: §a{$aiCount}§7个 | 自定义AI: §b{$customAICount}§7个\n";
        $content .= "§f选择一个操作:";

        $form->setContent($content);
        $form->addButton("§e重载配置", 0, "textures/ui/refresh");
        $form->addButton("§c清除所有原版AI组件", 0, "textures/ui/trash");
        $form->addButton("§c清除附近自定义生物", 0, "textures/ui/icon_trash");
        $form->addButton("§c清除附近原版生物", 0, "textures/ui/icon_trash");
        $form->addButton("§e查看统计", 0, "textures/ui/book_edit_default");
        $form->addButton("§f返回", 0, "textures/ui/arrow_left");

        $player->sendForm($form);
    }

    private static function reloadConfig(Main $plugin, Player $player): void
    {
        $plugin->reloadConfig();
        $plugin->getVanillaAIConfig()->reload();
        $plugin->getCustomAIConfig()->reload();

        FormHelper::success($plugin, $player, "配置已重载");
        self::open($plugin, $player);
    }

    private static function clearAllAI(Main $plugin, Player $player): void
    {
        MobAIManager::clearAll();
        FormHelper::success($plugin, $player, "已清除所有AI组件");
        self::open($plugin, $player);
    }

    private static function removeCustomAI(Main $plugin, Player $player): void
    {
        // 打开范围选择表单
        $form = new \MengBao\MEBForms\CustomForm(function (Player $player, $data) use ($plugin): void {
            if ($data === null) {
                self::open($plugin, $player);
                return;
            }

            $range = (float)($data[0] ?? 32);

            // 统计并移除附近的自定义AI生物
            $removed = 0;
            $pos = $player->getPosition();

            foreach ($player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy($range, $range, $range)) as $entity) {
                if ($entity instanceof \MengBao\MEBMobAI\Entity\AIEntity && $entity->isAlive()) {
                    $distance = $pos->distance($entity->getPosition());
                    if ($distance <= $range) {
                        $entity->flagForDespawn();
                        $removed++;
                    }
                }
            }

            FormHelper::success($plugin, $player, "已移除 {$removed} 个自定义AI生物");
            self::open($plugin, $player);
        });

        $form->setTitle("§l§3清除自定义生物");
        $form->addSlider("§f清除范围（格）", 1, 128, 1, 32);

        $player->sendForm($form);
    }

    private static function removeVanillaMobs(Main $plugin, Player $player): void
    {
        // 打开范围选择表单
        $form = new \MengBao\MEBForms\CustomForm(function (Player $player, $data) use ($plugin): void {
            if ($data === null) {
                self::open($plugin, $player);
                return;
            }

            $range = (float)($data[0] ?? 32);

            // 统计并移除附近的原版生物（非自定义AI）
            $removed = 0;
            $pos = $player->getPosition();

            foreach ($player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy($range, $range, $range)) as $entity) {
                // 排除玩家和自定义AI生物
                if ($entity instanceof \pocketmine\player\Player) {
                    continue;
                }
                if ($entity instanceof \MengBao\MEBMobAI\Entity\AIEntity) {
                    continue;
                }

                // 只移除 Living 实体（原版生物）
                if ($entity instanceof \pocketmine\entity\Living && $entity->isAlive()) {
                    $distance = $pos->distance($entity->getPosition());
                    if ($distance <= $range) {
                        $entity->flagForDespawn();
                        $removed++;
                    }
                }
            }

            FormHelper::success($plugin, $player, "已移除 {$removed} 个原版生物");
            self::open($plugin, $player);
        });

        $form->setTitle("§l§3清除原版生物");
        $form->addSlider("§f清除范围（格）", 1, 128, 1, 32);

        $player->sendForm($form);
    }

    private static function showStats(Main $plugin, Player $player): void
    {
        $aiCount = MobAIManager::getCount();
        $registeredMobs = $plugin->getMobRegistry()->getRegisteredMobs();

        $form = new SimpleForm(function (Player $player, $data) use ($plugin): void {
            self::open($plugin, $player);
        });

        $form->setTitle("§l§3统计信息");

        $content = "§e=== 系统统计 ===§r\n";
        $content .= "§7活跃AI数量: §e{$aiCount}\n";
        $content .= "§7已注册生物类型: §e" . count($registeredMobs) . "\n\n";

        $content .= "§e=== 已注册生物 ===§r\n";
        foreach ($registeredMobs as $mobId => $mobClass) {
            $content .= "§7- §f{$mobId}§7 (§e{$mobClass}§7)\n";
        }

        $form->setContent($content);
        $form->addButton("§f返回", 0, "textures/ui/arrow_left");

        $player->sendForm($form);
    }
}
