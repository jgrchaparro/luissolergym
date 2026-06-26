<?php

namespace App\Repository;

use App\Document\Cliente;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class ClienteRepository extends DocumentRepository
{
    public function buscarPaginado(string $busqueda = '', int $limit = 10, int $skip = 0, string $estado = '', string $orden = ''): array
    {
        $qb = $this->createQueryBuilder();

        if ($busqueda !== '') {
            $regex = new \MongoDB\BSON\Regex($busqueda, 'i');
            $qb->addOr($qb->expr()->field('cedula')->equals($regex));
            $qb->addOr($qb->expr()->field('nombres')->equals($regex));
            $qb->addOr($qb->expr()->field('apellidos')->equals($regex));
        }

        // Filtro por estado de vencimiento.
        if ($estado === 'vencido') {
            // Vencidos: sin fecha de vencimiento (nunca pagaron) o ya pasada.
            $hoy = new \DateTime();
            $qb->addAnd($qb->expr()->addOr(
                $qb->expr()->field('fecha_vencimiento')->equals(null),
                $qb->expr()->field('fecha_vencimiento')->lt($hoy)
            ));
        } elseif ($estado === 'aldia') {
            // Al día: con vencimiento en el futuro (o igual a hoy).
            $hoy = new \DateTime();
            $qb->field('fecha_vencimiento')->gte($hoy);
        }

        $qbCount = clone $qb;
        $total = $qbCount->count()->getQuery()->execute();

        if ($orden === 'venc_desc') {
            $qb->sort('fecha_vencimiento', 'desc');
        } elseif ($orden === 'venc_asc') {
            $qb->sort('fecha_vencimiento', 'asc');
        }

        $qb->limit($limit)->skip($skip);
        $clientes = $qb->getQuery()->execute();

        return [
            'data' => $clientes,
            'total' => $total,
        ];
    }

    /**
     * Devuelve todos los miembros que coinciden con la búsqueda (sin paginar).
     * Usado para filtrar los pagos por miembro.
     *
     * @return Cliente[]
     */
    public function findByBusqueda(string $busqueda): array
    {
        $qb = $this->createQueryBuilder();

        if ($busqueda !== '') {
            $regex = new \MongoDB\BSON\Regex($busqueda, 'i');
            $qb->addOr($qb->expr()->field('cedula')->equals($regex));
            $qb->addOr($qb->expr()->field('nombres')->equals($regex));
            $qb->addOr($qb->expr()->field('apellidos')->equals($regex));
        }

        return $qb->getQuery()->execute()->toArray();
    }
}
