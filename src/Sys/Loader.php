<?php

namespace Sys;

use pocketmine\plugin\PluginBase;

use SQLite3;

use Sys\handler\EventHandler;

use Sys\sqliteManage\SQLite3Manager;

use pocketmine\permission\{PermissionManager, Permission};

use Sys\commands\{allPasswordsGetCommand, deleteFromSQLite3PlayersObjectCommand, getPlayerPasswordCommand, refreshPasswordCommand, securityQuestionCommand};

final class Loader extends PluginBase

{

    private static Loader $instance;

    private SQLite3 $sqlite;

    private $form;

    

    function onLoad() : void

    {

        self::$instance = $this;

        $this->getLogger()->notice("§cЗапуск авторизации §d*§7/§6Powered by §a1kon§7/§d*");

    }

    

    function onEnable() : void

    {

        $this->sqlite = new SQLite3($this->getDataFolder() . "playersDataBase.db");

        $this->sqlite->exec("CREATE TABLE IF NOT EXISTS playersDataBase (

            player TEXT PRIMARY KEY,

            ip VARCHAR(20),

            question CHAR(40),

            answer CHAR(40),

            password CHAR(40),

            UNIQUE(player)

            )");

        $this->getServer()->getPluginManager()->registerEvents(new EventHandler(), $this);

        SQLite3Manager::register($this);

        PermissionManager::getInstance()->addPermission(new Permission("checkanotherplayerpassword.perm"));

        if($this->isFormSet())

        {

            if($this->getServer()->getPluginManager()->getPlugin("EasyForms") and $this->getServer()->getPluginManager()->getPlugin("FormAPI"))

            {

                $this->getLogger()->alert("У вас несколько форм§7,§c установите одну и повторите попытку запустить код§7.");

                $this->getServer()->getPluginManager()->disablePlugin($this);

                return;

            }

            

            if($this->getServer()->getPluginManager()->getPlugin("EasyForms") or $this->getServer()->getPluginManager()->getPlugin("FormAPI"))

            {

                $this->setFormType();

                $this->registerSpecialIndeedCommands();

            } else

            {

                $this->getLogger()->notice("§cУ вас нет необходимых плагинов для запуска с формами§7.");

                $this->getServer()->getPluginManager()->disablePlugin($this);

                return;

            }

        } else

        {

            $this->registerAllCommands();

        }

    }

    

    static function getInstance() : Loader

    {

        return self::$instance;

    }

    

    private function registerAllCommands() : void

    {

        $this->getServer()->getCommandMap()->registerAll("1kon", [new allPasswordsGetCommand("passget", "Получить все пароли игроков"), new deleteFromSQLite3PlayersObjectCommand("playerdel", "Удаление игрока из базы данных."), new getPlayerPasswordCommand("passpl", "Получить пароль игрока."), new refreshPasswordCommand("refreshpass", "Обновить пароль."), new securityQuestionCommand("questionpass", "Задать секретный вопрос.")]);

    }

    private function registerSpecialIndeedCommands() : void

    {

        $this->getServer()->getCommandMap()->registerAll("1kon", [new allPasswordsGetCommand("passget", "Получить все пароли игроков"), new deleteFromSQLite3PlayersObjectCommand("playerdel", "Удаление игрока из базы данных."), new getPlayerPasswordCommand("passpl", "Получить пароль игрока."), new securityQuestionCommand("questionpass", "Задать секретный вопрос.")]);

    }

    

    function getBaseObject() : SQLite3

    {

        return $this->sqlite;

    }

    

    function isFormSet() : bool

    {

        return $this->getConfig()->getNested("question") == "y" ? true : false;

    }

    

    function setFormType() : void

    {

        if($this->getServer()->getPluginManager()->getPlugin("EasyForms"))

        {

            $this->form = $this->getServer()->getPluginManager()->getPlugin("EasyForms");

            $this->getLogger()->notice("§aEasyForms успешно подключен!");

            return;

        }

        

        if($this->getServer()->getPluginManager()->getPlugin("FormAPI"))

        {

            $this->form = $this->getServer()->getPluginManager()->getPlugin("FormAPI");

            $this->getLogger()->notice("§aFormAPI успешно подключен!");

            return;

        }

    }

    

    function getFormType()

    {

        return $this?->form;

    }

}

?>
