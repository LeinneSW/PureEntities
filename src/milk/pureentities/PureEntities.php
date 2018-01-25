<?php

namespace milk\pureentities;

use milk\pureentities\entity\monster\walking\Blaze;
use milk\pureentities\entity\monster\walking\IronGolem;
use milk\pureentities\entity\monster\walking\Skeleton;
use milk\pureentities\entity\monster\walking\Zombie;
use milk\pureentities\entity\projectile\LargeFireBall;
use milk\pureentities\entity\projectile\SmallFireBall;
use milk\pureentities\tile\Spawner;
use pocketmine\block\Air;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

class PureEntities extends PluginBase implements Listener{

    public function onLoad(){
        Entity::registerEntity(Blaze::class, \false, ['minecraft:blaze']);
        //Entity::registerEntity(CaveSpider::class, \false, ['minecraft:cavespider']);
        //Entity::registerEntity(Chicken::class, \false, ['minecraft:chicken']);
        //Entity::registerEntity(Cow::class, \false, ['minecraft:cow']);
        //Entity::registerEntity(Creeper::class, \false, ['minecraft:creeper']);
        //Entity::registerEntity(Enderman::class, \false, ['minecraft:enderman']);
        //Entity::registerEntity(Ghast::class, \false, ['minecraft:ghast']);
        Entity::registerEntity(IronGolem::class, \false, ['minecraft:irongolem']);
        //Entity::registerEntity(MagmaCube::class, \false, ['minecraft:magmacube']);
        //Entity::registerEntity(Mooshroom::class, \false, ['minecraft:mooshroom']);
        //Entity::registerEntity(Ocelot::class, \false, ['minecraft:ocelot']);
        //Entity::registerEntity(Pig::class, \false, ['minecraft:pig']);
        //Entity::registerEntity(PigZombie::class, \false, ['minecraft:pigzombie']);
        //Entity::registerEntity(Rabbit::class, \false, ['minecraft:rabbit']);
        //Entity::registerEntity(Sheep::class, \false, ['minecraft:sheep']);
        //Entity::registerEntity(Silverfish::class, \false, ['minecraft:silverfish']);
        Entity::registerEntity(Skeleton::class, \false, ['minecraft:skeleton']);
        //Entity::registerEntity(Slime::class, \false, ['minecraft:slime']);
        //Entity::registerEntity(SnowGolem::class, \false, ['minecraft:snowgolem']);
        //Entity::registerEntity(Spider::class, \false, ['minecraft:spider']);
        //Entity::registerEntity(Wolf::class, \false, ['minecraft:wolf']);
        Entity::registerEntity(Zombie::class, \false, ['minecraft:zombie']);
        //Entity::registerEntity(ZombieVillager::class, \false, ['minecraft:zombievillager']);
        Entity::registerEntity(SmallFireBall::class, \false, ['minecraft:smallfireball']);
        Entity::registerEntity(LargeFireBall::class, \false, ['minecraft:largefireball']);

        Tile::registerTile(Spawner::class);

        $this->getServer()->getLogger()->info(TextFormat::GOLD . '[PureEntities]All entities were registered');
    }

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getLogger()->info(TextFormat::GOLD . '[PureEntities]Plugin has been enabled');
    }

    public function onDisable(){
        $this->getServer()->getLogger()->info(TextFormat::GOLD . '[PureEntities]Plugin has been disabled');
    }

    public function PlayerInteractEvent(PlayerInteractEvent $ev){
        if($ev->getFace() === 255 || $ev->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            return;
        }

        $item = $ev->getItem();
        $block = $ev->getBlock();
        if($item->getId() === Item::SPAWN_EGG && $block->getId() === Item::MONSTER_SPAWNER){
            $ev->setCancelled();

            $tile = $block->level->getTile($block);
            if($tile !== null && $tile instanceof Spawner){
                $tile->setSpawnEntityType($item->getDamage());
            }else{
                if($tile !== null){
                    $tile->close();
                }
                $nbt = new CompoundTag('', [
                    new StringTag('id', Tile::MOB_SPAWNER),
                    new IntTag('EntityId', $item->getId()),
                    new IntTag('x', $block->x),
                    new IntTag('y', $block->y),
                    new IntTag('z', $block->z),
                ]);
                new Spawner($block->getLevel(), $nbt);
            }
        }
    }

    public function BlockPlaceEvent(BlockPlaceEvent $ev){
        if($ev->isCancelled()){
            return;
        }

        $block = $ev->getBlock();
        if($block->getId() === Item::JACK_O_LANTERN || $block->getId() === Item::PUMPKIN){
            if(
                $block->getSide(Vector3::SIDE_DOWN)->getId() === Item::SNOW_BLOCK
                && $block->getSide(Vector3::SIDE_DOWN, 2)->getId() === Item::SNOW_BLOCK
            ){
                for($y = 1; $y < 3; $y++){
                    $block->getLevel()->setBlock($block->add(0, -$y, 0), new Air());
                }
                $entity = Entity::createEntity('SnowGolem', $block->level, Entity::createBaseNBT(Position::fromObject($block->add(0.5, -2, 0.5), $block->level)));
                if($entity !== null){
                    $entity->spawnToAll();
                }
                $ev->setCancelled();
            }elseif(
                $block->getSide(Vector3::SIDE_DOWN)->getId() === Item::IRON_BLOCK
                && $block->getSide(Vector3::SIDE_DOWN, 2)->getId() === Item::IRON_BLOCK
            ){
                $down = $block->getSide(Vector3::SIDE_DOWN);
                $first = $down->getSide(Vector3::SIDE_EAST);
                $second = $down->getSide(Vector3::SIDE_WEST);
                if(
                    $first->getId() === Item::IRON_BLOCK
                    && $second->getId() === Item::IRON_BLOCK
                ){
                    $block->getLevel()->setBlock($first, new Air());
                    $block->getLevel()->setBlock($second, new Air());
                }else{
                    $first = $down->getSide(Vector3::SIDE_NORTH);
                    $second = $down->getSide(Vector3::SIDE_SOUTH);
                    if(
                        $first->getId() === Item::IRON_BLOCK
                        && $second->getId() === Item::IRON_BLOCK
                    ){
                        $block->getLevel()->setBlock($first, new Air());
                        $block->getLevel()->setBlock($second, new Air());
                    }else{
                        return;
                    }
                }

                if($second !== null){
                    $entity = Entity::createEntity('IronGolem', $block->level, Entity::createBaseNBT(Position::fromObject($block->add(0.5, -2, 0.5), $block->level)));
                    if($entity !== null){
                        $entity->spawnToAll();
                    }

                    $down->getLevel()->setBlock($entity, new Air());
                    $down->getLevel()->setBlock($block->add(0, -1, 0), new Air());
                    $ev->setCancelled();
                }
            }
        }
    }

    //TODO: SilverFish will be soon coming.
    /*public function BlockBreakEvent(BlockBreakEvent $ev){
        if($ev->isCancelled()){
            return;
        }

        $block = $ev->getBlock();
        if(
            (
                $block->getId() === Block::STONE
                or $block->getId() === Block::STONE_WALL
                or $block->getId() === Block::STONE_BRICK
                or $block->getId() === Block::STONE_BRICK_STAIRS
            ) && ($block->level->getBlockLightAt((int) $block->x, (int) $block->y, (int) $block->z) < 12 and mt_rand(1, 5) < 2)
        ){
            $entity = PureEntities::create('Silverfish', $block);
            if($entity !== null){
                $entity->spawnToAll();
            }
        }
    }*/

}