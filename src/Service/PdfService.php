<?php

namespace App\Service;

use App\Entity\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private Environment $twig
    ) {}

    public function generateOrderPdf(Order $order): string
    {
        // Configuration de DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);

        // Générer le HTML de la facture
        $html = $this->twig->render('pdf/order_invoice.html.twig', [
            'order' => $order,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateOrderPdfFile(Order $order, string $filename = null): string
    {
        if (!$filename) {
            $filename = 'facture_' . $order->getOrderNumber() . '.pdf';
        }

        $pdfContent = $this->generateOrderPdf($order);
        
        // Sauvegarder le fichier temporairement
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($tempPath, $pdfContent);
        
        return $tempPath;
    }
}
