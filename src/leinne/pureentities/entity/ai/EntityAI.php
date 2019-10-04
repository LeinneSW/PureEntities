<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\math\Facing;
use pocketmine\Server;
use pocketmine\world\Position;

class EntityAI{

    const WALL = 0;
    const AIR = 1;
    const BLOCK = 2;
    const SLAB = 3;
    const STAIR = 4;
    const LAVA = 5;
    const DOOR = 6;

    public static function checkBlockState(Position $pos) : int{
        $block = $pos->getWorld()->getBlock($pos);
        $blockBox = $block->getCollisionBoxes()[0] ?? null;
        if($blockBox === null){ //블럭이 없을때
            $up = $block->getSide(Facing::UP)->getCollisionBoxes()[0] ?? null;
            if($up === null || $up->maxY - $up->minY <= 0){ //y + 1에도 아무 블럭이 없다면
                return EntityAI::AIR;
            }else{
                return EntityAI::WALL;
            }
        }elseif($blockBox->maxY - $blockBox->minY > 1){ //울타리일 때
            return EntityAI::WALL;
        }elseif($blockBox->maxY - $blockBox->minY == 1){ //블럭일 때
            $up = $block->getSide(Facing::UP)->getCollisionBoxes()[0] ?? null;
            if($up === null || $up->maxY - $up->minY <= 0){ //y + 1에 아무 블럭이 없고
                $aabb = $block->getSide(Facing::UP, 2)->getCollisionBoxes()[0] ?? null;
                if($aabb === null || $aabb->maxY - $aabb->minY <= 0){ //y + 2에도 아무 블럭이 없다면
                    return EntityAI::BLOCK;
                }else{
                    return EntityAI::WALL;
                }
            }else{
                return EntityAI::WALL;
            }
        }elseif($blockBox->maxY - $blockBox->minY === 0.5){ //반블럭일 때
            $up = $block->getSide(Facing::UP)->getCollisionBoxes()[0] ?? null;
            if($up === null || $up->maxY - $up->minY <= 0){ //y + 1에 아무 블럭이 없고
                $aabb = $block->getSide(Facing::UP, 2)->getCollisionBoxes()[0] ?? null;
                if($aabb === null || ($aabb->maxY - $aabb->minY === 0.5 && $aabb->minY - (int) $aabb->minY === 0.5)){ //y + 2에도 아무 블럭이 없거나 반블럭이라면
                    return EntityAI::SLAB;
                }else{
                    return EntityAI::WALL;
                }
            }else{
                return EntityAI::WALL;
            }
        }
        Server::getInstance()->getLogger()->warning("[PureEntities]정체불명의 블럭 감지됨: $block, 차이 값: " . ($blockBox->maxY - $blockBox->minY));
        return EntityAI::WALL;
    }

}
