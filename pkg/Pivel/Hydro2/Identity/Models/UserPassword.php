<?php

namespace Package\Pivel\Hydro2\Identity\Models;

use DateTime;
use Package\Pivel\Hydro2\Database\Extensions\OrderBy;
use Package\Pivel\Hydro2\Database\Extensions\TableName;
use Package\Pivel\Hydro2\Database\Extensions\TableColumn;
use Package\Pivel\Hydro2\Database\Extensions\TablePrimaryKey;
use Package\Pivel\Hydro2\Database\Extensions\TableForeignKey;
use Package\Pivel\Hydro2\Database\Extensions\Where;
use Package\Pivel\Hydro2\Database\Models\BaseObject;
use Package\Pivel\Hydro2\Database\Models\Order;
use Package\Pivel\Hydro2\Database\Models\ReferenceBehaviour;
use Package\Pivel\Hydro2\Database\Models\Type;

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

    public function SetPassword(string $password) : void {
        $this->PasswordHash = password_hash($password, PASSWORD_DEFAULT, ['cost'=>self::PASSWORD_COST]);
    }

    public function ComparePassword(string $password) : bool {
        if (!password_verify($password, $this->PasswordHash)) {
            return false;
        }

        if (password_needs_rehash($this->PasswordHash, PASSWORD_DEFAULT, ['cost'=>self::PASSWORD_COST])) {
            $this->SetPassword($password);
        }

        return true;
    }
}