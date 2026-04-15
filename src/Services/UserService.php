<?php

namespace App\Services;

use App\Document\User;
use App\Document\UserGroup;
use App\Document\UserRole;
use App\Document\UserSettings;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Nucleos\UserBundle\Model\UserManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
class UserService
{
    protected $document_manager;
    protected $security;
    protected $userManager;
    private $params;
    private $serviceLog;
    private UserPasswordHasherInterface $passwordHasher;
    /**
     * UserService constructor.
     */
    public function __construct(
        DocumentManager $document_manager,
        Security $security,
        UserManager $userManager,
        ParameterBagInterface $params,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->document_manager = $document_manager;
        $this->security = $security;
        $this->userManager = $userManager;
        $this->params = $params;
        $this->passwordHasher = $passwordHasher;
    }

    public function setUserGroup($userId, $groupId)
    {
        $user = $this->document_manager->getRepository(User::class)->find($userId);
        $group = $this->document_manager->getRepository(UserGroup::class)->find($groupId);

        if ($user->hasGroup($group->getName())) {
            $user->removeGroup($group);
        } else {
            $user->addGroup($group);
        }

        $this->document_manager->persist($user);
        $this->document_manager->flush();
    }

    public function checkRole($role)
    {
        $user = $this->security->getUser();
        if (!$user->hasRole($role)) {
            throw new AccessDeniedException('No tiene acceso');
        }
    }

    /** @throws MongoDBException */
    public function setAdminUser(): bool
    {
        $user = $this->userManager->findUserByUsername('admin');
        $userGroup = $this->document_manager->getRepository(UserGroup::class)->findOneBy([
            'name' => 'administradores_c']);

        if (!$user) {
            echo 'Creando user admin' . PHP_EOL;
            $user = $this->userManager->createUser();
            $user->setUsername('admin');
            $user->setEmail('john.doe@p.com');
            $user->setEnabled(true);

            // 🔑 Hashear password manualmente
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'Atom-Regular');
            $user->setPassword($hashedPassword);

            $this->document_manager->persist($user);
            $this->document_manager->flush();
        }

        if (!$userGroup){
            $userGroup = new UserGroup('administradores_c');
            $this->document_manager->persist($userGroup);

            echo 'no' . PHP_EOL;
        }else{
            echo 'si ---------------- .' . PHP_EOL;
        }
        $user->addGroup($userGroup);
        $this->userManager->updateUser($user);
        $this->document_manager->flush();

        echo "Usuario admin creado correctamente. " . PHP_EOL;

        $dir = $this->params->get('config_dir');
        $archivoRoles = $dir . '/roles.txt';

        $i = 0;
        $j = 0;

        try {
            $handle = fopen($archivoRoles, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $valores = explode(';', $line);
                    foreach ($valores as $clave => $valor) {
                        $valores[$clave] = trim(($valor));
                    }
                    $role = $valores[0];
                    echo $role . PHP_EOL;
                    $descripcionRole = $valores[1];

                    $qb = $this->document_manager->createQueryBuilder(UserRole::class);
                    $qb->field('role')->equals($role);

                    $rol = $qb->getQuery()->getSingleResult();

                    if (!$rol) {
                        echo str_pad('Creando rol: ' . $role, 70, ' ', STR_PAD_RIGHT);
                        echo ' ...... ';
                        $userRole = new UserRole();
                        $userRole->setRole($role);
                        $userRole->setDescripcion($descripcionRole);

                        $this->document_manager->persist($userRole);
                        $this->document_manager->flush();
                        echo 'Creado con exito' . PHP_EOL;
                        $j++;

                        if ($userGroup) {
                            $userGroup->addRole($role);
                            $i++;
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        }
        if ($j === 0) {
            echo 'No se encontraron nuevos roles a generar.' . PHP_EOL;
        }
        if (!$userGroup) {
            echo 'Grupo administradores no fue encontrado' . PHP_EOL;
        }

        if ($i > 0) {
            $this->document_manager->persist($userGroup);
            $this->document_manager->flush();
            echo "Usuario admin actualizado, " . $i . ' roles agregados' . PHP_EOL;

            return true;
        }
        echo 'Proceso terminado.' . PHP_EOL;
        return true;
    }

    /**
     * @throws MongoDBException
     */
    public function incLoginFail($userName)
    {
        $userSettings = $this->getUserSettings();
        $user = $this->userManager->findUserByUsername($userName);
        if ($user) {
            $user->setLoginFail($user->getLoginFail() + 1);
            if ($user->getLoginFail() >= $userSettings->getMaximoIntentosPasswordErrado()) {
                $user->setEnabled(false);
            }
            $this->userManager->updateUser($user);
        }
    }

    /** @throws MongoDBException */
    public function getUserSettings()
    {
        $qb = $this->document_manager->createQueryBuilder(UserSettings::class);
        $settings = $qb->getQuery()->getSingleResult();

        if (!$settings) {
            $settings = new UserSettings();
            $this->document_manager->persist($settings);
            $this->document_manager->flush();
        }
        return $settings;
    }

    public function resetLoginFail($userName)
    {
        $user = $this->userManager->findUserByUsername($userName);
        $user->setLoginFail(0);
        $this->userManager->updateUser($user);
    }

    public function checkEnableUser($userName): bool
    {
        $user = $this->userManager->findUserByUsername($userName);
        return $user && $user->isEnabled();
    }

    public function enabledUser($id)
    {
        $user = $this->document_manager->getRepository(User::class)->find($id);
        if ($user) {
            if($user->isEnabled()){
                $user->setEnabled(false);
            } else {
                $user->setEnabled(true);
            }
            $user->setLoginFail(0);
            $this->userManager->updateUser($user);
        }
    }
}
