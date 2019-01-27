<?php
namespace korado531m7\AnywhereBackpack\provider;

use \MySQLi;
use pocketmine\plugin\Plugin;
use korado531m7\AnywhereBackpack\inventory\BackpackInventory;

class MySQLProvider{
    private $db, $plugin;
    
    public function __construct(Plugin $plugin){
        $this->plugin = $plugin;
        $this->init();
    }
    
    public function init(){
        $this->db = @new MySQLi($this->plugin->config->get('database-host'), $this->plugin->config->get('database-username'), $this->plugin->config->get('database-password'), $this->plugin->config->get('database-name'), $this->plugin->config->get('database-port'));
        if($this->db->connect_error){
            $this->plugin->getLogger()->warning('Connect Error ('.$this->db->connect_errno.') '.$this->db->connect_error);
            $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
        }else{
            $this->plugin->getLogger()->notice('Connected to MySQL database');
            if(!mysqli_query($this->db, 'CREATE TABLE IF NOT EXISTS backpack(id INTEGER , data TEXT, PRIMARY KEY (`id`))')){
                $this->plugin->getLogger()->warning('Couldn\'t create table on database');
                $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
            }
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
        $rawdata = $this->db->query("SELECT data FROM backpack WHERE id = $id")->fetch_array()[0];
        $result = unserialize(hex2bin($rawdata));
        return $result === false ? [] : $result;
    }
    
    public function removeBackpack(int $id, bool $onlyReset = false){
        if($onlyReset){
            $data = bin2hex(serialize([]));
            $this->db->query("REPLACE INTO backpack values($id, '$data')");
        }else{
            $this->db->query("DELETE FROM backpack WHERE id = $id");
        }
    }
    
    public function getNextId() : int{
        return $this->db->query('SELECT count(id) FROM backpack')->fetch_array()[0] + 1;
    }
    
    public function formatDatabase() : void{
        $this->db->query('DROP TABLE IF EXISTS backpack');
    }
}