<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Nucleos\UserBundle\Model\User as BaseUser;

#[ODM\Document(collection: 'user')]
#[ODM\Index(keys: ['usernameCanonical' => 'asc'], options: ['unique' => true])]
#[ODM\Index(keys: ['emailCanonical' => 'asc'], options: ['unique' => true])]
class User extends BaseUser implements \Serializable
{
    #[ODM\Id(strategy: 'auto')]
    protected ?string $id = null;

    #[ODM\Field(type: 'string')]
    protected ?string $username = null;
    #[ODM\Field(type: 'string')]
    protected ?string $usernameCanonical = null;
    #[ODM\Field(type: 'string')]
    protected ?string $email = null;
    #[ODM\Field(type: 'string')]
    protected ?string $emailCanonical = null;
    #[ODM\Field(type: 'bool')]
    protected bool $enabled = false;
    #[ODM\Field(type: 'string', nullable: true)]
    protected ?string $salt = null;
    #[ODM\Field(type: 'string')]
    protected ?string $password = null;
    #[ODM\Field(type: 'string', nullable: true)]
    protected ?string $confirmationToken = null;
    #[ODM\Field(type: 'date', nullable: true)]
    protected ?\DateTime $lastLogin = null;

    #[ODM\Field(type: 'date', nullable: true)]
    protected ?\DateTime $passwordRequestedAt = null;

    #[ODM\Field(type: 'collection')]
    protected array $roles = [];

    #[ODM\Field(type: "string", nullable: true)]
    protected ?string $nombre = null;

    #[ODM\ReferenceMany(targetDocument: UserGroup::class)]
    protected Collection $groups;

    #[ODM\Field(type: "string", nullable: true)]
    protected ?string $shema_version = null;

    #[ODM\Field(type: "int", nullable: true)]
    protected ?int $loginFail = null;

    #[ODM\EmbedMany(targetDocument: UserAnswers::class, strategy: 'setArray')]
    protected Collection $answerUser;

    #[ODM\Field(type: "string", nullable: true)]
    protected ?string $cedula = null;

    #[ODM\Field(type: "string", nullable: true)]
    protected ?string $telefono = null;

    #[ODM\Field(type: "string", nullable: true)]
    protected ?string $unidad = null;

    public function __construct()
    {
        parent::__construct();
        $this->groups = new ArrayCollection();
        $this->answerUser = new ArrayCollection();
        $this->loginFail = 0;
    }

    // --- SERIALIZACIÓN SEGURA ---
    public function __serialize(): array
    {
        return [
            'id'                => $this->id,
            'username'          => $this->username,
            'usernameCanonical' => $this->usernameCanonical,
            'email'             => $this->email,
            'emailCanonical'    => $this->emailCanonical,
            'enabled'           => $this->enabled,
            'password'          => $this->password,
            'roles'             => $this->roles,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id                = $data['id'];
        $this->username          = $data['username'];
        $this->usernameCanonical = $data['usernameCanonical'];
        $this->email             = $data['email'];
        $this->emailCanonical    = $data['emailCanonical'];
        $this->enabled           = $data['enabled'];
        $this->password          = $data['password'];
        $this->roles             = $data['roles'] ?? ['ROLE_USER'];

        $this->groups     = new ArrayCollection();
        $this->answerUser = new ArrayCollection();
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize($serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }

    // --- GETTERS / SETTERS ---
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getShemaVersion(): ?string
    {
        return $this->shema_version;
    }

    public function setShemaVersion(?string $shema_version): static
    {
        $this->shema_version = $shema_version;
        return $this;
    }

    public function getLoginFail(): ?int
    {
        return $this->loginFail ?? 0;
    }

    public function setLoginFail(?int $loginFail): static
    {
        $this->loginFail = $loginFail;
        return $this;
    }

    public function getAnswerUser(): Collection
    {
        return $this->answerUser;
    }

    public function addAnswerUser(UserAnswers $answerUser): self
    {
        if (!$this->answerUser->contains($answerUser)) {
            $this->answerUser->add($answerUser);
        }
        return $this;
    }

    public function removeAnswerUser(UserAnswers $answerUser): self
    {
        $this->answerUser->removeElement($answerUser);
        return $this;
    }

    public function getCedula(): ?string
    {
        return $this->cedula;
    }

    public function setCedula(?string $cedula): static
    {
        $this->cedula = $cedula;
        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): static
    {
        $this->telefono = $telefono;
        return $this;
    }

    public function getUnidad(): ?string
    {
        return $this->unidad;
    }

    public function setUnidad(?string $unidad): static
    {
        $this->unidad = $unidad;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        if (empty($roles)) {
            $roles = ['ROLE_USER'];
        }

        foreach ($this->getGroups() as $group) {
            $groupRoles = $group->getRoles();
            if (is_array($groupRoles)) {
                $roles = array_merge($roles, $groupRoles);
            }
        }

        return array_unique($roles);
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getUsernameCanonical(): ?string
    {
        return $this->usernameCanonical;
    }

    public function setUsernameCanonical(?string $usernameCanonical): void
    {
        $this->usernameCanonical = $usernameCanonical;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getEmailCanonical(): ?string
    {
        return $this->emailCanonical;
    }

    public function setEmailCanonical(?string $emailCanonical): void
    {
        $this->emailCanonical = $emailCanonical;
    }


}
