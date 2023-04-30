<?php

namespace Pivel\Hydro2\Models\Identity;

use DateTime;
use DateTimeZone;
use Pivel\Hydro2\Attributes\Entity\Entity;
use Pivel\Hydro2\Attributes\Entity\EntityField;
use Pivel\Hydro2\Attributes\Entity\EntityPrimaryKey;
use Pivel\Hydro2\Attributes\Entity\ForeignEntityManyToOne;
use Pivel\Hydro2\Attributes\Entity\ForeignEntityOneToMany;
use Pivel\Hydro2\Extensions\Query;
use Pivel\Hydro2\Models\Database\Order;
use Pivel\Hydro2\Services\Entity\EntityCollection;

#[Entity(CollectionName: 'hydro2_users')]
class User
{
    #[EntityField(FieldName: 'id', AutoIncrement: true)]
    #[EntityPrimaryKey]
    public ?int $Id = null;
    #[EntityField(FieldName: 'random_id')]
    public ?string $RandomId = null;
    #[EntityField(FieldName: 'inserted')]
    public ?DateTime $InsertedTime = null;
    #[EntityField(FieldName: 'email')]
    public string $Email;
    #[EntityField(FieldName: 'email_verified')]
    public bool $EmailVerified;
    #[EntityField(FieldName: 'email_verification_token')]
    public ?string $EmailVerificationToken = null;
    #[EntityField(FieldName: 'name')]
    public string $Name;
    #[EntityField(FieldName: 'user_role_id')]
    #[ForeignEntityManyToOne()]
    private ?UserRole $role;
    #[EntityField(FieldName: 'needs_review')]
    public bool $NeedsReview;
    #[EntityField(FieldName: 'enabled')]
    public bool $Enabled;
    #[EntityField(FieldName: 'failed_login_attempts')]
    public int $FailedLoginAttempts;
    #[EntityField('failed_2FA_attempts')]
    public int $Failed2FAAttempts;
    
    /** @var EntityCollection<Session> */
    #[ForeignEntityOneToMany(OtherEntityClass: Session::class)]
    private EntityCollection $userSessions;

    /** @var EntityCollection<UserPassword> */
    #[ForeignEntityOneToMany(OtherEntityClass: UserPassword::class)]
    private EntityCollection $userPasswords;

    /** @var EntityCollection<PasswordResetToken> */
    #[ForeignEntityOneToMany(OtherEntityClass: PasswordResetToken::class)]
    private EntityCollection $userPasswordResetTokens;

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
        if ($this->Email !== '') {
            $this->GenerateRandomId();
            $this->InsertedTime = new DateTime(timezone: new DateTimeZone('UTC'));
        }
        $this->EmailVerified = false;
        $this->Name = $name;
        $this->NeedsReview = $needsReview;
        $this->Enabled = $enabled;
        $this->FailedLoginAttempts = $failedLoginAttempts;
        $this->Failed2FAAttempts = $failed2FAAttempts;
        $this->role = $role;
    }

    public function GetSessionCount(): int
    {
        if (!isset($this->userSessions)) {
            return 0;
        }

        return count($this->userSessions);
    }

    /**
     * @return Session[]
     */
    public function GetSessions(): array
    {
        return $this->userSessions->Read();
    }

    public function GetPasswordCount(): int
    {
        if (!isset($this->userPasswords)) {
            return 0;
        }

        return count($this->userPasswords);
    }

    public function GetUserRole(): UserRole
    {
        return $this->role;
    }

    public function SetUserRole(UserRole $role): void
    {
        $this->role = $role;
    }

    private function GenerateRandomId(): void
    {
        $this->RandomId = md5(uniqid($this->Email, true));
    }

    public function GetEmailVerificationToken(): string
    {
        return $this->EmailVerificationToken??$this->GenerateEmailVerificationToken();
    }

    public function GenerateEmailVerificationToken(): string {
        $this->EmailVerificationToken = bin2hex(random_bytes(16));
        return $this->EmailVerificationToken;
    }

    public function ValidateEmailVerificationToken(string $token) : bool
    {
        if ($this->EmailVerified) {
            return false;
        }
        
        return $token === $this->EmailVerificationToken;
    }
    
    public function CheckPassword(string $password): bool
    {
        // get current password
        /** @var UserPassword[] */
        $currentPasswords = $this->userPasswords->Read((new Query())->OrderBy('start', Order::Descending)->Limit(1));
        if (count($currentPasswords) != 1) {
            return false;
        }

        if (!$currentPasswords[0]->ComparePassword($password)) {
            return false;
        }

        // Check if password should be re-hashed, and persist it if it was.
        if ($currentPasswords[0]->RehashPasswordIfRequired($password)) {
            $this->userPasswords->Update($currentPasswords[0]);
        }

        return true;
    }

    public function SetNewPassword(string $password): bool {
        // TODO add enforcement for minimum length, complexity, not matching previous x passwords.
        $now = new DateTime(timezone: new DateTimeZone('UTC'));
        $expiry = null;
        if ($this->role->MaxPasswordAgeDays !== null) {
            $expiry = clone $now;
            $expiry->modify("+{$this->role->MaxPasswordAgeDays} days");
        }

        $newPassword = new UserPassword(
            user: $this,
            password: $password,
            startTime: $now,
            expireTime: $expiry,
        );

        return $this->userPasswords->Create($newPassword);
    }

    public function IsPasswordChangeRequired(): bool
    {
        // get current password
        /** @var UserPassword[] */
        $currentPasswords = $this->userPasswords->Read((new Query())->OrderBy('start', Order::Descending)->Limit(1));
        return count($currentPasswords) != 1 || $currentPasswords[0]->IsExpired();
    }

    public function CheckPasswordResetToken(string $token): bool
    {
        /** @var PasswordResetToken[] */
        $tokenObjs = $this->userPasswordResetTokens->Read((new Query())->Equal('reset_token', $token)->Limit(1));
        if (count($tokenObjs) != 1) {
            return false;
        }

        if (!$tokenObjs[0]->CompareToken($token)) {
            return false;
        }

        // update token since it is now used.
        $tokenObjs[0]->Used = true;
        $this->userPasswordResetTokens->Update($tokenObjs[0]);

        return true;
    }

    public function CreateNewPasswordResetToken(): ?PasswordResetToken
    {
        $newToken = new PasswordResetToken(
            user: $this,
        );

        if (!$this->userPasswordResetTokens->Create($newToken)) {
            return null;
        }

        return $newToken;
    }
}
