<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\ConferenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\String\Slugger\SluggerInterface;

#[ApiResource(
    collectionOperations: ['get' => ['normalization_context' => ['groups' => 'conference:list']]],
    itemOperations: ['get' => ['normalization_context' => ['groups' => 'conference:item']]],
    order: ['year' => 'DESC', 'city' => 'ASC'],
    paginationEnabled: false,
)]
#[ORM\Entity(repositoryClass: ConferenceRepository::class)]
#[UniqueEntity(fields: ['slug'])]
class Conference
{
    #[Groups(['conference:list', 'conference:item'])]
    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\OneToMany(mappedBy: 'conference', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    #[Groups(['conference:list', 'conference:item'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['conference:list', 'conference:item'])]
    #[ORM\Column]
    private ?bool $isInternational = null;

    #[Groups(['conference:list', 'conference:item'])]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[Groups(['conference:list', 'conference:item'])]
    #[ORM\Column(length: 4)]
    private ?string $year = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('%s %s', $this->city, $this->year);
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setConference($this);
        }

        return $this;
    }

    public function computeSlug(SluggerInterface $slugger): void
    {
        if (!$this->slug || '-' === $this->slug) {
            $this->slug = $slugger->slug((string) $this)->lower()->toString();
        }
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(string $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function isInternational(): ?bool
    {
        return $this->isInternational;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment) && $comment->getConference() === $this) {
            $comment->setConference(null);
        }

        return $this;
    }

    public function setIsInternational(bool $isInternational): self
    {
        $this->isInternational = $isInternational;

        return $this;
    }
}
