<?php

declare(strict_types=1);

namespace leinne\pureentities;

use leinne\pureentities\entity\EntityBase;
use leinne\pureentities\entity\neutral\ZombiePigman;
use leinne\pureentities\entity\neutral\Spider;
use leinne\pureentities\entity\passive\Chicken;
use leinne\pureentities\entity\passive\Cow;
use leinne\pureentities\entity\passive\Mooshroom;
use leinne\pureentities\entity\passive\Pig;
use leinne\pureentities\entity\passive\Sheep;
use leinne\pureentities\entity\hostile\Creeper;
use leinne\pureentities\entity\hostile\Skeleton;
use leinne\pureentities\entity\hostile\Zombie;
use leinne\pureentities\entity\utility\IronGolem;
use leinne\pureentities\task\AutoSpawnTask;
use leinne\pureentities\tile\MobSpawner;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\SpawnEgg;
use pocketmine\level\Position;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Spawnable;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;

class PureEntities extends PluginBase implements Listener{

    private $data = [];

    public function onLoad(){
        /** Register hostile */
//        Entity::registerEntity(Blaze::class, \false, ['minecraft:blaze']);
        Entity::registerEntity(Creeper::class, \false, ['minecraft:creeper']);
//        Entity::registerEntity(Enderman::class, \false, ['minecraft:enderman']);
//        Entity::registerEntity(Ghast::class, \false, ['minecraft:ghast']);
//        Entity::registerEntity(MagmaCube::class, \false, ['minecraft:magmacube']);
//        Entity::registerEntity(Silverfish::class, \false, ['minecraft:silverfish']);
        Entity::registerEntity(Skeleton::class, \false, ['minecraft:skeleton']);
//        Entity::registerEntity(Slime::class, \false, ['minecraft:slime']);
        Entity::registerEntity(Zombie::class, \false, ['Zombie', 'minecraft:zombie']);
        //Entity::registerEntity(ZombieVillager::class, \false, ['minecraft:zombie_villager']);

        /** Register neutral */
//        Entity::registerEntity(CaveSpider::class, \false, ['minecraft:cavespider']);
        Entity::registerEntity(ZombiePigman::class, \false, ['ZombiePigman', 'minecraft:zombie_pigman']);
        Entity::registerEntity(Spider::class, \false, ['Spider', 'minecraft:spider']);

        /** Register passive */
        Entity::registerEntity(Chicken::class, \false, ['Chicken', 'minecraft:chicken']);
        Entity::registerEntity(Cow::class, \false, ['Cow', 'minecraft:cow']);
        Entity::registerEntity(Mooshroom::class, \false, ['Mooshroom', 'minecraft:mooshroom']);
        Entity::registerEntity(Pig::class, \false, ['Pig', 'minecraft:pig']);
//        Entity::registerEntity(Rabbit::class, \false, ['Rabbit', 'minecraft:rabbit']);
        Entity::registerEntity(Sheep::class, \false, ['Sheep', 'minecraft:sheep']);

        /** Register tameable */
//        Entity::registerEntity(Ocelot::class, \false, ['minecraft:ocelot']);
//        Entity::registerEntity(Wolf::class, \false, ['minecraft:wolf']);

        /** Register utility */
        Entity::registerEntity(IronGolem::class, \false, ['IronGolem', 'minecraft:iron_golem']);
//        Entity::registerEntity(SnowGolem::class, \false, ['SnowGolem', 'minecraft:snow_golem']);

        /** Register Projectile */
//        Entity::registerEntity(SmallFireBall::class, \false, ['minecraft:smallfireball']);
//        Entity::registerEntity(LargeFireBall::class, \false, ['minecraft:largefireball']);

        Tile::registerTile(MobSpawner::class);

        foreach(Entity::getKnownEntityTypes() as $k => $className){
            /** @var EntityBase $className */
            if(
                \is_a($className, EntityBase::class, \true)
                && $className::NETWORK_ID !== -1
                && !ItemFactory::isRegistered(Item::SPAWN_EGG, $className::NETWORK_ID)
            ){
                ItemFactory::registerItem(new SpawnEgg(Item::SPAWN_EGG, $className::NETWORK_ID, "Spawn Egg"));
            }
        }

        $this->getServer()->getLogger()->info(TextFormat::AQUA . '[PureEntities]All entities were registered');
    }

