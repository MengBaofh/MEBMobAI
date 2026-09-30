<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Component\MobAIManager;
use MengBao\MEBMobAI\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\entity\Living;
use pocketmine\player\Player;

/**
 * 配置目标生物表单
 */
final class TargetEntityForm
{
    public static function open(Main $plugin, Player $player, ?Living $entity = null): void
    {
        // 如果没有传入实体，则尝试获取玩家视线中的实体
        if ($entity === null) {
            $entity = FormHelper::getTargetEntity($player);
        }

        if ($entity === null) {
            FormHelper::error($plugin, $player, "未找到目标生物，请看向一个生物后再打开");
            MainMenuForm::open($plugin, $player);
            return;
        }

        $targetEntity = $entity;

        $entityName = FormHelper::formatEntityName($targetEntity);
        $aiComponent = MobAIManager::getAI($targetEntity);

        $form = new SimpleForm(function (Player $player, $data) use ($plugin, $targetEntity): void {
            if ($data === null) {
                MainMenuForm::open($plugin, $player);
                return;
            }

            // 再次验证实体是否存在
            if ($targetEntity->isClosed() || !$targetEntity->isAlive()) {
                FormHelper::error($plugin, $player, "目标生物已不存在");
                MainMenuForm::open($plugin, $player);
                return;
            }

            match ($data) {
                0 => EntityDetailForm::open($plugin, $player, $targetEntity),
                1 => EntityAttributeForm::open($plugin, $player, $targetEntity),
                2 => EntityActionForm::open($plugin, $player, $targetEntity),
                3 => MainMenuForm::open($plugin, $player),
                default => null,
            };
        });

        $form->setTitle("§l§3配置生物");

        $content = "§e目标: §f{$entityName}\n";
        $content .= "§e位置: §f" . sprintf("%.1f, %.1f, %.1f",
            $targetEntity->getPosition()->x,
            $targetEntity->getPosition()->y,
            $targetEntity->getPosition()->z
        ) . "\n";
        $content .= "§e生命: §c" . round($targetEntity->getHealth(), 1) . "§7/§c" . $targetEntity->getMaxHealth() . "\n";

        // 检查AI状态
        $isCustomAI = $targetEntity instanceof \MengBao\MEBMobAI\Entity\AIEntity;
        $aiDisabled = !\MengBao\MEBMobAI\Component\MobAIManager::canMobBehave($targetEntity);

        if ($isCustomAI) {
            if ($aiDisabled) {
                $content .= "§eAI状态: §c自定义AI (未激活)\n";
            } else {
                $content .= "§eAI状态: §a自定义AI (已激活)\n";
            }
        } elseif ($aiComponent !== null) {
            if ($aiDisabled) {
                $content .= "§eAI状态: §c未激活\n";
            } else {
                $content .= "§eAI状态: §a已激活\n";
                $content .= "§e敌对性: " . ($aiComponent->isHostile() ? "§c敌对" : "§a友好") . "\n";
                $content .= "§e愤怒: " . ($aiComponent->isAngry() ? "§c是" : "§7否") . "\n";
            }
        } else {
            $content .= "§eAI状态: §c未激活\n";
        }

        $form->setContent($content);
        $form->addButton("§e查看详细信息", 0, "textures/ui/book_edit_default");
        $form->addButton("§e修改属性", 0, "textures/ui/gear");
        $form->addButton("§e执行动作", 0, "textures/ui/debug_glyph_color");
        $form->addButton("§f返回", 0, "textures/ui/arrow_left");

        $player->sendForm($form);
    }
}
