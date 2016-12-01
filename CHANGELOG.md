# 0.1.6
- Agora transações que retornarem o status pending no momento da execução do pagamento, adicionam uma mensagem temporária à fatura instruindo o cliente a aguardar a confirmação de pagamento por email. Uma transação com valor de R$0.00 é gravada no WHMCS para identificar as faturas que aguardam a confirmação de pagamento;
- Adicionado callback.php que recebe e processa notificações de pagamentos recebidas via webhooks, no caso de transações que não são aprovadas instantâneamente. Notificações de pagamentos aprovados, depois de verificados adicionam o pagamento à fatura associada enviando a confirmação de pagamento ao cliente e liberando o pedido, pagamentos rejeitados disparam um email ao cliente instruindo ele a acessar a fatura para tentar realizar o pagamento novamente;
- Removidos parâmetros desnecessários;
- Segurança: Agora o parâmetro remembered_cards só é invocado quando a chave client_id pertence ao aplicativo Rest Api que memorizou o cartão;
- Segurança: Agora o token de acesso é enviado via _back end_ para o arquivo _execute.php_, responsável por executar o pagamento;

## 0.1.5
- Corrigido o erro "Warning: end() expects parameter 1 to be array";
- Adicionada a capacidade de reportar todos os erros e avisos do php, independente da configuração do servidor;

## 0.1.4
- Removida a opção "Notificar admin por e-mail em caso de erro";
- Removida a opção onde era possível escolher usar o botão do PayPal, ao inés do botão do módulo ou uma imagem;
- Melhorias nos efeitos hover do botão "Finalizar pagamento";

## 0.1.3
- Remoção de linhas obsoletas
 
## 0.1.0
- Lançamento
