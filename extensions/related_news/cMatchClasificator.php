<?php

require_once ('cClasificator.php');

class cMatchClasificator extends cClasificator {
			
	function ClasificateNews($pNews1,$pNews2) {
						
		// Coincidencias en el titulo
		$matchTitle =  $this->ClasificateText($pNews1->summarizedTitle,$pNews1->summarizedTitleLength,$pNews2->summarizedTitle,$pNews2->summarizedTitleLength);
		$countTitle =  $pNews1->summarizedTitleLength + $pNews2->summarizedTitleLength;
		if ($countTitle == 0) $countTitle = 1;
		 
		// Coincidencias en el copete
		$matchSummary  = $this->ClasificateText($pNews1->summarizedSummary,$pNews1->summarizedSummaryLength,$pNews2->summarizedSummary,$pNews2->summarizedSummaryLength);
		$countSummary  = $pNews1->summarizedSummaryLength + $pNews2->summarizedSummaryLength;
		if ($countSummary == 0) $countSummary = 1;
		
		// Coincidencias en el cuerpo
		$matchBody = $this->ClasificateText($pNews1->summarizedBody,$pNews1->summarizedBodyLength,$pNews2->summarizedBody,$pNews2->summarizedBodyLength);
		$countBody = $pNews1->summarizedBodyLength + $pNews2->summarizedBodyLength;
		if ($countBody == 0) $countBody = 1;	
		
		return (($matchTitle / $countTitle) * $this->titleWeight +
		       ($matchSummary / $countSummary) * $this->summaryWeight +
		       ($matchBody / $countBody) * $this->bodyWeight);
		       				 
	}	

	/* Obtiene la cantidad de coincidencias entre ambas cadenas de texto */
	function ClasificateText($Text1,$Text1Len, $Text2,$Text2Len) {
							
		$TextElements  = array();
		$TextBody  = "";														
		// Paso a arreglo el string mas corto
		if ($Text1Len == 0 || $Text2Len == 0) {
			$counter = 0;
		}else{																
			$counter = 0;					
			$pos = 0;
			$TextElements = explode(" ",trim($Text1));
			$TextBody = " ".$Text2." ";
			for($i=0;$i<count($TextElements);$i++) {
				$pos = strpos($TextBody, " ".$TextElements[$i]." ");
				if ($pos !== false) {
					$counter++;				
				}				
			}						
		}	
		return $counter;
	}
	
}

?>