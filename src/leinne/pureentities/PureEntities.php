<?php

declare(strict_types=1);

namespace leinne\pureentities;

use leinne\pureentities\entity\ai\walk\AStarHelper;
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
use leinne\pureentities\entity\utility\SnowGolem;
use leinne\pureentities\task\AutoSpawnTask;
use leinne\pureentities\tile\MonsterSpawner;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIdentifier as BID;
use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Living;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\SpawnEgg;
use pocketmine\world\Position;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\block\tile\TileFactory;
use pocketmine\utils\TextFormat;

class PureEntities extends PluginBase implements Listener{

    private $data = [];

    public function onLoad(){
        /** Register hostile */
//        EntityFactory::register(Blaze::class, ['minecraft:blaze']);
        EntityFactory::register(Creeper::class, ['minecraft:creeper']);
//        EntityFactory::register(Enderman::class, ['minecraft:enderman']);
//        EntityFactory::register(Ghast::class, ['minecraft:ghast']);
//        EntityFactory::register(MagmaCube::class, ['minecraft:magmacube']);
//        EntityFactory::register(Silverfish::class, ['minecraft:silverfish']);
        EntityFactory::register(Skeleton::class, ['minecraft:skeleton']);
//        EntityFactory::register(Slime::class, ['minecraft:slime']);
        EntityFactory::register(Zombie::class, ['Zombie', 'minecraft:zombie']);
        //EntityFactory::register(ZombieVillager::class, ['minecraft:zombie_villager']);

        /** Register neutral */
//        EntityFactory::register(CaveSpider::class, ['minecraft:cavespider']);
        EntityFactory::register(ZombiePigman::class, ['ZombiePigman', 'minecraft:zombie_pigman']);
        EntityFactory::register(Spider::class, ['Spider', 'minecraft:spider']);

        /** Register passive */
        EntityFactory::register(Chicken::class, ['Chicken', 'minecraft:chicken']);
        EntityFactory::register(Cow::class, ['Cow', 'minecraft:cow']);
        EntityFactory::register(Mooshroom::class, ['Mooshroom', 'minecraft:mooshroom']);
        EntityFactory::register(Pig::class, ['Pig', 'minecraft:pig']);
//        EntityFactory::register(Rabbit::class, ['Rabbit', 'minecraft:rabbit']);
        EntityFactory::register(Sheep::class, ['Sheep', 'minecraft:sheep']);

        /** Register tameable */
//        EntityFactory::register(Ocelot::class, ['minecraft:ocelot']);
//        EntityFactory::register(Wolf::class, ['minecraft:wolf']);

        /** Register utility */
        EntityFactory::register(IronGolem::class, ['IronGolem', 'minecraft:iron_golem']);
//        EntityFactory::register(SnowGolem::class, ['SnowGolem', 'minecraft:snow_golem']);

        /** Register Projectile */
//        EntityFactory::register(SmallFireBall::class, ['minecraft:smallfireball']);
//        EntityFactory::register(LargeFireBall::class, ['minecraft:largefireball']);

        BlockFactory::register(new block\MonsterSpawner(new BID(BlockLegacyIds::MOB_SPAWNER, 0, null, MonsterSpawner::class), "Monster Spawner"), true);

        foreach(EntityFactory::getKnownTypes() as $k => $className){
            /** @var Living|string $className */
            if(is_a($className, EntityBase::class, true) && $className::NETWORK_ID !== -1){
                ItemFactory::register(new SpawnEgg(ItemIds::SPAWN_EGG, $className::NETWORK_ID, "Spawn " . (new \ReflectionClass($className))->getShortName(), $className), true);
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

        $astar = $this->data["astar"] ?? [];
        AStarHelper::init((int) $astar["maximum-tick"] ?? 80, (int) $astar["block-per-tick"] ?? 220);
        $this->getServer()->getLogger()->info(
            TextFormat::AQUA . "\n" .
            "------------------------------------------------------\n" .
            " _____                ______       _   _ _   _\n" .
            "|  __ \              |  ____|     | | (_) | (_)\n" .
            "| |__) |   _ _ __ ___| |__   _ __ | |_ _| |_ _  ___  ___ \n" .
            "|  ___/ | | | '__/ _ \  __| | '_ \| __| | __| |/ _ \/ __|\n" .
            "| |   | |_| | | |  __/ |____| | | | |_| | |_| |  __/\__ \\\n" .
            "|_|    \__,_|_|  \___|______|_| |_|\__|_|\__|_|\___||___/\n" .
            "------------------------------------------------------\n"
        );
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
        if($item->getId() === ItemIds::SPAWN_EGG && $block->getId() === ItemIds::MONSTER_SPAWNER){
            $ev->setCancelled();

            $tile = $block->getPos()->getWorld()->getTile($block->getPos());
            if($tile instanceof MonsterSpawner){
                $tile->setSpawnEntityType($item->getMeta());
            }else{
                if($tile !== null){
                    $tile->close();
                }

                $tile = TileFactory::create("MobSpawner", $block->getPos()->getWorld(), $block->getPos());
                $tile->readSaveData(CompoundTag::create()->setInt('EntityId', $item->getMeta()));
                $tile->getPos()->getWorld()->addTile($tile);
            }
        }
        /*$pos = $block->getPos()->asPosition();
        $pos->x = Math::floorFloat($pos->x) + 0.5;
        $pos->z = Math::floorFloat($pos->z) + 0.5;
        echo "좌표: $pos\n";

        $entity = $this->getServer()->getWorldManager()->getDefaultWorld()->getEntity($this->data[$ev->getPlayer()->getNameTag()] ?? -1);
        if($entity instanceof EntityBase){
            $entity->getNavigator()->setEnd($pos);
        }*/
    }

    /*public function onDamageEvent(EntityDamageEvent $ev) : void{
        $entity = $ev->getEntity();
        if($ev instanceof EntityDamageByEntityEvent){
            $damager = $ev->getDamager();
            if($damager->getInventory()->getItemInHand()->getId() == ItemIds::STICK){
                $ev->setCancelled();
            }
            $this->data[$damager->getNameTag()] = $entity->getId();
            $this->getLogger()->info($damager->getNameTag() . "님의 히트 감지, " . $entity->getId());
        }
    }*/

    public function onBlockPlaceEvent(BlockPlaceEvent $ev) : void{
        if($ev->isCancelled()){
            return;
        }

        $item = $ev->getItem();
        $block = $ev->getBlock();
        $player = $ev->getPlayer();
        if($block->getId() === BlockLegacyIds::JACK_O_LANTERN || $block->getId() === BlockLegacyIds::PUMPKIN){
            if(
                $block->getSide(Facing::DOWN)->getId() === BlockLegacyIds::SNOW_BLOCK
                && $block->getSide(Facing::DOWN, 2)->getId() === BlockLegacyIds::SNOW_BLOCK
            ){
                try{
                    $entity = EntityFactory::create(SnowGolem::class, $block->getPos()->getWorld(), EntityFactory::createBaseNBT(Position::fromObject($block->getPos()->add(0.5, -2, 0.5), $block->getPos()->getWorld())));
                }catch(\Exception $e){
                    $player->sendMessage(TextFormat::RED . 'Error');
                    return;
                }
                $ev->setCancelled();
                for($y = 1; $y < 3; $y++){
                    $block->getPos()->getWorld()->setBlock($block->getPos()->subtract(0, $y, 0), BlockFactory::get(BlockLegacyIds::AIR));
                }
                $entity->spawnToAll();

                if($player->hasFiniteResources()){
                    $item->pop();
                    $player->getInventory()->setItemInHand($item);
                }
            }elseif(
                ($down = $block->getSide(Facing::DOWN))->getId() === BlockLegacyIds::IRON_BLOCK
                && $block->getSide(Facing::DOWN, 2)->getId() === BlockLegacyIds::IRON_BLOCK
            ){
                if(($first = $down->getSide(Facing::EAST))->getId() === BlockLegacyIds::IRON_BLOCK){
                    $second = $down->getSide(Facing::WEST);
                }

                if(!isset($second) && ($first = $down->getSide(Facing::NORTH))->getId() === BlockLegacyIds::IRON_BLOCK){
                    $second = $down->getSide(Facing::SOUTH);
                }

                if(!isset($second) || $second->getId() !== BlockLegacyIds::IRON_BLOCK){
                    return;
                }

                $nbt = EntityFactory::createBaseNBT(Position::fromObject($pos = $block->getPos()->add(0.5, -2, 0.5), $block->getPos()->getWorld()));
                $nbt->setString("Owner", $player->getName());
                try{
                    $entity = EntityFactory::create(IronGolem::class, $block->getPos()->getWorld(), $nbt);
                }catch(\Exception $e){
                    $player->sendMessage('[PureEntities] 골렘 소환중 오류 발생');
                    return;
                }
                $ev->setCancelled();
                $entity->spawnToAll();

                $down->getPos()->getWorld()->setBlock($pos, BlockFactory::get(BlockLegacyIds::AIR));
                $down->getPos()->getWorld()->setBlock($first->getPos(), BlockFactory::get(BlockLegacyIds::AIR));
                $down->getPos()->getWorld()->setBlock($second->getPos(), BlockFactory::get(BlockLegacyIds::AIR));
                $down->getPos()->getWorld()->setBlock($block->getPos()->add(0, -1, 0), BlockFactory::get(BlockLegacyIds::AIR));

                if($player->hasFiniteResources()){
                    $item->pop();
                    $player->getInventory()->setItemInHand($item);
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
                $block->getId() === BlockLegacyIds::STONE
                or $block->getId() === BlockLegacyIds::STONE_WALL
                or $block->getId() === BlockLegacyIds::STONE_BRICK
                or $block->getId() === BlockLegacyIds::STONE_BRICK_STAIRS
            ) && ($block->level->getBlockLightAt((int) $block->x, (int) $block->y, (int) $block->z) < 12 and mt_rand(1, 5) < 2)
        ){
            $entity = PureEntities::create('Silverfish', $block);
            if($entity !== \null){
                $entity->spawnToAll();
            }
        }
    }*/

}