<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Component\MobAIManager;
use MengBao\MEBMobAI\Entity\AIEntity;
use MengBao\MEBMobAI\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\entity\Attribute;
use pocketmine\entity\Living;
use pocketmine\player\Player;

/**
 * 生物详细信息表单
 */
final class EntityDetailForm
{
    public static function open(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity->isClosed() || !$entity->isAlive()) {
            FormHelper::error($plugin, $player, "目标生物已不存在");
            MainMenuForm::open($plugin, $player);
            return;
        }

        $entityName = FormHelper::formatEntityName($entity);
        $aiComponent = MobAIManager::getAI($entity);

        $actions = $aiComponent !== null ? ["ai", "back"] : ["back"];

        $form = new SimpleForm(function (Player $player, $data) use ($plugin, $entity, $actions): void {
            if ($data === null) {
                TargetEntityForm::open($plugin, $player);
                return;
            }
            match ($actions[$data] ?? null) {
                "ai" => AIComponentForm::open($plugin, $player, $entity),
                "back" => TargetEntityForm::open($plugin, $player),
                default => TargetEntityForm::open($plugin, $player),
            };
        });

        $form->setTitle("§l§3生物详情");

        $content = "§e=== 基本信息 ===§r\n";
        $content .= "§7名称: §f{$entityName}\n";
        $content .= "§7类型: §f" . $entity::class . "\n";
        $content .= "§7世界: §f" . $entity->getWorld()->getFolderName() . "\n";
        $content .= "§7坐标: §f" . sprintf("%.2f, %.2f, %.2f",
            $entity->getPosition()->x,
            $entity->getPosition()->y,
            $entity->getPosition()->z
        ) . "\n\n";

        $content .= "§e=== 属性 ===§r\n";
        $content .= "§7生命值: §c" . round($entity->getHealth(), 1) . "§7/§c" . $entity->getMaxHealth() . "\n";
        $content .= "§7移动速度: §f" . round($entity->getMovementSpeed(), 2) . "\n";

        $attackDamage = $entity->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->getValue() ?? 0;
        $content .= "§7攻击伤害: §f" . round($attackDamage, 1) . "\n";

        $followRange = $entity->getAttributeMap()->get(Attribute::FOLLOW_RANGE)?->getValue() ?? 16;
        $content .= "§7跟随范围: §f" . round($followRange, 1) . "\n\n";

        if ($aiComponent !== null) {
            $content .= "§e=== AI信息 ===§r\n";

            // 检查AI是否被禁用
            $aiDisabled = !\MengBao\MEBMobAI\Component\MobAIManager::canMobBehave($entity);

            if ($aiDisabled) {
                $content .= "§7AI状态: §c未激活\n";
            } else {
                $content .= "§7AI状态: §a已激活\n";
                $content .= "§7敌对性: " . ($aiComponent->isHostile() ? "§c敌对" : "§a友好") . "\n";
                $content .= "§7愤怒状态: " . ($aiComponent->isAngry() ? "§c是" : "§7否") . "\n";
                $content .= "§7可攻击: " . ($aiComponent->canAttack() ? "§a是" : "§7否") . "\n";
            }
        } elseif ($entity instanceof AIEntity) {
            $content .= "§e=== AI信息 ===§r\n";

            // 检查自定义AI是否被禁用
            $aiDisabled = !\MengBao\MEBMobAI\Component\MobAIManager::canMobBehave($entity);

            if ($aiDisabled) {
                $content .= "§7AI状态: §c自定义AI (未激活)\n";
            } else {
                $content .= "§7AI状态: §a自定义AI (已激活)\n";
            }
        } else {
            $content .= "§e=== AI信息 ===§r\n";
            $content .= "§7AI状态: §c未激活\n";
        }

        if ($entity instanceof AIEntity) {
            $content .= "\n§e=== 动作队列 ===§r\n";
            $content .= "§7队列大小: §f" . $entity->getActionQueueSize() . "\n";
            $currentAction = $entity->getCurrentAction();
            if ($currentAction !== null) {
                $content .= "§7当前动作: §f" . $currentAction->getName() . "\n";
            } else {
                $content .= "§7当前动作: §7无\n";
            }
        }

        $form->setContent($content);

        if ($aiComponent !== null) {
            $form->addButton("§f配置AI组件", 0, "textures/ui/automation_glyph_color");
        }
        $form->addButton("§f返回", 0, "textures/ui/arrow_left");

        $player->sendForm($form);
    }
}
