<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\Timestampable;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
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
    private ?string $email;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity=Account::class, mappedBy="linkedTo", orphanRemoval=true)
     */
    private $accounts;

    /**
     * @ORM\OneToMany(targetEntity=TitleHistory::class, mappedBy="creator", orphanRemoval=true)
     */
    private $titleHistory;

    /**
     * @ORM\Column(type="datetime_immutable", options={"default": "CURRENT_TIMESTAMP"})
     */
    #[Timestampable(on: 'create')]
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    #[Timestampable(on: 'update')]
    private $updatedAt;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="moderatorOf")
     * @ORM\JoinTable(name="moderators",
     *      joinColumns={@ORM\JoinColumn(name="admin_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="moderator_id", referencedColumnName="id")}
     *      )
     */
    private $moderators;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="moderators")
     */
    private $moderatorOf;

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
        $this->titleHistory = new ArrayCollection();
        $this->moderators = new ArrayCollection();
        $this->moderatorOf = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
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
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
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

    /**
     * @return Collection|Account[]
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    public function addAccount(Account $account): self
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts[] = $account;
            $account->setLinkedTo($this);
        }

        return $this;
    }

    public function removeAccount(Account $account): self
    {
        if ($this->accounts->removeElement($account)) {
            // set the owning side to null (unless already changed)
            if ($account->getLinkedTo() === $this) {
                $account->setLinkedTo(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TitleHistory[]
     */
    public function getTitleHistory(): Collection
    {
        return $this->titleHistory;
    }

    public function addTitleHistory(TitleHistory $titleHistory): self
    {
        if (!$this->titleHistory->contains($titleHistory)) {
            $this->titleHistory[] = $titleHistory;
            $titleHistory->setCreator($this);
        }

        return $this;
    }

    public function removeTitleHistory(TitleHistory $titleHistory): self
    {
        if ($this->titleHistory->removeElement($titleHistory)) {
            // set the owning side to null (unless already changed)
            if ($titleHistory->getCreator() === $this) {
                $titleHistory->setCreator(null);
            }
        }

        return $this;
    }

    public function getLastTitles(): Collection
    {
        $criteria = Criteria::create()
            ->orderBy(['createdAt' => 'DESC'])
            ->setMaxResults(10);

        return $this->titleHistory->matching($criteria);
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getModerators(): Collection
    {
        return $this->moderators;
    }

    public function addModerator(self $moderator): self
    {
        if (!$this->moderators->contains($moderator)) {
            $this->moderators[] = $moderator;
        }

        return $this;
    }

    public function removeModerator(self $moderator): self
    {
        $this->moderators->removeElement($moderator);

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getModeratorOf(): Collection
    {
        return $this->moderatorOf;
    }

    public function addModeratorOf(self $moderatorOf): self
    {
        if (!$this->moderatorOf->contains($moderatorOf)) {
            $this->moderatorOf[] = $moderatorOf;
            $moderatorOf->addModerator($this);
        }

        return $this;
    }

    public function removeModeratorOf(self $moderatorOf): self
    {
        if ($this->moderatorOf->removeElement($moderatorOf)) {
            $moderatorOf->removeModerator($this);
        }

        return $this;
    }
}
