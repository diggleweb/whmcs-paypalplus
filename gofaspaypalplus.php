<?php
/** 
 * Módulo PayPal Plus por Mauricio Gofas (gofas.net)
 * @copyright Copyright (c) gofas.net 2016
 * @see https://gofas.net
*/
// Invoca Laravel's functions
use Illuminate\Database\Capsule\Manager as Capsule;
include __DIR__.'/gofaspaypalplus/configuration.php';

// Report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);

function gofaspaypalplus_link($params){
	
	// Parametros da configuração do Gateway
	include __DIR__.'/gofaspaypalplus/params.php';
	
	// Verifica instalação
	// Verifica DB se existe a tabela do módulo
	if (!Capsule::schema()->hasTable('gofaspaypalplus')) {
    	try {
    	//let's drop the table if it exists
		//now we create it again
		Capsule::schema()->create('gofaspaypalplus', function($table) {
			//incremented id
        	$table->increments('id');
       		//a unique column
        	$table->integer('user_id');
        	$table->string('payer_id');
        	$table->string('remembered_cards');
    	});
	
		} catch (\Exception $e) {
    		$error .= "Não foi possível criar tabela no banco de dados: {$e->getMessage()}";
		}
	}
	
	/**
	*
	* Obtem o access_token
	*
	**/
	if ( stripos($_SERVER['REQUEST_URI'], 'viewinvoice.php') ) { // if is invoice
		$GATcurl = curl_init($pp_host.'/v1/oauth2/token'); 
		curl_setopt($GATcurl, CURLOPT_POST, true); 
		curl_setopt($GATcurl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($GATcurl, CURLOPT_USERPWD, $client_id .':'. $client_secret);
		curl_setopt($GATcurl, CURLOPT_HEADER, false); 
		curl_setopt($GATcurl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($GATcurl, CURLOPT_POSTFIELDS, "grant_type=client_credentials"); 
		
		$GATresponse = curl_exec( $GATcurl );
		$GATerror = curl_error( $GATcurl );
	    $GATinfo = curl_getinfo( $GATcurl );
		curl_close( $GATcurl ); // close cURL handler
	    
		$GATarrayResponse = json_decode( $GATresponse ); // Convert the result from JSON format to a PHP array 
		$access_token = $GATarrayResponse->access_token; // Access Token 
		
		if ($GATerror) {$error .= $GATerror;} // Erro
		if ($GATarrayResponse->error) {$error .= $GATarrayResponse->error_description;}
		
		if ($debug and !$GATerror){
			echo'<br/><pre><b>Resultado da solicitação do Token (API PayPal).</b><br/>';
			echo 'Código de resposta: '.$GATinfo['http_code'];
			echo '<br/>Resposta crua: '.$GATresponse;
			echo '<br/>Token: '.$access_token;
			echo '<br/>Tempo levado: ' . $GATinfo['total_time']*1000 . 'ms';
			echo "<br/></pre>";
		} elseif ($debug and $GATerror){
			echo'<pre><b>ERRO na solicitação do Token (API PayPal).</b><br/>';
			echo 'Código de resposta: '.$GATinfo['http_code'];
			echo '<br/>Resposta crua: '.$GATresponse;
			//echo '<br/>Resposta decodificada: '.print_r($GATarrayResponse);
			echo '<br/>Erro: '.$GATerror;
			echo "<br/></pre>";
		}
		/** 
		*
		* Lista perfis de pagamento existentes
		*
		*/
		if ($access_token) {
			$LWEPcurl = curl_init($pp_host.'/v1/payment-experience/web-profiles'); 
			curl_setopt($LWEPcurl, CURLOPT_POST, false);
			curl_setopt($LWEPcurl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($LWEPcurl, CURLOPT_HEADER, false);
			curl_setopt($LWEPcurl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($LWEPcurl, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$access_token,
					'Accept: application/json',
					'Content-Type: application/json'
					)); 
		
			$LWEPresponse = curl_exec( $LWEPcurl );
			$LWEPerror = curl_error( $LWEPcurl );
	    	$LWEPinfo = curl_getinfo( $LWEPcurl );
			curl_close( $LWEPcurl ); // close cURL handler
	    
			$LWEParrayResponse = json_decode( $LWEPresponse, TRUE ); // Convert the result from JSON format to a PHP array
			
			function search_key($LWEParrayResponse, $key, $value){
				$results = array();
				if (is_array($LWEParrayResponse)) {
					if (isset($LWEParrayResponse[$key]) && $LWEParrayResponse[$key] == $value) {
						$results[] = $LWEParrayResponse;
					}
				foreach ($LWEParrayResponse as $subarray) {
					$results = array_merge($results, search_key($subarray, $key, $value));
					}
				}
				return $results;
			}
			$LWEParrayResponseClean = search_key($LWEParrayResponse, 'name', $profile_name);
			
			$experience_profile_name = $LWEParrayResponseClean['0']['name']; // Experience Profile Name
			$experience_profile_id = $LWEParrayResponseClean['0']['id']; // Experience Profile ID
			
			if ( $LWEPerror ) { $error .= $LWEPerror; } // Erro
			if ($LWEParrayResponse->error) {$error .= $LWEParrayResponse->error_description;}
			
			if ($debug and !$LWEPerror){
				echo'<pre><b>Resultado da listagem de perfis de experiência (API PayPal).</b><br/>';
				echo 'Código de resposta: '.$LWEPinfo['http_code'];
				echo '<br/>ID do Perfil de Experiência: '.$experience_profile_id;
				echo '<br/>Nome do Perfil de Experiência: '.$experience_profile_name;
				echo '<br/> KEY: '.$key ;
				echo '<br/>Resposta crua: '.$LWEPresponse;
				//echo '<br/>Resposta decodificada: '; print_r($LWEParrayResponse);
				echo "<br/></pre>";
			
			} elseif ($debug and $LWEPerror){
				echo'<pre><b>ERRO na listagem de perfis de experiência (API PayPal).</b><br/>';
				echo 'Código de resposta: '.$LWEPinfo['http_code'];
				echo '<br/>Resposta crua: '.$LWEPresponse;
				echo '<br/>Erro: '.$LWEPerror;
				echo "<br/></pre>";
			}
		}
		/** 
		*
		* Cria perfil de pagamento, se não existe nenhum ou o existente é diferente do padrão
		*
		*/
		if(!$experience_profile_name and $access_token and !$error) {
			$CWEPcurl = curl_init($pp_host.'/v1/payment-experience/web-profiles'); 
			curl_setopt($CWEPcurl, CURLOPT_POST, true);
			curl_setopt($CWEPcurl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($CWEPcurl, CURLOPT_HEADER, false);
			curl_setopt($CWEPcurl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($CWEPcurl, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$access_token,
					'Accept: application/json',
					'Content-Type: application/json'
					));
	
			curl_setopt($CWEPcurl, CURLOPT_POSTFIELDS, $experience_profile); 
		
			$CWEPresponse = curl_exec( $CWEPcurl );
			$CWEPerror = curl_error( $CWEPcurl );
	    	$CWEPinfo = curl_getinfo( $CWEPcurl );
			curl_close( $CWEPcurl ); // close cURL handler
	    
			$CWEParrayResponse = json_decode( $CWEPresponse, TRUE ); // Convert the result from JSON format to a PHP array
			$experience_profile_id = $CWEParrayResponse['id']; // Experience Profile ID
			$experience_profile_name = $CWEParrayResponse['name']; // Experience Profile Name
			
			if ( $CWEPerror ) { $error .= $CWEPerror; } // Erro
			if ($CWEParrayResponse->error) {$error .= $CWEParrayResponse->error_description;}
		
			if ($debug and !$CWEPerror){
				echo'<pre><b>Resultado da criação do perfil de experiência (API PayPal).</b><br/>';
				echo 'Código de resposta: '.$CWEPinfo['http_code'];
				echo '<br/>Perfil de Experiência: '.$experience_profile_id;
				echo '<br/>Resposta crua: '.$CWEPresponse;
				//echo '<br/>Resposta decodificada: '; print_r($CWEParrayResponse);
				echo "<br/></pre>";
			
			} elseif ($debug and $CWEPerror){
				echo'<pre><b>ERRO na criação do perfil de experiência (API PayPal).</b><br/>';
				echo 'Código de resposta: '.$CWEPinfo['http_code'];
				echo '<br/>Resposta crua: '.$CWEPresponse;
				echo '<br/>Erro: '.$CWEPerror;
				echo "<br/></pre>";
			}
		}
		/**
		*
		* Criar pagamento
		*
		*/
		// Json para gerar o pagamento
		$payment = '{
			"intent": "sale",
			"experience_profile_id": "'.$experience_profile_id.'",
			"payer":{
				"payment_method": "paypal"
				},
				"transactions":[
				{
					"amount":{
						"currency": "BRL",
						"total": "'.$invoiceAmount.'",
						"details":{
							"shipping": "0",
							"subtotal": "'.$invoiceAmount.'",
							"shipping_discount": "0.00",
							"insurance": "0.00",
							"handling_fee": "0.00",
							"tax": "0.00"
							}
						},
					"description": "'.$invoiceDescription.'",
					"payment_options":{
						"allowed_payment_method": "IMMEDIATE_PAY"
						},
						"item_list":{
							"shipping_address":{
								"recipient_name": "'.$firstname.' '.$lastname.'",
								"line1": "'.$address1.'",
								"line2": "'.$address2.'",
								"city": "'.$city.'",
								"country_code": "BR",
								"postal_code": "'.$postcode.'",
								"state": "'.$state.'",
								"phone": "'.(string)$phone.'"
								},
							"items":[
								{
								"name": "'.$companyName.'",
								"description": "'.$invoiceDescription.'",
								"quantity": "1",
								"price": "'.$invoiceAmount.'",
								"tax": "0",
								"sku": "'.$invoiceID.'",
								"currency": "BRL"
								}
							]
						}
					}
				],
				"redirect_urls":{
					"return_url": "'.$systemUrl.'/viewinvoice.php?id='.$invoiceID.'&return_ppp",
      				"cancel_url": "'.$systemUrl.'/viewinvoice.php?id='.$invoiceID.'&cancel_ppp"
					}
			}';
   
   			/*
			*
			* envia solicitação para criar um pagamento
			*
			*/
			if($access_token and $experience_profile_id and !$error) {
				$CPcurl = curl_init($pp_host.'/v1/payments/payment/'); 
				curl_setopt($CPcurl, CURLOPT_POST, true);
				curl_setopt($CPcurl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($CPcurl, CURLOPT_HEADER, false);
				curl_setopt($CPcurl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($CPcurl, CURLOPT_HTTPHEADER, array(
						'Authorization: Bearer '.$access_token,
						'Accept: application/json',
						'Content-Type: application/json'
						));
	
				curl_setopt($CPcurl, CURLOPT_POSTFIELDS, $payment); 
		
				$CPresponse = curl_exec( $CPcurl );
				$CPerror = curl_error( $CPcurl );
	    		$CPinfo = curl_getinfo( $CPcurl );
				curl_close( $CPcurl ); // close cURL handler
	    
				$CParrayResponse = json_decode( $CPresponse, TRUE ); // Convert the result from JSON format to a PHP array 
				$paymentId = $CParrayResponse['id']; // ID do pagamento, usado para efetuar o pagamento
				$approval_url = $CParrayResponse['links']['1']['href']; // URL do pagamento, usado para montar o iframe
				
				if($CPerror) {$error .= $CPerror;} // Erro
				if ($CParrayResponse->error) {$error .= $CParrayResponse->error_description;}
		
				if ($debug and !$CPerror){
					echo'<pre><b>Resultado da solicitação de criação de pagamento (API PayPal).</b><br/>';
					echo 'Código de resposta: '.$CPinfo['http_code'];
					echo '<br/> Approval URL: '.$approval_url;
					echo '<br/>Resposta crua: '.$CPresponse;
					//echo '<br/>Resposta decodificada: '; print_r($CParrayResponse);
					echo "<br/></pre>";
				} elseif ($debug and $CPerror){
					echo'<pre><b>ERRO na solicitação de criação de pagamento (API PayPal).</b><br/>';
					echo 'Código de resposta: '.$CPinfo['http_code'];
					echo '<br/>Resposta crua: '.$CPresponse;
					echo '<br/>Erro: '.$CPerror;
					echo "<br/></pre>";
				}
			}
			/**
			*
			* JavaScript debug
			* 
			*/
			
			$rememberedCards_data = Capsule::table('gofaspaypalplus')
                        ->where('user_id', $userID )
						->select('user_id','remembered_cards')
                        ->get();
						
			$rememberedCards = end($rememberedCards_data)->remembered_cards;
			$rememberedCardsUserId = end($rememberedCards_data)->user_id;
			
			if ($debug) {
				echo'<pre><b>Resultados da execução do pagamento via AJAX / JavaScript.</b><br/>';
				echo '<span id="gpppjsdebug"></span><br/>';
				echo 'Resposta Crua: <span id="gpppjsdebug2"></span><br/>';
				echo 'Payer ID: <span id="gpppjsdebugPayerId"></span><br/>';
				echo 'Remembered Cards: <span id="gpppjsdebugrememberedCards"></span><br/>';
				echo 'WH Remeb Cards: '.$rememberedCards.' - '.$rememberedCardsUserId.'<br/>';
				echo 'Retorno da execução de pagamento: <span id="executeReturn"></span></pre>';
			}
			/*
			*
			* Resultado impresso na área Visível na fatura/checkout
			*
			*/
			// payerTaxId & payerTaxIdType

			if (!$payerTaxId_2) {
				$payerTaxId = $payerTaxId_1;
				$payerTaxIdType = $payerTaxIdType_1;
				
			} elseif ($payerTaxId_2) {
				$payerTaxId = $payerTaxId_2;
				$payerTaxIdType = $payerTaxIdType_2;
			}
			$result = $css;
			$result .= '<script type="text/javascript" src="'.$systemUrl.'/assets/js/jquery.min.js"></script>';
			$result .= '<script src="https://www.paypalobjects.com/webstatic/ppplusdcc/ppplusdcc.min.js" type="text/javascript"></script>';
			$result .= '<div id="ppplus"> </div>';
			$result .= '<script type="text/javascript">var ppp = PAYPAL.apps.PPP({
					"placeholder": "ppplus",
      				"approvalUrl": "'.$approval_url.'",
      				"mode": "'.$pp_mode.'",
      				"buttonLocation": "'.$buttonLocation.'",
					"enableContinue":"continueButton",
					"disableContinue":"continueButton",
      				"preselection": "paypal",
     				"language": "pt_BR",
      				"country": "BR",
     				"disallowRememberedCards":false,
      				"payerEmail": "'.$email.'",
					"rememberedCards": "'.$rememberedCards.'",
      				"payerPhone": "'.$phone.'",
      				"payerFirstName": "'.$firstname.'",
     				"payerLastName": "'.$lastname.'",
     				"payerTaxId": "'.$payerTaxId.'",
      				"payerTaxIdType": "'.$payerTaxIdType.'",
      				"iframeHeight": "450",
      				"useraction": "continue",
      				"surcharging":false,
      				"thirdPartyPaymentMethods":[],
      				"hideAmount":true,
      				"miniBrowser":false,
      				"merchantInstallmentSelection":0,
      				"hideMxDebitCards":false,
     				"merchantInstallmentSelectionOptional":false
   				});</script>';
				//
				if ($debug) {
					$result .= '<script type="text/javascript">
					// IE and others compatible event handler
					var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
					var eventer = window[eventMethod];
					var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";
					
					// Ouça a mensagem da janela filho
					eventer(messageEvent,function(e) {
						
						var mensagem		= JSON.parse( e.data );
						var actionValue		= mensagem["action"];
							
						// Mostra confirmação de recebimento no console e debug
						console.log("Mensagem recebida do iframe:  " , mensagem); // Debug
						document.getElementById("gpppjsdebug2").innerHTML = typeof e.data + ": " + e.data; // Debug
						
						// Confirmação de renderização do iframe no debug
						if ( mensagem["result"] == "error" ) {
							document.getElementById("gpppjsdebug").innerHTML = "<br/>Erro ao carregar iframe.<br/>"; // Debug
							document.getElementById("continueButton").style.display = "none";

						} else if ( actionValue == "loaded" ) {
							
							document.getElementById("gpppjsdebug").innerHTML = "<br/>Iframe carregado com sucesso.<br/>"; // Debug
							document.getElementById("continueButton").style.display = "block";
							document.getElementById("continueButton").disabled = false;
							document.getElementById("continueButton").style.cursor = "pointer";
							
						// enableContinueButton
						} else if ( actionValue == "enableContinueButton") {
							document.getElementById("continueButton").disabled = false;
							document.getElementById("continueButton").style.cursor = "pointer";
						
						// disableContinueButton
						} else if ( actionValue == "disableContinueButton" || mensagem["result"] == "error" ) {
							document.getElementById("continueButton").disabled = true;
							document.getElementById("continueButton").style.cursor = "not-allowed";
							
						} else if ( actionValue == "checkout") {
							
							var paymentApproved		= mensagem.result["payment_approved"];
							var payerId				= mensagem.result["payer"]["payer_info"]["payer_id"];
							var PPrememberedCards	= mensagem.result["rememberedCards"];
							var cardOk				= "Dados prontos para execução do pagamento, aguarde..."; // Debug
							
							// disableContinueButton
							document.getElementById("continueButton").disabled = true;
							
							// Mostra confirmação de recebimento no console
							console.log("Mensagem recebida do iframe - 2 :  " , mensagem); // Debug
							
							// Mostra confirmação de recebimento no debug, quando ativo
							document.getElementById("gpppjsdebug").innerHTML = cardOk; // Debug
							document.getElementById("gpppjsdebug2").innerHTML = typeof e.data + ": " + e.data; // Debug
							document.getElementById("gpppjsdebugPayerId").innerHTML = payerId; // Debug
							document.getElementById("gpppjsdebugrememberedCards").innerHTML = PPrememberedCards; // Debug
							
							// Ativa lightbox
							document.getElementById("lightboxspan").innerHTML = "Processando pagamento, aguarde...";
							document.getElementById("lightbox").style.display = "block";
							
							// Envia post para criação do pagamento
							$.post("'.$systemUrl.'/modules/gateways/gofaspaypalplus/execute.php",
							// Dados enviados
							"pp_host='.$pp_host.'&access_token='.$access_token.'&payer_id=" + payerId +"&payment_id='.$paymentId.'&pp_remembered_cards=" + PPrememberedCards + "&wh_remembered_cards='.$rememberedCards.'&whmcs_admin='.$whmcsAdmin.'&user_id='.$userID.'",
							// Resposta
							function( rdata ) {
								// Executa resposta
								if ( rdata == "approved") {
									var EPresponse		= "Pagamento realizado com sucesso!"; // Debug
									var responseColor	= "green"; // Debug
									
									// Exibe confirmação antes de recarregar a página
									document.getElementById("lightboxspan").innerHTML = "Pagamento aprovado!";
									document.getElementById("lightbox").style.display = "block";
									document.getElementById("lightboxspan").style.background = "none";
									document.getElementById("lightboxspan").style.color = "green";
									
									// Recarrega a página ao confirmar o pagamento
									location.reload();
									
								} else {
									var EPresponse	= "Falha ao realizar o pagamento!" + rdata; // Debug
									var responseColor	= "red"; // Debug
									
									// Exibe erro
									document.getElementById("lightboxspan").innerHTML = "Falha ao realizar o pagamento,<br/> atualize a página e tente novamente.";
									document.getElementById("lightboxspan").style.background = "none";
									document.getElementById("lightboxspan").style.color = "red";
									document.getElementById("lightbox").style.display = "block";
								}
								console.log("Resposta da execução do pagamento: ", EPresponse + rdata); // Debug
								document.getElementById("gpppjsdebug").style.color = responseColor; // Debug
								document.getElementById("gpppjsdebug").innerHTML = EPresponse; // Debug
								document.getElementById("executeReturn").style.color = responseColor; // Debug
								document.getElementById("executeReturn").innerHTML = rdata; // Debug
								
								}
							);
						
						} // end of "if actionValue == "checkout""
					
						},false); // end of "eventer(messageEvent,function(e)"		
				</script>';
				} elseif (!$debug) {
					$result .= '
					<script type="text/javascript">
					// IE and others compatible event handler
					var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
					var eventer = window[eventMethod];
					var messageEvent = eventMethod == "attachEvent" ? "onmessage" : "message";
					
					// Ouça a mensagem da janela filho
					eventer(messageEvent,function(e) {
						
						var mensagem		= JSON.parse( e.data );
						var actionValue		= mensagem["action"];
											
						// Confirmação de renderização do iframe no debug
						if ( mensagem["result"] == "error" ) {
							document.getElementById("continueButton").style.display = "none";
							
						} else if ( actionValue == "loaded") {
							
							// document.getElementById("gpppjsdebug").innerHTML = "<br/>Iframe carregado com sucesso!<br/>"; // Debug
							document.getElementById("continueButton").style.display = "block";
							document.getElementById("continueButton").disabled = false;
							document.getElementById("continueButton").style.cursor = "pointer";
							
						// enableContinueButton
						} else if ( actionValue == "enableContinueButton") {
							document.getElementById("continueButton").disabled = false;
							document.getElementById("continueButton").style.cursor = "pointer";
						
						// disableContinueButton
						} else if ( actionValue == "disableContinueButton" || mensagem["result"] == "error" ) {
							document.getElementById("continueButton").disabled = true;
							document.getElementById("continueButton").style.cursor = "not-allowed";
							
						} else if ( actionValue == "checkout") {
							
							var paymentApproved		= mensagem.result["payment_approved"];
							var payerId				= mensagem.result["payer"]["payer_info"]["payer_id"];
							var PPrememberedCards	= mensagem.result["rememberedCards"];
							// var cardOk				= "Dados prontos para execução do pagamento, aguarde..."; // Debug
							
							// disableContinueButton
							document.getElementById("continueButton").disabled = true;
							
							// Ativa lightbox
							document.getElementById("lightboxspan").innerHTML = "Processando pagamento, aguarde...";
							document.getElementById("lightbox").style.display = "block";
							
							// Envia post para criação do pagamento
							$.post("'.$systemUrl.'/modules/gateways/gofaspaypalplus/execute.php",
							// Dados enviados
							"pp_host='.$pp_host.'&access_token='.$access_token.'&payer_id=" + payerId +"&payment_id='.$paymentId.'&pp_remembered_cards=" + PPrememberedCards + "&wh_remembered_cards='.$rememberedCards.'&whmcs_admin='.$whmcsAdmin.'&user_id='.$userID.'",
							// Resposta
							function( rdata ) {
								// Executa resposta
								if ( rdata == "approved") {
																		
									// Exibe confirmação antes de recarregar a página
									document.getElementById("lightboxspan").innerHTML = "Pagamento aprovado!";
									document.getElementById("lightbox").style.display = "block";
									document.getElementById("lightboxspan").style.background = "none";
									document.getElementById("lightboxspan").style.color = "green";
									
									// Recarrega a página ao confirmar o pagamento
									location.reload();
									
								} else {
																	
									// Erro
									document.getElementById("lightboxspan").innerHTML = "Falha ao realizar o pagamento,<br/>atualize a página e tente novamente.";
									document.getElementById("lightboxspan").style.background = "none";
									document.getElementById("lightboxspan").style.color = "red";
									document.getElementById("lightbox").style.display = "block";
								}
																
								}
							);
						
						} // end of "if actionValue == "checkout""
					
						},false); // end
						</script>';
				}
			$result .= $payButton;			
			$result .= '<div id="lightbox"><span id="lightboxspan"></span> </div>';
	}
	//
	if ( !$error ) {
		return $result;
		
	} elseif ( $error and !$emailonError) {
		return $error;
		
	}
}
?>
