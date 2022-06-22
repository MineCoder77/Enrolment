<?php

namespace Sys\commands;

use pocketmine\command\{CommandSender, Command};

use pocketmine\permission\DefaultPermissions;

use Sys\sqliteManage\SQLite3Manager;

use pocketmine\Server;

final class allPasswordsGetCommand extends Command

{

    function __construct(string $name, string $description)

    {

        $this->setPermission(DefaultPermissions::ROOT_OPERATOR);

        parent::__construct($name, $description);

    }

    

    function execute(CommandSender $sender, string $commandLabel, array $args)

    {

        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR))

        {

            return $sender->sendMessage("§cНет прав на использование команды§7.");

        }

        

        if(count($args) == 0)

        {

            $passwords = SQLite3Manager::getInstance()->constructPasswordsTypeList();

            if(count($passwords) == 0)

            {

                return $sender->sendMessage("§cНа Вашем сервере никто не зарегистрирован§7.");

            }

            SQLite3Manager::getInstance()->printAllPassValues(function() use($sender, $passwords) : void

            {

                foreach($passwords as $player => $password)

                {

                    file_put_contents(Server::getInstance()->getDataPath() . "playersPasswords.txt", "Ник: $player | Пароль: $password");

                }

                $sender->sendMessage("§eПароли успешно напечатны§7. §eПроверьте папку вашего сервера§7!");

            });

        } else

        {

            return $sender->sendMessage("§eИспользуйте§7: §8/§6passget");

        }

    }

}

?>
