<?php
namespace korado531m7\AnywhereBackpack\provider;

use \SQLite3;
use pocketmine\plugin\Plugin;
use korado531m7\AnywhereBackpack\inventory\BackpackInventory;

class SQLite3Provider{
    private $db, $plugin;
    
    public function __construct(Plugin $plugin){
        $this->plugin = $plugin;
        $this->init();
    }
    
    public function init(){
        if(file_exists($this->plugin->getDataFolder().'SaveData/data.db')){
            $this->db = new SQLite3($this->plugin->getDataFolder().'SaveData/data.db');
        }else{
            touch($this->plugin->getDataFolder().'SaveData/data.db');
            $this->db = new SQLite3($this->plugin->getDataFolder().'SaveData/data.db', \SQLITE3_OPEN_READWRITE | \SQLITE3_OPEN_CREATE);
            $this->db->exec('CREATE TABLE IF NOT EXISTS backpack(id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT)');
        }
    }
    
    public function saveBackpack(int $id, array $items){
        $data = bin2hex(serialize($items));
        $this->db->query("REPLACE INTO backpack values($id, '$data')");
    }
    
    public function registerBackpack(int $id){
        $data = bin2hex(serialize([]));
        $this->db->query("INSERT INTO backpack values($id, '$data')");
    }
    
    public function restoreBackpack(int $id) : array{
        $rawdata = $this->db->query("SELECT data FROM backpack WHERE id = $id")->fetchArray()[0];
        $result = unserialize(hex2bin($rawdata));
        return $result === false ? [] : $result;
    }
    
    public function removeBackpack(int $id){
        $this->db->query("DELETE FROM backpack WHERE id = $id");
    }
    
    public function getNextId() : int{
        return $this->db->query('SELECT count(*) FROM backpack')->fetchArray()[0] + 1;
    }
    
    public function formatDatabase() : void{
        @unlink($this->plugin->getDataFolder().'SaveData/data.db');
    }
}