<?php

namespace App\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class TipoMensualidadRepository extends DocumentRepository
{
    public function buscarPaginado(string $busqueda = '', int $limit = 10, int $skip = 0): array
    {
        $qb = $this->createQueryBuilder();

        if ($busqueda !== '') {
            $regex = new \MongoDB\BSON\Regex($busqueda, 'i');
            $qb->addOr($qb->expr()->field('codigo')->equals($regex));
            $qb->addOr($qb->expr()->field('descripcion')->equals($regex));
        }

        $qbCount = clone $qb;
        $total = $qbCount->count()->getQuery()->execute();

        $qb->sort('fechaCreacion', 'desc')->limit($limit)->skip($skip);
        $data = $qb->getQuery()->execute();

        return [
            'data' => $data,
            'total' => $total,
        ];
    }
}
