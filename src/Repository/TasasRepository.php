<?php

namespace App\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class TasasRepository extends DocumentRepository
{
    public function buscarPaginado(string $busqueda = '', int $limit = 10, int $skip = 0): array
    {
        $qb = $this->createQueryBuilder();

        if ($busqueda !== '' && is_numeric($busqueda)) {
            $valor = (float) $busqueda;
            $qb->addOr($qb->expr()->field('usd_vef')->equals($valor));
            $qb->addOr($qb->expr()->field('usd_cop')->equals($valor));
            $qb->addOr($qb->expr()->field('tasa')->equals($valor));
        }

        $qbCount = clone $qb;
        $total = $qbCount->count()->getQuery()->execute();

        $qb->sort('fechaCreacion', 'desc')->limit($limit)->skip($skip);
        $tasas = $qb->getQuery()->execute();

        return [
            'data' => $tasas,
            'total' => $total,
        ];
    }
}
