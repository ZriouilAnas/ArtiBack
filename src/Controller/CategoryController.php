<?php

namespace App\Controller;

use App\Entity\Category;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;


#[Route('/api/category', name: 'api_category')]

class CategoryController extends AbstractController
{

    //show all
     #[Route('/public/showall', name: 'categorys_index', methods:['get'] )]
    public function index( EntityManagerInterface $entityManager): JsonResponse
    {
         $categorys = $entityManager
            ->getRepository(Category::class)
            ->findAll();
    
        $data = [];
    
        foreach ($categorys as $category) {
           $data[] = [
               'id' => $category->getId(),
               'name' => $category->getName(),
               'description' => $category->getDescription(),
           ];
        }
    
        return $this->json($data);
    }

    // show by ID
#[Route('/public/show/{id}', name: 'category_show', methods:['get'] )]
    public function show(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);
    
        if (!$category) {
    
            return $this->json('No category found for id ' . $id, 404);
        }
    
        $data =  [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
        ];
            
        return $this->json($data);
    }
// create
     #[Route('/create', name: 'category_create', methods:['post'] )]
    public function create(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        
    $data = json_decode($request->getContent(), true);

    
    if (!isset($data['name'])) {
        return new JsonResponse(['error' => 'Category name is required'], Response::HTTP_BAD_REQUEST);
    }

    
    $category = new Category();
    $category->setName($data['name']);

    
    if (isset($data['description'])) {
        $category->setDescription($data['description']);
    }
    $category->setDescription($data['description']);
    
    $entityManager->persist($category);
    $entityManager->flush();

    
    return $this->json([
        'id' => $category->getId(),
        'name' => $category->getName(),
        'description' => $category->getDescription(),
    ], Response::HTTP_CREATED);
    }

    //  update
      #[Route('/update/{id}', name: 'category_update', methods:['put', 'patch'] )]
    public function update(EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);
    
        if (!$category) {
            return $this->json('No category found for id ' . $id, 404);
        }
    
       $data = json_decode($request->getContent(), true);

    if (isset($data['name'])) {
        $category->setName($data['name']);
    }

    if (isset($data['description'])) {
        $category->setDescription($data['description']);
    }

    $entityManager->flush();

    return new JsonResponse([
        'id' => $category->getId(),
        'name' => $category->getName(),
        'description' => $category->getDescription(),
    ]);
     
}

// delete
#[Route('/delete/{id}', name: 'category_delete', methods:['delete'] )]
    public function delete(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);
    
        if (!$category) {
            return $this->json('No category found for id ' . $id, 404);
        }
    
        $entityManager->remove($category);
        $entityManager->flush();
    
        return $this->json('Deleted a category successfully with id ' . $id);
    }

}

