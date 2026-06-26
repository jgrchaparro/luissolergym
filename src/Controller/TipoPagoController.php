<?php

namespace App\Controller;

use App\Document\TipoPago;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TipoPagoController extends AbstractController
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    #[Route('/tipo-pago/', name: 'tipo_pago_list')]
    public function listAction(): Response
    {
        return $this->render('tipo_pago/list.html.twig');
    }

    #[Route('/tipo-pago/listar/', name: 'tipo_pago_listar_json', options: ['expose' => true])]
    public function listarJsonAction(Request $request): JsonResponse
    {
        $busqueda = $request->query->get('busqueda', '');
        $limit = (int) $request->query->get('limit', 10);
        $skip = (int) $request->query->get('skip', 0);

        /** @var \App\Repository\TipoPagoRepository $repo */
        $repo = $this->documentManager->getRepository(TipoPago::class);
        $resultado = $repo->buscarPaginado($busqueda, $limit, $skip);

        $data = [];
        foreach ($resultado['data'] as $tipo) {
            $data[] = [
                'id' => $tipo->getId(),
                'codigo' => $tipo->getCodigo(),
                'descripcion' => $tipo->getDescripcion(),
                'numero' => $tipo->getNumero(),
                'titular' => $tipo->getTitular(),
                'rif' => $tipo->getRif(),
                'moneda' => $tipo->getMoneda(),
                'fechaCreacion' => $tipo->getFechaCreacion() ? $tipo->getFechaCreacion()->format('d/m/Y H:i') : '',
            ];
        }

        return new JsonResponse(['data' => $data, 'total' => $resultado['total']]);
    }

    #[Route('/tipo-pago/obtener/', name: 'tipo_pago_obtener', options: ['expose' => true])]
    public function obtenerAction(Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        $tipo = $this->documentManager->getRepository(TipoPago::class)->find($id);

        if (!$tipo) {
            return new JsonResponse(['success' => false, 'error' => 'Tipo de pago no encontrado'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'tipo' => [
                'id' => $tipo->getId(),
                'codigo' => $tipo->getCodigo(),
                'descripcion' => $tipo->getDescripcion(),
                'numero' => $tipo->getNumero(),
                'titular' => $tipo->getTitular(),
                'rif' => $tipo->getRif(),
                'moneda' => $tipo->getMoneda(),
            ],
        ]);
    }

    #[Route('/tipo-pago/guardar/', name: 'tipo_pago_guardar', options: ['expose' => true])]
    public function guardarAction(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $id = $params['id'] ?? null;

        $codigo = trim($params['codigo'] ?? '');
        $descripcion = trim($params['descripcion'] ?? '');

        if ($codigo === '' || $descripcion === '') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Código y descripción son obligatorios',
            ], 400);
        }

        if ($id) {
            $tipo = $this->documentManager->getRepository(TipoPago::class)->find($id);
            if (!$tipo) {
                return new JsonResponse(['success' => false, 'error' => 'Tipo de pago no encontrado'], 404);
            }
        } else {
            $tipo = new TipoPago();
        }

        $tipo->setCodigo($codigo);
        $tipo->setDescripcion($descripcion);
        $tipo->setNumero(isset($params['numero']) ? trim($params['numero']) : null);
        $tipo->setTitular(isset($params['titular']) ? trim($params['titular']) : null);
        $tipo->setRif(isset($params['rif']) ? trim($params['rif']) : null);

        $moneda = strtoupper(trim($params['moneda'] ?? 'USD'));
        $tipo->setMoneda(in_array($moneda, ['USD', 'VEF', 'COP'], true) ? $moneda : 'USD');

        $this->documentManager->persist($tipo);
        $this->documentManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $id ? 'Tipo de pago actualizado' : 'Tipo de pago registrado',
            'id' => $tipo->getId(),
        ]);
    }

    #[Route('/tipo-pago/eliminar/', name: 'tipo_pago_eliminar', options: ['expose' => true])]
    public function eliminarAction(Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        $tipo = $this->documentManager->getRepository(TipoPago::class)->find($id);

        if (!$tipo) {
            return new JsonResponse(['success' => false, 'error' => 'Tipo de pago no encontrado'], 404);
        }

        $this->documentManager->remove($tipo);
        $this->documentManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Tipo de pago eliminado']);
    }
}
