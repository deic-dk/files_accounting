<?php
namespace OCA\Files_Accounting;

require_once('fpdf.php');

use \OCP\User;

class PDF extends FPDF
{
	private $billingCurrency = "EUR";
	
	function __construct()
       {
          parent::__construct();
          $this->billingCurrency = \OCA\Files_Accounting\Storage_Lib::getBillingCurrency();
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
    	$fromAddress = \OCP\Config::getSystemValue('fromaddress', '');
    	$this->MultiCell(160,6,$fromAddress,'','L');
	}


	function AddressTable($name, $user_id, $email, $address)
	{     
    	$this->SetFont('Helvetica','',12);
    	$this->MultiCell(90,6,utf8_decode($name).' / '.utf8_decode($user_id).' / '.utf8_decode($email));
    	$this->Ln();
    	$this->SetFont('Helvetica','',10);
    	$this->MultiCell(100,5,utf8_decode($address),'','L');
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
    	$e = array(" ", "Gigabyte ", $this->billingCurrency."/Gigabyte ", $this->billingCurrency." ");
    	for($i=0;$i<count($d);$i++){
	    	for($j=0;$j<count($d[0])-1;$j++){
	    		$this->Cell($w[0][$j],7,$e[$j].$d[$i][$j],$b[1][$j],0,$a[1][$j]);
	    	}
	    	$this->Ln();
		$this->Cell($w[0][0],5,$e[0].$d[$i][4],"LBR",0,$a[1][0]);
		for($j=1;$j<count($d[0])-1;$j++){
  			$this->Cell($w[0][$j],5,"","LBR",0,$a[1][0]);
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
