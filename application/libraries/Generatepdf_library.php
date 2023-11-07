<?php

require_once(__DIR__ . '/../third_party/fpdf181/fpdf.php');
require_once(__DIR__ . '/../third_party/fpdf-easytable-master/exfpdf.php');
class Generatepdf_library extends exFPDF
{

    public function __construct($type = 'fleteTerminado')
    {
        parent::__construct();
        $this->type = $type;
    }

    function FancyBullet($text)
    {
        $this->Image($_SERVER["IMG_FOLDER"] . 'bullet-circle.png', 22, $this->GetY() + 1.4, 1.4, 1.4);
        $this->Cell(5);
        $this->MultiCell(0, 4, utf8_decode($text));
    }

    function Header()
    {

        // Logo
        $this->Image($_SERVER["IMG_FOLDER"] . "logo.jpg", 8, 8, 30);
        $this->SetFont('Arial', '', 10);
        $this->setY(5);
        $this->setX(200);
        $this->Cell(5, 5, "Pag. " . $this->PageNo() . '/{nb}', 0, 0, 'R');
        $this->SetFont('Arial', 'B', 20);
        $this->setY(12);
        $this->setX(45);
        if ($this->type == 'fleteTerminado') {
            $this->Cell(5, 5, "Reporte de Flete finalizado");
        }




        $this->SetFont('Arial', 'B', 8);
        /// Apartir de aqui empezamos con la tabla de productos
        $this->setY(30);
        $this->setX(135);
        $this->Ln();


        // // Header
        // $this->SetFillColor(254, 100, 46); //Fondo verde de celda
        // $this->SetTextColor(240, 255, 240); //Letra color blanco
        // for ($i = 0; $i < count($header); $i++) {
        //     $this->Cell($w[$i], 7, $header[$i], 0, 0, 'L', true);
        // }
        // $this->Ln();
    }

    public function getCellHeight($width, $text)
    {
        // Guarda la posición actual
        $startX = $this->GetX();
        $startY = $this->GetY();

        // Establece el ancho de la celda temporalmente para calcular la altura
        $this->SetX($startX);
        $this->SetY($startY);

        // Divide el texto en líneas
        $textLines = $this->MultiCell($width, 7, utf8_decode($text), 0, 'C', false);

        // Calcula la altura total necesaria para acomodar todas las líneas
        $totalHeight = count($textLines) * 7;

        // Restablece la posición original
        $this->SetX($startX);
        $this->SetY($startY);

        return $totalHeight;
    }

    // Pie de página
    // function Footer()
    // {

    //     // Posición: a 1,5 cm del final
    //     $this->SetY(-16);
    //     $this->SetFont('Arial', 'B', 10);
    //     $this->Cell(0, 8, "", 'T', 0, 'C');

    //     $this->SetY(-12);
    //     // Arial italic 8
    //     $this->SetFont('Arial', 'I', 8);
    //     // Número de página
    //     $this->SetTextColor(50, 50, 220);
    //     $this->Cell(0, 10, 'visitanos:' . "", 0, 0, 'L', 0, "");
    //     $this->Cell(0,10,'Pag. '.$this->PageNo().'/{nb}',0,0,'C');
    //     $this->Cell(0, 10, 'powered by   Mecanube.com', 0, 0, 'R', 0, 'https://mecanube.com');
    // }
}