    public function onEnable() : void{
        $this->saveDefaultConfig();
        $this->data = $this->getConfig()->getAll();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if(($this->data["autospawn"]["enable"] ?? "true") === "true"){
            $this->getScheduler()->scheduleRepeatingTask(new AutoSpawnTask(), (int) ($this->data["autospawn"]["tick"] ?? 80));
        }

        $this->getServer()->getLogger()->info(TextFormat::GOLD . '[PureEntities]Plugin has been enabled');
    }

    public function onDisable() : void{
        $this->getServer()->getLogger()->info(TextFormat::GOLD . '[PureEntities]Plugin has been disabled');
    }

    public function onInteractEvent(PlayerInteractEvent $ev) : void{
        if($ev->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            return;
        }

        $item = $ev->getItem();
        $block = $ev->getBlock();
        if($item->getId() === Item::SPAWN_EGG && $block->getId() === Item::MONSTER_SPAWNER){
            $ev->setCancelled();

            $tile = $block->level->getTile($block);
            if($tile instanceof MobSpawner){
                $tile->setSpawnEntityType($item->getDamage());
            }else{
                if($tile !== \null){
                    $tile->close();
                }

                $tile = Tile::createTile("MobSpawner", $block->level, new CompoundTag('', [
                    new StringTag('id', Tile::MOB_SPAWNER),
                    new IntTag('EntityId', $item->getDamage()),
                    new IntTag('x', $block->x),
                    new IntTag('y', $block->y),
                    new IntTag('z', $block->z),
                ]));
                if($tile instanceof Spawnable){
                    $tile->spawnToAll();
                }
            }
        }
    }

    public function onBlockPlaceEvent(BlockPlaceEvent $ev) : void{
        if($ev->isCancelled()){
            return;
        }

        $item = $ev->getItem();
        $block = $ev->getBlock();
        $player = $ev->getPlayer();
        if($block->getId() === Block::JACK_O_LANTERN || $block->getId() === Block::PUMPKIN){
            if(
                $block->getSide(Facing::DOWN)->getId() === Block::SNOW_BLOCK
                && $block->getSide(Facing::DOWN, 2)->getId() === Block::SNOW_BLOCK
            ){
                $entity = Entity::createEntity('SnowGolem', $block->level, Entity::createBaseNBT(Position::fromObject($block->add(0.5, -2, 0.5), $block->level)));
                if($entity !== \null){
                    $ev->setCancelled();
                    for($y = 1; $y < 3; $y++){
                        $block->getLevel()->setBlock($block->subtract(0, $y, 0), new Air());
                    }
                    $entity->spawnToAll();

                    if($player->isSurvival()){
                        $item->pop();
                        $player->getInventory()->setItemInHand($item);
                    }
                }
            }elseif(
                $block->getSide(Facing::DOWN)->getId() === Block::IRON_BLOCK
                && $block->getSide(Facing::DOWN, 2)->getId() === Block::IRON_BLOCK
            ){
                $down = $block->getSide(Facing::DOWN);
                if(($first = $down->getSide(Facing::EAST))->getId() === Block::IRON_BLOCK){
                    $second = $down->getSide(Facing::WEST);
                }elseif(($first = $down->getSide(Facing::NORTH))->getId() === Block::IRON_BLOCK){
                    $second = $down->getSide(Facing::SOUTH);
                }


                if(isset($second) && $second->getId() === Block::IRON_BLOCK){
                    $entity = Entity::createEntity('IronGolem', $block->level, Entity::createBaseNBT(Position::fromObject($pos = $block->add(0.5, -2, 0.5), $block->level)));
                    if($entity !== \null){
                        $ev->setCancelled();

                        $down->getLevel()->setBlock($pos, new Air());
                        $down->getLevel()->setBlock($first, new Air());
                        $down->getLevel()->setBlock($second, new Air());
                        $down->getLevel()->setBlock($block->add(0, -1, 0), new Air());

                        $entity->spawnToAll();

                        if($player->isSurvival()){
                            $item->pop();
                            $player->getInventory()->setItemInHand($item);
                        }
                    }
                }
            }
        }
    }

    //TODO: SilverFish
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
            if($entity !== \null){
                $entity->spawnToAll();
            }
        }
    }*/

}