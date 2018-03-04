<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Wolf extends WalkingMonster{
    const NETWORK_ID = 14;

    private $angry = 0;

    public $width = 0.72;
    public $height = 0.9;

    public function initEntity(){
        parent::initEntity();

        $this->speed = 1.2;
        if($this->namedtag->hasTag('Angry', IntTag::class)){
            $this->angry = $this->namedtag->getInt('Angry');
        }

        $this->setMaxHealth(8);
        $this->setDamage([0, 3, 4, 6]);
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->setInt('Angry', $this->angry);
    }

    public function getName() : string{
        return 'Wolf';
    }

    public function isFireProof() : bool{
        return \true;
    }

    public function isAngry() : bool{
        return $this->angry > 0;
    }

    public function setAngry(int $val){
        $this->angry = $val;
    }

    public function attack(EntityDamageEvent $source){
        parent::attack($source);

        if(!$source->isCancelled()){
            $this->setAngry(1000);
        }
    }

    public function targetOption(Creature $creature, $distance){
        return $this->isAngry() && parent::targetOption($creature, $distance);
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.6){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev);
        }
    }

    public function getDrops() : array{
        return [];
    }

}
