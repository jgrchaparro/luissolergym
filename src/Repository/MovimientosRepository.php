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

    /**
     * Lista de pagos filtrada por rango de fechas y (opcionalmente) por miembros.
     *
     * @param Cliente[]|null $clientes null = sin filtro de miembro; [] = sin coincidencias
     *
     * @return array{data: array, total: int, totales: array<string, float>}
     */
    public function buscarPaginado(?\DateTime $desde, ?\DateTime $hasta, ?array $clientes, int $limit, int $skip): array
    {
        $totalesVacios = ['USD' => 0.0, 'VEF' => 0.0, 'COP' => 0.0];

        // Si se filtró por miembro y no hubo coincidencias, evitamos consultar.
        if ($clientes !== null && count($clientes) === 0) {
            return ['data' => [], 'total' => 0, 'totales' => $totalesVacios];
        }

        $qb = $this->createQueryBuilder();

        if ($desde !== null) {
            $qb->field('fechaCreacion')->gte($desde);
        }
        if ($hasta !== null) {
            $qb->field('fechaCreacion')->lte($hasta);
        }

        if ($clientes !== null) {
            $orClientes = [];
            foreach ($clientes as $cliente) {
                $orClientes[] = $qb->expr()->field('cliente')->references($cliente);
            }
            $qb->addAnd($qb->expr()->addOr(...$orClientes));
        }

        // Total de registros y monto recaudado por moneda sobre todo el conjunto filtrado.
        $qbTotales = clone $qb;
        $todos = $qbTotales->getQuery()->execute()->toArray();
        $total = count($todos);
        $totales = $totalesVacios;
        foreach ($todos as $movimiento) {
            $moneda = $movimiento->getMoneda();
            if (!isset($totales[$moneda])) {
                $moneda = 'USD';
            }
            $totales[$moneda] += $movimiento->getMonto();
        }

        $qb->sort('fechaCreacion', 'desc')->limit($limit)->skip($skip);
        $data = $qb->getQuery()->execute()->toArray();

        return ['data' => $data, 'total' => $total, 'totales' => $totales];
    }
}
