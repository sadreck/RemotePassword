<?php

namespace Tests\Feature;

use App\Services\RemotePasswordManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use League\Csv\Reader;
use Tests\Shared;
use Tests\TestCase;

class RemotePasswordManagerTest extends TestCase
{
    use RefreshDatabase, WithFaker, Shared;

    public function test_createPassword()
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $user = $this->createUser();
        $key = $this->createPublicKey($user->getId());

        $password = $this->createPassword(
            $user->getId(),
            'first password',
            'some description',
            'this is the data',
            '12345678',
            true
        );
        $this->assertEquals('App\Models\RemotePassword', get_class($password));

        $pwd = $passwordManager->getPassword($password->getId());
        $this->assertEquals('first password', $pwd->label);
        $this->assertEquals('some description', $pwd->description);
        $this->assertEquals('this is the data', $pwd->data);
        $this->assertTrue($pwd->enabled);
        $this->assertEquals('12345678', $pwd->public_key_id);
        $this->assertEquals($passwordManager->getTokenLength(), strlen($pwd->token1));
        $this->assertEquals($passwordManager->getTokenLength(), strlen($pwd->token2));
        $this->assertEquals($password->token1, $pwd->token1);
        $this->assertEquals($password->token2, $pwd->token2);
        $this->assertTrue($passwordManager->isOwner($pwd->getId(), $user->getId()));

        $passwordManager->setTokenLength(8);
        //$password2 = $passwordManager->createPassword($user->getId(), 'second password', 'some description', 'this is the data', true, '1234qwer', 'QWER1a2s');
        $password2 = $this->createPassword(
            $user->getId(),
            'second password',
            'some description',
            'this is the data',
            '12345678',
            true,
            '1234qwer',
            'QWER1a2s'
        );
        $this->assertEquals('App\Models\RemotePassword', get_class($password2));

        $pwd = $passwordManager->getPassword($password2->getId());
        $this->assertEquals($pwd->token1, '1234qwer');
        $this->assertEquals($pwd->token2, 'QWER1a2s');

        $invalidTokenLength = $this->createPassword(
            $user->getId(),
            'second password',
            'some description',
            'this is the data',
            '12345678',
            true,
            '12',
            '12345678',
            false
        );
        $this->assertFalse($invalidTokenLength);

        $invalidTokenLength = $this->createPassword(
            $user->getId(),
            'second password',
            'some description',
            'this is the data',
            '12345678',
            true,
            '12345678',
            '12',
            false
        );
        $this->assertFalse($invalidTokenLength);

