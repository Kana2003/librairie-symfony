<?php

namespace App\Command;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-order-totals',
    description: 'Recalcule les totaux de toutes les commandes',
)]
class FixOrderTotalsCommand extends Command
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $orders = $this->orderRepository->findAll();
        $fixedCount = 0;

        foreach ($orders as $order) {
            $oldSubtotal = $order->getSubtotal();
            $oldTax = $order->getTax();
            $oldTotal = $order->getTotal();

            // Recalculer les totaux
            $order->calculateTotals();

            $newSubtotal = $order->getSubtotal();
            $newTax = $order->getTax();
            $newTotal = $order->getTotal();

            if ($oldSubtotal != $newSubtotal || $oldTax != $newTax || $oldTotal != $newTotal) {
                $fixedCount++;
                $io->text(sprintf(
                    'Commande #%s: %.2f → %.2f (sous-total), %.2f → %.2f (taxes), %.2f → %.2f (total)',
                    $order->getOrderNumber(),
                    $oldSubtotal,
                    $newSubtotal,
                    $oldTax,
                    $newTax,
                    $oldTotal,
                    $newTotal
                ));
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d commandes ont été corrigées.', $fixedCount));

        return Command::SUCCESS;
    }
}
