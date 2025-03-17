<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


#[Route('/api/card', name: 'api_card_')]
#[OA\Tag(name: 'Card', description: 'Routes for all about cards')]
class ApiCardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }
    #[OA\Get(
        description: 'List all cards with pagination',
        summary: 'Returns a paginated list of cards',
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'path',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Number of cards per page',
                in: 'path',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 100)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of paginated cards')
        ]
    )]    
    #[Route('/all/{page<\d+>?1}/{limit<\d+>?100}', name: 'api_card_all', methods: ['GET'])]
    public function cardAll(int $page = 1, int $limit = 100): JsonResponse
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->entityManager->getRepository(Card::class)->createQueryBuilder('c')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $cards = $qb->getQuery()->getResult();

        return $this->json([
            'cards' => $cards,
            'page' => $page,
            'limit' => $limit
        ]);
    }
    #[OA\Put(description: 'Return all cards in the database')]
    #[OA\Response(response: 200, description: 'List all cards')]
    #[Route('/search/{name}/{page<\d+>?1}/{limit<\d+>?20}', name: 'api_card_search', methods: ['GET'])]
    public function searchCard(string $name, int $page = 1, int $limit = 20): JsonResponse
    {
        if (strlen($name) < 3) {
            return new JsonResponse(['error' => 'Le nom doit contenir au moins 3 caractères'], 400);
        }
    
        $offset = ($page - 1) * $limit;
    
        $qb = $this->entityManager->getRepository(Card::class)->createQueryBuilder('c')
            ->where('c.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
    
        $cards = $qb->getQuery()->getResult();
    
        return $this->json([
            'cards' => $cards,
            'page' => $page,
            'limit' => $limit
        ]);
    }
    #[OA\Get(
        description: 'Search for cards with pagination',
        summary: 'Finds cards that match a given name with pagination',
        parameters: [
            new OA\Parameter(
                name: 'name',
                description: 'Part of the card name to search for',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Page number',
                in: 'path',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Number of cards per page',
                in: 'path',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of paginated cards')
        ]
    )]
    

    #[Route('/{uuid}', name: 'Show card', methods: ['GET'])]
    #[OA\Parameter(name: 'uuid', description: 'UUID of the card', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Put(description: 'Get a card by UUID')]
    #[OA\Response(response: 200, description: 'Show card')]
    #[OA\Response(response: 404, description: 'Card not found')]
    public function cardShow(string $uuid): Response
    {
        $card = $this->entityManager->getRepository(Card::class)->findOneBy(['uuid' => $uuid]);
        if (!$card) {
            return $this->json(['error' => 'Card not found'], 404);
        }
        return $this->json($card);
    }
}