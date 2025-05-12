<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Handler\UploadHandler;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api/product', name: 'api_product')]
final class ProductController extends AbstractController
{
    // show all
    #[Route('/public/showall', name: 'app_product')]
   public function index( EntityManagerInterface $entityManager): JsonResponse
    {
         $products = $entityManager
            ->getRepository(Product::class)
            ->findAll();
    
        $data = [];
    
        foreach ($products as $product) {
           $data[] = [
               'id' => $product->getId(),
               'name' => $product->getName(),
               'price' => $product->getPrice(),    
               'description' => $product->getDescription(),
               'category_id' => $product->getCategory() ? $product->getCategory()->getId() : null,
               'category_name' => $product->getCategory() ? $product->getCategory()->getName() : null,
               'image_url' => $product->getImageUrl(),
               'updated_at' => $product->getUpdatedAt(),
           ];
        }
    
        return $this->json($data);
    }

    // show by ID
    #[Route('/public/show/{id}', name: 'product_show', methods:['get'] )]
    public function show(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $product = $entityManager->getRepository(className: Product::class)->find($id);
    
        if (!$product) {
    
            return $this->json('No product found for id ' . $id, 404);
        }
    
        $data =  [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
            'category_id' => $product->getCategory() ? $product->getCategory()->getId() : null,
               'category_name' => $product->getCategory() ? $product->getCategory()->getName() : null,
            'image_url' => $product->getImageUrl(),
            'updated_at' => $product->getUpdatedAt(),
        ];
            
        return $this->json($data);
    }

    #[Route('/public/shoeByCategory/{id}', name: 'products_by_category')]
    public function showByCategory(EntityManagerInterface $entityManager, int $id): JsonResponse
{
    $products = $entityManager->getRepository(Product::class)->findBy(['category' => $id]);

    if (!$products) {
        return $this->json('No products found for category id ' . $id, 404);
    }

    $data = [];

    foreach ($products as $product) {
        $data[] = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
            'category_id' => $product->getCategory()?->getId(),
            'category_name' => $product->getCategory()?->getName(),
            'image_url' => $product->getImageUrl(),
        ];
    }

    return $this->json($data);
}


    // create
     #[Route('/private/create', name: 'product_create', methods:['post'] )]
    public function create(EntityManagerInterface $entityManager, Request $request): JsonResponse
{

    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

    $name = $request->get('name');
    $price = $request->get('price');
    $description = $request->get('description');
    $categoryId = $request->get('category_id');

    if (!$name) {
        return new JsonResponse(['error' => 'Product name is required'], Response::HTTP_BAD_REQUEST);
    }

    if (!$price) {
        return new JsonResponse(['error' => 'Product price is required'], Response::HTTP_BAD_REQUEST);
    }

    if (!$categoryId) {
        return new JsonResponse(['error' => 'category_id is required'], Response::HTTP_BAD_REQUEST);
    }

    $category = $entityManager->getRepository(Category::class)->find($categoryId);
    if (!$category) {
        return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
    }

    $product = new Product();
    $product->setName($name);
    $product->setPrice($price);
    $product->setDescription($description);
    $product->setCategory($category);

    /** @var UploadedFile|null $imageFile */
    $imageFile = $request->files->get('imageFile');
    if ($imageFile) {
        $product->setImageFile($imageFile);
    }

    $entityManager->persist($product);
    $entityManager->flush();

    return $this->json([
        'id' => $product->getId(),
        'name' => $product->getName(),
        'description' => $product->getDescription(),
        'price' => $product->getPrice(),
        'category_id' => $category->getId(),
        'image_url' => $product->getImageUrl()
    ], Response::HTTP_CREATED);
}


        //  update
      #[Route('/private/update/{id}', name: 'category_update', methods:['post'] )]
public function update(EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
{
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
    $product = $entityManager->getRepository(Product::class)->find($id);

    if (!$product) {
        return $this->json(['error' => 'No product found for id ' . $id], 404);
    }

    $name = $request->get('name');
    $price = $request->get('price');
    $description = $request->get('description');
   $categoryId = $request->get('category_id'); // Correct source


    if ($name) {
        $product->setName($name);
    }

    if ($price) {
        $product->setPrice($price); 
    }

    if ($description) {
        $product->setDescription($description);
    }

  if (!$categoryId) {
    return new JsonResponse([
        'error' => 'category_id is null',
        'request_data' => $request->get('name')
    ], Response::HTTP_BAD_REQUEST);
}

   

    $category = $entityManager->getRepository(Category::class)->find($categoryId);

    if (!$category) {
        return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
    }

    $product->setCategory($category); 

    // 🔽 Handle image upload if present
    /** @var UploadedFile $imageFile */
    $imageFile = $request->files->get('image');
    if ($imageFile) {
        try {
            $product->setImageFile($imageFile);
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'Image upload failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    $entityManager->flush();

    return new JsonResponse([
        'id' => $product->getId(),
        'name' => $product->getName(),
        'price' => $product->getPrice(),
        'description' => $product->getDescription(),
        'category_id' => $product->getCategory()->getId(),
        'image_url' => $product->getImageUrl(), // useful for your frontend
    ]);
}


// delete
#[Route('/private/delete/{id}', name: 'category_delete', methods:['delete'] )]
    public function delete(EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $produit = $entityManager->getRepository(Product::class)->find($id);
    
        if (!$produit) {
            return $this->json('No produit found for id ' . $id, 404);
        }
    
        $entityManager->remove($produit);
        $entityManager->flush();
    
        return $this->json('Deleted a produit successfully with id ' . $id);
    }

    
}
