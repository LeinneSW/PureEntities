# PureEntities

Development: **[LeinneSW](https://github.com/LeinneSW)** (before milk0417)

PureEntities is a Plug-in that makes implement the entity.  
This Plug-in provides a simple Entity AI.

## Notice

### Welcome Github issue!
This plug-in is in development. Therefore, It is possible to function abnormally.

### Supported Server software
[PocketMine-MP](https://pmmp.io/)

## Simple API
  * EntityBase
    * `float getSpeed()`
    * `void setSpeed(float $speed)`
    * `?Vector3 getTarget()`
    * `void setTarget(Vector $target, bool $fixed = \false)`
    * `void setTargetFixed(bool $fixed)`
  * Animal
    * `bool isBaby()`
  * Monster
    * `float getResultDamage()`
    * `float[] getDamages()`
    * `void setDamages(float[] $damages)`
    * `void setMaxDamage(float[] $damages)`
    * `void setMinDamages(float[] $damages)`
    * `void setDamage(float $damage, int $difficulty = -1)`
    * `float getMinDamage(int $difficulty = -1)`
    * `void setMinDamage(float $damage, int $difficulty = -1)`
    * `float getMaxDamage(int $difficulty = -1)`
    * `void setMaxDamage(float $damage, int $difficulty = -1)`

## Example
``` php
foreach(Server::getInstance()->getDefaultLevel()->getEntities() as $entity){
    if($entity instanceof Monster){
        $entity->setDamage(10); //Both max / min are set.

        $entity->setMaxDamage(10);
        $entity->setMinDamage(10);
        //If you do not specify the difficulty level, it is set to the current server difficulty level.
    }
});
```
