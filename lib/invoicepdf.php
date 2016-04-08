<?php
namespace OCA\Files_Accounting;

require_once('fpdf.php');

use \OCP\User;

class PDF extends FPDF {
	function Header() {
		$logoUrl = \OCP\Config::getSystemValue('billinglogo', '');
		$logoFileType = strtoupper(pathinfo(parse_url($logoUrl, PHP_URL_PATH) , PATHINFO_EXTENSION));
		$this->Image($logoUrl, 190-2*30+10, 10, 10, 20, $logoFileType/*PNG*/);
		$this->SetFont('Arial','B',12);
		$this->Ln(1);
	}
	function Footer() {
		$this->SetY(-15);
		$this->SetFont('Arial','I',8);
		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
	}
	function ChapterTitle($num, $label) {
		$this->SetFont('Arial','',12);
		$this->SetFillColor(200,220,255);
		$this->Cell(0,6,"$num $label",0,1,'L',true);
		$this->Ln(0);
	}
		function ChapterTitle2($num, $label) {
		$this->SetFont('Arial','',12);
		$this->SetFillColor(249,249,249);
		$this->Cell(0,6,"$num $label",0,1,'L',true);
		$this->Ln(0);
	}
}
