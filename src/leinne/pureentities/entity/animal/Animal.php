<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\animal;

use leinne\pureentities\entity\EntityBase;
use pocketmine\entity\Ageable;

abstract class Animal extends EntityBase implements Ageable{

    public function isBaby() : bool{
        return $this->getGenericFlag(self::DATA_FLAG_BABY);
    }

}