<?php

namespace Package\User;

use \Package\FireSock\ISubscription;
use \Package\User;
use \Package\User\AccountManager;

class UserSubscription implements ISubscription {
    private $hook = "";
    private $server = null;
    private $user = null;

    public $userObject = null;

    public function __construct($server, $user, $hook) {
        $this->server = $server;
        $this->user = $user;
        $this->hook = $hook;

        $this->server->send($this->user, "{$hook} ok");
    }

    public function processMessage($message) {
        $args = json_decode($message, true);
        
        if (!isset($args["function"])) {
            return;
        }

        if ($args["function"] == "authenticate") {
            if (!isset($args["token"])) {
                $this->server->send($this->user, "{$this->hook} fail");
                return;
            }

            

            if (!AccountManager::validateToken($args["token"], $this->user->address)) {
                $this->userObject = new User(null);
                $this->server->send($this->user, "{$this->hook} fail");
                return;
            }

            $this->userObject = User::userFromToken($args["token"]);
            $this->server->send($this->user, "{$this->hook} ok");
        }
    }

    public function tick() {

    }
}