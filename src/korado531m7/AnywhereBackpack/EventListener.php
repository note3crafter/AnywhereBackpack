<?php
namespace korado531m7\AnywhereBackpack;

use korado531m7\AnywhereBackpack\AnywhereBackpack;
use korado531m7\AnywhereBackpack\utils\BPUtils;

use pocketmine\event\Listener;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;

class EventListener implements Listener{
    private $instance;
    
    public function __construct(AnywhereBackpack $instance){
        $this->instance = $instance;
    }
    
    public function onUseItem(PlayerInteractEvent $event){
        $item = $event->getItem();
        if($item->getCustomName() === $this->instance->getItemName()){
            $event->setCancelled();
            $this->instance->sendBackpack($event->getPlayer());
        }
    }
    
    public function onCraft(CraftItemEvent $event){
        $output = $event->getOutputs();
        if(array_shift($output)->getCustomName() === $this->instance->getItemName()){
            $inv = $event->getPlayer()->getInventory();
            $event->setCancelled();
            $inv->removeItem(Item::get(ItemIds::LEATHER,0,8));
            $inv->addItem($this->instance->getSavedBackpackItem());
        }
    }
    
    public function onReceive(DataPacketReceiveEvent $event){
        $pk = $event->getPacket();
        $player = $event->getPlayer();
        if($pk instanceof ContainerClosePacket && $this->instance->isOpeningBackpack($player)){
            $status = $this->instance->getInventoryStatus($player);
            $this->instance->setBackpackItems($status[4], $status[3]->getContents());
            BPUtils::sendFakeChest($player, $status[0], $status[1], $status[2], true);
            $this->instance->resetInventoryStatus($player);
        }
    }
}