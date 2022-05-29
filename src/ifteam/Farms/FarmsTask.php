<?php

namespace ifteam\Farms;

use pocketmine\scheduler\Task;

class FarmsTask extends Task{
	/** @var Farms */
	private $owner;

	public function __construct(Farms $owner){
		$this->owner = $owner;
	}

	public function onRun() : void{
		$this->owner->tick();
	}
}