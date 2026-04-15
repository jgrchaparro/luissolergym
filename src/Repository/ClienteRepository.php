<?php

namespace App\Repository;

use App\Document\Cliente;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class ClienteRepository extends DocumentRepository
{
    public function buscarPaginado(string $busqueda = '', int $limit = 10, int $skip = 0): array
    {
        $qb = $this->createQueryBuilder();

        if ($busqueda !== '') {
            $regex = new \MongoDB\BSON\Regex($busqueda, 'i');
            $qb->addOr($qb->expr()->field('cedula')->equals($regex));
            $qb->addOr($qb->expr()->field('nombres')->equals($regex));
        }

        $qbCount = clone $qb;
        $total = $qbCount->count()->getQuery()->execute();

        $qb->limit($limit)->skip($skip);
        $clientes = $qb->getQuery()->execute();

        return [
            'data' => $clientes,
            'total' => $total,
        ];
    }
}
