<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Main;
use MengBao\MEBForms\CustomForm;
use pocketmine\entity\Attribute;
use pocketmine\entity\Living;
use pocketmine\player\Player;

/**
 * 修改生物属性表单
 */
final class EntityAttributeForm
{
    public static function open(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity->isClosed() || !$entity->isAlive()) {
            FormHelper::error($plugin, $player, "目标生物已不存在");
            MainMenuForm::open($plugin, $player);
            return;
        }

        $form = new CustomForm(function (Player $player, $data) use ($plugin, $entity): void {
            if ($data === null) {
                TargetEntityForm::open($plugin, $player);
                return;
            }

            if ($entity->isClosed() || !$entity->isAlive()) {
                FormHelper::error($plugin, $player, "目标生物已不存在");
                MainMenuForm::open($plugin, $player);
                return;
            }

            // 应用修改
            $health = (float)($data[0] ?? $entity->getMaxHealth());
            $maxHealth = (int)($data[1] ?? $entity->getMaxHealth());
            $speed = (float)($data[2] ?? $entity->getMovementSpeed());
            $attackDamage = (float)($data[3] ?? 0);

            $entity->setMaxHealth($maxHealth);
            $entity->setHealth(min($health, $maxHealth));
            $entity->setMovementSpeed($speed);
            $entity->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->setValue($attackDamage);

            // 保存AI数据到NBT（如果有AI组件）
            $aiComponent = \MengBao\MEBMobAI\Component\MobAIManager::getAI($entity);
            if ($aiComponent !== null) {
                $aiComponent->saveAIData();
            }

            FormHelper::success($plugin, $player, "已更新生物属性");
            TargetEntityForm::open($plugin, $player);
        });

        $form->setTitle("§l§3修改属性");

        $currentHealth = $entity->getHealth();
        $maxHealth = $entity->getMaxHealth();
        $speed = $entity->getMovementSpeed();
        $attackDamage = $entity->getAttributeMap()->get(Attribute::ATTACK_DAMAGE)?->getValue() ?? 0;

        $form->addSlider("§f生命值", 1, (float)$maxHealth, 0.5, $currentHealth);
        $form->addSlider("§f最大生命值", 1, 200, 1, (float)$maxHealth);
        $form->addSlider("§f移动速度", 0.1, 5.0, 0.1, $speed);
        $form->addSlider("§f攻击伤害", 0, 50, 0.5, $attackDamage);

        $player->sendForm($form);
    }
}
