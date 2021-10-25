<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface, EncoderAwareInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string
     */
    private $password = '';

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $refreshToken;

    /**
     * @ORM\OneToMany(targetEntity=CourseEvent::class, mappedBy="user", orphanRemoval=true)
     * @Serializer\Exclude()
     */
    private $courseEvents;

    /**
     * @ORM\Column(type="date")
     */
    private $lastLogin;

    public function __construct()
    {
        $this->courseEvents = new ArrayCollection();
        $this->lastLogin = new DateTime('NOW');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEncoderName() {
        return 'app_encoder';
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    /**
     * @return Collection|CourseEvent[]
     */
    public function getCourseEvents(): Collection
    {
        return $this->courseEvents;
    }

    public function addCourseEvent(CourseEvent $courseEvent): self
    {
        if (!$this->courseEvents->contains($courseEvent)) {
            $this->courseEvents[] = $courseEvent;
            $courseEvent->setUser($this);
        }

        return $this;
    }

    public function removeCourseEvent(CourseEvent $courseEvent): self
    {
        if ($this->courseEvents->removeElement($courseEvent)) {
            // set the owning side to null (unless already changed)
            if ($courseEvent->getUser() === $this) {
                $courseEvent->setUser(null);
            }
        }

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }
}
