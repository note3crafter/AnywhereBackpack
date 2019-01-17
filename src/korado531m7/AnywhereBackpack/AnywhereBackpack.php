<?php
namespace korado531m7\AnywhereBackpack;

use korado531m7\AnywhereBackpack\inventory\BackpackInventory;
use korado531m7\AnywhereBackpack\provider\SQLite3Provider;
use korado531m7\AnywhereBackpack\task\DelayAddWindowTask;
use korado531m7\AnywhereBackpack\utils\BPUtils;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class AnywhereBackpack extends PluginBase{
    private $invStatus = [];
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        @mkdir($this->getDataFolder(), 0744, true);
        $this->db = new SQLite3Provider($this);
        $this->saveResource('config.yml', false);
        $this->config = new Config($this->getDataFolder().'config.yml', Config::YAML);
        $recipe = new ShapedRecipe(['AAA','A A','AAA'], ['A' => Item::get(ItemIds::LEATHER,0,1)], [$this->getBackpackItem()]);
        $this->getServer()->getCraftingManager()->registerShapedRecipe($recipe);
        $this->getServer()->getCraftingManager()->buildCraftingDataCache();
    }
    
    public function onDisable(){
        if($this->config->get('reset-every-server-start')){
            $this->db->formatDatabase();
        }
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
        if(strtolower($label) === 'backpack' && $sender instanceof Player){
            if($this->config->get('allow-open-with-command')){
                if($sender->getInventory()->getItemInHand()->getCustomName() === $this->getItemName()){
                    $this->sendBackpack($sender);
                }else{
                    $sender->sendMessage('§cYou must have backpack to open with command');
                }
            }else{
                $sender->sendMessage($this->config->get('message-open-command-rejected'));
            }
        }
        return true;
    }
    
    public function sendBackpack(Player $player){
        if($this->isAllowedSpecificWorld() && ($player->getLevel()->getName() !== $this->isAllowedSpecificWorld(true))) return true;
        $id = BPUtils::getIdFromItem($player->getInventory()->getItemInHand());
        if($id === null){
            $player->sendMessage('§cThis backpack is broken. Create new one.');
        }else{
            $inv = new BackpackInventory($player, $this->config->get('backpack-inventory-name').'§r §7(No.'.$id.')');
            $inv->setContents($this->db->restoreBackpack($id));
            $this->setInventoryStatus($player, [$inv->getX(), $inv->getY(), $inv->getZ(), $inv->getInventory(), $id]);
            $this->getScheduler()->scheduleDelayedTask(new DelayAddWindowTask($player, $inv->getInventory()), 10);
        }
    }
    
    public function setBackpackItems(int $id, array $items) : void{
        $this->db->saveBackpack($id, $items);
    }
    
    public function getInventoryStatus(Player $player) : array{
        return $this->invStatus[strtolower($player->getName())] ?? [];
    }
    
    public function isOpeningBackpack(Player $player) : bool{
        return count($this->getInventoryStatus($player)) !== 0;
    }
    
    private function setInventoryStatus(Player $player, array $data) : void{
        $this->invStatus[strtolower($player->getName())] = $data;
    }
    
    public function isAllowedSpecificWorld(bool $getName = false){
        $data = $this->config->get('allow-open-specific-world');
        return $getName ? $data : (bool) $data;
    }
    
    public function resetInventoryStatus(Player $player) : void{
        $this->invStatus[strtolower($player->getName())] = [];
    }
    
    public function getItemName() : string{
        return $this->config->get('backpack-item-name');
    }
    
    public function getBackpackItem() : Item{
        return Item::get(54, 0, 1)->setCustomName($this->getItemName());
    }
    
    public function getSavedBackpackItem() : Item{
        return BPUtils::setIdToItem($this->getBackpackItem(), $this->db->getNextId());
    }
}