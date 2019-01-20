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
    public $db;
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        @mkdir($this->getDataFolder(), 0744, true);
        @mkdir($this->getDataFolder().'SaveData/', 0744, true);
        $this->saveResource('config.yml', false);
        $this->config = new Config($this->getDataFolder().'config.yml', Config::YAML);
        switch($this->config->get('backpack-savetype')){
            case 'SQLite3':
                $this->db = new SQLite3Provider($this);
            break;
            
            default:
                $this->getLogger()->warning('Save type '.$this->config->get('backpack-savetype').' is not available');
                $this->getServer()->getPluginManager()->disablePlugin($this);
            break;
        }
        $recipe = new ShapedRecipe(['AAA','A A','AAA'], ['A' => Item::get(ItemIds::LEATHER, -1, 1)], [$this->getBackpackItem()]);
        $this->getServer()->getCraftingManager()->registerShapedRecipe($recipe);
        $this->getServer()->getCraftingManager()->buildCraftingDataCache();
    }
    
    public function onDisable(){
        if($this->config->get('reset-every-server-start') && isset($this->db)){
            $this->db->formatDatabase();
        }
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $params) : bool{
        if(strtolower($label) === 'backpack' && $sender instanceof Player){
            if($this->config->get('allow-open-with-command')){
                if($this->config->get('allow-open-specific-backpack') && isset($params[0]) && filter_var($params[0], FILTER_VALIDATE_INT) !== false){
                    if(!$this->config->get('open-specific-onlypermitted') || ($this->config->get('open-specific-onlypermitted') && $sender->hasPermission('anywherebackpack.a2openspecificbackpack'))){;
                        if($this->db->getNextId() < $params[0]){
                            $sender->sendMessage(str_replace('%id', $params[0], $this->config->get('cannot-open-notregistered-backpack')));
                        }else{
                            $this->sendBackpack($sender, $params[0]);
                        }
                    }else{
                        $sender->sendMessage($this->config->get('open-specific-backpack-noperm'));
                    }
                    return true;
                }
                if($sender->getInventory()->getItemInHand()->getCustomName() === $this->getItemName(true)){
                    $this->sendBackpack($sender);
                }else{
                    $sender->sendMessage($this->config->get('cannot-open-nobackpack'));
                }
            }else{
                $sender->sendMessage($this->config->get('message-open-command-rejected'));
            }
        }
        return true;
    }
    
    public function sendBackpack(Player $player, ?int $id = null){
        if($this->isAllowedSpecificWorld() && ($player->getLevel()->getName() !== $this->isAllowedSpecificWorld(true))) return true;
        $id = $id === null ? BPUtils::getIdFromItem($player->getInventory()->getItemInHand()) : $id;
        if($id === null){
            $player->sendMessage($this->config->get('not-compatible-backpack'));
        }else{
            $inv = new BackpackInventory($player, $this->config->get('backpack-inventory-name').'§r §7(No.'.$id.')');
            $inv->setContents($this->db->restoreBackpack($id));
            $this->setInventoryStatus($player, $inv->getX(), $inv->getY(), $inv->getZ(), $inv->getInventory(), $id);
            $this->getScheduler()->scheduleDelayedTask(new DelayAddWindowTask($player, $inv->getInventory()), 10);
        }
    }
    
    public function getInventoryStatus(Player $player) : array{
        return $this->invStatus[strtolower($player->getName())] ?? [];
    }
    
    public function isOpeningBackpack(Player $player) : bool{
        return count($this->getInventoryStatus($player)) !== 0;
    }
    
    private function setInventoryStatus(Player $player, int $x, int $y, int $z, BackpackInventory $inv, int $id) : void{
        $this->invStatus[strtolower($player->getName())] = ['x' => $x, 'y' => $y, 'z' => $z, 'inventory' => $inv, 'id' => $id];
    }
    
    public function isAllowedSpecificWorld(bool $getName = false){
        $data = $this->config->get('allow-open-specific-world');
        return $getName ? $data : (bool) $data;
    }
    
    public function resetInventoryStatus(Player $player) : void{
        $this->invStatus[strtolower($player->getName())] = [];
    }
    
    public function getItemName(bool $activate = false) : string{
        $color = $activate ? 'a' : '7';
        return "§{$color}Backpack";
    }
    
    public function getBackpackItem(bool $activate = false) : Item{
<<<<<<< HEAD
        return Item::get(ItemIds::CHEST, 0, 1)->setCustomName($this->getItemName($activate));
=======
        $item = Item::get(ItemIds::CHEST, 0, 1)->setCustomName($this->getItemName($activate));
        if(!$activate) $item->setLore(['', $this->config->get('backpack-needtoactivate')]);
        return $item;
>>>>>>> origin/master
    }
    
    public function getSavedBackpackItem() : Item{
        return BPUtils::setIdToItem($this->getBackpackItem(true), $this->db->getNextId());
    }
}