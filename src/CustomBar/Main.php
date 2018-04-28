<?php

namespace CustomBar;

use CustomBar\Task\TaskHud;
use CustomBar\Utils\KillChatInterfaces;
use CustomBar\Utils\KillChats\KillChat;
use CustomBar\Utils\KillChats\KillEvents;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as CL;
use pocketmine\Player;

use CustomBar\Task\TaskHud as TH;


class Main extends PluginBase implements Listener
{
    /** @var string $prefix */
    public $prefix = CL::DARK_GRAY . "[" . CL::BLUE . "Custom" . CL::RED . "Hud" . CL::DARK_GRAY . "]" . CL::RESET;

    public $eco;
    public $pro;
    public $chat;
    public $pure;

    /** @var Config $killchat */
    public $killchat;

    /** @var KillChatInterfaces $instance */
    public $instance;



    public function onEnable()
    {
        $this->saveDefaultConfig();
        if(!$this->eco = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI')) {
            $this->getServer()->getLogger()->alert($this->prefix . CL::RED . " EconomyAPI not found");
        }
        if(!$this->pro = $this->getServer()->getPluginManager()->getPlugin('FactionsPro')) {
            $this->getServer()->getLogger()->alert($this->prefix . CL::RED . " FactionPro not found");
        }
        if(!$this->pure = $this->getServer()->getPluginManager()->getPlugin('PurePerms')) {
            $this->getServer()->getLogger()->alert($this->prefix . CL::RED . " PurePerms not found");
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info($this->prefix . CL::GREEN . " by SuperKali Enable");
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new TH($this), $this->getConfig()->get("time") * 4);
        if ($this->getConfig()->get("Allow.KillChat" == true)){
            $this->instance = new KillChat($this);
            $this->killchat = new Config($this->getDataFolder() . "players.yml", Config::YAML);
            $this->getServer()->getPluginManager()->registerEvents(new KillEvents($this), $this);
        }
    }

    public function onDisable()
    {
        $this->saveDefaultConfig();
        $this->getLogger()->info($this->prefix . CL::RED . " by SuperKali Disable");
    }

    /**
     * @return false|string
     */
    public function getTime()
    {
        date_default_timezone_set($this->getConfig()->getNested("timezone"));
        return date($this->getConfig()->get("formatime"));
    }

    /**
     * @param Player $player
     * @return string
     */
    public function onFactionCheck(Player $player){
        $name = $player->getName();
        if(!$this->pro) return "NoPlug";
        $faz = $this->pro->getPlayerFaction($name);
        If(!$faz) return "NoFaz";
        return $faz;
    }

    /**
     * @param Player $player
     * @return string
     */
    public function onGroupCheck(Player $player){
        if(!$this->pure) return "NoPlug";
        $pp = $this->pure->getUserDataMgr()->getGroup($player)->getName();
        return $pp;
    }

    /**
     * @param Player $player
     * @return string
     */
    public function onEconomyAPICheck(Player $player){
        $name = $player->getName();
        if(!$this->eco) return "NoPlug";
        $eco = $this->eco->myMoney($name);
        return $eco;
    }
    public function onJoin(PlayerJoinEvent $e)
    {
        $name = $e->getPlayer()->getLowerCaseName();
        if ($this->getConfig()->get("Allow.KillChat" == true)) {
            if (!$this->getPlayers()->exists($name)) {
                $this->getPlayers()->set($name, [
                    "kills" => 0,
                    "deaths" => 0
                ]);
                $this->getPlayers()->save(true);
            }
        }
    }

    /**
     * @param Player $player
     * @return string
     */
    public function formatHUD(Player $player): string
    {
        $name = $player->getName();
        return str_replace(array(
            "&", #1
            "{tps}", #2
            "{x}", #3
            "{y}", #4
            "{z}", #5
            "{coins}", #6
            "{load}", #7
            "{players}", #8
            "{max_players}", #9
            "{line}", #10
            "{MOTD}", #11
            "{faction}", #12
            "{name}", #13
            "{time}", #14
            "{kills}", #15
            "{deaths}", #16
            "{ping}", #17
            "{group}", #18
        ), array(
            "§", #1
            $this->getServer()->getTicksPerSecond(), #2
            (int)$player->getX(), #3
            (int)$player->getY(), #4
            (int)$player->getZ(), #5
            $this->onEconomyAPICheck($player), #6
            $this->getServer()->getTickUsage(), #7
            count($this->getServer()->getOnlinePlayers()), #8
            $this->getServer()->getMaxPlayers(), #9
            "\n", #10
            $this->getServer()->getMotd(), #11
            $this->onFactionCheck($player), #12
            $player->getName(), #13
            $this->getTime($player), #14
            $this->instance->getPlayerKills($player), #15
            $this->instance->getPlayerDeaths($player), #16
            $player->getPing($name), #17
            $this->onGroupCheck($player) #18
        ), $this->getConfig()->getNested("text"));
    }

    /**
     * @return Config
     */
    public function getPlayers(): Config{
        return $this->killchat;
    }
}
