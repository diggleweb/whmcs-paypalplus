# Módulo WHMCS PayPal Plus
##Módulo de integração para WHMCS com PayPal Plus, o Checkout Transparente do PayPal.

Orgulhosamente apresentamos o módulo<strong><a href="http://whmcs-paypalplus.gofas.net/#" target="_blank"> Gofas PayPal Plus para WHMCS</a>,</strong> o primeiro módulo de checkout transparente gratuito para WHMCS!

Esse módulo permite receber pagamentos via cartão de crédito diretamente nas suas faturas do WHMCS, o seu cliente não precisa sair do seu site para finalizar o pagamento.</strong>

<h2>Demonstração da finalização do pagamento na fatura</h2>

<img src="https://raw.githubusercontent.com/gofas/whmcs-paypalplus/master/whmcs-paypalplus.gif" alt="paypalplus" style="width: 100%; height: auto;"/>

<strong>Para utilizar PayPal Plus você precisa:</strong>
<ul>
 	<li>Conta PayPal Empresarial;</li>
 	<li>CNPJ;</li>
 	<li>SSL;</li>
 	<li>Solicitar avaliação <a href="https://www.paypal.com/br/webapps/mpp/paypal-payments-pro" target="_blank">aqui</a></li>
</ul>
<h2><strong>Requisitos do sistema</strong></h2>
<ul>
 	<li>PHP &gt;= 5.4.0</li>
 	<li>WHMCS &gt;= 6.0.4</li>
</ul>
<h2>Instalação do módulo</h2>
<ol>
 	<li>Faça <a href="https://github.com/gofas/whmcs-paypalplus/zipball/master">download aqui</a>;</li>
 	<li>Descompacte, mova o arquivo <code>gofaspaypalplus.php</code> e a pasta<code> gofaspaypalplus</code> para a pasta <code>/whmcs/modules/gateways/</code> da instalação do WHMCS;</li>
 	<li>Ative o módulo;</li>
 	<li>Acesse <a href="https://developer.paypal.com/" target="_blank">developer.paypal.com</a> &gt; Dashboard &gt; My Apps &amp; Credentials &gt; REST API apps e crie um aplicativo para ser utilizado apenas com o módulo de integração. Salve as credenciais <em>Client_ID</em> e <em>Client_Secret </em>ou mantenha essa página aberta;</li>
</ol>
<h2>Configurações do módulo</h2>
<a href="https://raw.githubusercontent.com/gofas/whmcs-paypalplus/master/whmcs-paypalplus-config.png"><img src="https://raw.githubusercontent.com/gofas/whmcs-paypalplus/master/whmcs-paypalplus-config.png" alt="whmcs-paypalplus-config" style="width: 100%; height: auto;"/></a>
<ol>
 	<li><strong>Live Client ID</strong>: Insira o Client ID do modo "<strong>produção</strong>" de acesso à REST API do seu aplicativo;</li>
 	<li><strong>Live Client Secret</strong>: Insira o Client Secret do modo "<strong>produção</strong>" do seu aplicativo;</li>
 	<li><strong>Sandbox Client ID</strong>: Insira o Client ID do modo "<strong>desenvolvimento</strong>" do seu aplicativo;</li>
 	<li><strong>Sandbox Client Secret: </strong>Insira o Client Secret do modo "desenvolvimento" do seu aplicativo;</li>
 	<li><strong>Sandbox</strong>: Marque essa opção se você estiver utilizando o par de chaves "Client_Id" e "Client_Secret" do modo Sandbox (Desenvolvimento);</li>
 	<li><strong>Debug</strong>: Marque essa opção para exibir resultados e erros retornados pela API PayPal e API interna do WHMCS.<b>
Por segurança, NÃO use isso em produção, apenas para testes ou se precisar diagnosticar erros;</b></li>
 	<li><strong>Enviar email em caso de erro</strong>: Adicione o ID do departamento de suporte que será notificado em caso de erro nas transações. Deixe em branco para desativar;</li>
 	<li><strong>Administrador atribuído</strong>: Insira o nome de usuário ou ID do administrador que será atribuído as transações. Necessário para usar a API interna do WHMCS;</li>
 	<li><strong>Ordem do campo CPF ou CNPJ: </strong>Insira a ordem de exibição do campo personalizado criado para coletar o CPF ou CNPJ do cliente;</li>
 	<li><strong>Ordem do campo CNPJ</strong>: Insira a ordem de exibição do campo personalizado criado para coletar o CNPJ do cliente. Deixe em branco se você usa apenas um campo para CPF e CNPJ;</li>
 	<li><strong>Utilizar botão Finalizar Pagamento do Módulo: </strong>Marque essa opção para utilizar o botão de pagamento do módulo ao invés do botão do PayPal;</li>
 	<li><strong>Imagem do botão "Finalizar Pagamento":</strong> Insira o URL da imagem que será usada como botão "Finalizar Pagamento" (tamanho recomendado: entre 160x40px e 339x40px);</li>
</ol>
Lembre-se de que o seu feedback é muito importante para dar continuidade aos projetos, se os <em>softwares</em> que compartilhamos são úteis para o seu negócio, contribua, é simples, basta <a href="http://whmcs-paypalplus.gofas.net">comentar</a>.

<a href="http://whmcs-paypalplus.gofas.net">Dúvidas, Comentários e Suporte</a>
