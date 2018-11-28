<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\mob;

use leinne\pureentities\entity\ai\WalkEntityTrait;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\nbt\tag\CompoundTag;

class Creeper extends Monster implements Explosive{
    //TODO: Beta, 매우 실험적

    use WalkEntityTrait;

    const NETWORK_ID = self::CREEPER;

    public $width = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    private $force = 3.0;

    protected $interactDistance = 3.6;

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);

        $this->setSpeed(0.95);
    }

    public function getName() : string{
        return 'Creeper';
    }

    public function explode() : void{
        $ev = new ExplosionPrimeEvent($this, $this->force);
        $ev->call();

        if(!$ev->isCancelled()){
            $explosion = new Explosion($this, $ev->getForce(), $this);
            if($ev->isBlockBreaking()){
                $explosion->explodeA();
            }
            $explosion->explodeB();
        }
    }

    public function interactTarget() : bool{
        if(($target = parent::checkInteract()) === \null){
            if($this->attackDelay > 0) {
                --$this->attackDelay;
            }elseif($this->getSpeed() < 1){
                $this->setSpeed(0.95);
            }
            return \false;
        }

        //TODO: boom event
        /*$pk = new EntityEventPacket();
        $pk->entityRuntimeId = $this->id;
        $pk->event = EntityEventPacket::ARM_SWING;
        $this->server->broadcastPacket($this->hasSpawned, $pk);*/

        $this->setSpeed(0.4);
        if(++$this->attackDelay >= 32){
            $this->flagForDespawn();
            $this->explode();
        }
        return \false;
    }

    public function getDrops() : array{
        $drops = [
            //TODO
        ];

        return $drops;
    }

    public function getXpDropAmount() : int{
        //TODO: 정확한 수치 모름
        return 0;
    }

}