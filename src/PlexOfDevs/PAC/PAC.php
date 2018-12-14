<?php

namespace PlexOfDevs\PAC;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\Plugin;
use pocketmine\plugin\PluginLoader;
use PlexOfDevs\PAC\EventListener;
use PlexOfDevs\PAC\Observer;
use PlexOfDevs\PAC\KickTask;

class PAC extends PluginBase
{
  public $Config;
  public $Logger;
  public $cl;
  public $PlayerObservers = array();
  public $PlayersToKick   = array();

  public function onEnable()
  {
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new KickTask($this), 1);
    @mkdir($this->getDataFolder());
    $this->saveDefaultConfig();
    $this->saveResource("AntiForceOP.txt");
    $this->saveResource("AntiForceGM.txt");
    $cl              = $this->getConfig()->get("Color");
  
    $Config = $this->getConfig();
    $Logger = $this->getServer()->getLogger();
    $Server = $this->getServer();
    
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $Logger->info(TextFormat::ESCAPE."$cl" . "[PAC] > PlexAntiCheat Activated"            );
    $Logger->info(TextFormat::ESCAPE."$cl" . "[PAC] > PlexAntiCheat v3.3.10 [PlexOfDevs]");
    $Logger->info(TextFormat::ESCAPE."$cl" . "[PAC] > Loading Modules");
    if($Config->get("ForceOP"    )) $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > Enabling AntiForceOP"    );
    if($Config->get("NoClip"     )) $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > Enabling AntiNoClip"     );
    if($Config->get("Fly"        )) $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > Enabling AntiFly"        );
    if($Config->get("Fly"        )) $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > Enabling AntiSpider"     );
    if($Config->get("Glide"      )) $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > Enabling AntiGlide"      );
    if($Config->get("KillAura"   )) $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > Enabling AntiKillAura"   );
    if($Config->get("Reach"      )) $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > Enabling AntiReach"      );
    if($Config->get("Speed"      )) $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > Enabling AntiSpeed"      );
    if($Config->get("FastBow"    )) $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > Enabling AntiFastBow"    );
    if($Config->get("Regen"      )) $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > Enabling AntiRegen"      );

    if($Config->get("Config-Version") !== "3.6.4")
    {
      $Logger->warning(TextFormat::ESCAPE."$cl"."[PAC] > Your Config is out of date!");
    }
    if($Config->get("Plugin-Version") !== "3.3.8" and $Config->get("Plugin-Version") !== "3.3.9" and $Config->get("Plugin-Version") !== "3.3.10")
    {
      $Logger->error(TextFormat::ESCAPE."$cl"."[PAC] > Your Config is incompatible with this plugin version, please update immediately!");
      $Server->shutdown();
    }

    foreach($Server->getOnlinePlayers() as $player)
    {
      $hash     = spl_object_hash($player);
      $name     = $player->getName();
      $oldhash  = null;
      $observer = null;
      
      foreach ($this->PlayerObservers as $key=>$obs)
      {
        if ($obs->PlayerName == $name)
        {
          $oldhash  = $key;
          $observer = $obs;
          $observer->Player = $player;
        }
      }
      if ($oldhash != null)
      {
        unset($this->PlayerObservers[$oldhash]);
        $this->PlayerObservers[$hash] = $observer;
        $this->PlayerObservers[$hash]->PlayerRejoin();
      }  
      else
      {
        $observer = new Observer($player, $this);
        $this->PlayerObservers[$hash] = $observer;
        $this->PlayerObservers[$hash]->PlayerJoin();      
      }      
    }  
  }

  public function onDisable()
  {
    $cl              = $this->getConfig()->get("Color");
    $Logger = $this->getServer()->getLogger();
    $Server = $this->getServer();

    $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > You are no longer protected from cheats!");
    $Logger->info(TextFormat::ESCAPE."$cl"."[PAC] > PlexAntiCheat Deactivated");
    $Server->enablePlugin($this);
  }
    
  public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool
  {
    $Logger = $this->getServer()->getLogger();
    $cl              = $this->getConfig()->get("Color");
    if ($this->getConfig()->get("ForceOP"))
    {
      if ($sender->isOp())
      {
        if (!$sender->hasPermission($this->getConfig()->get("ForceOP-Permission")))
        {
          if ($sender instanceof Player)
          {
            $sname = $sender->getName();
            $message  = "[PAC] > $sname used ForceOP!";
            $this->NotifyAdmins($message);
            $sender->getPlayer()->kick(TextFormat::ESCAPE."$cl"."[PAC] > ForceOP detected!");
          }
        }
      }
    }
    if ($command->getName() === "PAC" or $command->getName() === "plexanticheat")
    {
      $sender->sendMessage(TextFormat::ESCAPE."$cl"."[PAC] > PlexAntiCheat v3.3.10 [PlexOfDevs] (~PlexOfDevs)");
    }
	return false;
  }
  
  public function NotifyAdmins($message)
  {
    $cl              = $this->getConfig()->get("Color");
    if($this->getConfig()->get("Verbose"))
    {
      foreach ($this->PlayerObservers as $observer)
      {
        $player = $observer->Player;
        if ($player != null and $player->hasPermission("PAC.admin"))
        {
          $player->sendMessage(TextFormat::ESCAPE."$cl" . $message);
        }
      }
    }  
  }  
  
}

//////////////////////////////////////////////////////
//                                                  //
//     PAC by PlexOfDeevs.                          //
//     Distributed under the LGPL License.          //
//                                                  //
//////////////////////////////////////////////////////
