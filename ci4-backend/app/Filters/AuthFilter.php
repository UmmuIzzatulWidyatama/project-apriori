<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Services;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if ($session->get('user_id')) {
            return; // sudah login
        }

        // Jika request minta JSON / Ajax, balas 401 JSON
        $accept = $request->getHeaderLine('Accept');
        if ($request->isAJAX() || stripos($accept, 'application/json') !== false) {
            return Services::response()
                ->setJSON(['message' => 'Unauthorized'])
                ->setStatusCode(401);
        }

        // Selain itu, redirect ke halaman login
        return redirect()->to(site_url('login'))
            ->with('error', 'Silakan login terlebih dahulu.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
