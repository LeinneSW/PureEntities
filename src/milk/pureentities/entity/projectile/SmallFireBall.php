<?php

namespace milk\pureentities\entity\projectile;

use pocketmine\entity\projectile\Throwable;
use pocketmine\level\Explosion;
use pocketmine\event\entity\ExplosionPrimeEvent;

class SmallFireBall extends Throwable{
    const NETWORK_ID = self::SMALL_FIREBALL;

    public $fireTicks = 1200;

    protected $damage = 4;

    public function flagForDespawn() : void{
        parent::flagForDespawn();
        //TODO: Fire around
        $this->server->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this, 2.8));
        if(!$ev->isCancelled()){
            $explosion = new Explosion($this, $ev->getForce(), $this->getTargetEntity());
            if($ev->isBlockBreaking()){
                $explosion->explodeA();
            }
            $explosion->explodeB();
        }
    }

}