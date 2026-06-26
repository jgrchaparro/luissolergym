<?php

namespace App\Controller;

use App\Document\Cliente;
use App\Document\Movimientos;
use App\Document\Settings;
use App\Document\Tasas;
use App\Document\TipoMensualidad;
use App\Document\TipoPago;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClienteController extends AbstractController
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    #[Route('/cliente/', name: 'cliente_list')]
    public function listAction(): Response
    {
        return $this->render('cliente/list.html.twig');
    }

    #[Route('/cliente/vencidos/', name: 'cliente_vencidos')]
    public function vencidosAction(): Response
    {
        return $this->render('cliente/vencidos.html.twig');
    }

    #[Route('/cliente/tipos-mensualidad/', name: 'cliente_tipos_mensualidad', options: ['expose' => true])]
    public function tiposMensualidadAction(): JsonResponse
    {
        $tipos = $this->documentManager->getRepository(TipoMensualidad::class)->findAll();
        $data = [];
        foreach ($tipos as $tipo) {
            $data[] = [
                'id' => $tipo->getId(),
                'codigo' => $tipo->getCodigo(),
                'descripcion' => $tipo->getDescripcion(),
                'monto' => $tipo->getMonto(),
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/cliente/listar/', name: 'cliente_listar_json', options: ['expose' => true])]
    public function listarJsonAction(Request $request): JsonResponse
    {
        $busqueda = $request->query->get('busqueda', '');
        $estado = $request->query->get('estado', '');
        $orden = $request->query->get('orden', '');
        $limit = (int) $request->query->get('limit', 10);
        $skip = (int) $request->query->get('skip', 0);

        /** @var \App\Repository\ClienteRepository $repo */
        $repo = $this->documentManager->getRepository(Cliente::class);
        $resultado = $repo->buscarPaginado($busqueda, $limit, $skip, $estado, $orden);

        $hoy = new \DateTime();
        $data = [];
        foreach ($resultado['data'] as $cliente) {
            $tipoMens = $cliente->getTipoMensualidad();
            $venc = $cliente->getFechaVencimiento();
            $sinPagos = $venc === null;
            $vencido = $sinPagos || $venc < $hoy;
            $diasVencido = ($venc && $venc < $hoy) ? (int) $hoy->diff($venc)->days : null;
            $data[] = [
                'id' => $cliente->getId(),
                'cedula' => $cliente->getCedula(),
                'nombres' => $cliente->getNombres(),
                'apellidos' => $cliente->getApellidos(),
                'email' => $cliente->getEmail(),
                'telefono' => $cliente->getTelefono(),
                'tipoMensualidadCodigo' => $tipoMens ? $tipoMens->getCodigo() : '',
                'fechaVencimiento' => $venc ? $venc->format('d/m/Y') : null,
                'vencido' => $vencido,
                'sinPagos' => $sinPagos,
                'diasVencido' => $diasVencido,
                'fechaCreacion' => $cliente->getFechaCreacion() ? $cliente->getFechaCreacion()->format('d/m/Y H:i') : '',
            ];
        }

        return new JsonResponse(['data' => $data, 'total' => $resultado['total']]);
    }

    #[Route('/cliente/obtener/', name: 'cliente_obtener', options: ['expose' => true])]
    public function obtenerAction(Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        $cliente = $this->documentManager->getRepository(Cliente::class)->find($id);

        if (!$cliente) {
            return new JsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        $tipoMens = $cliente->getTipoMensualidad();
        return new JsonResponse([
            'success' => true,
            'cliente' => [
                'id' => $cliente->getId(),
                'cedula' => $cliente->getCedula(),
                'nombres' => $cliente->getNombres(),
                'apellidos' => $cliente->getApellidos(),
                'email' => $cliente->getEmail(),
                'telefono' => $cliente->getTelefono(),
                'tipo_mensualidad_id' => $tipoMens ? $tipoMens->getId() : null,
                'fechaCreacion' => $cliente->getFechaCreacion() ? $cliente->getFechaCreacion()->format('d/m/Y H:i:s') : '',
            ],
        ]);
    }

    #[Route('/cliente/{id}/detalle/', name: 'cliente_detalle', options: ['expose' => true])]
    public function detalleAction(string $id): Response
    {
        $cliente = $this->documentManager->getRepository(Cliente::class)->find($id);

        if (!$cliente) {
            throw $this->createNotFoundException('Cliente no encontrado');
        }

        /** @var \App\Repository\MovimientosRepository $movimientosRepo */
        $movimientosRepo = $this->documentManager->getRepository(Movimientos::class);
        $movimientos = $movimientosRepo->findByCliente($cliente);

        $ultimaTasa = $this->documentManager->getRepository(Tasas::class)
            ->findOneBy([], ['fechaCreacion' => -1]);

        return $this->render('cliente/detalle.html.twig', [
            'cliente' => $cliente,
            'movimientos' => $movimientos,
            'ultimaTasa' => $ultimaTasa,
        ]);
    }

    #[Route('/cliente/guardar/', name: 'cliente_guardar', options: ['expose' => true])]
    public function guardarAction(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $id = $params['id'] ?? null;

        if ($id) {
            $cliente = $this->documentManager->getRepository(Cliente::class)->find($id);
            if (!$cliente) {
                return new JsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
            }
        } else {
            $cliente = new Cliente();
        }

        $cliente->setCedula($params['cedula'] ?? null);
        $cliente->setNombres($params['nombres'] ?? null);
        $cliente->setApellidos($params['apellidos'] ?? null);
        $cliente->setEmail($params['email'] ?? null);
        $cliente->setTelefono($params['telefono'] ?? null);

        $tipoMensId = $params['tipo_mensualidad_id'] ?? null;
        if ($tipoMensId) {
            $tipoMens = $this->documentManager->getRepository(TipoMensualidad::class)->find($tipoMensId);
            $cliente->setTipoMensualidad($tipoMens);
        } else {
            $cliente->setTipoMensualidad(null);
        }

        $this->documentManager->persist($cliente);
        $this->documentManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $id ? 'Cliente actualizado correctamente' : 'Cliente registrado correctamente',
            'id' => $cliente->getId(),
        ]);
    }

    #[Route('/cliente/tipos-pago/', name: 'cliente_tipos_pago', options: ['expose' => true])]
    public function tiposPagoAction(): JsonResponse
    {
        $tipos = $this->documentManager->getRepository(TipoPago::class)->findBy([], ['codigo' => 'asc']);
        $data = [];
        foreach ($tipos as $tipo) {
            $data[] = [
                'id' => $tipo->getId(),
                'codigo' => $tipo->getCodigo(),
                'descripcion' => $tipo->getDescripcion(),
                'moneda' => $tipo->getMoneda(),
            ];
        }
        return new JsonResponse($data);
    }

    #[Route('/cliente/registrar-pago/', name: 'cliente_registrar_pago', options: ['expose' => true])]
    public function registrarPagoAction(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $clienteId = $params['cliente_id'] ?? null;
        $tipoPagoId = $params['tipo_pago_id'] ?? null;
        $monto = isset($params['monto']) ? (float) $params['monto'] : 0.0;
        $observaciones = isset($params['observaciones']) ? trim($params['observaciones']) : null;

        if (!$clienteId) {
            return new JsonResponse(['success' => false, 'error' => 'Cliente no especificado'], 400);
        }
        if (!$tipoPagoId) {
            return new JsonResponse(['success' => false, 'error' => 'Debe seleccionar un tipo de pago'], 400);
        }
        if ($monto <= 0) {
            return new JsonResponse(['success' => false, 'error' => 'El monto debe ser mayor a cero'], 400);
        }

        $cliente = $this->documentManager->getRepository(Cliente::class)->find($clienteId);
        if (!$cliente) {
            return new JsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        $tipoPago = $this->documentManager->getRepository(TipoPago::class)->find($tipoPagoId);
        if (!$tipoPago) {
            return new JsonResponse(['success' => false, 'error' => 'Tipo de pago no encontrado'], 404);
        }

        $tipoMens = $cliente->getTipoMensualidad();
        $mensualidadUsd = $tipoMens ? $tipoMens->getMonto() : 0.0;
        $moneda = $tipoPago->getMoneda();

        // Convertir lo pagado a USD según la moneda del tipo de pago y la tasa vigente.
        $tasa = $this->documentManager->getRepository(Tasas::class)
            ->findOneBy([], ['fechaCreacion' => -1]);
        $pagadoUsd = $monto;
        if ($moneda === 'VEF') {
            $usdVef = $tasa ? $tasa->getUsdVef() : 0.0;
            $pagadoUsd = $usdVef > 0 ? $monto / $usdVef : 0.0;
        } elseif ($moneda === 'COP') {
            $usdCop = $tasa ? $tasa->getUsdCop() : 0.0;
            $pagadoUsd = $usdCop > 0 ? $monto / $usdCop : 0.0;
        }

        // Vigencia que otorga el pago, proporcional a lo abonado respecto a la mensualidad:
        //   meses completos (+1 mes calendario c/u) + fracción proporcional a "Días/Mes".
        // Ej: paga 50% -> +15 días; paga 100% -> +1 mes; paga 150% -> +1 mes y 15 días.
        $settings = $this->documentManager->getRepository(Settings::class)->findOneBy([]);
        $diasMes = $settings ? $settings->getDiasMes() : 30;

        $fechaVencimiento = null;
        if ($mensualidadUsd > 0 && $pagadoUsd > 0) {
            $ratio = $pagadoUsd / $mensualidadUsd;
            $mesesEnteros = (int) floor($ratio);
            $diasExtra = (int) round(($ratio - $mesesEnteros) * $diasMes);

            if ($mesesEnteros > 0 || $diasExtra > 0) {
                $hoy = new \DateTime();
                $base = $cliente->getFechaVencimiento();
                // Acumula desde el vencimiento vigente si aún no ha vencido; si no, desde hoy.
                if (!$base || $base < $hoy) {
                    $base = $hoy;
                }
                $fechaVencimiento = clone $base;
                if ($mesesEnteros > 0) {
                    $fechaVencimiento->modify('+' . $mesesEnteros . ' months');
                }
                if ($diasExtra > 0) {
                    $fechaVencimiento->modify('+' . $diasExtra . ' days');
                }
            }
        }

        $movimiento = new Movimientos();
        $movimiento->setCliente($cliente);
        $movimiento->setTipoPago($tipoPago);
        $movimiento->setMonto($monto);
        $movimiento->setMoneda($moneda);
        $movimiento->setMontoUsdRef($mensualidadUsd > 0 ? $mensualidadUsd : null);
        $movimiento->setFechaVencimiento($fechaVencimiento);
        $movimiento->setTipoMvto('PAGO');
        $movimiento->setObservaciones($observaciones !== '' ? $observaciones : null);
        $this->documentManager->persist($movimiento);

        // Denormalizar el vencimiento y la solvencia en el cliente (para el listado de vencidos).
        if ($fechaVencimiento) {
            $cliente->setFechaVencimiento($fechaVencimiento);
            $cliente->setSolvente(true);
            $this->documentManager->persist($cliente);
        }

        $this->documentManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Pago registrado correctamente',
            'id' => $movimiento->getId(),
        ]);
    }

    #[Route('/cliente/eliminar/', name: 'cliente_eliminar', options: ['expose' => true])]
    public function eliminarAction(Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        $cliente = $this->documentManager->getRepository(Cliente::class)->find($id);

        if (!$cliente) {
            return new JsonResponse(['success' => false, 'error' => 'Cliente no encontrado'], 404);
        }

        $this->documentManager->remove($cliente);
        $this->documentManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Cliente eliminado correctamente']);
    }
}
