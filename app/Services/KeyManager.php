<?php
namespace App\Services;

use App\Events\PublicKeyCreated;
use App\Events\PublicKeyDeleted;
use App\Events\PublicKeyUpdated;
use App\Models\PublicKey;
use Illuminate\Database\Eloquent\Collection;

class KeyManager
{
    /**
     * @param int $userId
     * @param string $label
     * @param string $description
     * @param string $data
     * @return PublicKey
     */
    public function createKey(int $userId, string $label, string $description, string $data) : PublicKey
    {
        $key = new PublicKey([
            'user_id' => $userId,
            'label' => $label,
            'description' => $description,
            'data' => $data
        ]);
        $key->save();

        PublicKeyCreated::dispatch($key);
        return $key;
    }

    /**
     * @param int $keyId
     * @param int $userId
     * @return bool
     */
    public function deleteKey(int $keyId, int $userId = 0) : bool
    {
        $key = $this->getKey($keyId, $userId);
        $result = false;
        if ($key) {
            $result = $key->delete();
            PublicKeyDeleted::dispatch($key);
        }
        return $result;
    }

    /**
     * @param int $userId
     * @return Collection
     */
    public function getUserKeys(int $userId) : Collection
    {
        return PublicKey::where('user_id', $userId)->orderBy('label')->get();
    }

    /**
     * @param int $keyId
     * @param int $userId
     * @return PublicKey|bool
     */
    public function getKey(int $keyId, int $userId = 0) : PublicKey|bool
    {
        $where = ['id' => $keyId];
        if ($userId > 0) {
            $where['user_id'] = $userId;
        }
        $key = PublicKey::where($where)->first();
        return $key ?? false;
    }

    /**
     * @param int $keyId
     * @param int $userId
     * @return bool
     */
    public function isOwner(int $keyId, int $userId) : bool
    {
        return PublicKey
            ::where('id', $keyId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * @param int $keyId
     * @param string $label
     * @param string $description
     * @param string $data
     * @return PublicKey|bool
     */
    public function updateKey(int $keyId, string $label, string $description, string $data) : PublicKey|bool
    {
        $key = $this->getKey($keyId);
        if ($key === false) {
            // Couldn't find key.
            return false;
        }
        $originalKey = clone $key;

        $key->label = $label;
        $key->description = $description;
        $key->data = $data;
        $key->save();

        PublicKeyUpdated::dispatch($originalKey, $key);
        return $key;
    }
}
