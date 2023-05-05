<?php

namespace Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pivel\Hydro2\Models\Identity\PasswordResetToken;
use Pivel\Hydro2\Models\Identity\User;

#[CoversClass(PasswordResetToken::class)]
#[UsesClass(PasswordResetToken::class)]
class PasswordResetTokenTest extends TestCase
{
    public function testConstructor()
    {
        $result = new PasswordResetToken();

        $this->assertInstanceOf(PasswordResetToken::class, $result);
        $this->assertEquals(false, $result->Used);
        $this->assertNotEmpty($result->ResetToken);
    }

    public function testGetUser()
    {
        $result = new PasswordResetToken(user: new User());

        $this->assertInstanceOf(User::class, $result->GetUser());
    }

    public function testCompareToken()
    {
        $result = new PasswordResetToken(user: new User());

        $this->assertEquals(true, $result->CompareToken($result->ResetToken));
    }

    public function testCompareTokenFailsIfUsed()
    {
        $result = new PasswordResetToken(user: new User());
        $result->Used = true;

        $this->assertEquals(false, $result->CompareToken($result->ResetToken));
    }

    public function testCompareTokenFailsIfExpired()
    {
        $result = new PasswordResetToken(user: new User(), expireAfterMinutes: -1);

        $this->assertEquals(false, $result->CompareToken($result->ResetToken));
    }
}