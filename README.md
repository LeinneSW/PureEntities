<p align="center">
	<a href="https://github.com/LeinneSW/PureEntities"><img src="https://i.imgur.com/wSQCLmT.png" title="source: imgur.com"/></a>
	<b>The best plugin for PMMP to implement entities with AI</b>
</p>
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
    * `function getGoal() : Vector3`
    * `function getSpeed() : float`
    * `function setSpeed(float $speed) : void`
    * `function setGoal(?Vector3 $target) : void`
    * `function setTargetEntity(?Entity $target, bool $fixed = \false) : void`
  * Animal
    * `function isBaby() : bool`
  * Monster
    * `function getResultDamage() : float`
    * `function getDamages() : float[]`
    * `function setDamages(float[] $damages) : void`
    * `function setMaxDamage(float[] $damages) : void`
    * `function setMinDamages(float[] $damages) : void`
    * `function setDamage(float $damage, int $difficulty = -1) : void`
    * `function getMinDamage(int $difficulty = -1) : float`
    * `function setMinDamage(float $damage, int $difficulty = -1) : void`
    * `function getMaxDamage(int $difficulty = -1) : float`
    * `function setMaxDamage(float $damage, int $difficulty = -1) : void`

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
