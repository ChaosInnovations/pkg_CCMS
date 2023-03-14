<?php

namespace Package\Pivel\Hydro2\Models\Identity;

use DateTime;
use DateTimeZone;
use Package\Pivel\Hydro2\Extensions\Database\TableColumn;
use Package\Pivel\Hydro2\Extensions\Database\TableForeignKey;
use Package\Pivel\Hydro2\Extensions\Database\TableName;
use Package\Pivel\Hydro2\Extensions\Database\TablePrimaryKey;
use Package\Pivel\Hydro2\Extensions\Database\Where;
use Package\Pivel\Hydro2\Models\Database\BaseObject;
use Package\Pivel\Hydro2\Models\Database\ReferenceBehaviour;
use Package\Pivel\Hydro2\Models\Database\Type;
use Package\Pivel\Hydro2\Models\HTTP\Request;

#[TableName('hydro2_user_sessions')]
class Session extends BaseObject
{
    #[TableColumn('id', autoIncrement:true)]
    #[TablePrimaryKey]
    public ?int $Id = null;
    #[TableColumn('user_id')]
    #[TableForeignKey(ReferenceBehaviour::CASCADE,ReferenceBehaviour::CASCADE,'hydro2_users','id')]
    public ?int $UserId; // use int rather than User to avoid circular reference issues.
    #[TableColumn('random_id')]
    public ?string $RandomId = null;
    //session key hash
    #[TableColumn('key_hash', sqlType:Type::TINYTEXT)]
    public string $KeyHash;
    /**
     * Un-hashed key, only available after generating a new key to allow it to be send to
     * the client since the un-hashed key is not stored on the server.
     */
    public ?string $Key = null;

    #[TableColumn('browser')]
    public ?string $Browser;
    #[TableColumn('start')]
    public ?DateTime $StartTime;
    #[TableColumn('expire')]
    public ?DateTime $ExpireTime;
    #[TableColumn('expire_2fa')]
    public ?DateTime $Expire2FATime;
    #[TableColumn('last_access')]
    public ?DateTime $LastAccessTime;
    #[TableColumn('start_ip')]
    public ?string $StartIP;
    #[TableColumn('last_ip')]
    public ?string $LastIP;

    private const KEY_COST = 11;

    public function __construct(
        ?int $userId = null,
        ?string $browser = null,
        ?DateTime $startTime = null,
        ?DateTime $expireTime = null,
        ?DateTime $expire2FATime = null,
        ?DateTime $lastAccessTime = null,
        ?string $startIP = null,
        ?string $lastIP = null,
        ) {
        $this->UserId = $userId;
        $this->GenerateKey();
        $this->Browser = $browser;
        $this->StartTime = $startTime??new DateTime(timezone:new DateTimeZone('UTC'));
        $this->ExpireTime = $expireTime;
        $this->Expire2FATime = $expire2FATime;
        $this->LastAccessTime = $lastAccessTime;
        $this->StartIP = $startIP;
        $this->LastIP = $lastIP;
    }

    /**
     * @return Session[]
     */
    public static function GetAllByUser(User $user) : array {
        $results = self::getTable()->Select(null, (new Where())->Equal('user_id', $user->Id));
        $sessions = [];
        foreach ($results as $result) {
            $sessions[] = self::CastFromRow($result);
        }
        return $sessions;
    }

    public static function LoadFromRandomId(string $randomId) : ?Session {
        $table = self::getTable();
        $results = $table->Select(null, (new Where())->Equal('random_id', $randomId));
        if (count($results) != 1) {
            return null;
        }
        return self::CastFromRow($results[0]);
    }

    public static function LoadAndValidateFromRequest(Request $request) : Session|false {
        $random_id_and_key = explode(';', $request->getCookie('sridkey', ''), 2);

        if (count($random_id_and_key) != 2) {
            return false;
        }

        $random_id = $random_id_and_key[0];
        $key = $random_id_and_key[1];

        $session = self::LoadFromRandomId($random_id);
        if ($session === null) {
            return false;
        }
        
        if (!$session->CompareKey($key)) {
            return false;
        }

        if ($session->Browser !== $request->UserAgent) {
            return false;
        }

        if (!$session->IsValid()) {
            return false;
        }

        $session->LastAccessTime = new DateTime(timezone:new DateTimeZone('UTC'));
        $session->LastIP = $request->getClientAddress();
        $session->Save();

        return $session;
    }

    public function Save() : bool {
        if ($this->UserId === null) {
            return false;
        }
        if ($this->RandomId === null) {
            $this->RandomId = md5(uniqid($this->GetUser()->Email, true));
        }
        return $this->UpdateOrCreateEntry();
    }

    public function Delete() : bool {
        return $this->DeleteEntry();
    }

    public function GetUser() : ?User {
        if ($this->UserId === null) {
            return null;
        }
        return User::LoadFromId($this->UserId);
    }

    public function IsValid() : bool {
        $now = new DateTime(timezone:new DateTimeZone('UTC'));
        if ($now <= $this->StartTime) {
            return false;
        }

        if ($this->ExpireTime <= $now) {
            return false;
        }

        return true;
    }

    public function Expire() : void {
        $now = new DateTime(timezone:new DateTimeZone('UTC'));
        $this->ExpireTime = $now;
    }

    public function Is2FAValid() : bool {
        if ($this->Expire2FATime === null) {
            return true;
        }

        $now = new DateTime(timezone:new DateTimeZone('UTC'));
        if ($this->Expire2FATime <= $now) {
            return false;
        }

        return true;
    }

    private function GenerateKey() : void {
        $this->Key = bin2hex(random_bytes(16));
        $this->KeyHash = password_hash($this->Key, PASSWORD_DEFAULT, ['cost'=>self::KEY_COST]);
    }

    public function CompareKey(string $key) : bool {
        if (!password_verify($key, $this->KeyHash)) {
            return false;
        }

        if (password_needs_rehash($this->KeyHash, PASSWORD_DEFAULT, ['cost'=>self::KEY_COST])) {
            $this->KeyHash = password_hash($key, PASSWORD_DEFAULT, ['cost'=>self::KEY_COST]);
            $this->Save();
        }

        return true;
    }
}