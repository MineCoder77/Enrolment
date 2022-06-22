<?php

namespace Sys\commands;

use pocketmine\command\{CommandSender, Command};
use pocketmine\player\Player;
use Sys\Loader;
use Frago9876543210\EasyForms\EasyForms;
use jojoe77777\FormAPI\FormAPI;
use Sys\sqliteManage\SQLite3Manager;
use pocketmine\permission\DefaultPermissions;
use pocketmine\Server;

final class deleteFromSQLite3PlayersObjectCommand extends Command
{
    function __construct(string $name, string $description)
    {
        $this->setPermission(DefaultPermissions::ROOT_OPERATOR);
        parent::__construct($name, $description);
    }
    
    function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player)
        {
            return $sender->sendMessage("§cИспользуйте эту команду в игре§7!");
        }
        
        if(!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR))
        {
            return $sender->sendMessage("§cНет прав на использование команды§7.");
        }
        
        if(count($args) != 0 and Loader::getInstance()->isFormSet())
        {
            return $sender->sendMessage("§eИспользуйте§7: §8/§6playerdel");
        }
        
        if(count($args) == 0 and Loader::getInstance()->isFormSet())
        {
            if(Loader::getInstance()->getFormType() instanceof EasyForms)
            {
                $sender->sendForm(new \Frago9876543210\EasyForms\forms\CustomForm("Удаление данных об игроке", 
                [
                    new \Frago9876543210\EasyForms\elements\Dropdown("Выберите игрока", array_values(array_map(fn($player) => $player->getName(), Server::getInstance()->getOnlinePlayers())))
                ],
                function(Player $sender, \Frago9876543210\EasyForms\forms\CustomFormResponse $response) : void
                {
                    $player = Server::getInstance()->getPlayerExact($response->getDropdown()->getSelectedOption());
                    if(SQLite3Manager::getInstance()->isRegisteredPerson($player))
                    {
                        SQLite3Manager::getInstance()->deletePlayer(function() use($player) : void
                        {
                            $name = mb_strtolower($player->getName());
                            Loader::getInstance()->getBaseObject()->query("DELETE FROM playersDataBase WHERE player = '$name'");
                            return;
                        });
                        $player->kick("§eВаш пароль и секретный вопрос был сброшен§7.\n§cЕсли это сделали не по Вашему согласию§7,§c обратитесь к администрации проекта§7.", false, "§eВосстановление данных§7.");
                        $sender?->sendMessage("§eУчётная запись игрока §7{$player->getName()}§e успешно обнулена!");
                        return;
                    } else
                    {
                        $sender->sendMessage("§cИгрок с ником §7{$player->getName()}§c не зарегистрирован!");
                        return;
                    }
                }));
            } else if(Loader::getInstance()->getFormType() instanceof FormAPI)
            {
                $form = new \jojoe77777\FormAPI\CustomForm(function(Player $sender, ?array $data)
                {
                    if($data === null)
                    {
                        return true;
                    }
                    
                    $player = Server::getInstance()->getPlayerByPrefix($data[0]);
                    if(!$player)
                    {
                        $sender->sendMessage("§cИгрок с ником §6{$player->getName()}§c не найден!");
                        return;
                    }
                    if(SQLite3Manager::getInstance()->isRegisteredPerson($player))
                    {
                        SQLite3Manager::getInstance()->deletePlayer(function() use($player) : void
                        {
                            $name = mb_strtolower($player->getName());
                            Loader::getInstance()->getBaseObject()->query("DELETE FROM playersDataBase WHERE player = '$name'");
                            return;
                        });
                        $player->kick("§eВаш пароль и секретный вопрос был сброшен§7.\n§cЕсли это сделали не по Вашему согласию§7,§c обратитесь к администрации проекта§7.", "§eВосстановление данных§7.");
                        return $sender?->sendMessage("§eУчётная запись игрока §7{$player->getName()}§e успешно обнулена!");
                    } else
                    {
                        return $sender->sendMessage("§cИгрок с ником §7{$player->getName()}§c не зарегистрирован!");
                    }
                });
                $form->setTitle("Удаление профиля игрока");
                $form->addInput("Впишите ник игрока:", " ");
                $form->sendToPlayer($sender);
                return;
            }
        }
        
        if(count($args) != 1 and !Loader::getInstance()->isFormSet())
        {
            return $sender->sendMessage("§eИспользуйте§7: §8/§6playerdel §7<§cигрок§7>");
        }
        
        if(count($args) == 1 and !Loader::getInstance()->isFormSet())
        {
            if(Server::getInstance()->getPlayerByPrefix($args[0]))
            {
                $player = Server::getInstance()->getPlayerByPrefix($args[0]);
                if(SQLite3Manager::getInstance()->isRegisteredPerson($player))
                {
                    SQLite3Manager::getInstance()->deletePlayer(function() use($player) : void
                    {
                        $name = mb_strtolower($player->getName());
                        Loader::getInstance()->getBaseObject()->query("DELETE FROM playersDataBase WHERE player = '$name'");
                        return;
                    });
                    $player->kick("§eВаш пароль и секретный вопрос был сброшен§7.\n§cЕсли это сделали не по Вашему согласию§7,§c обратитесь к администрации проекта§7.", "§eВосстановление данных§7.");
                    return $sender?->sendMessage("§eУчётная запись игрока §7{$player->getName()}§e успешно обнулена!");
                } else
                {
                    return $sender->sendMessage("§cИгрок с ником §7{$player->getName()}§c не зарегистрирован!");
                }
            } else
            {
                if(SQLite3Manager::getInstance()->isRegisteredPerson(Server::getInstance()->getOfflinePlayer($args[0])))
                {
                    $nick = $args[0];
                    SQLite3Manager::getInstance()->deletePlayer(function() use($nick) : void
                    {
                        $name = mb_strtolower($nick);
                        Loader::getInstance()->getBaseObject()->query("DELETE FROM playersDataBase WHERE player = '$name'");
                        return;
                    });
                    return $sender->sendMessage("§eУчётная запись игрока §7{$nick}§e успешно обнулена!");
                } else
                {
                    return $sender->sendMessage("§cИгрок с ником §7{$args[0]}§c не зарегистрирован!");
                }
            }
        }
    }
}
?>