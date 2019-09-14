<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\passive;

use pocketmine\network\mcpe\protocol\types\entity\EntityLegacyIds;

class Mooshroom extends Cow{

    const NETWORK_ID = EntityLegacyIds::MOOSHROOM;

    public function getName() : string{
        return 'Mooshroom';
    }

}