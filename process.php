<?php
require_once 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;

// Verificar si se han subido los archivos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['pdf']) && isset($_FILES['signature'])) {
        $pdfFile = $_FILES['pdf'];
        $signatureFile = $_FILES['signature'];

        // Validar archivos
        $allowedPdf = 'application/pdf';
        $allowedImage = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];

        if ($pdfFile['type'] !== $allowedPdf) {
            die('El archivo subido no es un PDF válido.');
        }

        if (!in_array($signatureFile['type'], $allowedImage)) {
            die('El archivo de firma debe ser una imagen válida (PNG, JPG, GIF).');
        }

        // Directorio para almacenar archivos temporales
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Mover archivos subidos
        $pdfPath = $uploadDir . basename($pdfFile['name']);
        $signaturePath = $uploadDir . basename($signatureFile['name']);

        move_uploaded_file($pdfFile['tmp_name'], $pdfPath);
        move_uploaded_file($signatureFile['tmp_name'], $signaturePath);

        // Ruta para el PDF firmado
        $signedPdfPath = $uploadDir . 'firmado_' . basename($pdfFile['name']);

        // Crear una nueva instancia de FPDI
        $pdf = new FPDI();

        // Obtener el número de páginas del PDF original
        $pageCount = $pdf->setSourceFile($pdfPath);

        // Iterar sobre cada página y añadir al nuevo PDF
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            // Añadir una página con el mismo tamaño que la original
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Si es la última página, añadir la firma
            if ($pageNo == $pageCount) {
                // Posición y tamaño de la firma (ajusta según tus necesidades)
                $x = $size['width'] - 50; // 50 unidades desde la izquierda
                $y = $size['height'] - 50; // 50 unidades desde la parte inferior
                $width = 40; // Ancho de la firma

                // Añadir la imagen de la firma
                $pdf->Image($signaturePath, $x, $y, $width);
            }
        }

        // Guardar el PDF firmado
        $pdf->Output('F', $signedPdfPath);

        // Limpiar archivos temporales (opcional)
        unlink($pdfPath);
        unlink($signaturePath);

        // Ofrecer el PDF firmado para descargar
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($signedPdfPath) . '"');
        readfile($signedPdfPath);

        // Eliminar el PDF firmado después de la descarga (opcional)
        unlink($signedPdfPath);
        exit;
    } else {
        die('No se han subido los archivos requeridos.');
    }
} else {
    die('Método de solicitud no válido.');
}
