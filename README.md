# PureEntities

Development: **[Leinne](https://github.com/LeinneSW)** (before milk0417)

PureEntities is a Plug-in that makes implement the entity.  
This Plug-in provides a simple Entity AI.

## Notice

### Start developing again!
I decided to **start development again**.  
I want lots of **issues** and **interests**.

### Welcome Github issue!
This plug-in is in development. Therefore, It is possible to function abnormally.

### Supported Server software
[PocketMine-MP](https://pmmp.io/)

## Method list
  * EntityBase
    * `Entity getTarget()`
    * `boolean isMovement()`
    * `boolean isFriendly()`
    * `boolean isWallCheck()`
    * `void setTarget(Entity $target)`
    * `void setMovement(boolean $value)`
    * `void setFriendly(boolean $value)`
    * `void setWallCheck(boolean $value)`
  * Monster
    * `double getDamage()`
    * `double getMinDamage()`
    * `double getMaxDamage()`
    * `double getDamage(int $difficulty)`
    * `double getMinDamage(int $difficulty)`
    * `double getMaxDamage(int $difficulty)`
    * `void setDamage(double $damage)`
    * `void setDamage(double[] $damage)`
    * `void setDamage(double $damage, int $difficulty)`

## Example
``` php
foreach(Server::getInstance()->getDefaultLevel()->getEntities() as $entity){
    $entity->setWallCheck(\false);
    $entity->setMovement(!$entity->isMovement());

    if($entity instanceof Monster){
        $entity->setDamage(10); //Both max / min are set.

        $entity->setMaxDamage(10);
        $entity->setMinDamage(10);
        //If you do not specify the difficulty level, it is set to the current server difficulty level.
    }
});

$zombie = Entity::createEntity('Zombie', $position->level, Entity::createBaseNBT($position));
if($zombie !== \null){
    $zombie->spawnToAll(); //if you don't use this method, you couldn't see this
}

$arrow = Entity::createEntity('Arrow', $position->level, Entity::createBaseNBT($position), $player, \true);
if($arrow !== \null){
    $arrow->spawnToAll();
}
```