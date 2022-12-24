<?php

namespace Package\Pivel\Hydro2\Identity\Models;

use Package\Pivel\Hydro2\Database\Extensions\TableColumn;
use Package\Pivel\Hydro2\Database\Extensions\TableForeignKey;
use Package\Pivel\Hydro2\Database\Extensions\TableName;
use Package\Pivel\Hydro2\Database\Extensions\TablePrimaryKey;
use Package\Pivel\Hydro2\Database\Models\BaseObject;
use Package\Pivel\Hydro2\Database\Models\ReferenceBehaviour;

#[TableName('hydro2_user_permissions')]
class Permission extends BaseObject
{
    #[TableColumn('id', autoIncrement:true)]
    #[TablePrimaryKey]
    public ?int $Id = null;
    #[TableColumn('user_role_id')]
    #[TableForeignKey(ReferenceBehaviour::CASCADE,ReferenceBehaviour::CASCADE,'hydro2_user_roles','id')]
    public ?int $UserRoleId; // use int rather than UserRole to avoid circular reference issues.
    #[TableColumn('permission')]
    public ?string $PermissionString;

    public function __construct(?int $userRoleId=null, ?string $permissionString=null) {
        $this->UserRoleId = $userRoleId;
        $this->PermissionString = $permissionString;

        parent::__construct();
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

    public function GetUserRole() : ?UserRole {
        if ($this->UserRoleId === null) {
            return null;
        }
        return UserRole::LoadFromId($this->UserRoleId);
    }
}