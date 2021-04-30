<?php

namespace App\Entity;

use App\Repository\CourseEventRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass=CourseEventRepository::class)
 */
class CourseEvent
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $startsAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $endsAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $room;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $teacher;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="courseEvents")
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Exclude()
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity=CourseNote::class, mappedBy="event", orphanRemoval=true)
     */
    private $courseNotes;

    /**
     * @ORM\Column(type="integer")
     */
    private $alcuinId;

    public function __construct()
    {
        $this->courseNotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStartsAt(): ?DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(DateTimeInterface $startsAt): self
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(DateTimeInterface $endsAt): self
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getRoom(): ?string
    {
        return $this->room;
    }

    public function setRoom(string $room): self
    {
        $this->room = $room;

        return $this;
    }

    public function getTeacher(): ?string
    {
        return $this->teacher;
    }

    public function setTeacher(string $teacher): self
    {
        $this->teacher = $teacher;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection|CourseNote[]
     */
    public function getCourseNotes(): Collection
    {
        return $this->courseNotes;
    }

    public function addCourseNote(CourseNote $courseNote): self
    {
        if (!$this->courseNotes->contains($courseNote)) {
            $this->courseNotes[] = $courseNote;
            $courseNote->setEvent($this);
        }

        return $this;
    }

    public function removeCourseNote(CourseNote $courseNote): self
    {
        if ($this->courseNotes->removeElement($courseNote)) {
            // set the owning side to null (unless already changed)
            if ($courseNote->getEvent() === $this) {
                $courseNote->setEvent(null);
            }
        }

        return $this;
    }

    public function getAlcuinId(): ?int
    {
        return $this->alcuinId;
    }

    public function setAlcuinId(int $alcuinId): self
    {
        $this->alcuinId = $alcuinId;

        return $this;
    }
}
