<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Component\MobAIManager;
use MengBao\MEBMobAI\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\entity\Living;
use pocketmine\player\Player;

/**
 * 附近生物列表表单
 */
final class NearbyEntitiesForm
{
    public static function open(Main $plugin, Player $player): void
    {
        $nearbyEntities = $player->getWorld()->getNearbyEntities(
            $player->getBoundingBox()->expandedCopy(20, 20, 20)
        );

        $livingEntities = [];
        foreach ($nearbyEntities as $entity) {
            if ($entity instanceof Living && $entity !== $player) {
                $livingEntities[] = $entity;
            }
        }

        if (empty($livingEntities)) {
            FormHelper::error($plugin, $player, "附近20格内没有生物");
            MainMenuForm::open($plugin, $player);
            return;
        }

        // 重新索引数组以确保索引从0开始
        $livingEntities = array_values($livingEntities);

        $form = new SimpleForm(function (Player $player, $data) use ($plugin, $livingEntities): void {
            if ($data === null) {
                MainMenuForm::open($plugin, $player);
                return;
            }

            // 最后一个按钮是返回
            if ($data === count($livingEntities)) {
                MainMenuForm::open($plugin, $player);
                return;
            }

            if (isset($livingEntities[$data])) {
                $entity = $livingEntities[$data];
                if (!$entity->isClosed() && $entity->isAlive()) {
                    TargetEntityForm::open($plugin, $player, $entity);
                } else {
                    FormHelper::error($plugin, $player, "该生物已不存在");
                    self::open($plugin, $player);
                }
            }
        });

        $form->setTitle("§l§3附近的生物");
        $form->setContent("§7找到 §e" . count($livingEntities) . "§7 个生物\n§7点击选择:");

        foreach ($livingEntities as $entity) {
            $entityName = FormHelper::formatEntityName($entity);
            $distance = round($player->getPosition()->distance($entity->getPosition()), 1);
            $aiComponent = MobAIManager::getAI($entity);
            $isCustomAI = $entity instanceof \MengBao\MEBMobAI\Entity\AIEntity;

            if ($isCustomAI) {
                $aiDisabled = !\MengBao\MEBMobAI\Component\MobAIManager::canMobBehave($entity);
                if ($aiDisabled) {
                    $aiStatus = "§c[自定义AI]";
                } else {
                    $aiStatus = "§a[自定义AI]";
                }
            } elseif ($aiComponent !== null) {
                $aiStatus = "§a[AI]";
            } else {
                $aiStatus = "§7[无AI]";
            }

            $buttonText = "§f{$entityName}\n§7距离: {$distance}m {$aiStatus}";
            $form->addButton($buttonText, 0, "textures/ui/village");
        }

        $form->addButton("§f返回", 0, "textures/ui/arrow_left");

        $player->sendForm($form);
    }
}
