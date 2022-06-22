<?php

namespace Sys\commands;

use pocketmine\command\{CommandSender, Command};
use Sys\handler\EventHandler;
use pocketmine\player\Player;
use Sys\sqliteManage\SQLite3Manager;
use Sys\Loader;

final class refreshPasswordCommand extends Command
{
    function __construct(string $name, string $description)
    {
        parent::__construct($name, $description);
    }
    
    function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player)
        {
            return $sender->sendMessage("§eИспользуйте в игре§7!");
        }
        
        if(!SQLite3Manager::getInstance()->isRegisteredPerson($sender))
        {
            return $sender->sendMessage("§eДанный аккаунт не зарегистрирован");
        }
            
        if(!SQLite3Manager::getInstance()->isNeedToAuth($sender))
        {
            return $sender->sendMessage("§cДанная команда доступна только при авторизации§7.");
        }
        
        if(count($args) == 0 and !SQLite3Manager::getInstance()->isSecretQuestionSet($sender))
        {
            return $sender->sendMessage("§cВосстановление невозможно§7,§c т.к данный аккаунт не имеет запасных вариантов для самостоятельного восстановления§7.\n§eОбратитесь к администрации проекта§7.");
        }
        
        if(count($args) != 0)
        {
            return $sender->sendMessage("§eИспользуйте§7: §8/§6refreshpass");
        } else
        {
            $sender->sendMessage("§eДля дальнейшего восстановления, ответьте на указанный Вами секретный вопрос§7: §6" . SQLite3Manager::getInstance()->getSecretQuestion($sender));
            return EventHandler::$needToAnswer[spl_object_hash($sender)] = time() + 20;
        }
    }
}
?>