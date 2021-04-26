<?php

namespace App\Controller;

use App\Entity\CourseEvent;
use App\Entity\CourseNote;
use App\Entity\User;
use App\Utils;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use OpenApi\Annotations as OA;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\RememberMe\TokenBasedRememberMeServices;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractFOSRestController
{
    private $em;
    private $client;
    private $params;
    private $BASE_URL = 'https://esaip.alcuin.com/OpDotNet/Services';

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client, ParameterBagInterface $params) {
        $this->em = $em;
        $this->client = $client;
        $this->params = $params;
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
        $response = new Response();
        $json = $request->request->all();

        if (!array_key_exists('username', $json) || !array_key_exists('password', $json)) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent(Utils::jsonMsg("Username or password missing."));
            return $response;
        }

        $username = $json['username'];
        $password = $json['password'];

        $res = $this->client->request(
            'POST',
            $this->BASE_URL . '/OpenPortal.ServicesFundations.ContextOP/CreateTokensWithLogin.sopx',
            [
                'body' => [
                    'login' => $username,
                    'password' => $password,
                    'isHashed' => 'false',
                    'grant_type' => 'password',
                    'clientName' => 'alcMobApp'
                ]
            ]
        );

        if ($res->getStatusCode() != 200) {
            return $this->json([
                'error' => "Wrong username or password",
            ], Response::HTTP_UNAUTHORIZED);
        }

        $json = $res->toArray();
        $refreshToken = $json['refreshToken'];
        $accessToken = $json['accessToken'];

        // Storing the _actual_ good password and Alcuin ID
        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy(['username' => $username]);

        if (!$user) {
            // Register new user
            $user = new User();
            $user->setUsername($username);
        }
        $user->setRefreshToken($refreshToken);
        $this->em->persist($user);
        $this->em->flush();

        $token = new UsernamePasswordToken($user, $password, "main", $user->getRoles());
        $this->get('security.token_storage')->setToken($token);

        $event = new InteractiveLoginEvent($request, $token);
        $dispatcher->dispatch($event);

        $userProvider = new EntityUserProvider($this->getDoctrine(), 'App\Entity\User', 'username');

        //new TokenBasedRememberMeServices(array($userProvider), $this->params->get('$secret'),'main', array());

        return $this->json([
            'username' => $user->getUsername(),
            'refreshToken' => $user->getRefreshToken()
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
                'refreshToken' => $user->getRefreshToken(),
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

    /**
     * @Rest\Get(path="/grades", name="show_grades")
     */
    public function showGrades()
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null == $user) {
            return $this->json([
                'error' => 'User not connected',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = $this->getAccessToken($user->getRefreshToken());
        if ($accessToken == null) {
            return $this->json([
                'error' => 'An error occured while getting an access token.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $res = $this->client->request(
            'POST',
            $this->BASE_URL . '/OpenPortal.Entities.AppMobile.Grades.IGradeUIMobileServices%5EOpenPortal.Entities/GetNotesStagiaire.sopx',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken
                ]
            ]
        );

        if ($res->getStatusCode() != 200) {
            return $this->json([
                'error' => "An error occured while getting the grades.",
                'content' => $res->getContent(false)
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($res->toArray());
    }

    /**
     * @Route("/delete-account", name="delete_account", methods={"POST"})
     */
    public function deleteAccount()
    {
        $user = $this->getUser();
        $username = $user->getUsername();
        if (null == $user) {
            return $this->json([
                'error' => 'User not connected',
            ], Response::HTTP_UNAUTHORIZED);
        } else {
            $this->em->remove($user);
            $this->em->flush();

            return $this->json([
                'success' => 'All user data was deleted for ' . $username,
            ]);
        }
    }

    /**
     * @Rest\Get(path="/courses", name="show_courses")
     * @Rest\View()
     */
    public function showCourses()
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null == $user) {
            return $this->json([
                'error' => 'User not connected',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->em->getRepository(CourseEvent::class)->findBy(['user' => $user], ['startsAt' => 'ASC']);
    }

    /**
     * @Rest\Post(path="/courses/{id}/notes", name="create_note", requirements = { "id"="\d+" })
     * @Rest\View()
     */
    public function createNote($id, Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null == $user) {
            return $this->json([
                'error' => 'User not connected',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $event = $this->em->getRepository(CourseEvent::class)->find($id);
        if ($event == null || $event->getUser() != $user) {
            return $this->json([
                'error' => 'No event with this ID found for this user.',
            ], Response::HTTP_NOT_FOUND);
        }

        $json = $request->request->all();
        $note = new CourseNote();
        $note->setContent($json['content']);
        $note->setEvent($event);

        $this->em->persist($note);
        $this->em->flush();

        return $note;
    }

    /**
     * @Rest\Post(path="/notes/{id}", name="update_note", requirements = { "id"="\d+" })
     * @Rest\View()
     */
    public function updateNote($id, Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null == $user) {
            return $this->json([
                'error' => 'User not connected',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $note = $this->em->getRepository(CourseNote::class)->find($id);
        if ($note == null || $note->getEvent()->getUser() != $user) {
            return $this->json([
                'error' => 'No note with this ID found for this user.',
            ], Response::HTTP_NOT_FOUND);
        }

        $json = $request->request->all();
        $note->setContent($json['content']);
        $note->setCreatedDate(new DateTime());

        $this->em->persist($note);
        $this->em->flush();

        return $note;
    }

    /**
     * @Rest\Delete(path="/notes/{id}", name="delete_note", requirements = { "id"="\d+" })
     * @Rest\View()
     */
    public function deleteNote($id)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null == $user) {
            return $this->json([
                'error' => 'User not connected',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $note = $this->em->getRepository(CourseNote::class)->find($id);
        if ($note == null || $note->getEvent()->getUser() != $user) {
            return $this->json([
                'error' => 'No note with this ID found for this user.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($note);
        $this->em->flush();

        return $this->json([
            'success' => 'The note was successfully deleted.'
        ]);
    }

    /**
     * @Rest\Post(path="/sync-courses", name="sync_courses")
     * @Rest\View()
     */
    public function syncCourses()
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null == $user) {
            return $this->json([
                'error' => 'User not connected',
            ], Response::HTTP_UNAUTHORIZED);
        } else {
            $accessToken = $this->getAccessToken($user->getRefreshToken());
            if ($accessToken == null) {
                return $this->json([
                    'error' => 'An error occured while getting an access token.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $now = new DateTime();
            $year = intval($now->format('Y'));
            $month = intval($now->format('n'));

            if ($month >= 8) {
                $year += 1;
            }

            // TODO Jusqu'à la fin de l'année $endDate = new DateTime($year . '-07-15T00:00:00');


            $start = $now->format('Y-m-d\T08:00:00');

            $endDate = $now->add(new DateInterval('P60D'));
            $end = $endDate->format('Y-m-d\T23:59:59');

            $res = $this->client->request(
                'POST',
                $this->BASE_URL . '/OpenPortal.Entities.AppMobile.Agenda.IEventUIMobileServices%5EOpenPortal.Entities/GetEventsInRange.sopx',
                [
                    'body' => [
                        'StartDate' => $start,
                        'FinalDate' => $end,
                        'LastSync' => $start,
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken
                    ]
                ]
            );

            if ($res->getStatusCode() != 200) {
                return $this->json([
                    'error' => "An error occured while getting the course events.",
                    'token' => $accessToken,
                    'startsAt' => $start,
                    'endsAt' => $end,
                    'content' => $res->getContent(false)
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $eventsRepo = $this->em->getRepository(CourseEvent::class);

            // Suppression des événements + vieux d'un mois
            $events = $eventsRepo->findOlderThanAMonth($user);
            foreach ($events as $event) {
                $this->em->remove($event);
            }
            $this->em->flush();

            // Création / mise à jour des événements Alcuin
            $json = $res->toArray();
            $events = $json['Upsert'];
            $idsList = [];
            foreach ($events as $e) {
                $event = $eventsRepo->findOneBy(['alcuinId' => $e['id'], 'user' => $user]);
                if ($event == null) {
                    $event = new CourseEvent();
                }
                $event->setAlcuinId($e['id']);
                $event->setName(substr($e['name'],0, 5) === 'Cours' ? $e['projects'][0] : $e['name']);
                $event->setRoom(count($e['rooms']) > 0 ? join(', ', $e['rooms']) : 'Pas de salle');
                $event->setTeacher(count($e['teachers']) > 0 ? $e['teachers'][0] : 'Pas de prof');
                $event->setType($e['color'] == '#ffff00' ? 'Cours' : ($e['color'] == '#ff8080' ? 'Exam' : 'Autre'));
                $event->setUser($user);
                $event->setStartsAt(new DateTime(explode('+', $e['startsAt'])[0]));
                $event->setEndsAt(new DateTime(explode('+', $e['endsAt'])[0]));
                $this->em->persist($event);
                $idsList[] = $event->getAlcuinId();
            }

            // Suppression des événements futurs n'existant plus
            $events = $eventsRepo->findAfterToday($user);
            foreach ($events as $e) {
                if (!in_array($e->getAlcuinId(), $idsList)) {
                    $this->em->remove($e);
                }
            }

            $this->em->flush();

            return $eventsRepo->findBy(['user' => $user]);
        }
    }

    private function getAccessToken($refreshToken)
    {
        $res = $this->client->request(
            'POST',
            $this->BASE_URL . '/OpenPortal.ServicesFundations.ContextOP/CreateAccessWithRefresh.sopx',
            [
                'body' => [
                    'grant_type' => 'refresh_token',
                    'clientName' => 'alcMobApp',
                    'refreshToken' => $refreshToken
                ]
            ]
        );
        if ($res->getStatusCode() != 200) {
            return null;
        }
        $json = $res->toArray();
        return $json['accessToken'];
    }
}
