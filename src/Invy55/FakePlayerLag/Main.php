<?php
/*
8888888                            888888888  888888888  
  888                              888        888        
  888                              888        888        
  888   88888b.  888  888 888  888 8888888b.  8888888b.  
  888   888 "88b 888  888 888  888      "Y88b      "Y88b 
  888   888  888 Y88  88P 888  888        888        888 
  888   888  888  Y8bd8P  Y88b 888 Y88b  d88P Y88b  d88P 
8888888 888  888   Y88P    "Y88888  "Y8888P"   "Y8888P"  
                               888                       
                          Y8b d88P                       
                           "Y88P"
----- This project is under the GNU Affero General Public License v3.0 -----                       
*/
declare(strict_types=1);

namespace Invy55\FakePlayerLag;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ListTag;
use jojoe77777\FormAPI\CustomForm; //Form api

class Main extends PluginBase implements Listener{
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->stickName = '§cFake§bPlayer§aLag §6Stick';
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		$player = $sender->getName();
		switch($command->getName()){
            case "fpl":
                self::getStick($sender);
			default:
				return false;
        }
        
	}
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer()->getName();
        self::setAsDefault($player);
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer()->getName();
        unset($this->$player);
    }

    public function onEntityDamage(EntityDamageByEntityEvent $event) {
        $victim = $event->getEntity();
        $damager = $event->getDamager();
        $item = $damager->getInventory()->getItemInHand(); 
        if ($victim instanceof Player and $damager instanceof Player) {
            if ($item->getId() == 280 and $item->getCustomName() == $this->stickName) {
                self::openMen($damager, $victim);
                $event->setCancelled(true);
            }
        }
    }

    public function onPlayerMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer()->getName();
        if(!isset($this->$player)) self::setAsDefault($player);
        if(isset($this->$player->latestMove)) $passed = microtime(true) - $this->$player->latestMove; else{ $this->$player->latestMove = microtime(true); $passed = 1;}
        if($this->$player->moveLag > 0 and ($passed >= 1.00/($this->$player->moveLag/10)+mt_rand(10,30)/100 or $this->$player->moveLag == 100)){
            if(self::randomizer($this->$player->moveLag)){
                $event->setCancelled(true);
                $this->$player->latestMove = microtime(true);
            }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer()->getName();
        if(!isset($this->$player)) self::setAsDefault($player);
        if(isset($this->$player->latestPlace)) $passed = microtime(true) - $this->$player->latestPlace; else{ $this->$player->latestPlace = microtime(true); $passed = 1;}
        if($this->$player->placeLag > 0 and ($passed >= 1.00/($this->$player->placeLag/10) or $this->$player->placeLag == 100)){
            if(self::randomizer($this->$player->placeLag)){
                $event->setCancelled(true);
                $this->$player->latestPlace = microtime(true);
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer()->getName();
        if(!isset($this->$player)) self::setAsDefault($player);
        if(isset($this->$player->latestBreak)) $passed = microtime(true) - $this->$player->latestBreak; else{ $this->$player->latestBreak = microtime(true); $passed = 1;}
        if($this->$player->breakLag > 0 and ($passed >= 1.00/($this->$player->breakLag/10) or $this->$player->breakLag == 100)){
            if(self::randomizer($this->$player->breakLag)){
                $event->setCancelled(true);
                $this->$player->latestBreak = microtime(true);
            }
        }
    }

    public function openMen(Player $admin, Player $victim){
        $victim = $victim->getName();
        $this->$admin->currentVictim = $victim;
        $form = new CustomForm(function (Player $player, $data = null) {
            if ($data === null) return true;
            $victim = $this->$player->currentVictim->getName();
            unset($this->$player->currentVictim);
            $player->sendMessage('§aSuccessfully set Lag Parameters of '.$victim->getName());
            $this->$victim->moveLag = intval($data[0]);
            $this->$victim->placeLag = intval($data[1]);
            $this->$victim->breakLag = intval($data[2]);
        });
        $form->setTitle('Editing lag of '.$victim);
        $defaultMoveLag = $this->$victim->moveLag;
        $defaultPlaceLag = $this->$victim->placeLag;
        $defaultBreakLag = $this->$victim->breakLag;
        $form->addSlider('Player Move Lag', 0, 100, -1, $defaultMoveLag);
        $form->addSlider('Block Place Lag', 0, 100, -1, $defaultPlaceLag);
        $form->addSlider('Block Break Lag', 0, 100, -1, $defaultBreakLag);
        $form->sendToPlayer($admin);
    }

    public function getStick(Player $player){
        $item = Item::get(Item::STICK, 0, 1)->setCustomName($this->stickName);
        $item->setNamedTagEntry(new ListTag("ench"));
        $player->getInventory()->addItem($item);
    }

    public function randomizer(int $number){
        if(mt_rand(0, 100) <= $number) return true; else return false;
    }

    public function setAsDefault(String $player){
        $this->$player = new \stdClass();
        //All attributes set here
        $this->$player->moveLag = 0;
        $this->$player->placeLag = 0;
        $this->$player->breakLag = 0;
    }
}
