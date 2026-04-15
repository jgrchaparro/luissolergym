<?php

namespace App\Controller;

use App\Document\TipoMensualidad;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TipoMensualidadController extends AbstractController
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    #[Route('/tipo-mensualidad/', name: 'tipo_mensualidad_list')]
    public function listAction(): Response
    {
        return $this->render('tipo_mensualidad/list.html.twig');
    }

    #[Route('/tipo-mensualidad/listar/', name: 'tipo_mensualidad_listar_json', options: ['expose' => true])]
    public function listarJsonAction(Request $request): JsonResponse
    {
        $busqueda = $request->query->get('busqueda', '');
        $limit = (int) $request->query->get('limit', 10);
        $skip = (int) $request->query->get('skip', 0);

        /** @var \App\Repository\TipoMensualidadRepository $repo */
        $repo = $this->documentManager->getRepository(TipoMensualidad::class);
        $resultado = $repo->buscarPaginado($busqueda, $limit, $skip);

        $data = [];
        foreach ($resultado['data'] as $tipo) {
            $data[] = [
                'id' => $tipo->getId(),
                'codigo' => $tipo->getCodigo(),
                'descripcion' => $tipo->getDescripcion(),
                'monto' => $tipo->getMonto(),
                'fechaCreacion' => $tipo->getFechaCreacion() ? $tipo->getFechaCreacion()->format('d/m/Y H:i') : '',
            ];
        }

        return new JsonResponse(['data' => $data, 'total' => $resultado['total']]);
    }

    #[Route('/tipo-mensualidad/obtener/', name: 'tipo_mensualidad_obtener', options: ['expose' => true])]
    public function obtenerAction(Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        $tipo = $this->documentManager->getRepository(TipoMensualidad::class)->find($id);

        if (!$tipo) {
            return new JsonResponse(['success' => false, 'error' => 'Tipo de mensualidad no encontrado'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'tipo' => [
                'id' => $tipo->getId(),
                'codigo' => $tipo->getCodigo(),
                'descripcion' => $tipo->getDescripcion(),
                'monto' => $tipo->getMonto(),
            ],
        ]);
    }

    #[Route('/tipo-mensualidad/guardar/', name: 'tipo_mensualidad_guardar', options: ['expose' => true])]
    public function guardarAction(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $id = $params['id'] ?? null;

        if ($id) {
            $tipo = $this->documentManager->getRepository(TipoMensualidad::class)->find($id);
            if (!$tipo) {
                return new JsonResponse(['success' => false, 'error' => 'Tipo de mensualidad no encontrado'], 404);
            }
        } else {
            $tipo = new TipoMensualidad();
        }

        $tipo->setCodigo($params['codigo'] ?? null);
        $tipo->setDescripcion($params['descripcion'] ?? null);
        $tipo->setMonto(isset($params['monto']) ? (float) $params['monto'] : 0.0);

        $this->documentManager->persist($tipo);
        $this->documentManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $id ? 'Tipo de mensualidad actualizado' : 'Tipo de mensualidad registrado',
            'id' => $tipo->getId(),
        ]);
    }

    #[Route('/tipo-mensualidad/eliminar/', name: 'tipo_mensualidad_eliminar', options: ['expose' => true])]
    public function eliminarAction(Request $request): JsonResponse
    {
        $id = $request->query->get('id');
        $tipo = $this->documentManager->getRepository(TipoMensualidad::class)->find($id);

        if (!$tipo) {
            return new JsonResponse(['success' => false, 'error' => 'Tipo de mensualidad no encontrado'], 404);
        }

        $this->documentManager->remove($tipo);
        $this->documentManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Tipo de mensualidad eliminado']);
    }
}
