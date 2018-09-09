<?php

namespace milk\pureentities\entity_before\monster\walking;

use milk\pureentities\entity_before\animal\Animal;
use milk\pureentities\entity_before\EntityBase;
use milk\pureentities\entity_before\monster\WalkingMonster;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Blaze extends WalkingMonster implements ProjectileSource{
    const NETWORK_ID = 43;

    public $width = 0.72;
    public $height = 1.8;
    public $gravity = 0.04;

    public function initEntity(){
        parent::initEntity();

        $this->setDamage([0, 0, 0, 0]);
    }

    public function getName() : string{
        return 'Blaze';
    }

    public function isFireProof() : bool{
        return \true;
    }

    protected function checkTarget(){
        if($this->attackTime > 0){
            return;
        }

        if($this->followTarget != null && !$this->followTarget->closed && $this->followTarget->isAlive()){
            return;
        }

        $option = \true;
        $target = $this->target;
        if(!($target instanceof Creature) or !($option = $this->targetOption($target, $this->distanceSquared($target)))){
            if(!$option) $this->target = \null;

            $near = PHP_INT_MAX;
            foreach ($this->getLevel()->getEntities() as $creature){
                $distance = $this->distanceSquared($creature);
                if(
                    $creature === $this
                    || !($creature instanceof Creature)
                    || $creature instanceof Animal
                    || $creature instanceof EntityBase && $creature->isFriendly() === $this->isFriendly()
                    || $distance > $near or !$this->targetOption($creature, $distance)
                ){
                    continue;
                }

                $near = $distance;
                $this->target = $creature;
            }
        }

        if($this->target instanceof Creature && $this->target->isAlive()){
            return;
        }

        if($this->moveTime <= 0 or !($this->target instanceof Vector3)){
            $x = \mt_rand(20, 100);
            $z = \mt_rand(20, 100);
            $this->moveTime = \mt_rand(300, 1200);
            $this->target = $this->add(\mt_rand(0, 1) ? $x : -$x, 0, \mt_rand(0, 1) ? $z : -$z);
        }
    }

    public function fall(float $fallDistance){

    }

    public function updateMove($tickDiff){
        if(!$this->isMovement()){
            return null;
        }

        if($this->attackTime > 0){
            $this->move($this->motionX * $tickDiff, $this->motionY, $this->motionZ * $tickDiff);
            $this->motionY -= 0.2 * $tickDiff;
            $this->updateMovement();
            return null;
        }

        if($this->followTarget !== \null && !$this->followTarget->closed && $this->followTarget->isAlive()){
            $x = $this->followTarget->x - $this->x;
            $y = $this->followTarget->y - $this->y;
            $z = $this->followTarget->z - $this->z;

            $diff = \abs($x) + \abs($z);
            if($x ** 2 + $z ** 2 < 0.7){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
            }
            $this->yaw = \rad2deg(-\atan2($x / $diff, $z / $diff));
            $this->pitch = $y === 0 ? 0 : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));
        }

        $before = $this->target;
        $this->checkTarget();
        if($this->target instanceof Creature or $before !== $this->target){
            $x = $this->target->x - $this->x;
            $y = $this->target->y - $this->y;
            $z = $this->target->z - $this->z;

            $diff = \abs($x) + \abs($z);
            if($x ** 2 + $z ** 2 < 0.7){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                if($this->target instanceof Creature){
                    $this->motionX = 0;
                    $this->motionZ = 0;
                    $height = $this->y - $this->getLevel()->getHighestBlockAt((int) $this->x, (int) $this->z);
                    if($height < 8){
                        $this->motionY = $this->gravity;
                    }elseif((int) $height === 8){
                        $this->motionY = 0;
                    }
                }else{
                    $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                    $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
                }
            }
            $this->yaw = \rad2deg(-\atan2($x / $diff, $z / $diff));
            $this->pitch = $y === 0 ? 0 : \rad2deg(-\atan2($y, \sqrt($x ** 2 + $z ** 2)));
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
            }else{
                $this->motionY = -$this->gravity;
            }
        }
        $this->updateMovement();
        return $this->target;
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 20 && \mt_rand(1, 32) < 4 && $this->distanceSquared($player) <= 324){
            $this->attackDelay = 0;

            $yaw = $this->yaw + \mt_rand(-50, 50) / 10;
            $pitch = $this->pitch + \mt_rand(-50, 50) / 10;
            $fireball = Entity::createEntity('LargeFireBall', $this->level, new CompoundTag('', [
                'Pos' => new ListTag('Pos', [
                    new DoubleTag('', $this->x + (-\sin(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 0.5)),
                    new DoubleTag('', $this->y),
                    new DoubleTag('', $this->z +(\cos(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 0.5))
                ]),
                'Motion' => new ListTag('Motion', [
                    new DoubleTag('', -\sin(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 1.2),
                    new DoubleTag('', -\sin(\deg2rad($pitch)) * 1.2),
                    new DoubleTag('', \cos(\deg2rad($yaw)) * \cos(\deg2rad($pitch)) * 1.2)
                ]),
                'Rotation' => new ListTag('Rotation', [
                    new FloatTag('', 0),
                    new FloatTag('', 0)
                ]),
            ]), $this);

            if($fireball === \null){
                return;
            }

            $this->server->getPluginManager()->callEvent($launch = new ProjectileLaunchEvent($fireball));
            if($launch->isCancelled()){
                $fireball->kill();
            }else{
                $fireball->spawnToAll();
                $this->level->addSound(new LaunchSound($this), $this->getViewers());
            }
        }
    }

    public function targetOption(Creature $creature, $distance){
        return (!($creature instanceof Player) || ($creature->isSurvival() && $creature->spawned)) && $creature->isAlive() && !$creature->closed && $distance <= 400;
    }

    public function getDrops() : array{
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::GLOWSTONE_DUST, 0, \mt_rand(0, 2))];
        }
        return [];
    }

}