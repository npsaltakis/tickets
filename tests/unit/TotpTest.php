<?php

use App\Libraries\Totp;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TotpTest extends CIUnitTestCase
{
    // RFC 6238 test secret "12345678901234567890" in base32.
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function testMatchesRfc6238Vector(): void
    {
        $this->assertSame('287082', Totp::code(self::RFC_SECRET, 59));
        $this->assertSame('081804', Totp::code(self::RFC_SECRET, 1111111109));
    }

    public function testVerifyAcceptsAdjacentStepOnly(): void
    {
        $now = 1_700_000_000;

        $this->assertTrue(Totp::verify(self::RFC_SECRET, Totp::code(self::RFC_SECRET, $now), $now));
        $this->assertTrue(Totp::verify(self::RFC_SECRET, Totp::code(self::RFC_SECRET, $now - 30), $now));
        $this->assertFalse(Totp::verify(self::RFC_SECRET, Totp::code(self::RFC_SECRET, $now - 120), $now));
    }

    public function testVerifyRejectsMalformedCodes(): void
    {
        $this->assertFalse(Totp::verify(self::RFC_SECRET, ''));
        $this->assertFalse(Totp::verify(self::RFC_SECRET, '12345'));
        $this->assertFalse(Totp::verify('', '123456'));
    }

    public function testGeneratedSecretRoundTrips(): void
    {
        $secret = Totp::generateSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertTrue(Totp::verify($secret, Totp::code($secret)));
    }

    public function testProvisioningUri(): void
    {
        $uri = Totp::provisioningUri('ABC234', 'me@example.test', 'Tickets');

        $this->assertStringStartsWith('otpauth://totp/Tickets%3Ame%40example.test?secret=ABC234', $uri);
        $this->assertStringContainsString('issuer=Tickets', $uri);
    }
}
