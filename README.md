# MEBMobAI

强大的 PocketMine-MP 5 生物 AI 插件，包含原版生物和自定义生物的智能行为系统。

[![PocketMine-MP](https://img.shields.io/badge/PocketMine--MP-5.0-blue)](https://github.com/pmmp/PocketMine-MP)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)](https://www.php.net/)
[![MEB交流群](https://img.shields.io/badge/MEB交流群-495262926-orange?style=flat-square&logo=tencentqq)](https://qun.qq.com/universal-share/share?ac=1&authKey=HJqOZiQeXeja5NyPiqbfbPPRGX6UdRYf%2FZ8jxAr5B52Bl8a2L4ZqpgQ4%2FZ2JTQ%2BG&busi_data=eyJncm91cENvZGUiOiI0OTUyNjI5MjYiLCJ0b2tlbiI6Imh4Q01pWkpkQVgvekFSK0cwbTJjWU5xdHFBMGJJN01qQVN6SmhRMUZHTmcwRzNBOXpvdlArcW1EaTRNcFI1MEsiLCJ1aW4iOiI4MjU1ODUzOTgifQ%3D%3D&data=_H46ENc_fxiIeBZm8xNKqFoGMVQ2ZbAayO2_xLQ7-24neRXx2M6uoWZqOCk2iPBw_MgYalDv4PNB8uOLvhl3ww&svctype=4&tempid=h5_group_info)

## 功能特性

### 核心功能
- ✅ **原版特性恢复**：僵尸白天燃烧、蜘蛛爬墙、末影人遇水传送等
- ✅ **原版生物 AI 增强**：为原版生物添加智能追击、攻击、逃跑、击退等行为
- ✅ **属性持久化**：可实时修改单个生物的属性，在服务器重启后自动保留（原版生物受核心限制，部分属性可能无法保存）
- ✅ **自定义生物系统**：提供完整的自定义生物框架，支持复杂的动作序列和行为编排
- ✅ **GUI 管理界面**：完整的图形化配置界面（需要 MEBForms）

### 原版生物特性

#### 亡灵生物（僵尸、骷髅等）
- 白天在阳光下燃烧
- 戴头盔可防护，但头盔会损耗耐久
- 水中不燃烧

#### 蜘蛛
- 可以攀爬墙壁
- 贴墙时自动向上移动

#### 末影人
- 在水中或雨中受伤
- 受到水伤害时有 20% 几率随机传送

## 安装

### 前置要求
- PocketMine-MP 5.x
- PHP 8.0+
- MEBForms（可选，用于 GUI 功能）
- MEBWorldProtect（可选，一键启用/禁用生物行为）

### 安装步骤
1. 下载插件phar文件
2. 插件放入 `plugins/` 目录
3. 重启服务器
4. 配置文件将自动生成在 `plugin_data/MEBMobAI/`

## 配置文件

### vanilla_mob_ai.yml
配置原版生物属性：

```yaml
zombie:
  hostile: true  # 是否敌对生物（true为敌对生物，会自动攻击目标；false为友好生物，受到攻击后且攻击伤害不为0时会反击目标，攻击伤害为0时不会反击）
  speed: 1.2  # 移动速度
  damage: 4.0  # 攻击伤害
  jump_height: 0.5  # 跳跃高度（0.5=1格）
  search_range: 16.0  # 目标搜索半径（格）
  attack_distance: 0.5  # 攻击距离（0.5=1格）
  target_types:  # 攻击目标
    - "pocketmine\\player\\Player"
    - "pocketmine\\Entity\\Villager"

```

### custom_mob_ai.yml
配置自定义生物属性（值的含义与原版一致）：

```yaml
zombie:
  speed: 1.0  # 移动速度
  attack_damage: 3.0  # 攻击伤害
  max_health: 20  # 最大血量
  jump_height: 0.5  # 跳跃高度
  hostile: true  # 是否敌对生物

```

## AI 行为系统

### 原版生物 AI组件系统
- **敌对生物**：追击玩家、自动攻击、愤怒状态
- **被动生物**：受攻击后逃跑并提速
- **中立生物**：被攻击后反击（愤怒状态，持续 20 秒）
- **智能寻路**：自动越过障碍物


### 自定义生物 动作系统
- **MoveToAction**：移动到目标位置或跟随实体
- **FollowAction**：持续跟随目标（保持距离）
- **AttackAction**：攻击目标实体
- **RandomMoveAction**：随机方向移动
- **JumpAction**：跳跃动作
- **WaitAction**：等待指定时间

## 指令系统

### 主指令
```
/mebma 或 /mebma gui - 打开 GUI 主菜单（需要 MEBForms）
/mebma spawn <生物ID> - 生成自定义生物
/mebma list - 列出已注册的自定义生物
/mebma remove <半径（格）> - 移除附近的自定义生物
```

### 自定义AI动作测试指令
```
/aitest chase - 生成追击测试僵尸
/aitest patrol - 生成巡逻测试僵尸
/aitest demo - 演示动作队列
/aitest fly - 生成飞行测试蝙蝠
```

## GUI 界面

### 主菜单
- **配置目标生物**：配置视线中的最近的生物
- **附近的生物**：列出附近所有生物
- **生成自定义生物**：生成已注册的自定义生物
- **全局配置**：重载配置、清除 AI、查看统计

### 配置生物界面
- **查看详细信息**：查看单个原版或自定义生物的生物属性、AI 状态、动作队列
- **修改属性**：实时调整单个原版或自定义生物的生命值、速度、攻击伤害
- **执行动作**：移动、跟随、攻击、随机移动等（仅自定义生物支持）
- **配置 AI**：设置敌对性、愤怒状态

### AI 状态显示
- **已激活**：原版生物的 AI 正常运行
- **未激活**：原版生物未添加 AI 或 AI 被禁用
- **自定义AI**：自定义生物的 AI，绿色表示激活状态，红色表示禁用状态

## MEBMobAI API 文档

### 为原版生物附加 AI

```php
use MengBao\MEBMobAI\Component\MobAIManager;
use pocketmine\entity\Living;

// 附加 AI 组件
MobAIManager::attachAI(
    $entity,          // Living 实体
    true,             // 是否敌对
    1.2,              // 移动速度
    4.0,              // 攻击伤害
    ["pocketmine\\player\\Player"],  // 目标类型
    0.5               // 跳跃高度
);

// 获取 AI 组件
$aiComponent = MobAIManager::getAI($entity);

// 设置目标
$aiComponent->setTarget($player);

// 设置愤怒状态
$aiComponent->setAngry(true, 400); // 愤怒 20 秒
```

### 注册自定义生物

```php
use MengBao\MEBMobAI\Main;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\EntityDataHelper;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

// 注册实体
EntityFactory::getInstance()->register(
    MyCustomMob::class,
    function(World $world, CompoundTag $nbt): MyCustomMob {
        return new MyCustomMob(EntityDataHelper::parseLocation($nbt, $world), $nbt);
    },
    ['MyCustomMob']
);

// 注册到 MobRegistry
$plugin = Main::getInstance();
$plugin->getMobRegistry()->registerMob(
    'my_custom_mob',
    MyCustomMob::class,
    [
        'hostile' => true,
        'speed' => 1.5,
        'damage' => 5.0
    ]
);
```

### 为自定义生物配置动作队列

```php
use MengBao\MEBMobAI\Entity\AIEntity;
use MengBao\MEBMobAI\Action\MoveToAction;
use MengBao\MEBMobAI\Action\AttackAction;
use MengBao\MEBMobAI\Action\WaitAction;

// 添加动作到队列
$entity->addAction(new MoveToAction($player, 1.0, 2.0));
$entity->addAction(new WaitAction(40)); // 等待 2 秒
$entity->addAction(new AttackAction($player, 3));

// 清空动作队列
$entity->clearActions();

// 获取当前动作
$currentAction = $entity->getCurrentAction();
```

### 自定义动作定义

创建自定义动作需要继承 `Action` 基类：

```php
use MengBao\MEBMobAI\Action\Action;
use MengBao\MEBMobAI\Entity\AIEntity;

class MyCustomAction extends Action
{
    private string $message;
    private int $duration;

    public function __construct(string $message, int $duration = 60)
    {
        $this->message = $message;
        $this->duration = $duration;
        $this->ticksRemaining = $duration;
    }

    /**
     * 执行动作
     * @return bool true 表示动作完成，false 表示继续执行
     */
    public function execute(AIEntity $entity): bool
    {
        // 检查是否完成
        if ($this->ticksRemaining <= 0) {
            $this->completed = true;
            return true;
        }

        // 执行动作逻辑
        if ($this->ticksRemaining === $this->duration) {
            // 第一次执行
            $entity->getWorld()->addParticle($entity->getPosition(), new \pocketmine\world\particle\HeartParticle());
        }

        // 每 tick 的逻辑
        $entity->setRotation($entity->getLocation()->yaw + 5, 0);

        $this->ticksRemaining--;
        return false;
    }

    /**
     * 重置动作状态
     */
    public function reset(): void
    {
        parent::reset();
        $this->ticksRemaining = $this->duration;
    }

    /**
     * 获取动作名称
     */
    public function getName(): string
    {
        return "MyCustomAction";
    }
}
```

#### Action 基类方法

```php
abstract class Action
{
    protected bool $completed = false;
    protected int $ticksRemaining = 0;

    /**
     * 执行动作（必须实现）
     * @return bool true=动作完成，false=继续执行
     */
    abstract public function execute(AIEntity $entity): bool;

    /**
     * 获取动作名称（必须实现）
     */
    abstract public function getName(): string;

    /**
     * 检查动作是否完成
     */
    public function isCompleted(): bool;

    /**
     * 重置动作状态
     */
    public function reset(): void;
}
```

#### 动作执行流程

1. 动作添加到队列
2. `AIEntity::entityBaseTick()` 每 tick 调用
3. 如果当前无动作或已完成，从队列取出下一个
4. 调用 `action->execute()` 执行动作
5. 返回 `true` 时标记完成，进入下一个动作
6. 返回 `false` 时下一 tick 继续执行

#### 内置动作列表

```php
// MoveToAction - 移动到目标
new MoveToAction($target, $speed, $minDistance)

// FollowAction - 持续跟随
new FollowAction($target, $speed, $followDistance)

// AttackAction - 攻击目标
new AttackAction($target, $attackCount)

// RandomMoveAction - 随机移动
new RandomMoveAction($duration, $speed)

// JumpAction - 跳跃
new JumpAction($height)

// WaitAction - 等待
new WaitAction($ticks)
```

### 检查生物行为权限（MEBWorldProtect 集成）

```php
use MengBao\MEBMobAI\Component\MobAIManager;

// 检查生物是否可以行动
if (MobAIManager::canMobBehave($entity)) {
    // 执行 AI 逻辑
}
```

## 自定义生物示例

### MEBZombie（地面行走生物）

```php
class MEBZombie extends WalkingEntity
{
    protected function getDefaultSpeed(): float { return 1.0; }
    protected function getDefaultAttackDamage(): float { return 3.0; }
    protected function getDefaultMaxHealth(): int { return 20; }
}
```

### MEBBat（飞行生物）

```php
class MEBBat extends FlyingEntity
{
    protected function getDefaultSpeed(): float { return 1.2; }
    protected function getDefaultMaxHealth(): int { return 6; }
}
```

## 技术细节

### AI 更新流程

1. **MobAIManager::updateAll()** - 每 tick 调用
2. **MobAIComponent::update()** - 更新单个 AI
   - 应用原版特性（燃烧、爬墙等）
   - 检查击退冷却
   - 更新目标检测
   - 执行移动/攻击/逃跑逻辑
3. **AIEntity::entityBaseTick()** - 自定义生物更新
   - 执行动作队列
   - 更新行为系统

### 属性持久化

生物属性保存到 NBT 标签：
- `MEBMobAI_Hostile` - 是否敌对
- `MEBMobAI_Speed` - 移动速度
- `MEBMobAI_AttackDamage` - 攻击伤害
- `MEBMobAI_JumpHeight` - 跳跃高度

生成时优先从 NBT 加载，未找到则使用配置文件默认值。

### 击退机制

为避免 AI 覆盖击退效果：
1. 生物受击时设置 10 tick 击退冷却
2. 冷却期间跳过 AI 更新
3. 保证原版击退方向正确

## 性能优化

- **区块检测**：AI 只在加载的区块中更新
- **目标缓存**：减少重复的目标搜索
- **动作队列**：高效的顺序执行
- **NBT 优化**：只在必要时保存数据