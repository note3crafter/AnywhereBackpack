<?php
namespace korado531m7\AnywhereBackpack;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\item\ItemIds;
use pocketmine\item\Item;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\command\Command;
use korado531m7\AnywhereBackpack\task\DelayAddWindowTask;

class AnywhereBackpack extends PluginBase{
    private static $pBase;
    private static $config;
    private static $invStatus = [];
    private static $backpack = [];
    
    public function onEnable(){
        self::$pBase = $this;
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        @mkdir($this->getDataFolder(), 0744, true);
        $this->saveResource('config.yml', false);
        self::$config = new Config($this->getDataFolder().'config.yml', Config::YAML);
        $recipe = new ShapedRecipe(["AAA","A A","AAA"], ["A" => Item::get(ItemIds::LEATHER,0,1) ], [$this->getBackpackItem()]);
        $this->getServer()->getCraftingManager()->registerShapedRecipe($recipe);
        $this->getServer()->getCraftingManager()->buildCraftingDataCache();
        Item::addCreativeItem($this->getBackpackItem());
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
        if(strtolower($command->getName()) === 'backpack' && $sender instanceof Player){
            if(self::$config->get('allow-open-with-command')){
                self::sendBackpack($sender);
            }else{
                $sender->sendMessage(self::$config->get('message-open-command-rejected'));
            }
        }
        return true;
    }
    
    public static function sendBackpack(Player $player){
        if(self::isAllowedSpecificWorld() && ($player->getLevel()->getName() !== self::isAllowedSpecificWorld(true))) return true;
        $inv = new BackpackClass($player, (int) $player->getX(), (int) $player->getY() + 4, (int) $player->getZ(), self::$config->get('backpack-inventory-name'));
        $inv->prepare();
        $inv->setContents(self::getBackpackItems($player));
        self::setInventoryStatus($player, [$inv->getX(), $inv->getY(), $inv->getZ(), $inv->getInventory()]);
        self::$pBase->getScheduler()->scheduleDelayedTask(new DelayAddWindowTask($player, $inv->getInventory()), 10);
    }
    
    public static function setBackpackItems(Player $player, array $items) : void{
        self::$backpack[strtolower($player->getName())] = $items;
    }
    
    public static function formatBackpack(Player $player) : void{
        $name = strtolower($player->getName());
        if((self::$backpack[$name] ?? null) === null) self::$backpack[$name] = [];
    }
    
    public static function getBackpackItems(Player $player) : array{
        return self::$backpack[strtolower($player->getName())] ?? [];
    }
    
    public static function getInventoryStatus(Player $player) : array{
        return self::$invStatus[strtolower($player->getName())] ?? [];
    }
    
    public static function isOpeningBackpack(Player $player) : bool{
        return count(self::getInventoryStatus($player)) !== 0;
    }
    
    public static function isAllowedSpecificWorld(bool $getName = false){
        $data = self::$config->get('allow-open-specific-world');
        return (bool) $getName ? $data : (bool) $data;
    }
    
    public static function resetInventoryStatus(Player $player) : void{
        self::$invStatus[strtolower($player->getName())] = [];
    }
    
    public static function getItemName() : string{
        return self::$config->get('backpack-item-name');
    }
    
    private function getBackpackItem() : Item{
        return Item::get(54, 0, 1)->setCustomName(self::getItemName());
    }
    
    private static function setInventoryStatus(Player $player, array $data) : void{
        self::$invStatus[strtolower($player->getName())] = $data;
    }
}