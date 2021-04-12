<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\Encoder\MyPwdEncoder;
use App\Utils;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use OpenApi\Annotations as OA;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class SecurityController extends AbstractFOSRestController
{
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * Login with Alcuin id.
     * @OA\RequestBody(
     *     description="The Alcuin username and password in a JSON object",
     *     @OA\JsonContent()
     * )
     * @Rest\Post(
     *     path = "/login",
     *     name = "login",
     * )
     */
    public function login(Request $request, EventDispatcherInterface $dispatcher)
    {
        $encoder = new MyPwdEncoder($this->getParameter('app.enc_key'));
        $response = new Response();
        $json = $request->request->all();

        if (!array_key_exists('username', $json) || !array_key_exists('password', $json)) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent(Utils::jsonMsg("Username or password missing."));
            return $response;
        }

        $username = $json['username'];
        $password = $json['password'];

        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy(['username' => $username]);

        if (!$user) {
            // Register new user
            $user = new User();
            $user->setUsername($username);
            $user->setPassword($encoder->encodePassword($password, null));
            $this->em->persist($user);
            $this->em->flush();
        }
        $browser = new HttpBrowser(HttpClient::create());

        // Authentifying to Alcuin
        $browser->request('GET', 'https://esaip.alcuin.com/OpDotNet/Noyau/Login.aspx');
        $browser->submitForm('Connexion', [
            'UcAuthentification1$UcLogin1$txtLogin' => $username,
            'UcAuthentification1$UcLogin1$txtPassword' => $password
        ]);
        $browser->request('GET', 'https://esaip.alcuin.com/OpDotNet/Context/context.jsx');

        // Getting Alcuin user id
        $r = $browser->getResponse()->getContent();
        try {
            $alcuinId = intval(explode('=', explode(';', $r)[0])[1]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => "Error while connecting to Alcuin.",
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        // No alcuin id = bad credentials
        if ($alcuinId == 0) {
            if ($user->getAlcuinId() == null) {
                $this->em->remove($user);
                $this->em->flush();
            }
            return $this->json([
                'error' => "Wrong username or password",
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Storing the _actual_ good password and Alcuin ID
        $user->setPassword($encoder->encodePassword($password, null));
        $user->setAlcuinId($alcuinId);
        $this->em->persist($user);
        $this->em->flush();

        $token = new UsernamePasswordToken($user, $password, "main", $user->getRoles());
        $this->get('security.token_storage')->setToken($token);

        $event = new InteractiveLoginEvent($request, $token);
        $dispatcher->dispatch($event);

        return $this->json([
            'username' => $user->getUsername(),
            'alcuin_id' => $user->getAlcuinId()
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
            ], Response::HTTP_UNAUTHORIZED);
        } else {
            return $this->json([
                'username' => $user->getUsername(),
                'alcuin_id' => $user->getAlcuinId(),
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

    /**
     * @Route("/logout", name="app_logout", methods={"GET"})
     */
    public function logout()
    {
        // controller can be blank: it will never be executed!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }
}
