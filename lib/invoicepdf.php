<?php
namespace OCA\Files_Accounting;

require_once('fpdf.php');

use \OCP\User;

class PDF extends FPDF {
	function __construct() {
		parent::__construct();
	}
	function Header() {
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
		$this->Cell(0,6,"$num $label",0,1,'L',true);
		$this->Ln(0);
	}
		function ChapterTitle2($num, $label) {
		$this->SetFont('Arial','',12);
		$this->Cell(0,6,"$num $label",0,1,'L',true);
		$this->Ln(0);
	}
}
