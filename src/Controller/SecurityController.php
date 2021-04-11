<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function login(Request $request)
    {
        $user = $this->getUser();

        return $this->json([
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
        ]);
    }

    /**
     * @Route("/status", name="status")
     */
    public function loginStatus() {
        $user = $this->getUser();

        if (null == $user) {
            return $this->json([
                'error' => 'User not connected',
            ]);
        } else {
            return $this->json([
                'username' => $user->getUsername(),
                'roles' => $user->getRoles(),
            ]);
        }
    }

    /**
     * @Route("/error", name="error")
     */
    public function error() {
        return $this->json([
            'error' => "An error occured",
        ]);
    }
}
