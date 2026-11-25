<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\TestCase;
use QuickDRY\API\Security;

/** Tests for Security::HashPassword / VerifyPassword / PasswordNeedsRehash */
final class SecurityPasswordTest extends TestCase
{
    public function testHashAndVerify(): void
    {
        $hash = Security::HashPassword('correct horse');
        $this->assertNotSame('correct horse', $hash);
        $this->assertTrue(Security::VerifyPassword('correct horse', $hash));
        $this->assertFalse(Security::VerifyPassword('wrong horse', $hash));
        $this->assertFalse(Security::PasswordNeedsRehash($hash));
    }

    public function testLegacyMd5StillVerifiesAndNeedsRehash(): void
    {
        $legacy = Security::MD5('old password');
        $this->assertTrue(Security::VerifyPassword('old password', $legacy));
        $this->assertTrue(Security::VerifyPassword('old password', strtolower($legacy)));
        $this->assertFalse(Security::VerifyPassword('other', $legacy));
        $this->assertTrue(Security::PasswordNeedsRehash($legacy));
    }

    public function testEmptyValuesNeverVerify(): void
    {
        $this->assertFalse(Security::VerifyPassword('', Security::MD5('')));
        $this->assertFalse(Security::VerifyPassword('x', ''));
        $this->assertFalse(Security::VerifyPassword('x', null));
        $this->assertFalse(Security::VerifyPassword('x', 'not-a-hash'));
    }
}
