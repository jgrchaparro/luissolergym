<?php

namespace App\Controller;

use App\Document\Cliente;
use App\Document\Movimientos;
use App\Document\Settings;
use App\Document\Tasas;
use App\Document\TipoPago;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PagoController extends AbstractController
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    #[Route('/pagos/', name: 'pago_list')]
    public function listAction(): Response
    {
        return $this->render('pagos/list.html.twig');
    }

    #[Route('/pagos/listar/', name: 'pago_listar_json', options: ['expose' => true])]
    public function listarJsonAction(Request $request): JsonResponse
    {
        $desdeStr = $request->query->get('desde', '');
        $hastaStr = $request->query->get('hasta', '');
        $busqueda = trim((string) $request->query->get('busqueda', ''));
        $limit = (int) $request->query->get('limit', 10);
        $skip = (int) $request->query->get('skip', 0);

        $desde = $desdeStr ? \DateTime::createFromFormat('Y-m-d H:i:s', $desdeStr . ' 00:00:00') : null;
        $hasta = $hastaStr ? \DateTime::createFromFormat('Y-m-d H:i:s', $hastaStr . ' 23:59:59') : null;
        if ($desde === false) {
            $desde = null;
        }
        if ($hasta === false) {
            $hasta = null;
        }

        // Filtro por miembro: resolvemos los clientes que coinciden con la búsqueda.
        $clientes = null;
        if ($busqueda !== '') {
            /** @var \App\Repository\ClienteRepository $clienteRepo */
            $clienteRepo = $this->documentManager->getRepository(Cliente::class);
            $clientes = $clienteRepo->findByBusqueda($busqueda);
        }

        /** @var \App\Repository\MovimientosRepository $repo */
        $repo = $this->documentManager->getRepository(Movimientos::class);
        $resultado = $repo->buscarPaginado($desde, $hasta, $clientes, $limit, $skip);

        $data = [];
        foreach ($resultado['data'] as $movimiento) {
            $cliente = $movimiento->getCliente();
            $tipoPago = $movimiento->getTipoPago();
            $tipoMens = $cliente ? $cliente->getTipoMensualidad() : null;
            $esDia = $movimiento->getTipoMvto() === 'DIA';

            // Referencia USD: la guardada en el movimiento o, para registros viejos, la mensualidad del miembro.
            $montoUsdRef = $movimiento->getMontoUsdRef();
            if ($montoUsdRef === null && $tipoMens) {
                $montoUsdRef = $tipoMens->getMonto();
            }

            if ($cliente) {
                $miembro = trim($cliente->getNombres() . ' ' . $cliente->getApellidos());
            } else {
                $miembro = $movimiento->getClienteOcasional() ?: 'Día de entrenamiento';
            }

            $data[] = [
                'id' => $movimiento->getId(),
                'fecha' => $movimiento->getFechaCreacion() ? $movimiento->getFechaCreacion()->format('d/m/Y H:i') : '',
                'vence' => $movimiento->getFechaVencimiento() ? $movimiento->getFechaVencimiento()->format('d/m/Y') : '',
                'miembro' => $miembro,
                'cedula' => $cliente ? $cliente->getCedula() : '',
                'esDia' => $esDia,
                'tipoPago' => $tipoPago ? ($tipoPago->getCodigo() . ' - ' . $tipoPago->getDescripcion()) : '',
                'montoMensualidadUsd' => $montoUsdRef,
                'monto' => $movimiento->getMonto(),
                'moneda' => $movimiento->getMoneda(),
                'observaciones' => $movimiento->getObservaciones(),
            ];
        }

        return new JsonResponse([
            'data' => $data,
            'total' => $resultado['total'],
            'totales' => $resultado['totales'],
        ]);
    }

    #[Route('/pagos/buscar-miembros/', name: 'pago_buscar_miembros', options: ['expose' => true])]
    public function buscarMiembrosAction(Request $request): JsonResponse
    {
        $busqueda = trim((string) $request->query->get('busqueda', ''));
        if (mb_strlen($busqueda) < 2) {
            return new JsonResponse([]);
        }

        /** @var \App\Repository\ClienteRepository $repo */
        $repo = $this->documentManager->getRepository(Cliente::class);
        $resultado = $repo->buscarPaginado($busqueda, 8, 0);

        $hoy = new \DateTime();
        $data = [];
        foreach ($resultado['data'] as $cliente) {
            $tipoMens = $cliente->getTipoMensualidad();
            $venc = $cliente->getFechaVencimiento();
            $solvente = $venc !== null && $venc >= $hoy;
            $data[] = [
                'id' => $cliente->getId(),
                'cedula' => $cliente->getCedula(),
                'nombre' => trim($cliente->getNombres() . ' ' . $cliente->getApellidos()),
                'tipoMensualidadCodigo' => $tipoMens ? $tipoMens->getCodigo() : '',
                'montoSugerido' => $tipoMens ? $tipoMens->getMonto() : null,
                'solvente' => $solvente,
                'fechaVencimiento' => $venc ? $venc->format('d/m/Y') : null,
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/pagos/tasa-actual/', name: 'pago_tasa_actual', options: ['expose' => true])]
    public function tasaActualAction(): JsonResponse
    {
        $tasa = $this->documentManager->getRepository(Tasas::class)
            ->findOneBy([], ['fechaCreacion' => -1]);
        $settings = $this->documentManager->getRepository(Settings::class)->findOneBy([]);

        return new JsonResponse([
            'usdVef' => $tasa ? $tasa->getUsdVef() : 0.0,
            'usdCop' => $tasa ? $tasa->getUsdCop() : 0.0,
            'montoUsdDia' => $settings ? $settings->getMontoUsdDia() : 0.0,
        ]);
    }

    #[Route('/pagos/registrar-dia/', name: 'pago_registrar_dia', options: ['expose' => true])]
    public function registrarDiaAction(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $tipoPagoId = $params['tipo_pago_id'] ?? null;
        $nombre = isset($params['nombre']) ? trim($params['nombre']) : null;
        $observaciones = isset($params['observaciones']) ? trim($params['observaciones']) : null;

        if (!$tipoPagoId) {
            return new JsonResponse(['success' => false, 'error' => 'Debe seleccionar un tipo de pago'], 400);
        }

        $tipoPago = $this->documentManager->getRepository(TipoPago::class)->find($tipoPagoId);
        if (!$tipoPago) {
            return new JsonResponse(['success' => false, 'error' => 'Tipo de pago no encontrado'], 404);
        }

        $settings = $this->documentManager->getRepository(Settings::class)->findOneBy([]);
        $montoUsdDia = $settings ? $settings->getMontoUsdDia() : 0.0;

        if ($montoUsdDia <= 0) {
            return new JsonResponse([
                'success' => false,
                'error' => 'No hay un "Monto USD Día" configurado. Defínalo en Configuraciones.',
            ], 400);
        }

        // El monto se calcula en el servidor según la moneda del tipo de pago y la tasa vigente.
        $tasa = $this->documentManager->getRepository(Tasas::class)
            ->findOneBy([], ['fechaCreacion' => -1]);
        $moneda = $tipoPago->getMoneda();
        $monto = $montoUsdDia;
        if ($moneda === 'VEF') {
            $monto = $montoUsdDia * ($tasa ? $tasa->getUsdVef() : 0.0);
        } elseif ($moneda === 'COP') {
            $monto = $montoUsdDia * ($tasa ? $tasa->getUsdCop() : 0.0);
        }
        $monto = round($monto, 2);

        if ($monto <= 0) {
            return new JsonResponse([
                'success' => false,
                'error' => 'No se pudo calcular el monto. Verifique la tasa vigente.',
            ], 400);
        }

        $movimiento = new Movimientos();
        $movimiento->setCliente(null);
        $movimiento->setClienteOcasional($nombre !== '' ? $nombre : null);
        $movimiento->setTipoPago($tipoPago);
        $movimiento->setMonto($monto);
        $movimiento->setMoneda($moneda);
        $movimiento->setMontoUsdRef($montoUsdDia);
        $movimiento->setFechaVencimiento(new \DateTime());
        $movimiento->setTipoMvto('DIA');
        $movimiento->setObservaciones($observaciones !== '' ? $observaciones : null);

        $this->documentManager->persist($movimiento);
        $this->documentManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Pago de día registrado correctamente',
            'id' => $movimiento->getId(),
        ]);
    }
}
