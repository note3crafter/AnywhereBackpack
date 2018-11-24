<?php
namespace korado531m7\AnywhereBackpack\task;

use pocketmine\scheduler\Task;
use pocketmine\Player;

class DelayAddWindowTask extends Task{
    public function __construct(Player $player,$inventory){
        $this->who = $player;
        $this->inventory = $inventory;
    }
    
    public function onRun(int $tick) : void{
        $this->who->addWindow($this->inventory);
    }
}
