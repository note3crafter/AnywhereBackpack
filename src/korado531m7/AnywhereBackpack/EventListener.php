<?php
namespace korado531m7\AnywhereBackpack;

use korado531m7\AnywhereBackpack\AnywhereBackpack;
use korado531m7\AnywhereBackpack\utils\BPUtils;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;

class EventListener implements Listener{
    private $instance;
    
    public function __construct(AnywhereBackpack $instance){
        $this->instance = $instance;
    }
    
    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        if($this->instance->isOpeningBackpack($player)){
            $this->closeBackpack($player);
        }
    }
    
    public function onCraft(CraftItemEvent $event){
        $player = $event->getPlayer();
        if(!$player->hasPermission('anywherebackpack.craftingitem') && $event->getRecipe() == $this->instance->getRecipe()){
            $event->setCancelled();
        }
    }
    
    public function onUseItem(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $event->getItem();
        switch(true){
            case $item->getCustomName() === $this->instance->getItemName():
                $newItem = $this->instance->getSavedBackpackItem();
                $this->instance->db->registerBackpack(BPUtils::getIdFromItem($newItem));
                $player->getInventory()->setItemInHand($newItem);
            case $item->getCustomName() === $this->instance->getItemName(true):
                $this->instance->sendBackpack($player);
                $event->setCancelled();
            break;
        }
    }
    
    public function onReceive(DataPacketReceiveEvent $event){
        $pk = $event->getPacket();
        $player = $event->getPlayer();
        if($this->instance->isOpeningBackpack($player) && $pk instanceof ContainerClosePacket){
            $this->closeBackpack($player);
        }
    }
    
    private function closeBackpack(Player $player){
        $status = $this->instance->getInventoryStatus($player);
        $this->instance->db->saveBackpack($status['id'], $status['inventory']->getContents());
        BPUtils::sendFakeChest($player, $status['x'], $status['y'], $status['z'], true);
        $this->instance->resetInventoryStatus($player);
        $player->
    }
}