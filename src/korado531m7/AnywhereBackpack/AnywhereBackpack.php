<?php
namespace korado531m7\AnywhereBackpack;

use korado531m7\AnywhereBackpack\inventory\BackpackInventory;
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
    private $backpack = [];
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        @mkdir($this->getDataFolder(), 0744, true);
        $this->saveResource('config.yml', false);
        $this->config = new Config($this->getDataFolder().'config.yml', Config::YAML);
        $recipe = new ShapedRecipe(['AAA','A A','AAA'], ['A' => Item::get(ItemIds::LEATHER,0,1)], [$this->getBackpackItem()]);
        $this->getServer()->getCraftingManager()->registerShapedRecipe($recipe);
        $this->getServer()->getCraftingManager()->buildCraftingDataCache();
        Item::addCreativeItem($this->getBackpackItem());
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
        if(strtolower($label) === 'backpack' && $sender instanceof Player){
            if($this->config->get('allow-open-with-command')){
                $this->sendBackpack($sender);
            }else{
                $sender->sendMessage($this->config->get('message-open-command-rejected'));
            }
        }
        return true;
    }
    
    public function sendBackpack(Player $player){
        if($this->isAllowedSpecificWorld() && ($player->getLevel()->getName() !== $this->isAllowedSpecificWorld(true))) return true;
        $inv = new BackpackInventory($player, $this->config->get('backpack-inventory-name'));
        $inv->prepare();
        $inv->setContents($this->getBackpackItems($player));
        $this->setInventoryStatus($player, [$inv->getX(), $inv->getY(), $inv->getZ(), $inv->getInventory()]);
        $this->getScheduler()->scheduleDelayedTask(new DelayAddWindowTask($player, $inv->getInventory()), 10);
    }
    
    public function setBackpackItems(Player $player, array $items) : void{
        $this->backpack[strtolower($player->getName())] = $items;
    }
    
    public function formatBackpack(Player $player) : void{
        $name = strtolower($player->getName());
        if(($this->backpack[$name] ?? null) === null) $this->backpack[$name] = [];
    }
    
    public function getBackpackItems(Player $player) : array{
        return $this->backpack[strtolower($player->getName())] ?? [];
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
    
    private function getBackpackItem() : Item{
        return Item::get(54, 0, 1)->setCustomName($this->getItemName());
    }
}