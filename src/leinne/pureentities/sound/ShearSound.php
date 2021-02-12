<?php

declare(strict_types=1);

namespace leinne\pureentities\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\world\sound\Sound;

class ShearSound implements Sound{
    public function encode(?Vector3 $pos) : array{
        return [
            LevelSoundEventPacket::create(LevelSoundEventPacket::SOUND_SHEAR, $pos)
        ];
    }
}