<?php

namespace Tests\Feature;

use App\Services\KeyManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Shared;
use Tests\TestCase;

class KeyManagerTest extends TestCase
{
    use RefreshDatabase, WithFaker, Shared;

    public function test_createKey()
    {
        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');
        $user = $this->createUser();

        $key = $keyManager->createKey($user->getId(), 'my first key', 'this is a description', 'key data');
        $this->assertEquals('App\Models\PublicKey', get_class($key));

        $key2 = $keyManager->getKey($key->getId());
        $this->assertEquals($key->getId(), $key2->getId());
        $this->assertEquals($key->label, 'my first key');
        $this->assertEquals($key->description, 'this is a description');
        $this->assertEquals($key->data, 'key data');

        $key2 = $keyManager->getKey($key->getId(), $user->getId());
        $this->assertEquals($key->getId(), $key2->getId());

        $key2 = $keyManager->getKey(9999);
        $this->assertEquals(false, $key2);

        $key2 = $keyManager->getKey($key->getId(), 9999);
        $this->assertEquals(false, $key2);
    }

    public function test_getKeys()
    {
        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $key1 = $keyManager->createKey($user1->getId(), 'user 1 key 1', '', '');
        $key2 = $keyManager->createKey($user1->getId(), 'user 1 key 2', '', '');
        $key3 = $keyManager->createKey($user2->getId(), 'user 2 key 1', '', '');
        $key4 = $keyManager->createKey($user2->getId(), 'user 2 key 2', '', '');

        $user1KeysShouldBeEmpty = $keyManager->getUserKeys(-1);
        $this->assertCount(0, $user1KeysShouldBeEmpty);

        $user1Keys = $keyManager->getUserKeys($user1->getId());
        $this->assertCount(2, $user1Keys);
    }

    public function test_deleteKeys()
    {
        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $key1 = $keyManager->createKey($user1->getId(), 'user 1 key 1', '', '');
        $key2 = $keyManager->createKey($user1->getId(), 'user 1 key 2', '', '');
        $key3 = $keyManager->createKey($user2->getId(), 'user 2 key 1', '', '');
        $key4 = $keyManager->createKey($user2->getId(), 'user 2 key 2', '', '');

        $user1Keys = $keyManager->getUserKeys($user1->getId());
        $this->assertCount(2, $user1Keys);

        $this->assertTrue($keyManager->deleteKey($key1->getId()));
        $user1Keys = $keyManager->getUserKeys($user1->getId());
        $this->assertCount(1, $user1Keys);

        $this->assertTrue($keyManager->deleteKey($key2->getId(), $user1->getId()));
        $user1Keys = $keyManager->getUserKeys($user1->getId());
        $this->assertCount(0, $user1Keys);
    }

    public function test_updateKeys()
    {
        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');
        $user1 = $this->createUser();

        $key1 = $keyManager->createKey($user1->getId(), 'this is label 1', 'description 1', 'data 1');
        $key2 = $keyManager->createKey($user1->getId(), 'this is label 2', 'description 2', 'data 2');

        $user1Keys = $keyManager->getUserKeys($user1->getId());
        $this->assertCount(2, $user1Keys);

        $result = $keyManager->updateKey(9999999, 'new label', $key1->description, $key1->data);
        $this->assertFalse($result);

        $keyManager->updateKey($key1->getId(), 'new label', $key1->description, $key1->data);
        $key1 = $keyManager->getKey($key1->getId());
        $this->assertEquals('new label', $key1->label);

        $keyManager->updateKey($key1->getId(), $key1->label, 'new description', $key1->data);
        $key1 = $keyManager->getKey($key1->getId());
        $this->assertEquals('new description', $key1->description);

        $keyManager->updateKey($key1->getId(), $key1->label, $key1->description, 'new data');
        $key1 = $keyManager->getKey($key1->getId());
        $this->assertEquals('new data', $key1->data);
    }

    public function test_checkOwner()
    {
        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');
        $user1 = $this->createUser();
        $user2 = $this->createUser();

        $key1 = $keyManager->createKey($user1->getId(), 'user 1 key 1', '', '');
        $key2 = $keyManager->createKey($user1->getId(), 'user 1 key 2', '', '');
        $key3 = $keyManager->createKey($user2->getId(), 'user 2 key 1', '', '');
        $key4 = $keyManager->createKey($user2->getId(), 'user 2 key 2', '', '');

        $this->assertTrue($keyManager->isOwner($key1->getId(), $user1->getId()));
        $this->assertTrue($keyManager->isOwner($key3->getId(), $user2->getId()));
        $this->assertFalse($keyManager->isOwner($key1->getId(), $user2->getId()));
        $this->assertFalse($keyManager->isOwner($key4->getId(), $user1->getId()));
    }
}
// phpcs:ignoreFile
