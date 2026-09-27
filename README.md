# MEBMobAI - 生物AI插件

[![PocketMine-MP](https://img.shields.io/badge/PocketMine--MP-5.0-blue)](https://github.com/pmmp/PocketMine-MP)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)](https://www.php.net/)

为 PocketMine-MP 服务器提供高级生物AI系统，支持自定义AI生物和原版生物AI增强。

---

## 📋 目录

- [功能特性](#功能特性)
- [系统架构](#系统架构)
- [快速开始](#快速开始)
- [配置说明](#配置说明)
- [自定义生物开发](#自定义生物开发)
- [API文档](#api文档)
- [常见问题](#常见问题)

---

## ✨ 功能特性
所有生物AI都会受MEBWroldProtect的生物行为规则控制。  
### 核心功能
- **双AI系统**
  - 动作系统（Action System）：适用于自定义AI生物
  - 组件系统（Component System）：适用于原版生物AI增强
  
- **原版生物AI增强**
  - 为原版生物（僵尸、骷髅、苦力怕等）附加智能AI
  - 支持寻路、追击、攻击、逃跑等行为
  - 可配置移动速度、攻击伤害、跳跃高度等属性
  
- **中立生物系统**
  - 支持受攻击后反击机制（如狼、铁傀儡）
  - 愤怒状态计时器（默认20秒）
  - 自动目标锁定和追击

- **智能行为**
  - 自动寻找并追击目标
  - 障碍物检测与跳跃
  - 随机漫游
  - 被动生物逃跑机制

---

## 🏗️ 系统架构

### 双AI系统架构

```
MEBMobAI
├── 动作系统 (Action System)
│   ├── AIEntity (基类)
│   │   ├── WalkingEntity (陆地生物)
│   │   ├── FlyingEntity (飞行生物)
│   │   └── SwimmingEntity (水生生物)
│   └── Action (动作接口)
│       ├── WalkAction (行走)
│       ├── AttackAction (攻击)
│       └── 自定义动作...
│
└── 组件系统 (Component System)
    ├── MobAIComponent (AI组件)
    │   ├── 目标搜索
    │   ├── 移动控制
    │   ├── 攻击逻辑
    │   └── 中立生物反击
    └── MobAIManager (管理器)
        └── 自动附加到原版生物
```

### 目录结构

```
src/MengBao/MEBMobAI/
├── Action/              # 动作系统
│   ├── Action.php       # 动作接口
│   ├── WalkAction.php   # 行走动作
│   └── ...
├── Behavior/            # 行为系统
│   ├── Behavior.php     # 行为接口
│   └── ...
├── Component/           # 组件系统（原版生物AI）
│   ├── MobAIComponent.php   # AI组件
│   └── MobAIManager.php     # AI管理器
├── Entity/              # 实体类
│   ├── AIEntity.php         # AI实体基类
│   ├── WalkingEntity.php    # 陆地生物
│   ├── FlyingEntity.php     # 飞行生物
│   ├── SwimmingEntity.php   # 水生生物
│   └── MEBZombie.php        # 示例：自定义僵尸
├── Listener/            # 事件监听器
│   └── EntityListener.php   # 生物事件监听
└── Main.php             # 插件主类

resources/
├── config.yml               # 全局配置
├── custom_mob_ai.yml        # 自定义生物AI配置
└── vanilla_mob_ai.yml       # 原版生物AI配置
```

---

## ⚙️ 配置说明

### config.yml - 全局配置

```yaml
# MEBMobAI 插件配置文件

# 常规设置
general:
  # 这里为插件的全局配置
```

### vanilla_mob_ai.yml - 原版生物AI配置

```yaml
# 原版生物AI配置文件

# 配置格式：
# 生物名称:
#   hostile: true/false        # 是否主动攻击
#   speed: 浮点数              # 移动速度倍数
#   damage: 浮点数             # 攻击伤害
#   jump_height: 浮点数        # 跳跃高度
#   target_types: []           # 攻击目标类型（类名数组）

# 敌对生物
zombie:
  hostile: true
  speed: 1.0
  damage: 3.0
  jump_height: 0.5
  target_types:
    - "pocketmine\\player\\Player"
    - "pocketmine\\Entity\\Villager"

skeleton:
  hostile: true
  speed: 1.2
  damage: 2.5
  jump_height: 0.5
  target_types:
    - "pocketmine\\player\\Player"

# 中立生物（受攻击后反击）
wolf:
  hostile: false      # 不主动攻击
  speed: 1.2
  damage: 4.0
  jump_height: 0.6
  target_types:
    - "pocketmine\\player\\Player"

# 和平生物
pig:
  hostile: false
  speed: 0.8
  damage: 0.0
  jump_height: 0.5
  target_types: []
```

### custom_mob_ai.yml - 自定义生物AI配置

```yaml
# 自定义AI生物配置文件

# 僵尸
zombie:
  speed: 1.0
  attack_damage: 3.0
  max_health: 20
  jump_height: 0.5
  hostile: true

# 蝙蝠
bat:
  speed: 1.2
  attack_damage: 1.0
  max_health: 6
  jump_height: 0.42
  hostile: false

# 全局默认值
default:
  speed: 1.0
  attack_damage: 0.0
  max_health: 20
  jump_height: 0.5
  hostile: false
```

---

## 🛠️ 自定义生物开发

### 创建自定义陆地生物

```php
<?php

namespace YourNamespace;

use MengBao\MEBMobAI\Entity\WalkingEntity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class CustomZombie extends WalkingEntity
{
    public static function getNetworkTypeId(): string
    {
        return EntityIds::ZOMBIE;
    }

    public function getName(): string
    {
        return "Custom Zombie";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.8, 0.6, 1.62);
    }
}
```

### 创建自定义动作

```php
<?php

namespace YourNamespace\Action;

use MengBao\MEBMobAI\Action\Action;
use MengBao\MEBMobAI\Entity\AIEntity;

class CustomAction implements Action
{
    private AIEntity $entity;
    private int $duration;

    public function __construct(AIEntity $entity, int $duration)
    {
        $this->entity = $entity;
        $this->duration = $duration;
    }

    public function start(): void
    {
        // 动作开始时执行
    }

    public function update(): void
    {
        $this->duration--;
    }

    public function isFinished(): bool
    {
        return $this->duration <= 0;
    }

    public function onFinish(): void
    {
        // 动作结束时执行
    }
}
```

### 注册自定义生物

```php
// 在 Main.php 的 onEnable() 中注册
EntityFactory::getInstance()->register(
    CustomZombie::class,
    function(World $world, CompoundTag $nbt): CustomZombie {
        return new CustomZombie(EntityDataHelper::parseLocation($nbt, $world), $nbt);
    },
    ['CustomZombie', 'minecraft:custom_zombie']
);
```

---

## 📚 API文档

### MobAIManager - AI管理器

```php
// 为实体附加AI
MobAIManager::attachAI(
    Living $entity,          // 实体对象
    bool $hostile,           // 是否敌对
    float $speed,            // 移动速度倍数
    float $attackDamage,     // 攻击伤害
    array $targetTypes,      // 目标类型数组
    float $jumpHeight        // 跳跃高度
);

// 移除AI
MobAIManager::removeAI(int $entityId);

// 获取AI组件
$component = MobAIManager::getAIComponent(Living $entity);
```

### MobAIComponent - AI组件

```php
// 检查是否敌对
$component->isHostile(): bool

// 检查是否可以攻击
$component->canAttack(): bool

// 设置目标
$component->setTarget(?Entity $target): void

// 设置愤怒状态（中立生物）
$component->setAngry(bool $angry, int $duration = 400): void

// 检查是否愤怒
$component->isAngry(): bool

// 获取实体
$component->getEntity(): Living
```

### AIEntity - AI实体基类

```php
// 添加动作到队列
$entity->addAction(Action $action): void

// 添加持续行为
$entity->addBehavior(Behavior $behavior): void

// 移除持续行为
$entity->removeBehavior(): void

// 获取当前动作
$entity->getCurrentAction(): ?Action

// 清空动作队列
$entity->clearActions(): void
```

---

## ❓ 常见问题

### Q: 原版生物不会移动/攻击？
**A:** 检查以下几点：
1. 确认 `vanilla_mob_ai.yml` 配置正确
2. 查看日志是否有 "附加AI到生物" 的提示
3. 确认生物类型在配置文件中存在

### Q: 如何禁用某个生物的AI？
**A:** 在 `vanilla_mob_ai.yml` 中删除对应生物的配置即可

### Q: 中立生物不会反击？
**A:** 确保配置中：
- `hostile: false` （不主动攻击）
- `damage: > 0` （有攻击伤害）
- `target_types` 包含 `"pocketmine\\player\\Player"`

### Q: 如何调整生物追击距离？
**A:** 修改 `MobAIComponent.php` 中的 `getFollowRange()` 方法返回值（默认16格）

### Q: 自定义生物如何使用AI？
**A:** 继承 `AIEntity` 或其子类（`WalkingEntity`、`FlyingEntity`、`SwimmingEntity`），并在 `custom_mob_ai.yml` 中配置

### Q: 生物跳跃高度如何调整？
**A:** 修改配置文件中的 `jump_height` 值：
- 默认：0.5
- 蜘蛛：0.8（跳得更高）
- 鸡：0.3（跳得较低）

---

## 📝 更新日志

### v1.0.0
- ✅ 双AI系统架构
- ✅ 原版生物AI增强
- ✅ 中立生物反击机制
- ✅ 智能寻路和跳跃
- ✅ 配置文件系统
- ✅ 动作和行为系统

---

