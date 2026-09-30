<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Form;

use MengBao\MEBMobAI\Main;
use pocketmine\player\Player;

/**
 * GUI入口与可用性判断
 */
final class FormFactory
{
    public function __construct(
        private readonly Main $plugin,
    ) {
    }

    /**
     * MEBForms是否可用
     */
    public function isAvailable(): bool
    {
        return class_exists(\MengBao\MEBForms\SimpleForm::class);
    }

    /**
     * 打开主菜单
     */
    public function openMain(Player $player): void
    {
        if (!$this->isAvailable()) {
            $player->sendMessage("§c[MEBMobAI] 需要安装 MEBForms 插件才能使用GUI功能");
            return;
        }
        MainMenuForm::open($this->plugin, $player);
    }
}
