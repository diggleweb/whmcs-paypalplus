<?php
/** 
 * Módulo PayPal Plus por Mauricio Gofas (gofas.net)
 * @copyright Copyright (c) gofas.net 2016
 * @see https://gofas.net
 *
 * Executa o pagamento
 *
 */
 // Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Puxa parâmetros de configuração do gateway
$gatewayParams	= getGatewayVariables('gofaspaypalplus');
	// Debug
	//echo '<pre>';
	//print_r($gatewayParams);
	//echo '</pre>';
	
// Morre se o módulo está inativo.
if (!$gatewayParams['type']) {
	die("Module Not Activated");
}
//Function to check if the request is an AJAX request
function is_ajax() {
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

if (is_ajax()) {
		$pp_host					= $_POST['pp_host'];
		$access_token				= $_POST['access_token'];
		$payment_id					= $_POST['payment_id'];
		$payer_id					= $_POST["payer_id"];
		$user_id					= $_POST['user_id'];
		$wh_remembered_cards		= $_POST['wh_remembered_cards'];
		$pp_remembered_cards		= $_POST['pp_remembered_cards'];
		$efetuePayment				= '{ "payer_id" : "'.$payer_id.'" }';
		
		$EPcurl = curl_init($pp_host.'/v1/payments/payment/'.$payment_id.'/execute/'); 
		curl_setopt($EPcurl, CURLOPT_POST, true);
		curl_setopt($EPcurl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($EPcurl, CURLOPT_HEADER, false);
		curl_setopt($EPcurl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($EPcurl, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer '.$access_token,
			'Accept: application/json',
			'Content-Type: application/json'
		));
	
		curl_setopt($EPcurl, CURLOPT_POSTFIELDS, $efetuePayment ); 
		
		$EPresponse			= curl_exec( $EPcurl );
		$EPerror			= curl_error( $EPcurl );
		$EPinfo				= curl_getinfo( $EPcurl );
		
		curl_close( $EPcurl ); // close cURL handler
	    
		$EParrayResponse	= json_decode( $EPresponse, true ); // Convert the result from JSON format to a PHP array
		$EPpaytId			= $EParrayResponse['id'];
		$paymentState		= $EParrayResponse['state'];
		$EPsku				= $EParrayResponse['transactions']['0']['item_list']['items']['0']['sku'];
		$EPamount			= $EParrayResponse['transactions']['0']['item_list']['items']['0']['price'];
		$EPpaymentFee		= $EParrayResponse['transactions']['0']['related_resources']['0']['sale']['transaction_fee']['value'];
		$EPpayState			= $EParrayResponse['transactions']['0']['related_resources']['0']['sale']['state'];
		
		if($paymentState == "approved") {
			
			// Registra transação no log do whmcs
			logTransaction($gatewayParams['paymentmethod'], $EPresponse, $paymentState.'->'.$EPpayState);

			// Adiciona o pagamento a fatura =)
			addInvoicePayment(
				$EPsku, // Invoice ID
				$EPpaytId, // Transaction ID
				$EPamount, // Payment Amount
				$EPpaymentFee, // Payment Fee
				$gatewayParams['paymentmethod']
				);
				
			// completa a confirmação de pagamento na visualização da fatura
			echo $paymentState;

			// Salva remembered_cards ID
			if (!$wh_remembered_cards) {
			
				try {
					$add_wh_remembered_cards = Capsule::table('gofaspaypalplus')
						->insert([
						'user_id' => $user_id,
						'payer_id' => $payer_id,
						'remembered_cards' => $pp_remembered_cards, ]);
					//echo $add_wh_remembered_cards;
				} catch (\Exception $e) {
    				echo "Não foi possível gravar os dados do cartão do cliente. {$e->getMessage()}";
				} 
			} elseif ( $wh_remembered_cards and $pp_remembered_cards and $pp_remembered_cards != $wh_remembered_cards) {
				try {
				$add_wh_remembered_cards = Capsule::table('gofaspaypalplus')
					->where('user_id', $user_id)
					->update([ 'remembered_cards' => $pp_remembered_cards ]);
					//echo $add_wh_remembered_cards;
				} catch (\Exception $e) {
    				echo "Não foi possível atualizar os dados do cartão do cliente. {$e->getMessage()}";
				}
			}
			// End Salva remembered_cards ID
		} elseif ($EPerror) {
			echo 'Erro: '.$EPerror;
		} else {
			echo 'Erro: '.$EParrayResponse;
		}
	}
?>