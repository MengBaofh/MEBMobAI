<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Action\AttackAction;
use MengBao\MEBMobAI\Action\FollowAction;
use MengBao\MEBMobAI\Action\MoveToAction;
use MengBao\MEBMobAI\Action\RandomMoveAction;
use MengBao\MEBMobAI\Action\StopAction;
use MengBao\MEBMobAI\Action\WaitAction;
use MengBao\MEBMobAI\Action\JumpAction;
use MengBao\MEBMobAI\Entity\AIEntity;
use MengBao\MEBMobAI\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\entity\Living;
use pocketmine\player\Player;

/**
 * 生物动作执行表单
 */
final class EntityActionForm
{
    public static function open(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity->isClosed() || !$entity->isAlive()) {
            FormHelper::error($plugin, $player, "目标生物已不存在");
            MainMenuForm::open($plugin, $player);
            return;
        }

        $form = new SimpleForm(function (Player $player, $data) use ($plugin, $entity): void {
            if ($data === null) {
                TargetEntityForm::open($plugin, $player);
                return;
            }

            if ($entity->isClosed() || !$entity->isAlive()) {
                FormHelper::error($plugin, $player, "目标生物已不存在");
                MainMenuForm::open($plugin, $player);
                return;
            }

            match ($data) {
                0 => self::executeMoveTo($plugin, $player, $entity),
                1 => self::executeFollow($plugin, $player, $entity),
                2 => self::executeAttack($plugin, $player, $entity),
                3 => self::executeRandomMove($plugin, $player, $entity),
                4 => self::executeJump($plugin, $player, $entity),
                5 => self::executeWait($plugin, $player, $entity),
                6 => self::executeStop($plugin, $player, $entity),
                7 => self::clearActions($plugin, $player, $entity),
                8 => TargetEntityForm::open($plugin, $player),
                default => null,
            };
        });

        $form->setTitle("§l§3执行动作");

        $entityName = FormHelper::formatEntityName($entity);
        $content = "§e目标: §f{$entityName}\n";

        if ($entity instanceof AIEntity) {
            $content .= "§e队列大小: §f" . $entity->getActionQueueSize() . "\n";
            $currentAction = $entity->getCurrentAction();
            if ($currentAction !== null) {
                $content .= "§e当前动作: §f" . $currentAction->getName() . "\n";
            }
        } else {
            $content .= "§c该生物不支持动作队列\n";
        }

        $form->setContent($content);
        $form->addButton("§e移动到玩家", 0, "textures/ui/move");
        $form->addButton("§e跟随玩家", 0, "textures/ui/icon_recipe_item");
        $form->addButton("§e攻击玩家", 0, "textures/ui/icon_iron_sword");
        $form->addButton("§e随机移动", 0, "textures/ui/free_download_symbol");
        $form->addButton("§e跳跃", 0, "textures/ui/jump");
        $form->addButton("§e等待3秒", 0, "textures/ui/timer");
        $form->addButton("§e停止移动", 0, "textures/ui/cancel");
        $form->addButton("§c清空动作队列", 0, "textures/ui/trash");
        $form->addButton("§f返回", 0, "textures/ui/arrow_left");

        $player->sendForm($form);
    }

    private static function executeMoveTo(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity instanceof AIEntity) {
            // 传入玩家实体而不是固定位置，这样会跟随玩家移动
            $entity->addAction(new MoveToAction($player, 1.0, 2.0));
            FormHelper::success($plugin, $player, "已添加移动动作（跟随你的位置）");
        } else {
            FormHelper::error($plugin, $player, "该生物不支持动作队列");
        }
        self::open($plugin, $player, $entity);
    }

    private static function executeFollow(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity instanceof AIEntity) {
            $entity->addAction(new FollowAction($player, 3.0, 60));
            FormHelper::success($plugin, $player, "已添加跟随动作");
        } else {
            FormHelper::error($plugin, $player, "该生物不支持动作队列");
        }
        self::open($plugin, $player, $entity);
    }

    private static function executeAttack(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity instanceof AIEntity) {
            $entity->addAction(new AttackAction($player, 40));
            FormHelper::success($plugin, $player, "已添加攻击动作");
        } else {
            FormHelper::error($plugin, $player, "该生物不支持动作队列");
        }
        self::open($plugin, $player, $entity);
    }

    private static function executeRandomMove(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity instanceof AIEntity) {
            $entity->addAction(new RandomMoveAction(60));
            FormHelper::success($plugin, $player, "已添加随机移动动作");
        } else {
            FormHelper::error($plugin, $player, "该生物不支持动作队列");
        }
        self::open($plugin, $player, $entity);
    }

    private static function executeJump(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity instanceof AIEntity) {
            $entity->addAction(new JumpAction());
            FormHelper::success($plugin, $player, "已添加跳跃动作");
        } else {
            FormHelper::error($plugin, $player, "该生物不支持动作队列");
        }
        self::open($plugin, $player, $entity);
    }

    private static function executeWait(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity instanceof AIEntity) {
            $entity->addAction(new WaitAction(60));
            FormHelper::success($plugin, $player, "已添加等待动作");
        } else {
            FormHelper::error($plugin, $player, "该生物不支持动作队列");
        }
        self::open($plugin, $player, $entity);
    }

    private static function executeStop(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity instanceof AIEntity) {
            $entity->addAction(new StopAction());
            FormHelper::success($plugin, $player, "已添加停止动作");
        } else {
            FormHelper::error($plugin, $player, "该生物不支持动作队列");
        }
        self::open($plugin, $player, $entity);
    }

    private static function clearActions(Main $plugin, Player $player, Living $entity): void
    {
        if ($entity instanceof AIEntity) {
            $entity->clearActions();
            FormHelper::success($plugin, $player, "已清空动作队列");
        } else {
            FormHelper::error($plugin, $player, "该生物不支持动作队列");
        }
        self::open($plugin, $player, $entity);
    }
}
