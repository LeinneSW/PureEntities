<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use pocketmine\block\Liquid;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;

class Creeper extends WalkingMonster implements Explosive{
    const NETWORK_ID = 33;
    const DATA_POWERED = 19;

    public $width = 0.72;
    public $height = 1.8;

    private $bombTime = 0;

    public function initEntity(){
        parent::initEntity();

        $this->speed = 0.9;
        if(isset($this->namedtag->IsPowered)){
            $this->setGenericFlag(self::DATA_POWERED, $this->namedtag->IsPowered ? 1 : 0);
        }elseif(isset($this->namedtag->powered)){
            $this->setGenericFlag(self::DATA_POWERED, $this->namedtag->powered ? 1 : 0);
        }

        if(isset($this->namedtag->BombTime)){
            $this->bombTime = (int) $this->namedtag['BombTime'];
        }
    }

    public function isPowered(){
        return $this->getGenericFlag(self::DATA_POWERED);
    }

    public function setPowered($value = \true){
        $this->namedtag->powered = $value;
        $this->setGenericFlag(self::DATA_POWERED, $value ? 1 : 0);
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->BombTime = new IntTag('BombTime', $this->bombTime);
    }

    public function getName() : string{
        return 'Creeper';
    }

    public function explode(){
        $this->server->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this, 2.8));

        if(!$ev->isCancelled()){
            $explosion = new Explosion($this, $ev->getForce(), $this);
            if($ev->isBlockBreaking()){
                $explosion->explodeA();
            }
            $explosion->explodeB();
            $this->close();
        }
    }

    public function onUpdate(int $currentTick) : bool{
        if($this->server->getDifficulty() < 1 || $this->isFlaggedForDespawn()){
            $this->close();
            return \false;
        }

        if(!$this->isAlive()){
            if(++$this->deadTicks >= 23){
                $this->close();
                return \false;
            }
            return \true;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
        $this->entityBaseTick($tickDiff);

        if(!$this->isMovement()){
            return \true;
        }

        if($this->attackTime > 0){
            $this->move($this->motionX * $tickDiff, $this->motionY, $this->motionZ * $tickDiff);
            $this->motionY -= 0.15 * $tickDiff;
            $this->updateMovement();
            return \true;
        }

        $before = $this->target;
        $this->checkTarget();

        if($this->target instanceof Creature || $before !== $this->target){
            $x = $this->target->x - $this->x;
            $y = $this->target->y - $this->y;
            $z = $this->target->z - $this->z;

            $diff = \abs($x) + \abs($z);
            $target = $this->target;
            $distance = ($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2;
            if($distance <= 20){
                if($target instanceof Creature){
                    $this->bombTime += $tickDiff;
                    if($this->bombTime >= 64){
                        $this->explode();
                        return \false;
                    }
                }elseif($distance <= 1){
                    $this->moveTime = 0;
                }
            }else{
                $this->bombTime -= $tickDiff;
                if($this->bombTime < 0){
                    $this->bombTime = 0;
                }

                $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
            }
            $this->yaw = \rad2deg(-\atan2($x / $diff, $z / $diff));
            $this->pitch = $y === 0 ? 0 : \rad2deg(-\atan2($y, \sqrt($x * $x + $z * $z)));
        }

        $dx = $this->motionX * $tickDiff;
        $dz = $this->motionZ * $tickDiff;
        $isJump = $this->checkJump($tickDiff, $dx, $dz);
        if($this->stayTime > 0){
            $this->stayTime -= $tickDiff;
            $this->move(0, $this->motionY * $tickDiff, 0);
        }else{
            $be = new Vector2($this->x + $dx, $this->z + $dz);
            $this->move($dx, $this->motionY * $tickDiff, $dz);
            $af = new Vector2($this->x, $this->z);

            if(($be->x !== $af->x || $be->y !== $af->y) && !$isJump){
                $this->moveTime -= 90 * $tickDiff;
            }
        }

        if(!$isJump){
            if($this->onGround){
                $this->motionY = 0;
            }elseif($this->motionY > -$this->gravity * 4){
                if(!($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.9), Math::floorFloat($this->z))) instanceof Liquid)){
                    $this->motionY -= $this->gravity * $tickDiff;
                }
            }else{
                $this->motionY -= $this->gravity * $tickDiff;
            }
        }
        $this->updateMovement();
        return \true;
    }

    public function updateMove($tickDiff){
        return null;
    }

    public function attackEntity(Entity $player){

    }

    public function getDrops() : array{
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(\mt_rand(0, 2)){
                case 0:
                    return [Item::get(Item::FLINT, 0, 1)];
                case 1:
                    return [Item::get(Item::GUNPOWDER, 0, 1)];
                case 2:
                    return [Item::get(Item::REDSTONE_DUST, 0, 1)];
            }
        }
        return [];
    }

}
