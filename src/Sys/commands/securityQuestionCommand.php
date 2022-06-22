<?php

namespace Sys\commands;

use pocketmine\command\{CommandSender, Command};
use pocketmine\player\Player;
use Frago9876543210\EasyForms\EasyForms;
use jojoe77777\FormAPI\FormAPI;
use Sys\sqliteManage\SQLite3Manager;
use Sys\Loader;

final class securityQuestionCommand extends Command
{
    function __construct(string $name, string $description)
    {
        parent::__construct($name, $description);
    }
    
    function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player)
        {
            $sender->sendMessage("§eИспользуйте эту команду в игре§7!");
            return;
        }
        
        if(count($args) != 0 and Loader::getInstance()->isFormSet())
        {
            return $sender->sendMessage("§eИспользуйте§7: §8/§6questionpass");
        }
        
        if(count($args) == 0 and Loader::getInstance()->isFormSet())
        {
            if(SQLite3Manager::getInstance()->isSecretQuestionSet($sender))
            {
                $sender->sendMessage("§eНа данном аккаунте уже имеются данные о секретном вопросе§7!");
                return;
            }
            
            if(Loader::getInstance()->getFormType() instanceof EasyForms)
            {
                $sender->sendForm(new \Frago9876543210\EasyForms\forms\CustomForm("Добавление данных о вопросе", 
                [
                    new \Frago9876543210\EasyForms\elements\Input("Задайте секретный вопрос:", " "),
                    new \Frago9876543210\EasyForms\elements\Input("Дайте ответ на вопрос:", " ")
                ],
                function(Player $sender, \Frago9876543210\EasyForms\forms\CustomFormResponse $response) : void
                {
                    $question = $response->getInput()->getValue();
                    $answer = $response->getInput()->getValue();
                    if(empty($question))
                    {
                        $sender->sendMessage("§cВопрос не может быть пустым§7.");
                        return;
                    }
                    
                    if(empty($answer))
                    {
                        $sender->sendMessage("§cОтвет не может быть пустым§7.");
                        return;
                    }
                    
                    if(strlen($question) > 35)
                    {
                        $sender->sendMessage("§cВопрос не может быть длиннее 35 символов§7.");
                        return;
                    }
                    
                    if(strlen($answer) > 35)
                    {
                        $sender->sendMessage("§cОтвет на вопрос не может быть длиннее 35 символов§7.");
                        return;
                    }
                    
                    if(substr($question, -1) != "?")
                    {
                        $sender->sendMessage("§cЛюбой вопрос должен заканчиваться знаком §7'§f?§7'§8.");
                        return;
                    }
                    
                    SQLite3Manager::getInstance()->setSecretQuestion(function() use($sender, $question, $answer) : void
                    {
                        $name = mb_strtolower($sender->getName());
                        Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET question = '$question', answer = '$answer' WHERE player = '$name'");
                        $sender->sendMessage("§aДанные успешно добавлены§7!\n§eВопрос§7: §8{$question}\n§eОтвет§7: §8{$answer}");
                        return;
                    });
                }));
            } else if(Loader::getInstance()->getFormType() instanceof FormAPI)
            {
                if(SQLite3Manager::getInstance()->isSecretQuestionSet($sender))
                {
                    $sender->sendMessage("§eНа данном аккаунте уже имеются данные о секретном вопросе§7!");
                    return;
                }
                $form = new \jojoe77777\FormAPI\CustomForm(function(Player $sender, ?array $data)
                {
                    if($data === null)
                    {
                        return true;
                    }
                    
                    $question = $data[1];
                    $answer = $data[2];
                    
                    if(empty($question))
                    {
                        return $sender->sendMessage("§cВопрос не может быть пустым§7.");
                    }
                    
                    if(empty($answer))
                    {
                        return $sender->sendMessage("§cОтвет не может быть пустым§7.");
                    }
                    
                    if(strlen($question) > 35)
                    {
                        return $sender->sendMessage("§cВопрос не может быть длиннее 35 символов§7.");
                    }
                    
                    if(strlen($answer) > 35)
                    {
                        return $sender->sendMessage("§cОтвет на вопрос не может быть длиннее 35 символов§7.");
                    }
                    
                    if(substr($question, -1) != "?")
                    {
                        return $sender->sendMessage("§cЛюбой вопрос должен заканчиваться знаком §7'§f?§7'§8.");
                    }
                    
                    SQLite3Manager::getInstance()->setSecretQuestion(function() use($sender, $question, $answer) : void
                    {
                        $name = mb_strtolower($sender->getName());
                        Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET question = '$question', answer = '$answer' WHERE player = '$name'");
                        $sender->sendMessage("§aДанные успешно добавлены§7!\n§eВопрос§7: §8{$question}\n§eОтвет§7: §8{$answer}");
                        return;
                    });
                });
                $form->setTitle("Добавление данных о вопросе");
                $form->addLabel("Заполните необходимые данные:");
                $form->addInput("Впишите секретный вопрос:", " ");
                $form->addInput("Впишите ответ на Ваш вопрос:", " ");
                return $form->sendToPlayer($sender);
            }
        }
        
        if(count($args) != 2 and !Loader::getInstance()->isFormSet())
        {
            return $sender->sendMessage("§eИспользуйте§7: §8/§6questionpass §7<§cвопрос§8(§eформат§7: §dПростойВопрос§8)§7> §7<§5ответ§8(§eформат§7: §dОтветТакой-то§8)§7>");
        }
        
        if(count($args) == 2 and !Loader::getInstance()->isFormSet())
        {
            if(SQLite3Manager::getInstance()->isSecretQuestionSet($sender))
            {
                return $sender->sendMessage("§eНа данном аккаунте уже имеются данные о секретном вопросе§7!");
            }
            
            $question = $args[0];
            $answer = $args[1];
            
            if(empty($question) or empty($answer))
            {
                return $sender->sendMessage("§cНи вопрос§7,§c ни ответ не могут быть пустыми§7.");
            }
            
            if(strlen($question) > 35)
            {
                return $sender->sendMessage("§cВопрос не может быть длиннее 35 символов§7.");
            }
                    
            if(strlen($answer) > 35)
            {
                return $sender->sendMessage("§cОтвет на вопрос не может быть длиннее 35 символов§7.");
            }
            
            if(substr($question, -1) != "?")
            {
                return $sender->sendMessage("§cЛюбой вопрос должен заканчиваться знаком §7'§f?§7'§8.");
            }
            
            SQLite3Manager::getInstance()->setSecretQuestion(function() use($sender, $question, $answer) : void
            {
                $name = mb_strtolower($sender->getName());
                Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET question = '$question', answer = '$answer' WHERE player = '$name'");
                $sender->sendMessage("§aДанные успешно добавлены§7!\n§eВопрос§7: §8{$question}\n§eОтвет§7: §8{$answer}");
                return;
            });
        }
    }
}
?>