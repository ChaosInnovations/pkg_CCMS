<?php

namespace Pivel\Hydro2\Models\Identity;

use Pivel\Hydro2\Extensions\Database\ChildTable;
use Pivel\Hydro2\Extensions\Database\TableColumn;
use Pivel\Hydro2\Extensions\Database\TableName;
use Pivel\Hydro2\Extensions\Database\TablePrimaryKey;
use Pivel\Hydro2\Models\Database\BaseObject;

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
    public ?int $MaxPasswordAgeDays;
    #[TableColumn('days_until_2fa_setup_required')]
    public int $DaysUntil2FASetupRequired;
    #[TableColumn('challenge_interal')]
    public int $ChallengeIntervalMinutes;
    #[TableColumn('max_2fa_attempts')]
    public int $Max2FAAttempts;
    /** @var UserPermission[] */
    #[ChildTable(UserPermission::class)]
    public array $Permissions;

    /** @param UserPermission[] $permissions */
    public function __construct(
        string $name='',
        ?string $description=null,
        int $maxLoginAttempts=5,
        int $maxSessionLengthMinutes=43200,
        int $daysUntil2FASetupRequired=3,
        int $challengeIntervalMinutes=21600,
        int $max2FAAttempts=5,
        ) {
        $this->Name = $name;
        $this->Description = $description;
        $this->MaxLoginAttempts = $maxLoginAttempts;
        $this->MaxSessionLengthMinutes = $maxSessionLengthMinutes;
        $this->MaxPasswordAgeDays = null;
        $this->DaysUntil2FASetupRequired = $daysUntil2FASetupRequired;
        $this->ChallengeIntervalMinutes = $challengeIntervalMinutes;
        $this->Max2FAAttempts = $max2FAAttempts;
        $this->Permissions = [];
    }

    public function Save() : bool {
        return $this->UpdateOrCreateEntry();
    }

    public function Delete() : bool {
        // remove permissions first
        foreach ($this->Permissions as $idx => $permission) {
            $permission->Delete();
            unset($this->Permissions[$idx]);
            $this->Permissions = array_values($this->Permissions);
        }
        return $this->DeleteEntry();
    }

    public function AddPermission(string $permissionKey) : bool {
        if ($this->HasPermission($permissionKey)) {
            return true; // say we added it. more permissive than refusing to add because it was already added previously.
        }
        $permission = new UserPermission($this->GetPrimaryKeyValue(), $permissionKey);
        if (!$permission->Save()) {
            return false;
        }

        $this->Permissions[] = $permission;
        return true;
    }

    public function RemovePermission(string $permissionKey) : bool {
        foreach ($this->Permissions as $idx => $permission) {
            if ($permission->PermissionKey == $permissionKey) {
                $permission->Delete();
                unset($this->Permissions[$idx]);
                $this->Permissions = array_values($this->Permissions);
                // don't break the loop. if there are somehow multiple instances
                //  of the same permission for this role, we should remove all
                //  of them.
            }
        }

        return true;
    }

    public function HasPermission(string $permissionKey) : bool {
        foreach ($this->Permissions as $permission) {
            if ($permission->PermissionKey == $permissionKey) {
                return true;
            }
        }

        return false;
    }
}