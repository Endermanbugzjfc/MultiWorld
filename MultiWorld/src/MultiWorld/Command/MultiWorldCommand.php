<?php

namespace MultiWorld\Command;

use MultiWorld\MultiWorld;
use MultiWorld\Util\ConfigManager;
use MultiWorld\Util\LanguageManager;
use MultiWorld\WorldEdit\WorldEdit;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\level\generator\Generator;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

/**
 * Class MultiWorldCommand
 * @package MultiWorld\Command
 */
class MultiWorldCommand extends Command implements PluginIdentifiableCommand {

    /** @var  MultiWorld $plugin */
    public $plugin;

    /** @var  WorldEdit $worldEdit */
    public $worldEdit;

    /**
     * MultiWorldCommand constructor.
     * @param string $name
     * @param string $description
     * @param null $usageMessage
     * @param array $aliases
     */
    public function __construct($name = "multiworld", $description = "MultiWorld commands", $usageMessage = null, $aliases = ["mw", "wm"]) {
        $this->plugin = MultiWorld::getInstance();
        $this->worldEdit = $this->plugin->worldEdit;
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if (empty($args[0])) {
            $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("default-usage"));
            return false;
        }

        switch (strtolower($args[0])) {
            case "help":
            case "?":
                if (!$this->checkPerms($sender, "help")) return false;
                $sender->sendMessage(LanguageManager::translateMessage("help-0") . "\n" .
                    LanguageManager::translateMessage("help-1") . "\n" .
                    LanguageManager::translateMessage("help-2") . "\n" .
                    LanguageManager::translateMessage("help-3") . "\n" .
                    LanguageManager::translateMessage("help-4") . "\n" .
                    LanguageManager::translateMessage("help-5") . "\n" .
                    LanguageManager::translateMessage("help-6") . "\n");
                return false;
            case "create":
            case "new":
            case "add":
            case "generate":
                if (!$this->checkPerms($sender, "create")) return false;
                if (empty($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("create-usage"));
                    return false;
                }
                if ($this->getServer()->isLevelGenerated($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . str_replace("%1", $args[1], LanguageManager::translateMessage("create-exists")));
                    return false;
                }
                $seed = null;
                $generator = null;
                count($args) < 3 ? $seed = rand(rand(1, 10), rand(50, 99999999999999)) : $seed = $args[2];
                count($args) < 4 ? $generator = "normal" : $generator = $args[3];
                strtolower($generator) == "nether" ? $generator = "hell" : $generator = strtolower($generator);
                strtolower($generator) == "end" ? $generator = "ender" : $generator = strtolower($generator);
                if (Generator::getGeneratorName(Generator::getGenerator($generator)) != strtolower($generator)) {
                    $sender->sendMessage(str_replace("%1", strtolower($generator), LanguageManager::translateMessage("create-gennotexists")));
                    return false;
                }
                is_numeric($seed) ? $seed = (int)$seed : $seed = intval($seed);
                $this->getServer()->generateLevel($args[1], $seed, Generator::getGenerator($generator));
                $sender->sendMessage(MultiWorld::getPrefix() . str_replace("%1", $args[1], str_replace("%2", $seed, str_replace("%3", strtolower($generator), LanguageManager::translateMessage("create-done")))));
                return false;
            case "teleport":
            case "tp":
            case "move":
                if (!$this->checkPerms($sender, "teleport")) return false;
                if (empty($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("teleport-usage"));
                    return false;
                }

                if (!Server::getInstance()->isLevelGenerated($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("teleport-levelnotexists"));
                    return false;
                }

                if (!Server::getInstance()->isLevelLoaded($args[1])) {
                    Server::getInstance()->loadLevel($args[1]);
                    $this->plugin->getLogger()->debug(MultiWorld::getPrefix() . str_replace("%1", $args[1], LanguageManager::translateMessage("teleport-load")));
                }

                if ($sender instanceof Player) {
                    if (empty($args[2])) {
                        $sender->teleport($this->getServer()->getLevelByName($args[1])->getSpawnLocation());
                        $sender->sendMessage(MultiWorld::getPrefix() . str_replace("%1", $args[1], LanguageManager::translateMessage("teleport-done-1")));
                        return false;
                    }
                }
                if (isset($args[2])) {
                    $player = $this->getServer()->getPlayer($args[2]);
                    if (!is_null($player) && $player->isOnline()) {
                        $player->teleport($this->getServer()->getLevelByName($args[1])->getSpawnLocation());
                        $player->sendMessage(MultiWorld::getPrefix() . str_replace("%1", $args[1], LanguageManager::translateMessage("teleport-done-1")));
                        $sender->sendMessage(MultiWorld::getPrefix() . str_replace("%1", $args[1], str_replace("%2", $args[2], LanguageManager::translateMessage("teleport-done-2"))));
                        return false;
                    } else {
                        $sender->sendMessage(MultiWorld::getPrefix() . str_replace("%1", $args[2], LanguageManager::translateMessage("teleport-playernotexists")));
                        return false;
                    }
                }
                return false;
            case "ls":
            case "list":
                if (!$this->checkPerms($sender, "list")) return false;
                $allLevels = scandir(ConfigManager::getDataPath() . "worlds");
                unset($allLevels[0]);
                unset($allLevels[1]);
                $loaded = [];
                foreach ($this->getServer()->getLevels() as $level) {
                    array_push($loaded, $level->getName());
                }
                $list = implode(", ", $allLevels);
                $sender->sendMessage(MultiWorld::getPrefix() . str_replace("%1", $list, LanguageManager::translateMessage("list-done")));
                return false;
            case "load":
            case "ld":
                if (!$this->checkPerms($sender, "load")) return false;
                if (empty($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("load-usage"));
                    return false;
                }
                if (!$this->getServer()->isLevelGenerated($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("load-levelnotexists"));
                    return false;
                }
                if ($this->getServer()->isLevelLoaded($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("load-loaded"));
                    return false;
                }
                $this->getServer()->loadLevel($args[1]);
                $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("load-done"));
                return false;
            case "unload":
            case "uld":
                if (!$this->checkPerms($sender, "unload")) return false;
                if (empty($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("unload-usage"));
                    return false;
                }
                if (!$this->getServer()->isLevelGenerated($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("unload-levelnotexists"));
                    return false;
                }
                if (!$this->getServer()->isLevelLoaded($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("unload-unloaded"));
                    return false;
                }
                $this->getServer()->unloadLevel($this->getServer()->getLevelByName($args[1]));
                $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("unload-done"));
                return false;
            case "delete":
            case "remove":
            case "del":
            case "rm":
                if (!$this->checkPerms($sender, "delete")) return false;
                if (empty($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("delete-usage"));
                    return false;
                }
                if (!$this->getServer()->isLevelGenerated($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("delete-levelnotexists"));
                    return false;
                }
                $levelName = $args[1];
                $folderName = $args[1];
                if ($this->getServer()->isLevelLoaded($levelName)) {
                    $level = $this->getServer()->getLevelByName($levelName);
                    if (count($level->getPlayers()) != 0) {
                        foreach ($level->getPlayers() as $player) {
                            $player->teleport($this->getServer()->getDefaultLevel()->getSpawnLocation());
                        }
                    }
                    $folderName = $level->getFolderName();
                    $level->unload();
                }
                $folderPath = ConfigManager::getDataPath() . "worlds/{$folderName}";
                try {
                    $count = 0;
                    if(is_dir($folderPath)) {
                        if(is_dir($folderPath."/region")) {
                            foreach (glob($folderPath."/region/*.mca") as $chunks) {
                                unlink($chunks);
                                $count++;
                            }
                            foreach (glob($folderPath."/region/*.mcapm") as $chunks) {
                                unlink($chunks);
                                $count++;
                            }
                        }
                        rmdir($folderPath."/region");
                        $count++;
                        unlink($folderPath."/level.dat");
                        $count++;
                        foreach (scandir($folderPath) as $file) {
                            if(!in_array($file, [".", ".."])) {
                                $count++;
                                is_dir($file) ? rmdir($folderPath.$file) : unlink($folderPath.$file);
                            }
                        }
                        rmdir($folderPath);
                        $sender->sendMessage(MultiWorld::getPrefix().str_replace("%1", $count,LanguageManager::translateMessage("delete-done")));
                    }
                }
                catch (\Exception $exception) {
                    $github = MultiWorld::GITHUB;
                    $sender->sendMessage("§cError when deleting world. Submit issue to {$github}\n§7Error: {$exception->getMessage()}");
                    $this->plugin->getLogger()->critical("\n§cError when deleting world. Submit issue to {$github}\n§7Error: {$exception->getMessage()}");
                }
                return false;
            case "update":
            case "ue":
            case "upte":
                if (!$this->checkPerms($sender, "update")) return false;
                if(empty($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix().LanguageManager::translateMessage("update-usage"));
                    return false;
                }
                switch (strtolower($args[1])) {
                    case "spawn":
                    case "0":
                        if($sender instanceof Player) {
                            $sender->getLevel()->setSpawnLocation($sender->asVector3());
                            $sender->sendMessage(MultiWorld::getPrefix().str_replace("%1", $sender->getLevel()->getName(), LanguageManager::translateMessage("update-spawn-done")));
                        }
                        else {
                            if(!(count($args) < 7)) {
                                $sender->sendMessage(MultiWorld::getPrefix().LanguageManager::translateMessage("update-spawn-usage"));
                                return false;
                            }
                            if(!$this->getServer()->isLevelGenerated($args[5])) {
                                $sender->sendMessage(MultiWorld::getPrefix().LanguageManager::translateMessage("update-levelnotexists"));
                                return false;
                            }
                            if(!$this->getServer()->isLevelLoaded($args[5])) {
                                $this->getServer()->loadLevel($args[5]);
                            }
                            $level = $this->getServer()->getLevelByName($args[5]);
                            $level->setSpawnLocation(new Vector3(intval($args[2]), intval($args[3]), intval($args[4])));
                            $sender->sendMessage(MultiWorld::getPrefix().str_replace("%1", $level->getName(), LanguageManager::translateMessage("update-spawn-done")));
                        }
                        return false;
                    case "lobby":
                    case "hub":
                    case "1":
                        if($sender instanceof Player) {
                            $level = $sender->getLevel();
                            $this->getServer()->setDefaultLevel($level);
                            $level->setSpawnLocation($sender->asVector3());
                            $sender->sendMessage(MultiWorld::getPrefix().str_replace("%1", $level->getName(), LanguageManager::translateMessage("update-lobby-done")));
                        }
                        else {
                            $sender->sendMessage(MultiWorld::getPrefix().LanguageManager::translateMessage("update-notsupported"));
                        }
                        return false;
                    case "default":
                    case "defaultlevel":
                    case "2":
                        if(empty($args[1])) {
                            $sender->sendMessage(MultiWorld::getPrefix().LanguageManager::translateMessage("update-default-usage"));
                            return false;
                        }
                        if(in_array($args[2], scandir(ConfigManager::getDataPath()."worlds"))) {
                            if(!$this->getServer()->isLevelLoaded($args[2])) $this->getServer()->loadLevel($args[2]);
                            $this->getServer()->setDefaultLevel($this->getServer()->getLevelByName($args[2]));
                            $sender->sendMessage(MultiWorld::getPrefix().str_replace("%1", $args[2], LanguageManager::translateMessage("update-default-done")));
                        }
                        else {
                            $sender->sendMessage(MultiWorld::getPrefix().str_replace("%1", $args[2], LanguageManager::translateMessage("update-levelnotexists")));
                        }
                        return false;
                    default:
                        $sender->sendMessage(MultiWorld::getPrefix().LanguageManager::translateMessage("update-usage"));
                        return false;
                }
            case "worldedit":
            case "we":
            case "/":
                if (!$this->checkPerms($sender, "worldedit")) return false;
                if(!$sender instanceof Player) {
                    $sender->sendMessage(MultiWorld::getPrefix()."§cThis command is not supported in console.");
                    return false;
                }
                if(empty($args[1])) {
                    $sender->sendMessage(MultiWorld::getPrefix()."§cUsage: §7/mw we <pos1|pos2|set>");
                    return false;
                }
                switch ($args[1]) {
                    case "1":
                    case "pos1":
                        $this->worldEdit->selectPos($sender, $sender->asPosition(), 1);
                        return false;
                    case "2":
                    case "pos2":
                        $this->worldEdit->selectPos($sender, $sender->asPosition(), 2);
                        return false;
                    case "set":
                        if(empty($args[2])) {
                            $sender->sendMessage("§cMissing arguments");
                            return false;
                        }
                        $this->worldEdit->fill($sender, $args[1]);
                        return false;
                }

                return false;
            default:
                if ($this->checkPerms($sender, "help")) {
                    $sender->sendMessage(MultiWorld::getPrefix() . LanguageManager::translateMessage("default-usage"));
                }
                return false;
        }
    }

    /**
     * @param CommandSender $sender
     * @param string $command
     * @return bool
     */
    function checkPerms(CommandSender $sender, string $command):bool {
        if($sender instanceof Player) {
            if(!$sender->hasPermission("mw.cmd.{$command}")) {
                $sender->sendMessage(LanguageManager::translateMessage("not-perms"));
                return false;
            }
            else {
                return true;
            }
        }
        else {
            return true;
        }
    }

    /**
     * @return Server
     */
    function getServer():Server {
        return Server::getInstance();
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin {
        return MultiWorld::getInstance();
    }
}