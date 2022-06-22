<?php

namespace Sys\handler;

use pocketmine\event\Listener;

use pocketmine\event\player\{PlayerJoinEvent, PlayerChatEvent, PlayerQuitEvent, PlayerDropItemEvent};

use pocketmine\event\block\{BlockPlaceEvent, BlockBreakEvent};

use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};

use pocketmine\player\Player;

use pocketmine\Server;

use Sys\sqliteManage\SQLite3Manager;

use Sys\Loader;

use Frago9876543210\EasyForms\EasyForms;

use jojoe77777\FormAPI\FormAPI;

use pocketmine\event\server\CommandEvent;

final class EventHandler implements Listener

{

    static array $isAuth = [];

    static array $needToAnswer = [];

    private array $playerMissCounts = [];

    

    public function onJoin(PlayerJoinEvent $event) : void

    {

        if(!$event->getPlayer()->hasPlayedBefore())

        {

            $text = Loader::getInstance()->getConfig()->getNested("msg_first_join_all");

            $text = str_replace("%ник%", $event->getPlayer()->getName(), $text);

            Server::getInstance()->broadcastMessage("§c§lСЕРВЕР §7|§r " . $text);

        }

        

        if(SQLite3Manager::getInstance()->isNeedToAuth($event->getPlayer()))

        {

            $event->getPlayer()->setImmobile(true);

            if(!Loader::getInstance()->isFormSet())

            {

                if(!SQLite3Manager::getInstance()->isRegisteredPerson($event->getPlayer()))

                {

                    $event->getPlayer()->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_first_join_player"));

                    return;

                } else

                {

                    $event->getPlayer()->sendMessage("§eВаш §7IP§e адрес не совпадает с тем, что вы использовали при входе на сервер§7.\n§rВам необходимо ввести пароль§7,§r что вы вводили при регистрации§7.\n§f>>§e Если Вы не помните пароль§7, §eиспользуйте§7: §8/§6refreshpass§7.");

                    return;

                }

            } else

            {

                if(Loader::getInstance()->getFormType() instanceof EasyForms)

                {

                    if(!SQLite3Manager::getInstance()->isRegisteredPerson($event->getPlayer()))

                    {

                        $event->getPlayer()->sendForm(new \Frago9876543210\EasyForms\forms\CustomForm("Регистрация:", 

                        [

                            new \Frago9876543210\EasyForms\elements\Label("§eДанный аккаунт не зарегистрирован§7!\n§aЕсли Вам нужно зарегистрировать данный аккаунт §6- §aвведите пароль§7!"),

                            new \Frago9876543210\EasyForms\elements\Input("§8Введите пароль§f:", " ")

                        ],

                        function(Player $sender, \Frago9876543210\EasyForms\forms\CustomFormResponse $response) : void

                        {

                            $pass = $response->getInput()->getValue();

                            if(empty($pass))

                            {

                                $sender->sendMessage("§cПароль не может быть пустым§7.");

                                return;

                            }

                            

                            if(strlen($pass) > 35)

                            {

                                $sender->sendMessage("§cПароль не может быть длиннее 35 символов§7.");

                                return;

                            }

                            

                            SQLite3Manager::getInstance()->registerNewPerson(function() use($sender, $pass) : void

                            {

                                $name = mb_strtolower($sender->getName());

                                Loader::getInstance()->getBaseObject()->query("INSERT INTO playersDataBase (player, ip, password) VALUES ('$name', '{$sender->getNetworkSession()->getIp()}', '$pass')");

                            });

                            $sender->sendMessage("§aУспешная регистрация§7!\n§eВаш пароль§7:§d $pass");

                            $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                            $sender->setImmobile(false);

                            self::$isAuth[spl_object_hash($sender)] = true;

                            return;

                        }, function(Player $sender) : void

                        {

                            $sender->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$sender->getName()}§c провалена§7.");

                            return;

                        }));

                    } else

                    {

                        $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $event->getPlayer());

                        return;

                    }

                } else if(Loader::getInstance()->getFormType() instanceof FormAPI)

                {

                    if(!SQLite3Manager::getInstance()->isRegisteredPerson($event->getPlayer()))

                    {

                        $form = new \jojoe77777\FormAPI\CustomForm(function(Player $sender, ?array $data)

                        {

                            if($data === null)

                            {

                                $sender->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$sender->getName()}§cпровалена§7.");

                                return true;

                            }

                

                            $pass = $data[1];

                        

                            if(empty($pass))

                            {

                                $sender->sendMessage("§cПароль не может быть пустым§7.");

                                return;

                            }

                        

                            if(strlen($pass) > 35)

                            {

                                $sender->sendMessage("§cПароль не может быть длиннее 35 символов§7.");

                                return;

                            }

                            SQLite3Manager::getInstance()->registerNewPerson(function() use($sender, $pass) : void

                            {

                                $name = mb_strtolower($sender->getName());

                                Loader::getInstance()->getBaseObject()->query("INSERT INTO playersDataBase (player, ip, password) VALUES ('$name', '{$sender->getNetworkSession()->getIp()}', '$pass')");

                            });

                            $sender->sendMessage("§aУспешная регистрация§7!\n§eВаш пароль§7:§d $pass");

                            $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                            $sender->setImmobile(false);

                            self::$isAuth[spl_object_hash($sender)] = true;

                            return;

                        });

                        $form->setTitle("Регистрация");

                        $form->addLabel("§eДанный аккаунт не зарегистрирован§7!\n§aЕсли Вам нужно зарегистрировать данный аккаунт §6- §aвведите пароль§7!");

                        $form->addInput("§8Введите пароль§f:");

                        $form->sendToPlayer($event->getPlayer());

                        return;

                    } else

                    {

                        $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $event->getPlayer());

                        return;

                    }

                }

            }

        } else

        {

            $event->getPlayer()->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_success_auth_ip"));

            $event->getPlayer()->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

            return;

        }

    }

    

    public function quitPlayer(PlayerQuitEvent $event) : void

    {

        if(isset(self::$isAuth[spl_object_hash($event->getPlayer())]))

        {

            unset(self::$isAuth[spl_object_hash($event->getPlayer())]);

            return;

        }

        

        if(isset(self::$needToAnswer[spl_object_hash($event->getPlayer())]))

        {

            unset(self::$needToAnswer[spl_object_hash($event->getPlayer())]);

            return;

        }

    }

    

    public function onChat(PlayerChatEvent $event) : void

    {

        if(SQLite3Manager::getInstance()->isNeedToAuth($event->getPlayer()))

        {

            if(isset(self::$needToAnswer[spl_object_hash($event->getPlayer())]))

            {

                if(time() < self::$needToAnswer[spl_object_hash($event->getPlayer())])

                {

                    if($event->getMessage() != SQLite3Manager::getInstance()->getAnswer($event->getPlayer()))

                    {

                        $event->getPlayer()->sendMessage("§cОтвет не подходит§7.");

                        if(isset($this->playerMissCounts[spl_object_hash($event->getPlayer())]))

                        {

                            if($this->playerMissCounts[spl_object_hash($event->getPlayer())] < 3)

                            {

                                $this->playerMissCounts[spl_object_hash($event->getPlayer())]++;

                            } else

                            {

                                unset($this->playerMissCounts[spl_object_hash($event->getPlayer())]);

                                $event->getPlayer()->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$event->getPlayer()->getName()}§c провалена§7.");

                                return;

                            }

                        } else

                        {

                            $this->playerMissCounts[spl_object_hash($event->getPlayer())] = 1;

                        }

                        $event->cancel();

                        return;

                    } else

                    {

                        $sender = $event->getPlayer();

                        SQLite3Manager::getInstance()->updateIp(function() use($sender)

                        {

                            $name = mb_strtolower($sender->getName());

                            Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET ip = '{$sender->getNetworkSession()->getIp()}' WHERE player = '$name'");

                        });

                        

                        $sender->sendMessage("§aВы верно ответили на вопрос§7!\n§eВаш пароль§7: §d" . SQLite3Manager::getInstance()->getPlayerPass($sender));

                        $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                        self::$needToAnswer[spl_object_hash($sender)] = null;

                        self::$isAuth[spl_object_hash($sender)] = true;

                        if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                        {

                            unset($this->playerMissCounts[spl_object_hash($sender)]);

                        }

                        $sender->setImmobile(false);

                        $event->cancel();

                        return;

                    }

                } else

                {

                    self::$needToAnswer[spl_object_hash($event->getPlayer())] = null;

                }

            }

            

            if(!Loader::getInstance()->isFormSet())

            {

                if(!SQLite3Manager::getInstance()->isRegisteredPerson($event->getPlayer()))

                {

                    $pass = $event->getMessage();

                    if(empty($pass))

                    {

                        $event->getPlayer()->sendMessage("§cПароль не может быть пустым§7.");

                        return;

                    }

                    if(strlen($pass) > 35)

                    {

                        $event->getPlayer()->sendMessage("§cПароль не может быть длиннее 35 символов§7.");

                        return;

                    }

                    

                    $text = Loader::getInstance()->getConfig()->getNested("msg_reg_success");

                    $text = str_replace("%пароль%", $pass, $text);

                    $sender = $event->getPlayer();

                    $sender->sendMessage($text);

                    $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                    self::$isAuth[spl_object_hash($sender)] = true;

                    $sender->setImmobile(false);

                    SQLite3Manager::getInstance()->registerNewPerson(function() use($sender, $pass) : void

                    {

                        $name = mb_strtolower($sender->getName());

                        Loader::getInstance()->getBaseObject()->query("INSERT INTO playersDataBase (player, ip, password) VALUES ('$name', '{$sender->getNetworkSession()->getIp()}', '$pass')");

                        return;

                    });

                    $event->cancel();

                } else

                {

                    if(empty($event->getMessage()))

                    {

                        $event->getPlayer()->sendMessage("§cПароль не может быть пустым§7.");

                        return;

                    }

                    

                    if(SQLite3Manager::getInstance()->isPassNotSuitable($event->getPlayer(), $event->getMessage()))

                    {

                        $event->getPlayer()->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_error_pass"));

                        if(isset($this->playerMissCounts[spl_object_hash($event->getPlayer())]))

                        {

                            if($this->playerMissCounts[spl_object_hash($event->getPlayer())] < 3)

                            {

                                $this->playerMissCounts[spl_object_hash($event->getPlayer())]++;

                            } else

                            {

                                unset($this->playerMissCounts[spl_object_hash($event->getPlayer())]);

                                $event->getPlayer()->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$event->getPlayer()->getName()}§c провалена§7.");

                                return;

                            }

                        } else

                        {

                            $this->playerMissCounts[spl_object_hash($event->getPlayer())] = 1;

                        }

                        $event->cancel();

                        return;

                    } else

                    {

                        $sender = $event->getPlayer();

                        SQLite3Manager::getInstance()->updateIp(function() use($sender)

                        {

                            $name = mb_strtolower($sender->getName());

                            Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET ip = '{$sender->getNetworkSession()->getIp()}' WHERE player = '$name'");

                        });

                        $sender->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_success_auth"));

                        $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                        self::$isAuth[spl_object_hash($sender)] = true;

                        if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                        {

                            unset($this->playerMissCounts[spl_object_hash($sender)]);

                        }

                        $sender->setImmobile(false);

                        $event->cancel();

                        return;

                    }

                }

            }

        } else

        {

        

            if(mb_stristr($event->getMessage(), SQLite3Manager::getInstance()->getPlayerPass($event->getPlayer())))

            {

                $event->getPlayer()->sendMessage("§cСообщения§7, §cдля безопасности Вашего аккаунта§7, §cне может содержать пароль от Вашего профиля§7.");

                $event->cancel();

                return;

            }

        }

    }

    

    public function onCommand(CommandEvent $event) : void

    {

        if(!$event->getSender() instanceof Player)

        {

            return;

        }

        

        if($event->getCommand() != "refreshpass")

        {

            if(SQLite3Manager::getInstance()->isNeedToAuth($event->getSender()))

            {

                $event->cancel();

            }

        

            if($event->isCancelled())

            {

                $event->getSender()->sendMessage("§cНельзя использовать команду§7,§c пока вы не авторизовались§7.");

                return;

            }

        } else

        {

            if(Loader::getInstance()->isFormSet())

            {

                $event->cancel();

            }

            

            if($event->isCancelled())

            {

                $event->getSender()->sendMessage("§cДанная команда недоступна§7.");

                return;

            }

        }

    }

    

    public function onDrop(PlayerDropItemEvent $event) : void

    {

        if(SQLite3Manager::getInstance()->isNeedToAuth($event->getPlayer()))

        {

            

            $event->cancel();

        }

        

        if($event->isCancelled())

        {

            $event->getPlayer()->sendTip("§cНельзя выкидывать предмет§7,§c пока Вы не авторизовались§7.");

            return;

        }

    }

    

    public function onBlockPlace(BlockPlaceEvent $event) : void

    {

        if(SQLite3Manager::getInstance()->isNeedToAuth($event->getPlayer()))

        {

            

            $event->cancel();

        }

        

        if($event->isCancelled())

        {

            $event->getPlayer()->sendTip("§cНельзя устанавливать блок§7,§c пока Вы не авторизовались§7.");

            return;

        }

    }

    

    public function onBlockBreak(BlockBreakEvent $event) : void

    {

        if(SQLite3Manager::getInstance()->isNeedToAuth($event->getPlayer()))

        {

            

            $event->cancel();

        }

        

        if($event->isCancelled())

        {

            $event->getPlayer()->sendTip("§cНельзя ломать блок§7,§c пока Вы не авторизовались§7.");

            return;

        }

    }

    

    public function onDamage(EntityDamageEvent $event) : void

    {

        if($event instanceof EntityDamageByEntityEvent)

        {

            if($event->getDamager() instanceof Player and $event->getEntity() instanceof Player)

            {

                if(SQLite3Manager::getInstance()->isNeedToAuth($event->getEntity()) and SQLite3Manager::getInstance()->isNeedToAuth($event->getDamager()))

                {

                    $event->getEntity()->sendTip("§cПока ты не авторизовался§7,§c тебя не могут ударить другие игроки§7.");

                    $event->getDamager()->sendTip("§cНельзя атаковать игрока§7,§c что ещё не авторизовался");

                    $event->cancel();

                    return;

                }

                

                if(SQLite3Manager::getInstance()->isNeedToAuth($event->getEntity()))

                {

                    $event->getDamager()->sendTip("§cНельзя атаковать игрока§7,§c что ещё не авторизовался");

                    $event->cancel();

                    return;

                }

                

                if(SQLite3Manager::getInstance()->isNeedToAuth($event->getDamager()))

                {

                    $event->getDamager()->sendTip("§cТы не можешь атаковать игрока§7,§c так как ты не авторизовался§7.");

                    $event->cancel();

                    return;

                }

            }

        }

    }

    

    private function sendFormToPlayer(EasyForms | FormAPI $form, Player $player) : void

    {

        if($form instanceof EasyForms)

        {

            $type = SQLite3Manager::getInstance()->getSecretQuestion($player) == null ? "§cНе установлено" : SQLite3Manager::getInstance()->getSecretQuestion($player);

            $player->sendForm(new \Frago9876543210\EasyForms\forms\CustomForm("Авторизация", 

            [

                new \Frago9876543210\EasyForms\elements\Label("§6ВНИМАНИЕ§c!\n§eВаш §7IP §eне совпадает с тем§7, §eчто использовался при регистрации§7.\n§aАвторизуйтесь, введя пароль от данного аккаунта§7, §aлибо ответив на секретный вопрос§7."),

                new \Frago9876543210\EasyForms\elements\Input("Впишите пароль:", " "),

                new \Frago9876543210\EasyForms\elements\Input("Ответьте на вопрос: §6" . $type, " "),

            ],

            function(Player $sender, \Frago9876543210\EasyForms\forms\CustomFormResponse $response) : void

            {

                $pass = $response->getInput()->getValue();

                $answer = $response->getInput()->getValue();

                

                if((empty($pass) and empty($answer)) or (!empty($pass) and !empty($answer)))

                {

                    $sender->sendMessage("§cЧто-то одно из полей пароля или секретного вопроса должно быть заполнено§7.");

                    $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $sender);

                    return;

                }

                

                if(!SQLite3Manager::getInstance()->isSecretQuestionSet($sender))

                {

                    if(!empty($pass) and empty($answer))

                    {

                        if(SQLite3Manager::getInstance()->isPassNotSuitable($sender, $pass))

                        {

                            $sender->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_error_pass"));

                            if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                            {

                                if($this->playerMissCounts[spl_object_hash($sender)] < 3)

                                {

                                    $this->playerMissCounts[spl_object_hash($sender)]++;

                                } else

                                {

                                    unset($this->playerMissCounts[spl_object_hash($sender)]);

                                    $sender->kick("§cВы не авторизовались§7.", "§cАвторизация  игрока §6{$sender->getName()}§c провалена§7.");

                                    return;

                                }

                            } else

                            {

                                $this->playerMissCounts[spl_object_hash($sender)] = 1;

                            }

                            $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $sender);

                            return;

                        } else

                        {

                            SQLite3Manager::getInstance()->updateIp(function() use($sender)

                            {

                                $name = mb_strtolower($sender->getName());

                                Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET ip = '{$sender->getNetworkSession()->getIp()}' WHERE player = '$name'");

                            });

                            $sender->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_success_auth"));

                            $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                            self::$isAuth[spl_object_hash($sender)] = true;

                            if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                            {

                                unset($this->playerMissCounts[spl_object_hash($sender)]);

                            }

                            $sender->setImmobile(false);

                            return;

                        }

                    } else

                    {

                        $sender->sendMessage("§cВы не можете использовать ответ на вопрос§7,§c т.к аккаунт не имеет его§7.");

                        $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $sender);

                        return;

                    }

                }

                

                if(!empty($pass) and empty($answer))

                {

                    if(SQLite3Manager::getInstance()->isPassNotSuitable($sender, $pass))

                    {

                        $sender->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_error_pass"));

                        if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                        {

                            if($this->playerMissCounts[spl_object_hash($sender)] < 3)

                            {

                                $this->playerMissCounts[spl_object_hash($sender)]++;

                            } else

                            {

                                unset($this->playerMissCounts[spl_object_hash($sender)]);

                                $sender->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$sender->getName()}§c провалена§7.");

                                return;

                            }

                        } else

                        {

                            $this->playerMissCounts[spl_object_hash($sender)] = 1;

                        }

                        $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $sender);

                        return;

                    } else

                    {

                        SQLite3Manager::getInstance()->updateIp(function() use($sender)

                        {

                            $name = mb_strtolower($sender->getName());

                            Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET ip = '{$sender->getNetworkSession()->getIp()}' WHERE player = '$name'");

                        });

                        $sender->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_success_auth"));

                        $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                        self::$isAuth[spl_object_hash($sender)] = true;

                        if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                        {

                            unset($this->playerMissCounts[spl_object_hash($sender)]);

                        }

                        $sender->setImmobile(false);

                        return;

                    }

                } 

                

                if(empty($pass) and !empty($answer))

                {

                    if($answer != SQLite3Manager::getInstance()->getAnswer($sender))

                    {

                        $sender->sendMessage("§cОтвет не подходит§7.");

                        if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                        {

                            if($this->playerMissCounts[spl_object_hash($sender)] < 3)

                            {

                                $this->playerMissCounts[spl_object_hash($sender)]++;

                            } else

                            {

                                unset($this->playerMissCounts[spl_object_hash($sender)]);

                                $sender->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$sender->getName()}§c провалена§7.");

                                return;

                            }

                        } else

                        {

                            $this->playerMissCounts[spl_object_hash($sender)] = 1;

                        }

                        $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $sender);

                        return;

                    } else

                    {

                        SQLite3Manager::getInstance()->updateIp(function() use($sender)

                        {

                            $name = mb_strtolower($sender->getName());

                            Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET ip = '{$sender->getNetworkSession()->getIp()}' WHERE player = '$name'");

                        });

                        

                        $sender->sendMessage("§aВы верно ответили на вопрос§7!\n§eВаш пароль§7: §d" . SQLite3Manager::getInstance()->getPlayerPass($sender));

                        $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                        self::$isAuth[spl_object_hash($sender)] = true;

                        if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                        {

                            unset($this->playerMissCounts[spl_object_hash($sender)]);

                        }

                        $sender->setImmobile(false);

                        return;

                    }

                }

            },

            function(Player $sender) : void

            {

                $sender->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$sender->getName()}§c провалена§7.");

                return;

            }));

        }

        

        if($form instanceof FormAPI)

        {

            $form = new \jojoe77777\FormAPI\CustomForm(function(Player $sender, ?array $data)

            {

                if($data === null)

                {

                    $sender->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$sender->getName()}§c провалена§7.");

                    return true;

                }

                

                $pass = $data[1];

                $answer = $data[2];

                

                if((empty($pass) and empty($answer)) or (!empty($pass) and !empty($answer)))

                {

                    $sender->sendMessage("§cЧто-то одно из полей пароля или секретного вопроса должно быть заполнено§7.");

                    $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $sender);

                    return;

                }

                

                if(!SQLite3Manager::getInstance()->isSecretQuestionSet($sender))

                {

                    if(!empty($pass) and empty($answer))

                    {

                        if(SQLite3Manager::getInstance()->isPassNotSuitable($sender, $pass))

                        {

                            $sender->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_error_pass"));

                            if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                            {

                                if($this->playerMissCounts[spl_object_hash($sender)] < 3)

                                {

                                    $this->playerMissCounts[spl_object_hash($sender)]++;

                                } else

                                {

                                    unset($this->playerMissCounts[spl_object_hash($sender)]);

                                    return $sender->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$sender->getName()}§c провалена§7.");

                                }

                            } else

                            {

                                $this->playerMissCounts[spl_object_hash($sender)] = 1;

                            }

                            $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $sender);

                            return;

                        } else

                        {

                            SQLite3Manager::getInstance()->updateIp(function() use($sender)

                            {

                                $name = mb_strtolower($sender->getName());

                                Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET ip = '{$sender->getNetworkSession()->getIp()}' WHERE player = '$name'");

                            });

                            $sender->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_success_auth"));

                            $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                            self::$isAuth[spl_object_hash($sender)] = true;

                            if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                            {

                                unset($this->playerMissCounts[spl_object_hash($sender)]);

                            }

                            $sender->setImmobile(false);

                            return;

                        }

                    } else

                    {

                        $sender->sendMessage("§cВы не можете использовать ответ на вопрос§7,§c т.к аккаунт не имеет его§7.");

                        $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $sender);

                        return;

                    }

                }

                

                if(!empty($pass) and empty($answer))

                {

                    if(SQLite3Manager::getInstance()->isPassNotSuitable($sender, $pass))

                    {

                        $sender->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_error_pass"));

                        if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                        {

                            if($this->playerMissCounts[spl_object_hash($sender)] < 3)

                            {

                                $this->playerMissCounts[spl_object_hash($sender)]++;

                            } else

                            {

                                unset($this->playerMissCounts[spl_object_hash($sender)]);

                                return $sender->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$sender->getName()}§c провалена§7.");

                            }

                        } else

                        {

                            $this->playerMissCounts[spl_object_hash($sender)] = 1;

                        }

                        $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $sender);

                        return;

                    } else

                    {

                        SQLite3Manager::getInstance()->updateIp(function() use($sender)

                        {

                            $name = mb_strtolower($sender->getName());

                            Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET ip = '{$sender->getNetworkSession()->getIp()}' WHERE player = '$name'");

                        });

                        $sender->sendMessage(Loader::getInstance()->getConfig()->getNested("msg_success_auth"));

                        $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                        self::$isAuth[spl_object_hash($sender)] = true;

                        if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                        {

                            unset($this->playerMissCounts[spl_object_hash($sender)]);

                        }

                        $sender->setImmobile(false);

                        return;

                    }

                } 

                

                if(empty($pass) and !empty($answer))

                {

                    if($answer != SQLite3Manager::getInstance()->getAnswer($sender))

                    {

                        $sender->sendMessage("§cОтвет не подходит§7.");

                        if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                        {

                            if($this->playerMissCounts[spl_object_hash($sender)] < 3)

                            {

                                $this->playerMissCounts[spl_object_hash($sender)]++;

                            } else

                            {

                                unset($this->playerMissCounts[spl_object_hash($sender)]);

                                return $sender->kick("§cВы не авторизовались§7.", "§cАвторизация игрока §6{$sender->getName()}§c провалена§7.");

                            }

                        } else

                        {

                            $this->playerMissCounts[spl_object_hash($sender)] = 1;

                        }

                        $this->sendFormToPlayer(Loader::getInstance()->getFormType(), $sender);

                        return;

                    } else

                    {

                        SQLite3Manager::getInstance()->updateIp(function() use($sender)

                        {

                            $name = mb_strtolower($sender->getName());

                            Loader::getInstance()->getBaseObject()->query("UPDATE playersDataBase SET ip = '{$sender->getNetworkSession()->getIp()}' WHERE player = '$name'");

                        });

                        

                        $sender->sendMessage("§aВы верно ответили на вопрос§7!\n§eВаш пароль§7: §d" . SQLite3Manager::getInstance()->getPlayerPass($sender));

                        $sender->sendTitle(Loader::getInstance()->getConfig()->getNested("send_title"), Loader::getInstance()->getConfig()->getNested("send_subtitle"));

                        self::$isAuth[spl_object_hash($sender)] = true;

                        if(isset($this->playerMissCounts[spl_object_hash($sender)]))

                        {

                            unset($this->playerMissCounts[spl_object_hash($sender)]);

                        }

                        $sender->setImmobile(false);

                        return;

                    }

                }

            });

            $type = SQLite3Manager::getInstance()->getSecretQuestion($player) == null ? "§cНе установлено" : SQLite3Manager::getInstance()->getSecretQuestion($player);

            $form->setTitle("Авторизация");

            $form->addLabel("§6ВНИМАНИЕ§c!\n§eВаш §7IP §eне совпадает с тем§7, §eчто использовался при регистрации§7.\n§aАвторизуйтесь, введя пароль от данного аккаунта§7.");

            $form->addInput("§8Введите пароль§f:", " ");

            $form->addInput("Ответьте на вопрос: §6" . $type, " ");

            $form->sendToPlayer($player);

            return;

        }

    }

}

?>
