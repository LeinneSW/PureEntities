<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

use pocketmine\block\Lava;
use pocketmine\math\Facing;
use pocketmine\Server;
use pocketmine\world\Position;

class EntityAI{

    const WALL = 0;
    const AIR = 1;
    const BLOCK = 2;
    const SLAB = 3;
    const STAIR = 4;
    const DOOR = 5;

    public static function checkBlockState(Position $pos) : int{
        $block = $pos->getWorld()->getBlock($pos);
        if(count($blocks = $block->getAffectedBlocks()) > 1){ //이웃된 블럭이 있을 때
            $blockA = $blocks[0]->getCollisionBoxes()[0] ?? null;
            $blockB = $blocks[1]->getCollisionBoxes()[0] ?? null;
            if($blockA !== null && $blockB !== null && max($blockA->maxY, $blockB->maxY) - min($blockA->minY, $blockB->minY) == 2){ //그 블럭이 문이라면
                return EntityAI::DOOR;
            }
        }

        $blockBox = $block->getCollisionBoxes()[0] ?? null;
        if($blockBox === null){ //블럭이 통과 가능할 때
            $up = $block->getSide(Facing::UP)->getCollisionBoxes()[0] ?? null;
            if($up === null || $up->maxY - $up->minY <= 0){ //y + 1에 아무 블럭이 없고
                if($block instanceof Lava){ //통과하려는 블럭이 용암이라면
                    return EntityAI::WALL;
                }
                return EntityAI::AIR;
            }else{
                return EntityAI::WALL;
            }
        }/*elseif($block instanceof Stair){ //계단일 때
            //TODO: 계단은 체크하기가 힘들다
        }*/elseif($blockBox->maxY - $blockBox->minY > 1){ //울타리일 때
            return EntityAI::WALL;
        }elseif($blockBox->maxY - $blockBox->minY == 1){ //블럭일 때
            $up = $block->getSide(Facing::UP)->getCollisionBoxes()[0] ?? null;
            if($up === null || $up->maxY - $up->minY <= 0){ //y + 1에 아무 블럭이 없고
                $aabb = $block->getSide(Facing::UP, 2)->getCollisionBoxes()[0] ?? null;
                if($aabb === null || $aabb->maxY - $aabb->minY <= 0){ //y + 2에도 아무 블럭이 없다면
                    return $pos->y - (int) $pos->y === 0.5 ? EntityAI::SLAB : EntityAI::BLOCK;
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
        Server::getInstance()->getLogger()->warning("[PureEntities] 정체불명의 블럭 감지됨: $block, 차이 값: " . ($blockBox->maxY - $blockBox->minY));
        return EntityAI::WALL;
    }

}
