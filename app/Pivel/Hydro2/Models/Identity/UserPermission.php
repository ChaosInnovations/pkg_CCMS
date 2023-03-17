<?php

namespace Pivel\Hydro2\Models\Identity;

use Pivel\Hydro2\Extensions\Database\TableColumn;
use Pivel\Hydro2\Extensions\Database\TableForeignKey;
use Pivel\Hydro2\Extensions\Database\TableName;
use Pivel\Hydro2\Extensions\Database\TablePrimaryKey;
use Pivel\Hydro2\Models\Database\BaseObject;
use Pivel\Hydro2\Models\Database\ReferenceBehaviour;

#[TableName('hydro2_user_permissions')]
class UserPermission extends BaseObject
{
    #[TableColumn('id', autoIncrement:true)]
    #[TablePrimaryKey]
    public ?int $Id = null;
    #[TableColumn('user_role_id')]
    #[TableForeignKey(ReferenceBehaviour::CASCADE,ReferenceBehaviour::CASCADE,'hydro2_user_roles','id')]
    public ?int $UserRoleId; // use int rather than UserRole to avoid circular reference issues.
    #[TableColumn('permission_key')]
    public ?string $PermissionKey;

    public function __construct(?int $userRoleId=null, ?string $permissionKey=null) {
        $this->UserRoleId = $userRoleId;
        $this->PermissionKey = $permissionKey;

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