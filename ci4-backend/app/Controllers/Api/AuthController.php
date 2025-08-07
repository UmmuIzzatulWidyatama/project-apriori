<?php

namespace App\Controllers\Api;
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;

class AuthController extends BaseController
{
    use ResponseTrait;

    public function login()
    {
        $request = $this->request->getJSON(true);
        $username = $request['username'] ?? '';
        $password = $request['password'] ?? '';

        $userModel = new UserModel();
        $user = $userModel->where('username', $username)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Username atau password salah.');
        }

        return $this->respond([
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username']
            ]
        ]);
    }

    public function loginView()
    {
        return view('login');
    }

}