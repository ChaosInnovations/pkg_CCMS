<?php

namespace Package\Pivel\Hydro2\Models\Identity;

use DateTime;
use DateTimeZone;
use \Package\Pivel\Hydro2\Core\Models\Response;
use \Package\Pivel\Hydro2\Core\Models\Request;
use \Package\Pivel\Hydro2\Core\Utilities;
use Package\Pivel\Hydro2\Database\Extensions\ChildTable;
use Package\Pivel\Hydro2\Database\Extensions\TableColumn;
use Package\Pivel\Hydro2\Database\Extensions\TableForeignKey;
use Package\Pivel\Hydro2\Database\Extensions\TableName;
use Package\Pivel\Hydro2\Database\Extensions\TablePrimaryKey;
use Package\Pivel\Hydro2\Database\Extensions\Where;
//use \Package\Mailer;
use \Package\Pivel\Hydro2\Database\Models\BaseObject;
//use \Package\ModuleMenu;
//use \Package\Page;
//use \Package\SecureMenu;
//use \Package\SiteConfiguration;
//use \Package\User\AccountManager;
//use \Package\User\UserPermissions;

#[TableName('hydro2_users')]
class User extends BaseObject
{
    #[TableColumn('id', autoIncrement:true)]
    #[TablePrimaryKey]
    public ?int $Id = null;
    #[TableColumn('random_id')]
    public ?string $RandomId = null;
    #[TableColumn('inserted')]
    public ?DateTime $InsertedTime = null;
    #[TableColumn('email')]
    public string $Email;
    #[TableColumn('email_verified')]
    public bool $EmailVerified;
    #[TableColumn('email_verification_token')]
    public ?string $EmailVerificationToken = null;
    #[TableColumn('name')]
    public string $Name;
    #[TableColumn('user_role_id')]
    #[TableForeignKey()]
    public ?UserRole $Role;
    #[TableColumn('needs_review')]
    public bool $NeedsReview;
    #[TableColumn('enabled')]
    public bool $Enabled;
    #[TableColumn('failed_login_attempts')]
    public int $FailedLoginAttempts;
    #[TableColumn('failed_2FA_attempts')]
    public int $Failed2FAAttempts;
    //#[ChildTable('hydro2_user_sessions')]
    ///** @var Session[] */
    //public array $Sessions;

    public function __construct(
        string $email='',
        string $name='',
        bool $needsReview=false,
        bool $enabled=false,
        int $failedLoginAttempts=0,
        int $failed2FAAttempts=0,
        ?UserRole $role=null,
        ) {
        $this->Email = $email;
        $this->EmailVerified = false;
        $this->Name = $name;
        $this->NeedsReview = $needsReview;
        $this->Enabled = $enabled;
        $this->FailedLoginAttempts = $failedLoginAttempts;
        $this->Failed2FAAttempts = $failed2FAAttempts;
        $this->Role = $role;
        
        parent::__construct();
    }

    public static function LoadFromRandomId(string $randomId) : ?User {
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

    public static function LoadFromEmail(string $email) : ?User {
        // 1. need to run a query like:
        //     SELECT * FROM [tablename] WHERE [idcolumnname] = [id];
        $table = self::getTable();
        $results = $table->Select(null, (new Where())->Equal('email', $email));
        // 2. check that there is a single result
        if (count($results) != 1) {
            return null;
        }
        // 3. 'cast' result to an instance of User
        // 4. return instance
        return self::CastFromRow($results[0]);
    }

    /** @return User[] */
    public static function GetAllWithRole(UserRole $role) : array {
        $table = self::getTable();
        $results = $table->Select(null, (new Where())->Equal('user_role_id', $role->Id));
        return array_map(fn($row)=>self::CastFromRow($row), $results);
    }

    public static function Blank() : self {
        return new self();
    }

    public function Save() : bool {
        if ($this->RandomId === null) {
            $this->RandomId = md5(uniqid($this->Email, true));
        }
        if ($this->InsertedTime === null) {
            $this->InsertedTime = new DateTime(timezone:new DateTimeZone('UTC'));
        }

        return $this->UpdateOrCreateEntry();
    }

    public function Delete() : bool {
        return $this->DeleteEntry();
    }
    
    public function isValidUser()
    {
        return $this->Id !== null;
    }

    public function GetEmailVerificationToken() : string
    {
        return $this->EmailVerificationToken??$this->GenerateEmailVerificationToken();
    }

    public function GenerateEmailVerificationToken() : string {
        $this->EmailVerificationToken = bin2hex(random_bytes(16));
        $this->Save();
        return $this->EmailVerificationToken;
    }

    public function ValidateEmailVerificationToken(string $token) : bool
    {
        if ($this->EmailVerified) {
            return false;
        }
        
        return $token === $this->EmailVerificationToken;
    }

    public function GetCurrentPassword() : ?UserPassword
    {
        return UserPassword::LoadCurrentFromUser($this);
    }
    
}
