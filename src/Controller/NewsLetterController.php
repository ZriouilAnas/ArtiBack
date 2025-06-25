<?php

namespace App\Controller;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\NewsLetter;

final class NewsLetterController extends AbstractController
{
    #[Route('/newsletter/get', name: 'project_index', methods:['get'] )]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        
        $newsletters = $entityManager
            ->getRepository(NewsLetter::class)
            ->findAll();
    
        $data = [];
    
        foreach ($newsletters as $newsletter) {
           $data[] = [
               'id' => $newsletter->getId(),
               'email' => $newsletter->getEmail(),
               
           ];
        }
    
        return $this->json($data);
    }
  
  
    #[Route('/newsletter/create', name: 'project_create', methods:['post'] )]
    public function create(EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
    
        if (!is_string($email) || empty($email)) {
            return $this->json(['error' => 'Email is required'], Response::HTTP_BAD_REQUEST);
        }
    
        $newsletter = new NewsLetter();
        $newsletter->setEmail($email);
    
        $entityManager->persist($newsletter);
        $entityManager->flush();
    
        return $this->json([
            'id' => $newsletter->getId(),
            'email' => $newsletter->getEmail(),
        ]);
    }
  
}
