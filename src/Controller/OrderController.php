<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderLine;
use App\Repository\ProductRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\OrderRepository;

#[Route('/api/order', name: 'api_product')]
class OrderController extends AbstractController
{
    #[Route('/public/commande', name: 'api_create_order', methods: ['POST'])]
public function create(
    Request $request,
    EntityManagerInterface $em,
    ProductRepository $productRepository
): JsonResponse {
    $data = json_decode($request->getContent(), true);

    if (!isset($data['items']) || !is_array($data['items'])) {
        return new JsonResponse(['error' => 'Invalid request format'], 400);
    }

    if (!isset($data['customer'])) {
        return new JsonResponse(['error' => 'Missing customer info'], 400);
    }

    $customer = $data['customer'];

    $order = new Order();
    $order->setNom( $customer['nom']);
    $order->setPrenom($customer['prenom']);
    $order->setEmail($customer['email']);
    $order->setTel($customer['tel']);
    $order->setAdress($customer['adresse']);
    $order->setZipCode($customer['codePostal']);
    $order->setEtat($customer['etat']);
    $order->setCity($customer['ville']);
    $order->setCountry($customer['pays']);
    $order->setCommentaire($customer['commentaire']);
    $order->setOrderDate(new DateTime());
    $order->setStatus('pending');

    $total = 0;

    foreach ($data['items'] as $item) {
        if (!isset($item['id'], $item['quantity'])) {
            return new JsonResponse(['error' => 'Missing product_id or quantity'], 400);
        }

        $product = $productRepository->find($item['id']);

        if (!$product) {
            return new JsonResponse(['error' => 'Invalid product ID: ' . $item['id']], 400);
        }

        $quantity = (int)$item['quantity'];

        $orderLine = new OrderLine();
        $orderLine->setProduct($product);
        $orderLine->setQuantity($quantity);
        $orderLine->setPrice($product->getPrice());
        $orderLine->setTotal($product->getPrice() * $quantity);
        $orderLine->setOrders($order);

        $order->addOrderLine($orderLine);
        $total += $orderLine->getTotal();
    }

    $order->setTotal($total);

    $em->persist($order);
    $em->flush();

    return new JsonResponse([
        'message' => 'Order created successfully',
        'order_id' => $order->getId()
    ], 201);
}

    #[Route('/public/showall', name: 'api_order_showall', methods: ['GET'])]
public function showall(OrderRepository $orderRepository): JsonResponse
{
    $orders = $orderRepository->findAll();

    $data = [];

    foreach ($orders as $order) {
        $orderLines = [];

        foreach ($order->getOrderLines() as $line) {
            $orderLines[] = [
                'product' => $line->getProduct()->getName(),
                'quantity' => $line->getQuantity(),
                'total' => $line->getTotal(),
            ];
        }

        $data[] = [
            'id' => $order->getId(),
            'email' => $order->getEmail(),
            'total' => $order->getTotal(),
            'status' => $order->getStatus(),
            'orderLines' => $orderLines,
            'createdAt' => $order->getOrderDate()->format('Y-m-d H:i:s'),
        ];
    }

    return $this->json($data);
}

    #[Route('/public/showone/{id}', name: 'api_order_showone', methods: ['GET'])]

public function showone(int $id, EntityManagerInterface $em): JsonResponse
{
    $order = $em->getRepository(Order::class)->find($id);

    if (!$order) {
        return $this->json(['message' => 'Order not found'], 404);
    }

    $data = [
        'id' => $order->getId(),
        'email' => $order->getEmail(),
        'prenom' => $order->getPrenom(),
        'nom' => $order->getNom(),
        'tel' => $order->getTel(),
        'adresse' => $order->getAdress(),
        'codePostal' => $order->getZipCode(),
        'etat' => $order->getEtat(),
        'ville' => $order->getCity(),
        'pays' => $order->getCountry(),
        'commentaire' => $order->getCommentaire(),
        'status' => $order->getStatus(),
        'total' => $order->getTotal(),
        'orderDate' => $order->getOrderDate()->format('Y-m-d H:i:s'),
        'orderLines' => [],
    ];

    foreach ($order->getOrderLines() as $line) {
        $product = $line->getProduct();
        $data['orderLines'][] = [
            'id' => $line->getId(),
            'quantity' => $line->getQuantity(),
            'total' => $line->getTotal(),
            'product' => [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'stock' => $product->getStock(),
                'description' => $product->getDescription(),
                'image_url' => $product->getImageUrl(),
                'category' => $product->getCategory()->getName(),
                'category_id' => $product->getCategory()->getId(),

            ],
        ];
    }

    return $this->json($data);
}

        #[Route('/public/{id}/status', name: 'update_status', methods: ['PUT'])]
    public function updateStatus(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $order = $em->getRepository(Order::class)->find($id);

        if (!$order) {
            return $this->json(['message' => 'Order not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['status'])) {
            return $this->json(['message' => 'Missing status'], 400);
        }

        $order->setStatus($data['status']);
        $em->flush();

        return $this->json(['message' => 'Order status updated']);
    }

        #[Route('/delete/{id}', name: 'api_order_delete', methods: ['DELETE'])]
    public function delete(int $id, OrderRepository $orderRepository, EntityManagerInterface $em): JsonResponse
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], 404);
        }

        $em->remove($order);
        $em->flush();

        return new JsonResponse(['message' => 'Order deleted successfully']);
    }

}
