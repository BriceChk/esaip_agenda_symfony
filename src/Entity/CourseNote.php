<?php

namespace App\Entity;

use App\Repository\CourseNoteRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass=CourseNoteRepository::class)
 */
class CourseNote
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity=CourseEvent::class, inversedBy="courseNotes")
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Exclude()
     */
    private $event;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdDate;

    public function __construct() {
        $this->createdDate = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getEvent(): ?CourseEvent
    {
        return $this->event;
    }

    public function setEvent(?CourseEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getCreatedDate(): ?DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(DateTimeInterface $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }
}
