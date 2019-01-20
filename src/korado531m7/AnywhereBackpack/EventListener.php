<?php
namespace korado531m7\AnywhereBackpack;

use korado531m7\AnywhereBackpack\AnywhereBackpack;
use korado531m7\AnywhereBackpack\utils\BPUtils;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;

class EventListener implements Listener{
    private $instance;
    
    public function __construct(AnywhereBackpack $instance){
        $this->instance = $instance;
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
        if($pk instanceof ContainerClosePacket && $this->instance->isOpeningBackpack($player)){
            $status = $this->instance->getInventoryStatus($player);
            $this->instance->db->saveBackpack($status['id'], $status['inventory']->getContents());
            BPUtils::sendFakeChest($player, $status['x'], $status['y'], $status['z'], true);
            $this->instance->resetInventoryStatus($player);
        }
    }
}