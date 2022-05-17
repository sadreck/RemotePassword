<?php

namespace App\Http\Controllers;

use App\Services\RemotePasswordManager;
use Illuminate\Http\Request;

class RetrievePasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware(['has.users']);
    }

    /**
     * @param string $token1
     * @param string $token2
     * @param string $format
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function accessPasswordGet(string $token1, string $token2, string $format)
    {
        return $this->getPassword($token1, $token2, $format);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function accessPasswordGetQuery(Request $request)
    {
        return $this->getPassword(
            $request->get('token1', '') ?? '',
            $request->get('token2', '') ?? '',
            $request->get('format', 'raw') ?? ''
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function accessPasswordPost(Request $request)
    {
        $token1 = $request->post('token1', '') ?? '';
        $token2 = $request->post('token2', '') ?? '';
        $format = $request->post('format', 'raw') ?? '';

        return $this->getPassword($token1, $token2, $format);
    }

    /**
     * @param string $token1
     * @param string $token2
     * @param string $format
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getPassword(string $token1, string $token2, string $format)
    {
        /** @var RemotePasswordManager $passwordManager */
        $passwordManager = app()->make('passwordManager');

        $data = $passwordManager->retrievePassword($token1, $token2, $format);
        if ($data === false) {
            return response(null, 403);
        }

        return response($data, 200)->header('Content-Type', 'text/plain');
    }
}
