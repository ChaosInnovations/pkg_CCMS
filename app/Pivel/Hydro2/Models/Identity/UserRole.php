<?php

namespace Pivel\Hydro2\Models\Identity;

use Pivel\Hydro2\Attributes\Entity\Entity;
use Pivel\Hydro2\Attributes\Entity\EntityField;
use Pivel\Hydro2\Attributes\Entity\EntityPrimaryKey;
use Pivel\Hydro2\Attributes\Entity\ForeignEntityOneToMany;
use Pivel\Hydro2\Extensions\Query;
use Pivel\Hydro2\Services\Entity\EntityCollection;

#[Entity(CollectionName: 'hydro2_user_roles')]
class UserRole
{
    #[EntityField(FieldName: 'id', AutoIncrement: true)]
    #[EntityPrimaryKey]
    public ?int $Id = null;
    #[EntityField(FieldName: 'name')]
    public string $Name;
    #[EntityField(FieldName: 'description')]
    public ?string $Description = null;
    #[EntityField(FieldName: 'max_login_attempts')]
    public int $MaxLoginAttempts;
    #[EntityField(FieldName: 'max_session_length')]
    public int $MaxSessionLengthMinutes;
    #[EntityField(FieldName: 'max_password_age')]
    public ?int $MaxPasswordAgeDays;
    #[EntityField(FieldName: 'days_until_2fa_setup_required')]
    public int $DaysUntil2FASetupRequired;
    #[EntityField(FieldName: 'challenge_interval')]
    public int $ChallengeIntervalMinutes;
    #[EntityField(FieldName: 'max_2fa_attempts')]
    public int $Max2FAAttempts;

    /** @var EntityCollection<UserPermission> */
    #[ForeignEntityOneToMany(OtherEntityClass: UserPermission::class)]
    private EntityCollection $permissions;

    /** @var EntityCollection<User> */
    #[ForeignEntityOneToMany(OtherEntityClass: User::class)]
    private EntityCollection $users;

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
    }

    public function GetUserCount(): int
    {
        if (!isset($this->users)) {
            return 0;
        }

        return count($this->users);
    }

    /**
     * @return UserPermission[]
     */
    public function GetPermissions(): array
    {
        return $this->permissions->Read();
    }

    public function GrantPermission(string $permissionKey): bool
    {
        if ($this->HasPermission($permissionKey)) {
            return true; // say we added it. more permissive than refusing to add because it was already added previously.
        }

        $permission = new UserPermission(
            userRole: $this,
            permissionKey: $permissionKey,
        );

        return $this->permissions->Create($permission);
    }

    public function DenyPermission(string $permissionKey): bool
    {
        $matches = $this->permissions->Read((new Query())->Equal('permission_key', $permissionKey)->Limit(1));
        if ($matches == 0) {
            return true;
        }

        return $this->permissions->Delete($matches[0]);
    }

    public function HasPermission(string $permissionKey) : bool {
        $matches = $this->permissions->Read((new Query())->Equal('permission_key', $permissionKey)->Limit(1));
        return count($matches) == 1;
    }
}