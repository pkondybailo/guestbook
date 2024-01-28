<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    collectionOperations: ['get' => ['normalization_context' => ['groups' => 'comment:list']]],
    itemOperations: ['get' => ['normalization_context' => ['groups' => 'comment:item']]],
    order: ['createdAt' => 'DESC'],
    paginationEnabled: false,
)]
#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Comment
{
    #[Assert\NotBlank]
    #[Groups(['comment:list', 'comment:item'])]
    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[Groups(['comment:list', 'comment:item'])]
    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Conference $conference = null;

    #[Groups(['comment:list', 'comment:item'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Assert\Email]
    #[Assert\NotBlank]
    #[Groups(['comment:list', 'comment:item'])]
    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[Groups(['comment:list', 'comment:item'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['comment:list', 'comment:item'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoFilename = null;

    #[ORM\Column(options: ['default' => 'submitted'])]
    private string $status = 'submitted';

    #[Assert\NotBlank]
    #[Groups(['comment:list', 'comment:item'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $text = null;

    public function __toString(): string
    {
        return $this->email;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getConference(): ?Conference
    {
        return $this->conference;
    }

    public function setConference(?Conference $conference): self
    {
        $this->conference = $conference;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhotoFilename(): ?string
    {
        return $this->photoFilename;
    }

    public function setPhotoFilename(?string $photoFilename): self
    {
        $this->photoFilename = $photoFilename;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
