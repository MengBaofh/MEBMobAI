<?php

declare(strict_types=1);

namespace MengBao\MEBMobAI\Command;

use MengBao\MEBMobAI\Action\AttackAction;
use MengBao\MEBMobAI\Action\FlyToAction;
use MengBao\MEBMobAI\Action\FlyUpAction;
use MengBao\MEBMobAI\Action\HoverAction;
use MengBao\MEBMobAI\Action\JumpAction;
use MengBao\MEBMobAI\Action\MoveForwardAction;
use MengBao\MEBMobAI\Action\MoveToAction;
use MengBao\MEBMobAI\Action\TurnToAction;
use MengBao\MEBMobAI\Action\WaitAction;
use MengBao\MEBMobAI\Entity\AIEntity;
use MengBao\MEBMobAI\Entity\FlyingEntity;
use MengBao\MEBMobAI\Entity\MEBBat;
use MengBao\MEBMobAI\Entity\MEBZombie;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

/**
 * AI测试命令
 */
class AITestCommand extends Command
{
    public function __construct()
    {
        parent::__construct("aitest", "测试AI系统", "/aitest <chase|patrol|demo|fly>");
        $this->setPermission("mebmobai.aitest");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§c此指令只能在游戏内使用");
            return true;
        }

        if (!$this->testPermission($sender)) {
            return false;
        }

        if (count($args) < 1) {
            $sender->sendMessage("§e用法: /aitest <chase|patrol|demo|fly>");
            return true;
        }

        switch (strtolower($args[0])) {
            case "chase":
                return $this->testChase($sender);
            case "patrol":
                return $this->testPatrol($sender);
            case "demo":
                return $this->testDemo($sender);
            case "fly":
                return $this->testFly($sender);
            default:
                $sender->sendMessage("§c未知测试: {$args[0]}");
                return false;
        }
    }

    /**
     * 测试追击玩家
     */
    private function testChase(Player $player): bool
    {
        $playerLoc = $player->getLocation();
        $location = new Location($playerLoc->x + 5, $playerLoc->y, $playerLoc->z, $player->getWorld(), 0, 0);
        $zombie = new MEBZombie($location, CompoundTag::create());
        $zombie->spawnToAll();

        // 使用持续追击行为
        $behavior = new \MengBao\MEBMobAI\Behavior\ChaseAndAttackBehavior(
            $zombie,
            $player,
            1.2,  // 速度
            3.0,  // 伤害
            2.0,  // 追击距离
            2.5   // 攻击范围
        );
        $zombie->setBehavior($behavior);

        $player->sendMessage("§a已生成持续追击僵尸！它会一直追击并攻击你。");
        return true;
    }

    /**
     * 测试巡逻路径
     */
    private function testPatrol(Player $player): bool
    {
        $playerLoc = $player->getLocation();
        $location = new Location($playerLoc->x + 3, $playerLoc->y, $playerLoc->z, $player->getWorld(), 0, 0);
        $zombie = new MEBZombie($location, CompoundTag::create());
        $zombie->spawnToAll();

        // 创建一个正方形巡逻路径
        $basePos = $player->getPosition();
        $waypoints = [
            new Vector3($basePos->x + 5, $basePos->y, $basePos->z),
            new Vector3($basePos->x + 5, $basePos->y, $basePos->z + 5),
            new Vector3($basePos->x, $basePos->y, $basePos->z + 5),
            new Vector3($basePos->x, $basePos->y, $basePos->z),
        ];

        $zombie->clearActions();
        foreach ($waypoints as $waypoint) {
            $zombie->addAction(new MoveToAction($waypoint, 1.0, 1.0));
            $zombie->addAction(new WaitAction(20)); // 等待1秒
        }

        $player->sendMessage("§a已生成巡逻僵尸！它会沿着正方形路径巡逻。");
        return true;
    }

    /**
     * 测试复杂动作序列
     */
    private function testDemo(Player $player): bool
    {
        $playerLoc = $player->getLocation();
        $location = new Location($playerLoc->x + 3, $playerLoc->y, $playerLoc->z, $player->getWorld(), 0, 0);
        $zombie = new MEBZombie($location, CompoundTag::create());
        $zombie->spawnToAll();

        $zombie->clearActions();
        // 1. 前进2秒
        $zombie->addAction(new MoveForwardAction(1.0, 40));
        // 2. 跳跃
        $zombie->addAction(new JumpAction());
        // 3. 等待1秒
        $zombie->addAction(new WaitAction(20));
        // 4. 转向180度
        $zombie->addAction(new TurnToAction($zombie->getLocation()->yaw + 180, 10.0));
        // 5. 前进2秒
        $zombie->addAction(new MoveForwardAction(1.0, 40));
        // 6. 转向玩家
        $zombie->addAction(new TurnToAction($player, 15.0));
        // 7. 移动到玩家
        $zombie->addAction(new MoveToAction($player, 1.0, 2.0));

        $player->sendMessage("§a已生成演示僵尸！它会执行一系列复杂动作。");
        return true;
    }

    /**
     * 测试飞行生物
     */
    private function testFly(Player $player): bool
    {
        $playerLoc = $player->getLocation();
        $location = new Location($playerLoc->x + 3, $playerLoc->y, $playerLoc->z, $player->getWorld(), 0, 0);
        $bat = new MEBBat($location, CompoundTag::create());
        $bat->spawnToAll();

        $targetPos = $player->getPosition()->add(0, 10, 5);

        $bat->clearActions();
        // 1. 向上飞行2秒
        $bat->addAction(new FlyUpAction(1.5, 40));
        // 2. 悬停1秒
        $bat->addAction(new HoverAction(20));
        // 3. 飞向目标位置
        $bat->addAction(new FlyToAction($targetPos, 1.2, 1.0));
        // 4. 悬停2秒
        $bat->addAction(new HoverAction(40));
        // 5. 飞回玩家
        $bat->addAction(new FlyToAction($player, 1.0, 2.0));

        $player->sendMessage("§a已生成飞行蝙蝠！它会向上飞、悬停、飞到指定位置再飞回来。");
        return true;
    }
}