        $this->assertCount(2, $passwordManager->getUserPasswords($user->getId()));
    }

    public function test_deletePassword()
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $password1 = $this->createPassword($user1->getId());
        $password2 = $this->createPassword($user1->getId());
        $password3 = $this->createPassword($user2->getId());
        $password4 = $this->createPassword($user2->getId());

        // No passwords should be deleted.
        $passwordManager->deletePassword(9999999, 9999999);
        $this->assertCount(2, $passwordManager->getUserPasswords($user1->getId()));
        $passwordManager->deletePassword(9999999);
        $this->assertCount(2, $passwordManager->getUserPasswords($user1->getId()));

        $passwordManager->deletePassword($password1->getId());
        $this->assertCount(1, $passwordManager->getUserPasswords($user1->getId()));
        $passwordManager->deletePassword($password3->getId(), 999);
        $this->assertCount(2, $passwordManager->getUserPasswords($user2->getId()));
        $passwordManager->deletePassword($password3->getId(), $user2->getId());
        $this->assertCount(1, $passwordManager->getUserPasswords($user2->getId()));
    }

    public function test_updatePassword()
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $user1 = $this->createUser();
        $key1 = $this->createPublicKey($user1->getId());

        $password1 = $this->createPassword($user1->getId());
        $password2 = $this->createPassword($user1->getId());

        $invalidPasswordId = $passwordManager->updatePassword(99999, '', '', '', '', false);
        $this->assertFalse($invalidPasswordId);

        $password = $passwordManager->updatePassword($password1->getId(), 'label1', 'description1', 'data1', '12345678', true);
        $this->assertEquals('App\Models\RemotePassword', get_class($password));
        $password = $passwordManager->getPassword($password->getId());
        $this->assertEquals('label1', $password->label);
        $this->assertEquals('description1', $password->description);
        $this->assertEquals('data1', $password->data);
        $this->assertEquals('12345678', $password->public_key_id);
        $this->assertTrue($password->enabled);
    }

    public function test_retrievePassword()
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $user1 = $this->createUser();
        $key1 = $this->createPublicKey($user1->getId());

        $password1 = $this->createPassword($user1->getId());

        $data = $passwordManager->retrievePassword($password1->token1, $password1->token2, '');
        $this->assertFalse($passwordManager->retrievePassword($password1->token1, $password1->token2, 'invalid-format'));
        $this->assertEquals($password1->data, $data);

        $data = $passwordManager->retrievePassword($password1->token1, $password1->token2, 'xml');
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<rpass>';
        $xml[] = '<password>'. htmlentities($password1->data) .'</password>';
        $xml[] = '<checksum>'. htmlentities($password1->getChecksum()) .'</checksum>';
        $xml[] = '</rpass>';
        $xml = implode(PHP_EOL, $xml);
        $this->assertEquals($xml, $data);

        $data = $passwordManager->retrievePassword($password1->token1, $password1->token2, 'base64');
        $this->assertEquals(base64_encode($password1->data), $data);

        $data = $passwordManager->retrievePassword($password1->token1, $password1->token2, 'json');
        $json = json_encode([
            'password' => $password1->data,
            'checksum' => $password1->getChecksum()
        ]);
        $this->assertEquals($json, $data);

        $data = $passwordManager->retrievePassword($password1->token1, $password1->token2, 'raw');
        $this->assertEquals($password1->data, $data);

        $checksum = $passwordManager->retrievePassword($password1->token1, $password1->token2, 'checksum');
        $this->assertEquals($password1->getChecksum(), $checksum);

        // Now test edge cases and false tests.
        $data = $passwordManager->retrievePassword(strtolower($password1->token1), strtolower($password1->token2), '');
        $this->assertFalse($data);

        $data = $passwordManager->retrievePassword($password1->token1 . 'x', $password1->token2, '');
        $this->assertFalse($data);

        // Disabled password.
        $passwordManager->updatePassword($password1->getId(), $password1->label, $password1->description, $password1->data, $password1->public_key_id, false);
        $data = $passwordManager->retrievePassword($password1->token1, $password1->token2, '');
        $this->assertFalse($data);
    }

    public function test_ImportExportPasswords()
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $user = $this->createUser();

        $passwords = [];
        for ($i = 0; $i < 99; $i++) {
            $passwords[] = $this->createPassword($user->getId());
        }

        // Check the export.
        $passwordIds = array_map(
            function ($p) {
                return $p->getId();
            },
            $passwords
        );
        $passwordIds[] = '-1'; // Add an invalid one.
        $csvString = $passwordManager->exportPasswords($passwordIds, $user->getId());
        $this->assertNotEmpty($csvString);

        $csv = Reader::createFromString($csvString);
        $headers = $csv->fetchOne();
        $csv->setHeaderOffset(0);

        $exportedPasswordCount = 0;
        $fieldCheckCount = 7;
        foreach ($csv as $line) {
            foreach ($passwords as $password) {
                $c = 0;

                if ($password->label == $line['Label']) { $c++; }
                if ($password->description == $line['Description']) { $c++; }
                if ($password->enabled == $line['Enabled']) { $c++; }
                if ($password->data == $line['Data']) { $c++; }
                if ($password->public_key_id == $line['Public Key']) { $c++; }
                if ($password->token1 == $line['Token 1']) { $c++; }
                if ($password->token2 == $line['Token 2']) { $c++; }

                if ($c == $fieldCheckCount) {
                    $exportedPasswordCount++;
                }
            }
        }
        $this->assertEquals(count($passwords), $exportedPasswordCount);

        // Delete everything.
        foreach ($passwords as $password) {
            $passwordManager->deletePassword($password->getId(), $user->getId());
        }
        $this->assertEquals(0, $passwordManager->getUserPasswords($user->getId())->count());

        // Import the previous export.
        $this->assertTrue($passwordManager->importPasswords($csvString, $user->getId()));
        $userPasswords = $passwordManager->getUserPasswords($user->getId());
        $this->assertEquals(count($passwords), $userPasswords->count());

        $importedPasswordCount = 0;
        $fieldCheckCount = 7;
        foreach ($userPasswords as $userPassword) {
            foreach ($passwords as $password) {
                $c = 0;
                if ($userPassword->label == $password->label) { $c++; }
                if ($userPassword->description == $password->description) { $c++; }
                if ($userPassword->enabled == $password->enabled) { $c++; }
                if ($userPassword->data == $password->data) { $c++; }
                if ($userPassword->public_key_id == $password->public_key_id) { $c++; }
                if ($userPassword->token1 == $password->token1) { $c++; }
                if ($userPassword->token2 == $password->token2) { $c++; }


                if ($c == $fieldCheckCount) {
                    $importedPasswordCount++;
                }
            }
        }
        $this->assertEquals(count($passwords), $importedPasswordCount);
    }
}
// phpcs:ignoreFile
