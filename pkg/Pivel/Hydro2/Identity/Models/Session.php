<?php

namespace Package\Pivel\Hydro2\Identity\Models;

use DateTime;
use Package\Pivel\Hydro2\Database\Extensions\TableName;
use Package\Pivel\Hydro2\Database\Extensions\TableColumn;
use Package\Pivel\Hydro2\Database\Extensions\TablePrimaryKey;
use Package\Pivel\Hydro2\Database\Extensions\TableForeignKey;
use Package\Pivel\Hydro2\Database\Extensions\Where;
use Package\Pivel\Hydro2\Database\Models\BaseObject;
use Package\Pivel\Hydro2\Database\Models\ReferenceBehaviour;

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
        $this->Browser = $browser;
        $this->StartTime = $startTime;
        $this->ExpireTime = $expireTime;
        $this->Expire2FATime = $expire2FATime;
        $this->LastAccessTime = $lastAccessTime;
        $this->StartIP = $startIP;
        $this->LastIP = $lastIP;
    }

    public static function LoadFromRandomId(int $randomId) : ?Session {
        // 1. need to run a query like:
        //     SELECT * FROM [tablename] WHERE [idcolumnname] = [id];
        $table = self::getTable();
        $results = $table->Select(null, (new Where())->Equal('random_id', $randomId));
        // 2. check that there is a single result
        if (count($results) != 1) {
            return null;
        }
        // 3. 'cast' result to an instance of User
        // 4. return instance
        return self::CastFromRow($results[0]);
    }

    public function Save() : bool {
        if ($this->UserRoleId === null) {
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
}