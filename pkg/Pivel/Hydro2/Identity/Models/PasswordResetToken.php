<?php

namespace Package\Pivel\Hydro2\Identity\Models;

use DateTime;
use DateTimeZone;
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

#[TableName('hydro2_user_password_reset_tokens')]
class PasswordResetToken extends BaseObject
{
    #[TableColumn('id', autoIncrement:true)]
    #[TablePrimaryKey]
    public ?int $Id = null;
    #[TableColumn('user_id')]
    #[TableForeignKey(ReferenceBehaviour::CASCADE,ReferenceBehaviour::CASCADE,'hydro2_users','id')]
    public ?int $UserId; // use int rather than User to avoid circular reference issues.
    #[TableColumn('reset_token')]
    public string $ResetToken;
    #[TableColumn('start')]
    public ?DateTime $StartTime;
    #[TableColumn('expire')]
    public ?DateTime $ExpireTime;
    #[TableColumn('used')]
    public bool $Used;

    public function __construct(
        ?int $userId = null,
        ?DateTime $startTime = null,
        int $expireAfterMinutes = 10,
        ) {
        $this->UserId = $userId;
        $this->GenerateToken();
        $this->StartTime = $startTime??new DateTime(timezone:new DateTimeZone('UTC'));
        $this->ExpireTime = (clone $this->StartTime)->modify("+{$expireAfterMinutes} minutes");
        $this->Used = false;
    }

    public static function LoadFromToken(string $token) : ?self {
        $table = self::getTable();
        $results = $table->Select(null, (new Where())->Equal('reset_token', $token));
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

    private function GenerateToken() {
        $this->ResetToken = bin2hex(random_bytes(16));
    }

    public function CompareToken(string $token) : bool {
        if ($this->Used) {
            return false;
        }

        if ($this->ExpireTime < new DateTime(timezone:new DateTimeZone('UTC'))) {
            return false;
        }
        
        return $token === $this->ResetToken;
    }
}