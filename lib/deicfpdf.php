<?php
namespace OCA\Files_Accounting;

require('fpdf.php');

use \OCP\User;
class PDF extends FPDF
{
	function __construct()
       {
          parent::FPDF();
       }
	// Load data
	function LoadData($file)
	{
	    // Read file lines
	    $lines = file($file);
	    $data = array();
	    foreach($lines as $line)
	        $data[] = explode(';',trim($line));
	    return $data;
	}

	function Header()
	{     
    	$this->SetFont('Helvetica','',20);
    	$this->MultiCell(160,6,"Danish e-Infrastructure Cooperation",'','L');
	}


	function AddressTable($a, $name)
	{     
    	$this->SetFont('Helvetica','',12);
    	$this->MultiCell(90,6,utf8_decode($name));
    	$this->Ln();
    	$this->SetFont('Helvetica','',10);
    	$this->MultiCell(100,5,utf8_decode($a),'','L');
    	$this->Ln();
	}

	function UserTable($w, $b, $h, $d)
	{     
	    for($i=0;$i<count($h);$i++){
	    	// Render Headings
	    	$this->SetFont('Helvetica','',10);
	    	for($j=0;$j<count($h[0]);$j++){
	    		$this->Cell($w[$j],5,$h[$i][$j],$b[0][$j],0,'L');
	    	}
	    	$this->Ln();
	    	// Render Data
	    	$this->SetFont('Helvetica','',12);
	    	for($j=0;$j<count($d[0]);$j++){
	    		$this->Cell($w[$j],7,' '.$d[$i][$j],$b[1][$j],0,'L');
	    	}
	    	$this->Ln();
	    }
	}

	function ProductTable($w, $b, $a, $h, $d, $f, $sum)
	{     
	    
    	// Render Headings
    	$this->SetFont('Helvetica','',10);
    	for($j=0;$j<count($h);$j++){
    		$this->Cell($w[0][$j],5,$h[$j],$b[0][$j],0,$a[0][$j]);
    	}
    	$this->Ln();
    	// Render Products
    	$this->SetFont('Helvetica','',12);
    	$e = array(" ", "Gigabyte ", "DKK/Gigabyte ", "DKK ");
    	for($i=0;$i<count($d);$i++){
	    	for($j=0;$j<count($d[0]);$j++){
	    		$this->Cell($w[0][$j],7,$e[$j].$d[$i][$j],$b[1][$j],0,$a[1][$j]);
	    	}
	    	$this->Ln();
    	}
		// Render Footer Headings
		$this->SetFont('Helvetica','',10);
    	for($j=0;$j<count($f);$j++){
    		$this->Cell($w[1][$j],5,$f[$j],$b[2][$j],0,$a[2][$j]);
    	}
    	$this->Ln();
    	// Render Footer SumData
		$this->SetFont('Helvetica','',12);
    	for($j=0;$j<count($sum);$j++){
    		$this->Cell($w[1][$j],7,$sum[$j],$b[3][$j],0,$a[3][$j]);
    	}
    	$this->Ln();	    
	}
	function CommentsTable($w,$b,$h,$d)
	{     
    	$this->SetFont('Helvetica','',10);
    	$this->Cell($w,5,$h,$b[0],0,'L');
    	$this->Ln();
    	// Render Data
    	$this->SetFont('Helvetica','',12);
    	$this->Cell($w,7,' '.$d,$b[1],0,'L');
    	$this->Ln();
	}

}

