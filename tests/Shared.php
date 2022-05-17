<?php
namespace Tests;

use App\Models\PublicKey;
use App\Models\RemotePassword;
use App\Models\User;
use App\Services\Core\Settings;
use App\Services\KeyManager;
use App\Services\RemotePasswordManager;
use App\Services\UserManager;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

trait Shared
{
    use WithFaker;

    protected function createUser() : User
    {
        /** @var UserManager $userManager */
        $userManager = app()->make('userManager');
        $userManager->disablePasswordComplexity();

        $user = $userManager->createUser(
            $this->faker->userName(),
            $this->faker->email(),
            $this->faker->password(12, 14),
            true,
            true
        );
        $this->assertEquals('App\Models\User', get_class($user));
        return $user;
    }

    protected function createPassword(
        int $userId,
        string $label = '',
        string $description = '',
        string $data = '',
        string $publicKeyId = '',
        bool $enabled = true,
        string $token1 = null,
        string $token2 = null,
        bool $validateResult = true,
    ) : RemotePassword|bool
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $password = $passwordManager->createPassword(
            $userId,
            empty($label) ? $this->faker->text() : $label,
            empty($description) ? $this->faker->text() : $description,
            empty($data) ? $this->faker->text() : $data,
            empty($publicKeyId) ? $this->faker->text(8) : $publicKeyId,
            $enabled,
            $token1,
            $token2
        );
        if ($validateResult) {
            $this->assertEquals('App\Models\RemotePassword', get_class($password));
        }
        return $password;
    }

    protected function createPublicKey(int $userId) : PublicKey
    {
        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');

        $key = $keyManager->createKey(
            $userId,
            $this->faker->text(),
            $this->faker->text(),
            $this->faker->text()
        );
        $this->assertEquals('App\Models\PublicKey', get_class($key));
        return $key;
    }

    protected function prepareMail()
    {
        Mail::fake();
        Notification::fake();

        /** @var Settings $settings */
        $settings = app()->make('settings');

        $settings->setMultiple(
            [
                'email_enabled' => true,
                'email_host' => null,
                'email_port' => null,
                'email_username' => null,
                'email_password' => null,
                'email_from' => null,
                'email_from_name' => null,
                'password_email_notifications_enabled' => true
            ]
        );
    }
}
// phpcs:ignoreFile
