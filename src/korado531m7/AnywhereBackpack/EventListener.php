<?php
namespace korado531m7\AnywhereBackpack;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\block\BlockIds;
use pocketmine\Player;
use korado531m7\AnywhereBackpack\inventory\BackpackInventory;

class EventListener implements Listener{
    public function __construct(){
    }
    
    public function onUseItem(PlayerInteractEvent $event){
        $item = $event->getItem();
        if($item->getCustomName() === AnywhereBackpack::getItemName()){
            $event->setCancelled();
            AnywhereBackpack::sendBackpack($event->getPlayer());
        }
    }
    
    public function onJoin(PlayerPreLoginEvent $event){
        AnywhereBackpack::formatBackpack($event->getPlayer());
    }
    
    public function onReceive(DataPacketReceiveEvent $event){
        $pk = $event->getPacket();
        $player = $event->getPlayer();
        if($pk instanceof ContainerClosePacket && AnywhereBackpack::isOpeningBackpack($player)){
            $status = AnywhereBackpack::getInventoryStatus($player);
            $inv = new BackpackClass($player, $status[0], $status[1], $status[2]);
            AnywhereBackpack::setBackpackItems($player, $status[3]->getContents());
            $inv->sendFakeChest(true);
            AnywhereBackpack::resetInventoryStatus($player);
        }
    }
}