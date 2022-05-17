<?php

namespace App\Http\Controllers;

use App\Services\KeyManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KeyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'auth.session', 'has.users']);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index()
    {
        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');

        return view(
            'manage.keys.index',
            [
                'userKeys' => $keyManager->getUserKeys(Auth::id())
            ]
        );
    }

    /**
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function editKey(int $id)
    {
        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');
        if ($id > 0 && !$keyManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        return view(
            'manage.keys.edit',
            [
                'keyId' => $id,
                'publicKey' => $keyManager->getKey($id, Auth::id())
            ]
        );
    }

    /**
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function editKeySave(Request $request, int $id)
    {
        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');
        if ($id > 0 && !$keyManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        $label = trim($request->post('label', ''));
        $description = trim($request->post('description', ''));
        $data = trim($request->post('data', ''));

        if ($label == "") {
            return redirect(route('manageKeysEdit', ['id' => $id]))
                ->withErrors(['label' => __('Label cannot be empty')])
                ->withInput();
        } elseif ($data == "") {
            return redirect(route('manageKeysEdit', ['id' => $id]))
                ->withErrors(['data' => __('Data cannot be empty')])
                ->withInput();
        }

        $result = $id > 0
            ? $keyManager->updateKey($id, $label, $description, $data)
            : $keyManager->createKey(Auth::id(), $label, $description, $data);

        if (!$result) {
            return redirect(route('manageKeysEdit', ['id' => $id]))
                ->with('error', __('There was an error while trying to save your public key.'))
                ->withInput();
        }

        return redirect(route('manageKeys'))->with('success', __('Saved'));
    }

    /**
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function deleteKey(int $id)
    {
        /** @var KeyManager $keyManager */
        $keyManager = app()->make('keyManager');
        if (!$keyManager->isOwner($id, Auth::id())) {
            return redirect(route('home'))->with('error', __('Access Denied'));
        }

        if (!$keyManager->deleteKey($id, Auth::id())) {
            return redirect(route('manageKeys'))->with('error', __('Could not delete public key.'));
        }

        return redirect(route('manageKeys'))->with('success', __('Key Deleted'));
    }
}
