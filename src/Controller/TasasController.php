<?php

namespace App\Controller;

use App\Document\Tasas;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TasasController extends AbstractController
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    #[Route('/tasas/', name: 'tasas_list')]
    public function listAction(): Response
    {
        return $this->render('tasas/list.html.twig');
    }

    #[Route('/tasas/listar/', name: 'tasas_listar_json', options: ['expose' => true])]
    public function listarJsonAction(Request $request): JsonResponse
    {
        $busqueda = $request->query->get('busqueda', '');
        $limit = (int) $request->query->get('limit', 10);
        $skip = (int) $request->query->get('skip', 0);

        /** @var \App\Repository\TasasRepository $repo */
        $repo = $this->documentManager->getRepository(Tasas::class);
        $resultado = $repo->buscarPaginado($busqueda, $limit, $skip);

        $data = [];
        foreach ($resultado['data'] as $tasa) {
            $data[] = [
                'id' => $tasa->getId(),
                'usd_vef' => $tasa->getUsdVef(),
                'usd_cop' => $tasa->getUsdCop(),
                'tasa' => $tasa->getTasa(),
                'fechaCreacion' => $tasa->getFechaCreacion() ? $tasa->getFechaCreacion()->format('d/m/Y H:i') : '',
            ];
        }

        return new JsonResponse(['data' => $data, 'total' => $resultado['total']]);
    }

    #[Route('/tasas/guardar/', name: 'tasas_guardar', options: ['expose' => true])]
    public function guardarAction(Request $request): JsonResponse
    {
        $params = $request->query->all();

        $usdVef = isset($params['usd_vef']) ? (float) $params['usd_vef'] : 0.0;
        $usdCop = isset($params['usd_cop']) ? (float) $params['usd_cop'] : 0.0;

        if ($usdVef <= 0) {
            return new JsonResponse(['success' => false, 'error' => 'USD/VEF debe ser mayor a cero'], 400);
        }
        if ($usdCop <= 0) {
            return new JsonResponse(['success' => false, 'error' => 'USD/COP debe ser mayor a cero'], 400);
        }

        $tasa = new Tasas();
        $tasa->setUsdVef($usdVef);
        $tasa->setUsdCop($usdCop);
        $tasa->calcularTasa();

        $this->documentManager->persist($tasa);
        $this->documentManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Tasa registrada correctamente',
            'id' => $tasa->getId(),
            'tasa' => $tasa->getTasa(),
        ]);
    }
}
