<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;

class AuthController extends BaseController
{
    use ResponseTrait;

    public function loginView()
    {
        return view('login');
    }
    
    // POST /api/login
    public function login()
    {
        // Ambil input (prioritas JSON, fallback ke form fields)
        $input = $this->request->getJSON(true);
        if (!$input) {
            $input = $this->request->getPost();
        }

        $username = trim($input['username'] ?? '');
        $password = (string)($input['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->failValidationErrors('Username dan password wajib diisi.');
        }

        $user = (new UserModel())
            ->where('username', $username)
            ->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Username atau password salah.');
        }

        // ===== gunakan helper session() (BUKAN $this->session)
        $session = session();
        $session->set([
            'user_id'  => (int)$user['id'],
            'username' => $user['username']
        ]);

        return $this->respond([
            'message' => 'Login berhasil',
            'user' => [
                'id'       => (int)$user['id'],
                'username' => $user['username'],
            ],
        ]);
    }

    // POST /api/logout
    public function logout()
    {
        $session = session();
        $session->destroy(); // hapus semua data sesi

        return $this->respond([
            'message' => 'Logout berhasil',
        ]);
    }
}
