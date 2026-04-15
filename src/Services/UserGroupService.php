<?php

namespace App\Services;

use App\Document\UserGroup;
use App\Document\UserRole;
use Doctrine\ODM\MongoDB\DocumentManager;

class UserGroupService
{
    protected $document_manager;

    /**
     * ServiceCliente constructor.
     * @param $document_manager
     */
    public function __construct(DocumentManager $document_manager)
    {
        $this->document_manager = $document_manager;
    }

    public function setRoleGroup($roleId, $groupId){
        $role = $this->document_manager->getRepository(UserRole::class)->find($roleId);
        $group = $this->document_manager->getRepository(UserGroup::class)->find($groupId);

        if($group->hasRole($role->getRole())){
            $group->removeRole($role->getRole());
        }else{
            $group->addRole($role->getRole());
        }

        $this->document_manager->persist($group);
        $this->document_manager->flush();
    }
}
