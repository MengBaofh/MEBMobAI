<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Entity\MEBBat;
use MengBao\MEBMobAI\Entity\MEBVillager;
use MengBao\MEBMobAI\Entity\MEBZombie;
use MengBao\MEBMobAI\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

/**
 * 生成生物表单
 */
final class SpawnMobForm
{
    public static function open(Main $plugin, Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) use ($plugin): void {
            if ($data === null) {
                MainMenuForm::open($plugin, $player);
                return;
            }

            $mobClass = match ($data) {
                0 => MEBZombie::class,
                1 => MEBBat::class,
                2 => MEBVillager::class,
                3 => null,
                default => null,
            };

            if ($mobClass === null) {
                MainMenuForm::open($plugin, $player);
                return;
            }

            self::spawnMob($plugin, $player, $mobClass);
        });

        $form->setTitle("§l§3生成自定义生物");
        $form->setContent("§f选择要生成的生物类型:");

        $form->addButton("§e僵尸\n§7敌对生物", 0, "textures/ui/zombie");
        $form->addButton("§e蝙蝠\n§7友好生物", 0, "textures/ui/bat");
        $form->addButton("§e村民\n§7友好生物", 0, "textures/ui/villager");
        $form->addButton("§f返回", 0, "textures/ui/arrow_left");

        $player->sendForm($form);
    }

    private static function spawnMob(Main $plugin, Player $player, string $mobClass): void
    {
        $playerLoc = $player->getLocation();
        $world = $player->getWorld();

        // 计算生成位置（玩家前方3格）
        $yaw = $playerLoc->yaw;
        $rad = deg2rad($yaw + 90);
        $x = $playerLoc->x + cos($rad) * 3;
        $z = $playerLoc->z + sin($rad) * 3;

        $nbt = CompoundTag::create();
        $mob = new $mobClass(
            new Location($x, $playerLoc->y, $z, $world, $yaw, 0),
            $nbt
        );

        $mob->spawnToAll();

        $mobName = basename(str_replace('\\', '/', $mobClass));
        FormHelper::success($plugin, $player, "已生成 {$mobName}");
        MainMenuForm::open($plugin, $player);
    }
}
