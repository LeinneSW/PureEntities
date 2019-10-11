<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\block\Block;
use pocketmine\block\Door;
use pocketmine\block\Lava;
use pocketmine\block\Stair;
use pocketmine\block\Trapdoor;
use pocketmine\math\Facing;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class EntityAI{

    const WALL = 0;
    const PASS = 1;
    const BLOCK = 2;
    const SLAB = 3;
    const UP_SLAB = 4;
    const DOOR = 5;

    /** @var int[] */
    private static $cache = [];

    public static function getHash(Vector3 $pos) : string{
        $pos = self::getFloorPos($pos);
        return "{$pos->x}:{$pos->y}:{$pos->z}";
    }

    public static function getFloorPos(Vector3 $pos) : Position{
        $newPos = new Position(Math::floorFloat($pos->x), $pos->getFloorY(), Math::floorFloat($pos->z));
        if($pos instanceof Position){
            $newPos->world = $pos->world;
        }
        return $newPos;
    }

    public static function onBlockChanged(Vector3 $pos) : void{
        unset(self::$cache[self::getHash($pos)]);
    }

    /**
     * 특정 블럭이 어떤 상태인지를 확인해주는 메서드
     *
     * @param Block|Position $data
     *
     * @return int
     */
    public static function checkBlockState($data) : int{
        if($data instanceof Position){
            if(isset(self::$cache[$hash = self::getHash($data)])){
                return self::$cache[$hash];
            }
            $floor = self::getFloorPos($data);
            $block = $data->world->getBlockAt($floor->x, $floor->y, $floor->z);
        }elseif($data instanceof Block){
            if(isset(self::$cache[$hash = self::getHash($data->getPos())])){
                return self::$cache[$hash];
            }
            $block = $data;
        }else{
            throw new \RuntimeException("$data is not Block|Position class");
        }

        $value = EntityAI::BLOCK;
        if($block instanceof Door && count($block->getAffectedBlocks()) > 1){ //이웃된 블럭이 있을 때
            $value = EntityAI::DOOR;
        }elseif($block instanceof Stair){
            $value = EntityAI::BLOCK;
        }else{
            $blockBox = $block->getCollisionBoxes()[0] ?? null;
            $boxDiff = $blockBox === null ? 0 : $blockBox->maxY - $blockBox->minY;
            if($boxDiff <= 0){
                if($block instanceof Lava){ //통과 가능 블럭중 예외처리
                    $value = EntityAI::WALL;
                }else{
                    $value = EntityAI::PASS;
                }
            }elseif($boxDiff > 1){ //울타리라면
                $value = EntityAI::WALL;
            }elseif($boxDiff <= 0.5){ //반블럭/카펫/트랩도어 등등
                $value = $blockBox->minY == (int) $blockBox->minY ? EntityAI::SLAB : EntityAI::UP_SLAB;
            }
        }
        //self::$cache[$hash] = $value;
        return $block instanceof Trapdoor ? EntityAI::PASS : $value; //TODO: 트랩도어, 카펫 등
    }

    /**
     * 블럭이 통과 가능한 위치인지를 판단하는 메서드
     *
     * @param Position $pos
     * @param Block|null $block
     *
     * @return int
     */
    public static function checkPassablity(Position $pos, ?Block $block = null) : int{
        if($block === null){
            $floor = self::getFloorPos($pos);
            $block = $pos->world->getBlockAt($floor->x, $floor->y, $floor->z);
        }else{
            $floor = $block->getPos();
        }
        $state = self::checkBlockState($block); //현재 위치에서의 블럭 상태가
        switch($state){
            case EntityAI::WALL:
            case EntityAI::DOOR: //벽이거나 문이라면 체크가 더이상 필요 없음
                return $state;
            case EntityAI::PASS: //통과가능시에
                //윗블럭도 통과 가능하다면 통과판정 아니라면 벽 판정
                return self::checkBlockState($floor->getSide(Facing::UP)) === EntityAI::PASS ? EntityAI::PASS : EntityAI::WALL;
            case EntityAI::BLOCK:
            case EntityAI::UP_SLAB: //블럭이거나 위에 설치된 반블럭일경우
                $up = self::checkBlockState($upBlock = $block->getSide(Facing::UP)); //y+1의 블럭이
                if($up === EntityAI::SLAB){ //반블럭 이고
                    $up2 = self::checkBlockState($floor->getSide(Facing::UP, 2));
                    //그 위가 통과 가능하며 블럭의 최고점과 자신의 위치의 차가 블럭 이하라면 블럭 판정
                    return $up2 === EntityAI::PASS && $upBlock->getCollisionBoxes()[0]->maxY - $pos->y <= 1 ? EntityAI::BLOCK : EntityAI::WALL;
                }elseif($up === EntityAI::PASS){ //통과가능시에
                    //y+ 2도 통과 가능이라면
                    return self::checkBlockState($floor->getSide(Facing::UP, 2)) === EntityAI::PASS ?
                        //블럭의 최고점과 자신의 위치의 차가 반블럭 이하라면 반블럭 판정 아니라면 블럭 판정
                        ($block->getCollisionBoxes()[0]->maxY - $pos->y <= 0.5 ? EntityAI::SLAB : EntityAI::BLOCK) : EntityAI::WALL;
                }
                return EntityAI::WALL;
            case EntityAI::SLAB:
                return (
                    self::checkBlockState($floor->getSide(Facing::UP)) === EntityAI::PASS //y + 1이 통과가능하고
                    && (($up = self::checkBlockState($floor->getSide(Facing::UP, 2))) === EntityAI::PASS || $up === EntityAI::UP_SLAB) //y + 2을 통과가능(반블럭 포함)하면
                ) ? EntityAI::SLAB : EntityAI::WALL;
        }
        return EntityAI::WALL;
    }

    /**
     * 현재 위치에서 도달하게 될 최종 Y좌표를 계산합니다
     *
     * @param Position $pos
     *
     * @return float
     */
    public static function calculateYOffset(Position $pos) : float{
        $newY = (int) $pos->y;
        switch(EntityAI::checkBlockState($pos)){
            case EntityAI::BLOCK:
                $newY += 1;
                break;
            case EntityAI::SLAB:
                $newY += 0.5;
                break;
            case EntityAI::PASS:
                $newPos = self::getFloorPos($pos);
                $newPos->y -= 1;
                for(; $newPos->y >= 0; $newPos->y -= 1){
                    $block = $pos->world->getBlockAt($newPos->x, $newPos->y, $newPos->z);
                    $state = EntityAI::checkBlockState($block);
                    if($state === EntityAI::UP_SLAB || $state === EntityAI::BLOCK || $state === EntityAI::SLAB){
                        foreach($block->getCollisionBoxes() as $_ => $bb){
                            if($newPos->y < $bb->maxY){
                                $newPos->y = $bb->maxY;
                            }
                        }
                        break;
                    }
                }
                $newY = $newPos->y;
                break;
        }
        return $newY;
    }

}
