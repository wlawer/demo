<?php

namespace App\Controller;

use App\Service\PropertyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PropertyController extends AbstractController
{
    #[Route('/properties', name: 'app_properties', methods: ['GET'])]
    public function getProperties(PropertyService $propertyService): JsonResponse
    {
        return $propertyService->getProperties();
    }
}
