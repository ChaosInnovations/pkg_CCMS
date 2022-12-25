<?php

namespace Package\Pivel\Hydro2\Identity\Models;

use DateTime;
use DateTimeZone;
use Package\Pivel\Hydro2\Database\Extensions\ChildTable;
use Package\Pivel\Hydro2\Database\Extensions\TableColumn;
use Package\Pivel\Hydro2\Database\Extensions\TableName;
use Package\Pivel\Hydro2\Database\Extensions\TablePrimaryKey;
use Package\Pivel\Hydro2\Database\Models\BaseObject;
use Package\Pivel\Hydro2\Identity\Models\Permission;

#[TableName('hydro2_user_roles')]
class UserRole extends BaseObject
{
    #[TableColumn('id', autoIncrement:true)]
    #[TablePrimaryKey]
    public ?int $Id = null;
    #[TableColumn('name')]
    public string $Name;
    #[TableColumn('description')]
    public ?string $Description = null;
    #[TableColumn('max_login_attempts')]
    public int $MaxLoginAttempts;
    #[TableColumn('max_session_length')]
    public int $MaxSessionLengthMinutes;
    #[TableColumn('max_password_age')]
    public int $MaxPasswordAgeDays;
    #[TableColumn('days_until_2fa_setup_required')]
    public int $DaysUntil2FASetupRequired;
    #[TableColumn('challenge_interal')]
    public int $ChallengeIntervalMinutes;
    #[TableColumn('max_2fa_attempts')]
    public int $Max2FAAttempts;
    /** @var Permission[] */
    #[ChildTable('hydro2_user_roles')]
    public array $Permissions;

    /** @param Permission[] $permissions */
    public function __construct(
        string $name='',
        ?string $description=null,
        int $maxLoginAttempts=5,
        int $maxSessionLengthMinutes=43200,
        int $daysUntil2FASetupRequired=3,
        int $challengeIntervalMinutes=21600,
        int $max2FAAttempts=5,
        array $permissions=[],
        ) {
        $this->Name = $name;
        $this->Description = $description;
        $this->MaxLoginAttempts = $maxLoginAttempts;
        $this->MaxSessionLengthMinutes = $maxSessionLengthMinutes;
        $this->DaysUntil2FASetupRequired = $daysUntil2FASetupRequired;
        $this->ChallengeIntervalMinutes = $challengeIntervalMinutes;
        $this->Max2FAAttempts = $max2FAAttempts;
        $this->Permissions = $permissions;
    }

    public function Save() : bool {
        return $this->UpdateOrCreateEntry();
    }

    public function Delete() : bool {
        return $this->DeleteEntry();
    }

    public function AddPermissionString(string $permissionString) : bool {
        // should check if already has it
        $permission = new Permission($this->GetPrimaryKeyValue(), $permissionString);
        if (!$permission->Save()) {
            return false;
        }

        $this->Permissions[] = $permission;
        return true;
    }

    public function HasPermission(string $permissionString) : bool {
        foreach ($this->Permissions as $permission) {
            if ($permission->PermissionString == $permissionString) {
                return true;
            }
        }

        return false;
    }
}