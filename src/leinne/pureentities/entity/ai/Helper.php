<?php

declare(strict_types=1);

namespace leinne\pureentities\entity\ai;

abstract class Helper{

    /** @var EntityNavigator */
    protected $navigator;

    public function __construct(EntityNavigator $navigator){
        $this->navigator = $navigator;
    }

    public abstract function calculate() : ?array;

}