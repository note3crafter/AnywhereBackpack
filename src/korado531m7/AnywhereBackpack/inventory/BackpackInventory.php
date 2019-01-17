<?php
namespace korado531m7\AnywhereBackpack\inventory; 

use korado531m7\AnywhereBackpack\AnywhereBackpack;
use korado531m7\AnywhereBackpack\utils\BPUtils;

use pocketmine\Player;
use pocketmine\inventory\ContainerInventory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\WindowTypes;

class BackpackInventory extends ContainerInventory{
    protected $network_type = WindowTypes::CONTAINER;
    protected $title;
    protected $size = 54;
    private $pos;
    
    public function __construct(Player $player, string $title){
        $this->title = $title;
        $this->player = $player;
        $this->pos = $pos = $player->floor()->add(0, 3);
        parent::__construct($pos, [], $this->size, $title);
        $this->prepare();
    }
    
    public function getInventory() : BackpackInventory{
        return $this;
    }
    
    public function getPlayer() : Player{
        return $this->player;
    }
    
    public function getX() : int{
        return $this->pos->x;
    }
    
    public function getY() : int{
        return $this->pos->y;
    }
    
    public function getZ() : int{
        return $this->pos->z;
    }

    public function getNetworkType() : int{
        return $this->network_type;
    }
    
    public function getName() : string{
        return $this->title;
    }
    
    public function getDefaultSize() : int{
        return $this->size;
    }
    
    public function setName() : void{
        $tag = new CompoundTag();
        $tag->setString('CustomName', $this->title);
        BPUtils::sendTagData($this->getPlayer(), $tag, $this->getX(), $this->getY(), $this->getZ());
    }
    
    public function setPair(){
        $tag = new CompoundTag();
        $tag->setInt('pairx', $this->getX());
        $tag->setInt('pairz', $this->getZ());
        BPUtils::sendTagData($this->getPlayer(), $tag, $this->getX(), $this->getY(), $this->getZ() + 1);
    }
    
    public function prepare() : void{
        BPUtils::sendFakeChest($this->getPlayer(), $this->getX(), $this->getY(), $this->getZ());
        $this->setPair();
        $this->setName();
    }
}