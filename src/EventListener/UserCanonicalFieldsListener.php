<?php

namespace App\EventListener;

use App\Document\User;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;

class UserCanonicalFieldsListener
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $document = $args->getDocument();

        if (!$document instanceof User) {
            return;
        }

        $this->updateCanonicalFields($document);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $document = $args->getDocument();

        if (!$document instanceof User) {
            return;
        }

        $this->updateCanonicalFields($document);
    }

    private function updateCanonicalFields(User $user): void
    {

        $reflection = new \ReflectionClass($user);

        $username = $user->getUsername();
        if ($username) {
            $usernameCanonicalProperty = $reflection->getProperty('usernameCanonical');
            $usernameCanonicalProperty->setAccessible(true);
            $usernameCanonicalProperty->setValue($user, $this->canonicalize($username));
        }

        $email = $user->getEmail();
        if ($email) {
            $emailCanonicalProperty = $reflection->getProperty('emailCanonical');
            $emailCanonicalProperty->setAccessible(true);
            $emailCanonicalProperty->setValue($user, $this->canonicalize($email));
        }
    }

    private function canonicalize(string $value): string
    {
        return mb_convert_case($value, MB_CASE_LOWER, mb_detect_encoding($value));
    }
}
