<?php
namespace korado531m7\AnywhereBackpack;

use korado531m7\AnywhereBackpack\inventory\BackpackInventory;
use pocketmine\Player;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockEntityDataPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\types\WindowTypes;

class BackpackClass{
    private $player;
    private $x;
    private $y;
    private $z;
    
    public function __construct(Player $player, int $x, int $y, int $z, ?string $backpackName = null){
        $this->player = $player;
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
        $this->backpackName = $backpackName;
        $this->inv = null;
    }
    
    private function makeBackpack(){
        $this->inv = new BackpackInventory($this->getBackpackType(), new Vector3($this->getX(), $this->getY(), $this->getZ()), $this->getBackpackMaximumInventories());
    }
    
    public function prepare() : void{
        $this->makeBackpack();
        $this->sendFakeChest();
        $this->setPair();
        $this->setName();
    }
    
    public function getInventory() : ?BackpackInventory{
        return $this->inv;
    }
    
    public function getContents() : array{
        return $this->inv->getContents();
    }
    
    public function setContents(array $items) : void{
        $this->inv->setContents($items);
    }
    
    public function setName() : void{
        $tag = new CompoundTag();
        $tag->setString('CustomName', $this->backpackName ?? 'unknown');
        self::sendTagData($this->getPlayer(), $tag, $this->getX(), $this->getY(), $this->getZ());
    }
    
    public function getPlayer() : Player{
        return $this->player;
    }
    
    public function getX() : int{
        return $this->x;
    }
    
    public function getY() : int{
        return $this->y;
    }
    
    public function getZ() : int{
        return $this->z;
    }
    
    public function sendFakeChest(bool $isAir = false){
        $id = BlockIds::CHEST;
        if($isAir) $id = BlockIds::AIR;
        $this->sendFakeBlock($this->getPlayer(), $this->getX(), $this->getY(), $this->getZ(), $id);
        $this->sendFakeBlock($this->getPlayer(), $this->getX(), $this->getY(), $this->getZ() + 1, $id);
    }
    
    public function setPair(){
        $tag = new CompoundTag();
        $tag->setInt('pairx', $this->getX());
        $tag->setInt('pairz', $this->getZ());
        $this->sendTagData($this->getPlayer(), $tag, $this->getX(), $this->getY(), $this->getZ() + 1);
    }
    
    private static function sendFakeBlock(Player $player,int $x,int $y,int $z,int $blockid){
        $pk = new UpdateBlockPacket();
        $pk->x = $x;
        $pk->y = $y;
        $pk->z = $z;
        $pk->flags = UpdateBlockPacket::FLAG_ALL;
        $pk->blockRuntimeId = BlockFactory::toStaticRuntimeId($blockid);
        $player->dataPacket($pk);
    }
    
    private function sendTagData(Player $player, CompoundTag $tag, int $x, int $y, int $z){
        $writer = new NetworkLittleEndianNBTStream();
        $pk = new BlockEntityDataPacket;
        $pk->x = $x;
        $pk->y = $y;
        $pk->z = $z;
        $pk->namedtag = $writer->write($tag);
        $player->dataPacket($pk);
    }
    
    protected static function getBackpackType(){
        return WindowTypes::CONTAINER;
    }
    
    protected static function getBackpackMaximumInventories(){
        return 54;
    }
}