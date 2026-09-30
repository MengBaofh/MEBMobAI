<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Component\MobAIManager;
use MengBao\MEBMobAI\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\player\Player;

/**
 * GUI主菜单
 */
final class MainMenuForm
{
    public static function open(Main $plugin, Player $player): void
    {
        $isOp = $player->hasPermission("mebmobai.admin");
        $aiCount = MobAIManager::getCount();

        // 统计自定义AI数量
        $customAICount = 0;
        foreach ($player->getWorld()->getEntities() as $entity) {
            if ($entity instanceof \MengBao\MEBMobAI\Entity\AIEntity && $entity->isAlive()) {
                $customAICount++;
            }
        }

        $actions = ["target", "nearby"];
        if ($isOp) {
            $actions = ["target", "nearby", "spawn", "config"];
        }

        $form = new SimpleForm(function (Player $player, $data) use ($plugin, $actions): void {
            if ($data === null) {
                return;
            }
            match ($actions[$data] ?? null) {
                "target" => TargetEntityForm::open($plugin, $player),
                "nearby" => NearbyEntitiesForm::open($plugin, $player),
                "spawn" => SpawnMobForm::open($plugin, $player),
                "config" => ConfigForm::open($plugin, $player),
                default => null,
            };
        });

        $form->setTitle("§l§3MEB生物AI");
        $form->setContent("§e当前已激活AI生物: §a" . ($aiCount + $customAICount) . "§e个\n" .
                          "§7原版AI: §a{$aiCount}§7个 | 自定义AI: §b{$customAICount}§7个\n" .
                          "§f选择一个操作:");

        $icons = [
            "target" => "textures/ui/mashup_world",
            "nearby" => "textures/ui/magnifyingGlass",
            "spawn" => "textures/ui/color_plus",
            "config" => "textures/ui/gear",
        ];
        $labels = [
            "target" => "§e配置目标生物",
            "nearby" => "§e附近的生物",
            "spawn" => "§e生成自定义生物",
            "config" => "§e全局配置",
        ];

        foreach ($actions as $action) {
            $form->addButton($labels[$action], 0, $icons[$action]);
        }

        $player->sendForm($form);
    }
}
