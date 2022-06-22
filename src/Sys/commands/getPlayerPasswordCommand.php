<?php

namespace Sys\commands;

use pocketmine\command\{CommandSender, Command};
use Sys\Loader;
use pocketmine\player\Player;
use Frago9876543210\EasyForms\EasyForms;
use jojoe77777\FormAPI\FormAPI;
use Sys\sqliteManage\SQLite3Manager;
use pocketmine\Server;

final class getPlayerPasswordCommand extends Command
{
    function __construct(string $name, string $description)
    {
        $this->setPermission("checkanotherplayerpassword.perm");
        parent::__construct($name, $description);
    }
    
    function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player)
        {
            return $sender->sendMessage("§eИспользуйте эту команду в игре§7!");
        }
        
        if(!$sender->hasPermission("checkanotherplayerpassword.perm"))
        {
            return $sender->sendMessage("§cНет прав§7!");
        }
        
        if(count($args) != 0 and Loader::getInstance()->isFormSet())
        {
            return $sender->sendMessage("§eИспользуйте§7: §8/§6passpl");
        }
        
        if(count($args) == 0 and Loader::getInstance()->isFormSet())
        {
            if(Loader::getInstance()->getFormType() instanceof EasyForms)
            {
                $sender->sendForm(new \Frago9876543210\EasyForms\forms\CustomForm("Получение пароля игрока", 
                [
                    new \Frago9876543210\EasyForms\elements\Dropdown("Выберите игрока", array_values(array_map(fn($player) => $player->getName(), Server::getInstance()->getOnlinePlayers())))
                ],
                function(Player $sender, \Frago9876543210\EasyForms\forms\CustomFormResponse $response) : void
                {
                    $player = Server::getInstance()->getPlayerExact($response->getDropdown()->getSelectedOption());
                    if(SQLite3Manager::getInstance()->isRegisteredPerson($player))
                    {
                        $sender->sendMessage("§eПароль игрока §7{$player->getName()}§8: §d" . SQLite3Manager::getInstance()->getPlayerPass($player));
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
                        return $sender->sendMessage("§eПароль игрока §7{$player->getName()}§8: §d" . SQLite3Manager::getInstance()->getPlayerPass($player));
                    } else
                    {
                        return $sender->sendMessage("§cИгрок с ником §7{$player->getName()}§c не зарегистрирован!");
                    }
                });
                $form->setTitle("Получение пароля игрока");
                $form->addInput("Впишите ник игрока", " ");
                return $form->sendToPlayer($sender);
            }
        }
        
        if(count($args) != 1 and !Loader::getInstance()->isFormSet())
        {
            return $sender->sendMessage("§eИспользуйте§7: §8/§6passpl §7<§cигрок§7>");
        }
        
        if(count($args) == 1 and !Loader::getInstance()->isFormSet())
        {
            if(Server::getInstance()->getPlayerByPrefix($args[0]))
            {
                $player = Server::getInstance()->getPlayerByPrefix($args[0]);
                if(SQLite3Manager::getInstance()->isRegisteredPerson($player))
                {
                    return $sender->sendMessage("§eПароль игрока §7{$player->getName()}§8: §d" . SQLite3Manager::getInstance()->getPlayerPass($sender));
                } else
                {
                    return $sender->sendMessage("§eИгрок с никнеймом §7{$player->getName()}§e не зарегистрирован§7.");
                }
            } else
            {
                $player = Server::getInstance()->getOfflinePlayer($args[0]);
                if(SQLite3Manager::getInstance()->isRegisteredPerson($player))
                {
                    $sender->sendMessage("§eПароль игрока §7{$player->getName()}§8: §d" . SQLite3Manager::getInstance()->getPlayerPass($player));
                } else
                {
                    return $sender->sendMessage("§eИгрок с никнеймом §7{$player->getName()}§e не зарегистрирован§7.");
                }
            }
        }
    }
}
?>