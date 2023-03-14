<?php

namespace Package\Pivel\Hydro2\Models\Identity;

use DateTime;
use DateTimeZone;
use Package\Pivel\Hydro2\Extensions\Database\OrderBy;
use Package\Pivel\Hydro2\Extensions\Database\TableColumn;
use Package\Pivel\Hydro2\Extensions\Database\TableForeignKey;
use Package\Pivel\Hydro2\Extensions\Database\TableName;
use Package\Pivel\Hydro2\Extensions\Database\TablePrimaryKey;
use Package\Pivel\Hydro2\Extensions\Database\Where;
use Package\Pivel\Hydro2\Models\Database\BaseObject;
use Package\Pivel\Hydro2\Models\Database\Order;
use Package\Pivel\Hydro2\Models\Database\ReferenceBehaviour;
use Package\Pivel\Hydro2\Models\Database\Type;

#[TableName('hydro2_user_passwords')]
class UserPassword extends BaseObject
{
    #[TableColumn('id', autoIncrement:true)]
    #[TablePrimaryKey]
    public ?int $Id = null;
    #[TableColumn('user_id')]
    #[TableForeignKey(ReferenceBehaviour::CASCADE,ReferenceBehaviour::CASCADE,'hydro2_users','id')]
    public ?int $UserId; // use int rather than User to avoid circular reference issues.
    //session key hash

    #[TableColumn('password_hash', sqlType:Type::TINYTEXT)]
    public string $PasswordHash;
    #[TableColumn('start')] // regardless of expire time, the password with the most recent start is the current one
    public ?DateTime $StartTime;
    #[TableColumn('expire')] // if within 5 days, prompt to change on login. if past, require change on login. if null, doesn't expire
    public ?DateTime $ExpireTime;

    private const PASSWORD_COST = 11;

    public function __construct(
        ?int $userId = null,
        string $password = '',
        ?DateTime $startTime = null,
        ?DateTime $expireTime = null,
        ) {
        $this->UserId = $userId;
        $this->SetPassword($password);
        $this->StartTime = $startTime;
        $this->ExpireTime = $expireTime;
    }

    public static function LoadCurrentFromUser(User $user) : ?self {
        $table = self::getTable();
        $results = $table->Select(null, (new Where())->Equal('user_id', $user->Id), (new OrderBy())->Column('start', Order::Descending), 1);
        if (count($results) != 1) {
            return null;
        }
        
        return self::CastFromRow($results[0]);
    }

    public static function Blank() : self {
        return new self();
    }

    public function Save() : bool {
        if ($this->UserId === null) {
            return false;
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

    public function IsExpired() : bool {
        if ($this->ExpireTime === null) {
            return false;
        }

        $now = new DateTime(timezone: new DateTimeZone('UTC'));
        return $now >= $this->ExpireTime;
    }

    public function SetPassword(string $password) : void {
        $this->PasswordHash = password_hash($password, PASSWORD_DEFAULT, ['cost'=>self::PASSWORD_COST]);
    }

    public function ComparePassword(string $password) : bool {
        if (!password_verify($password, $this->PasswordHash)) {
            return false;
        }

        if (password_needs_rehash($this->PasswordHash, PASSWORD_DEFAULT, ['cost'=>self::PASSWORD_COST])) {
            $this->SetPassword($password);
            $this->Save();
        }

        return true;
    }
}