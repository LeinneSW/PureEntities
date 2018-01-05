# PureEntities

Development: **[Leinne](https://github.com/LeinneSW)**

PureEntities is a Plug-in that makes implement the entity.
This Plug-in provides a simple Entity AI.

## Notice
#### Welcome Github issue!
This plug-in is in development. Therefore, It is possible to function abnormally.

## Sub Module
[EntityManager](https://github.com/LeinneSW/EntityManager)  

## Method list
  * PureEntities
    * `static EntityBase create(int|string $type, Position $pos, Object... $args)`
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
    * `void setDamage(double damage, int difficulty)`

## Example
``` php
foreach(Server::getInstance()->getDefaultLevel()->getEntities() as $entity){
    $entity->setWallCheck(false);
    $entity->setMovement(!$entity->isMovement());

    if($entity instanceof Monster){
        $entity->setDamage(10);

        $entity->setMaxDamage(10);
        $entity->setMinDamage(10);
    }
});

$zombie = PureEntities.create("Zombie", $position);
if($zombie != null){
    $zombie->spawnToAll(); //if you don't use this method, you couldn't see this
}

$arrow = PureEntities.create("Arrow", $position, $player, true);
if($arrow != null){
    $arrow->spawnToAll();
}
```