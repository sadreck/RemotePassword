<?php

namespace Tests\Feature;

use App\Services\PasswordComplexity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PasswordComplexityTest extends TestCase
{
    /**
     * @return void
     */
    public function test_PasswordManager()
    {
        $passwordManager = $this->getPasswordManager(8, true, true, true, true, true);
        $this->assertTrue($passwordManager->validate("This-is-definitely-not-hunter2"));
        $this->assertFalse($passwordManager->validate("testtest"));
        $this->assertFalse($passwordManager->validate("test123test123"));
        $this->assertFalse($passwordManager->validate("test--test--"));
        $this->assertFalse($passwordManager->validate("Test123Test123"));
        $this->assertFalse($passwordManager->validate("Test!Test!"));
        $this->assertFalse($passwordManager->validate("Passw0rd!"));

        $this->assertTrue($passwordManager->validate("HelloHello!1"));
    }

    /**
     * @param int $length
     * @param bool $letters
     * @param bool $mixedCase
     * @param bool $numbers
     * @param bool $symbols
     * @param bool $uncompromised
     * @return PasswordComplexity
     */
    protected function getPasswordManager(
        int $length,
        bool $letters,
        bool $mixedCase,
        bool $numbers,
        bool $symbols,
        bool $uncompromised
    ) : PasswordComplexity {
        return new PasswordComplexity($length, $letters, $mixedCase, $numbers, $symbols, $uncompromised);
    }
}
// phpcs:ignoreFile
