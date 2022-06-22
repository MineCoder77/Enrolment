<?php

namespace Sys\sqliteManage;

use pocketmine\utils\SingletonTrait;

use Sys\Loader;

use pocketmine\Server;

use pocketmine\player\Player;

use pocketmine\permission\DefaultPermissions;

use Sys\handler\EventHandler;

use Closure;

use pocketmine\console\ConsoleCommandSender;

use pocketmine\player\OfflinePlayer;

final class SQLite3Manager

{

    private static bool $registered = false;

    private static Loader $plugin;

    

    use SingletonTrait

    {

        setInstance as private;

        getInstance as private _getInstance;

    }

    

    function __construct()

    {

        self::setInstance($this);

    }

    

    static function register(Loader $loaderClass) : bool

    {

        if(self::isRegistered()) 

        {

            return false;

        }

        self::setRegistered(true);

        self::setPlugin($loaderClass);

        return true;

    }

    

    private static function isRegistered() : bool

    {

        return self::$registered;

    }

    

    private static function setRegistered(bool $registered) : void

    {

        self::$registered = $registered;

    }

    static function getInstance() : SQLite3Manager

    {

        return self::_getInstance();

    }

    

    private static function setPlugin(?Loader $plugin) : void

    {

        self::$plugin = $plugin;

    }

    

    function getDataBaseDataIncludedInformation(\Closure $closure = null) : ?string

    {

        if($closure == null)

        {

            throw new \ArgumentCountError("Closure must not be object of null.");

        }

        

        return $closure();

    }

    

    function isRegisteredPerson(Player | ConsoleCommandSender | OfflinePlayer $player) : bool

    {

        $name = mb_strtolower($player->getName());

        return Loader::getInstance()->getBaseObject()->query("SELECT * FROM playersDataBase WHERE player = '$name'")->fetchArray(SQLITE3_ASSOC) ? true : false;

    }

    

    function isNeedToAuth(Player | ConsoleCommandSender $player) : bool

    {

        if(isset(EventHandler::$isAuth[spl_object_hash($player)]))

        {

            return false;

        }

        

        if(!isset(EventHandler::$isAuth[spl_object_hash($player)]))

        {

            if($this->isRegisteredPerson($player))

            {

                if(!$this->isIpNotSuitable($player, $player->getNetworkSession()->getIp()))

                {

                    EventHandler::$isAuth[spl_object_hash($player)] = true;

                    return false;

                } else

                {

                    return true;

                }

            } else

            {

                return true;

            }

        }

    }

    

    function registerNewPerson(Closure $closure = null)

    {

        if($closure == null)

        {

            throw new \ArgumentCountError("Closure must not be object of null.");

        }

        

        return $closure();

    }

    

    function deletePlayer(Closure $closure = null)

    {

        if($closure == null)

        {

            throw new \ArgumentCountError("Closure must not be object of null.");

        }

        

        return $closure();

    }

    

    function updateIp(Closure $closure = null)

    {

        if($closure == null)

        {

            throw new \ArgumentCountError("Closure must not be object of null.");

        }

        

        return $closure();

    }

    

    function isPassNotSuitable(Player $player, int | string $pass) : bool

    {

        return $this->getDataBaseDataIncludedInformation(function() use($player) : string

        {

            $name = mb_strtolower($player->getName());

            $data = Loader::getInstance()->getBaseObject()->query("SELECT * FROM playersDataBase WHERE player = '$name'")->fetchArray(SQLITE3_ASSOC);

            return $data["password"];

        }) != $pass ? true : false;

    }

    

    function isIpNotSuitable(Player $player, string $ip) : bool

    {

        return $this->getDataBaseDataIncludedInformation(function() use($player) : string

        {

            $name = mb_strtolower($player->getName());

            $data = Loader::getInstance()->getBaseObject()->query("SELECT * FROM playersDataBase WHERE player = '$name'")->fetchArray(SQLITE3_ASSOC);

            return $data["ip"];

        }) != $ip ? true : false;

    }

    

    function getPlayerPass(Player $player) : string

    {

        return $this->getDataBaseDataIncludedInformation(function() use($player) : string

        {

            $name = mb_strtolower($player->getName());

            $data = Loader::getInstance()->getBaseObject()->query("SELECT * FROM playersDataBase WHERE player = '$name'")->fetchArray(SQLITE3_ASSOC);

            return $data["password"];

        });

    }

    

    function printAllPassValues(Closure $closure = null)

    {

        if($closure == null)

        {

            throw new \ArgumentCountError("Closure must not be object of null.");

        }

        

        if(file_exists(Server::getInstance()->getDataPath() . "playersPasswords.txt"))

        {

            rename(Server::getInstance()->getDataPath() . "playersPasswords.txt", Server::getInstance()->getDataPath() . "playersPasswords__OUTDATED.txt");

        }

        

        return $closure();

        

    }

    function isSecretQuestionSet(Player $player)

    {

        return $this->getDataBaseDataIncludedInformation(function() use($player) : bool

        {

            $name = mb_strtolower($player->getName());

            $data = Loader::getInstance()->getBaseObject()->query("SELECT * FROM playersDataBase WHERE player = '$name'")->fetchArray(SQLITE3_ASSOC);

            return $data["question"] ? true : false;

        });

    }

    

    function setSecretQuestion(Closure $closure = null)

    {

        if($closure == null)

        {

            throw new \ArgumentCountError("Closure must not be object of null.");

        }

        

        return $closure();

    }

    

    function getSecretQuestion(Player $player)

    {

        return $this->getDataBaseDataIncludedInformation(function() use($player) : ?string

        {

            $name = mb_strtolower($player->getName());

            $data = Loader::getInstance()->getBaseObject()->query("SELECT * FROM playersDataBase WHERE player = '$name'")->fetchArray(SQLITE3_ASSOC);

            return $data["question"];

        });

    }

    

    function getAnswer(Player $player) : string

    {

        return $this->getDataBaseDataIncludedInformation(function() use($player) : string

        {

            $name = mb_strtolower($player->getName());

            $data = Loader::getInstance()->getBaseObject()->query("SELECT * FROM playersDataBase WHERE player = '$name'")->fetchArray(SQLITE3_ASSOC);

            return $data["answer"];

        });

    }

    

    function isAccountBinded(Player $player) : bool

    {

        //SOON...

    }

    

    function bindAccount(Player $player) : void

    {

        //SOON...

    }

    

    function getBindType(Player $player) : string

    {

        //SOON...

    }

    

    function constructPasswordsTypeList()

    {

        $closure = function() : array

        {

            $result = Loader::getInstance()->getBaseObject()->query("SELECT * FROM playersDataBase"); 

            $storeArray = Array(); 

            while($row = $result->fetchArray(SQLITE3_ASSOC))

            { 

                $storeArray[$row['player']] = $row['password']; 

            }

            return $storeArray;

        };

        

        return $closure();

    }

}

?>
