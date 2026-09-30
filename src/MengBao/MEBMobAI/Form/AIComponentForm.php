<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Component\MobAIComponent;
use MengBao\MEBMobAI\Component\MobAIManager;
use MengBao\MEBMobAI\Main;
use MengBao\MEBForms\CustomForm;
use pocketmine\entity\Living;
use pocketmine\player\Player;

/**
 * AI组件配置表单
 */
final class AIComponentForm
{
    public static function open(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity->isClosed() || !$entity->isAlive()) {
            FormHelper::error($plugin, $player, "目标生物已不存在");
            MainMenuForm::open($plugin, $player);
            return;
        }

        $aiComponent = MobAIManager::getAI($entity);

        if ($aiComponent === null) {
            // 创建新的AI组件
            self::createAIComponent($plugin, $player, $entity);
            return;
        }

        // 修改现有AI组件
        self::editAIComponent($plugin, $player, $entity, $aiComponent);
    }

    private static function createAIComponent(Main $plugin, Player $player, Living $entity): void
    {
        $form = new CustomForm(function (Player $player, $data) use ($plugin, $entity): void {
            if ($data === null) {
                EntityDetailForm::open($plugin, $player, $entity);
                return;
            }

            if ($entity->isClosed() || !$entity->isAlive()) {
                FormHelper::error($plugin, $player, "目标生物已不存在");
                MainMenuForm::open($plugin, $player);
                return;
            }

            $hostile = (bool)$data[0];
            $speed = (float)$data[1];
            $attackDamage = (float)$data[2];
            $jumpHeight = (float)$data[3];

            MobAIManager::attachAI($entity, $hostile, $speed, $attackDamage, [\pocketmine\player\Player::class], $jumpHeight);

            FormHelper::success($plugin, $player, "已为生物添加AI组件");
            EntityDetailForm::open($plugin, $player, $entity);
        });

        $form->setTitle("§l§3创建AI组件");

        $form->addToggle("§f敌对性", false);
        $form->addSlider("§f移动速度", 0.1, 5.0, 0.1, 1.0);
        $form->addSlider("§f攻击伤害", 0, 50, 0.5, 0);
        $form->addSlider("§f跳跃高度", 0.1, 2.0, 0.1, 0.5);

        $player->sendForm($form);
    }

    private static function editAIComponent(Main $plugin, Player $player, Living $entity, MobAIComponent $aiComponent): void
    {
        $form = new CustomForm(function (Player $player, $data) use ($plugin, $entity, $aiComponent): void {
            if ($data === null) {
                EntityDetailForm::open($plugin, $player, $entity);
                return;
            }

            if ($entity->isClosed() || !$entity->isAlive()) {
                FormHelper::error($plugin, $player, "目标生物已不存在");
                MainMenuForm::open($plugin, $player);
                return;
            }

            // 设置愤怒状态
            $angry = (bool)$data[0];
            $duration = (int)$data[1];

            if ($angry) {
                $aiComponent->setAngry(true, $duration);
                FormHelper::success($plugin, $player, "已设置生物为愤怒状态");
            } else {
                $aiComponent->setAngry(false);
                FormHelper::success($plugin, $player, "已取消愤怒状态");
            }

            // 保存AI数据到NBT
            $aiComponent->saveAIData();

            EntityDetailForm::open($plugin, $player, $entity);
        });

        $form->setTitle("§l§3编辑AI组件");

        $form->addToggle("§f愤怒状态", $aiComponent->isAngry());
        $form->addSlider("§f愤怒持续时间(秒)", 1, 60, 1, 20);

        $player->sendForm($form);
    }
}
