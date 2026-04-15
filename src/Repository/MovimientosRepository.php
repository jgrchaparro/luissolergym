<?php

namespace App\Repository;

use App\Document\Cliente;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class MovimientosRepository extends DocumentRepository
{
    public function findByCliente(Cliente $cliente): array
    {
        return $this->createQueryBuilder()
            ->field('cliente')->references($cliente)
            ->sort('fechaCreacion', 'desc')
            ->getQuery()
            ->execute()
            ->toArray();
    }
}
