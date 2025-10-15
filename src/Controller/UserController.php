<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    #[Route('/profile', name: 'app_user_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password', name: 'app_user_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Vérifier l'ancien mot de passe
            if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                $this->addFlash('error', 'L\'ancien mot de passe est incorrect.');
                return $this->redirectToRoute('app_user_reset_password');
            }

            // Vérifier que les nouveaux mots de passe correspondent
            if ($data['newPassword'] !== $data['confirmPassword']) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_user_reset_password');
            }

            // Hacher et enregistrer le nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
            $user->setPassword($hashedPassword);
            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès !');

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/orders', name: 'app_user_orders', methods: ['GET'])]
    public function orders(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Récupérer les commandes et recalculer les totaux
        $orders = $user->getOrders();
        foreach ($orders as $order) {
            $order->calculateTotals();
        }
        $entityManager->flush();

        return $this->render('user/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/delete-account', name: 'app_user_delete_account', methods: ['POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            // Marquer le compte comme supprimé mais conserver les données
            $user->setIsDeleted(true);
            $user->setIsActive(false);
            $user->setDeletedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été supprimé. Vous pouvez le réactiver à tout moment en contactant le support.');

            return $this->redirectToRoute('app_logout');
        }

        $this->addFlash('error', 'Une erreur est survenue lors de la suppression du compte.');
        return $this->redirectToRoute('app_user_profile');
    }

    #[Route('/download-invoice/{orderNumber}', name: 'app_user_download_invoice', methods: ['GET'])]
    public function downloadInvoice(string $orderNumber, PdfService $pdfService, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Trouver la commande appartenant à l'utilisateur
        $order = null;
        foreach ($user->getOrders() as $userOrder) {
            if ($userOrder->getOrderNumber() === $orderNumber) {
                $order = $userOrder;
                break;
            }
        }

        if (!$order) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        // Recalculer les totaux avant de générer le PDF
        $order->calculateTotals();
        $entityManager->flush();

        // Générer le PDF
        $pdfContent = $pdfService->generateOrderPdf($order);

        // Créer la réponse
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'facture_' . $orderNumber . '.pdf'
        ));

        return $response;
    }
}
