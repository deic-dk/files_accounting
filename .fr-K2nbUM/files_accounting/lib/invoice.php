re('fpdf.php');

class PDF extends FPDF
{
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


		function AddressTable($a)
		  	{     
			      	$this->SetFont('Helvetica','',12);
					    	$this->MultiCell(90,6,"Rasmus Jones\nrasmus@jones.com");
					    	$this->Ln();
							    	$this->SetFont('Helvetica','',10);
							    	$this->MultiCell(100,5,$a,'','L');
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
							    	$e = array(" ", "GB ", "DKK/GB ", "DKK ");
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

function createInvoice($name, $email, $address, $date, $dueDate, $ref, $articles, $vat, $price, $total, $comment) {
      $pdf = new PDF();
	  	// Logo
	  	$pdf->AddPage();
	  	$pdf->Image('logo.png', 190-2*30+10, 10, 2*30, 2*17.1, 'PNG');
			$pdf->Cell(190,2*17.1,'',0,0,0);
			$pdf->Ln();

				// Addresses
				$pdf->AddressTable($address);

				// Table 1
				$header = array(
				  		array('Date', 'Customer eMail'),
								array('Due date', 'Reference #'),
										);
					/* DATA */
					$data = array(
					  		array($date, $email),
									array($dueDate, $ref),
											);
					/* DATA */
					$widths = array(90, 100);
					$borders = array(
					  		array('LT','LTR'),
									array('LB','LBR'),
											);
						$pdf->UserTable($widths,$borders,$header,$data);

						// Gap
						$pdf->Ln();

							// Table 2
							$header = array('Description', 'Quantity', 'Unit price', 'Total amount');
							$footer = array('', 'Subtotal', 'VAT', 'Total');
								/* DATA */
								$data = $articles;
								$sumData = array('', 'DKK '.($total*(1-$vat)), 'DKK '.($total*$vat), 'DKK '.$total);
									/* DATA */
									$widths = array(
									  				array(90, 34, 33, 33),
																	array(45, 45, 50, 50),
																					);
									$borders = array(
									  		array('LTB','LTB','LTB','LTBR'),
													array('L','L','L','LR'),
															array('T','LT','LT','LTR'),
																	array('','LB','LB','LBR')
																			);
									$aligns = array(
									  		array('L','L','L','L','L'),
													array('L','R','R','R','R'),
															array('L','L','L','L'),
																	array('R','R','R','R')
																			);
									$pdf->ProductTable($widths,$borders,$aligns,$header,$data,$footer,$sumData);

									// Gap
									$pdf->Ln();

										// Table 3
										$width = 190;
										$borders = array('LTR', 'LBR');
											$header = 'Comments';
											$data = $comment;
												if($comment){
												  		$pdf->CommentsTable($width,$borders,$header,$data);
															}
												$pdf->Output();
} 

?>
