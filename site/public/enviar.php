<?php
// 1. Configurações Iniciais
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

// Define o fuso horário para o Brasil
date_default_timezone_set('America/Sao_Paulo');

// 2. Captura os dados do formulário
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido']);
    exit;
}

// 3. Sanitização e Organização das Variáveis
$nome = htmlspecialchars($data['name'] ?? 'Cliente');
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$telefone = htmlspecialchars($data['phone'] ?? '');
$passageiros = htmlspecialchars($data['passengers'] ?? '0');
$origem = htmlspecialchars($data['departure-location'] ?? '');
$destino = htmlspecialchars($data['destination'] ?? '');

// Formata as datas (que vêm como yyyy-mm-ddThh:mm do HTML)
$dataIda = isset($data['departure-date']) ? date('d/m/Y H:i', strtotime($data['departure-date'])) : '-';
$dataVolta = (!empty($data['return-date'])) ? date('d/m/Y H:i', strtotime($data['return-date'])) : 'Somente Ida';

// Limpa o telefone para usar no Link do WhatsApp (apenas números)
$telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);
$dataHoje = date('d/m/Y');

// ==================================================================
// EMAIL 1: PARA O ADMIN (RAFA'S TURISMO)
// ==================================================================

$toAdmin = "atendimento@rafasturismo.com.br"; // SEU EMAIL AQUI
$subjectAdmin = "Novo Orçamento: $nome - $dataHoje";

// Template HTML do Admin (O seu template original, adaptado com variáveis PHP)
$messageAdmin = <<<HTML
<div style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
<div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
<div style="background-color: #1a459c; padding: 30px; text-align: center;">
<h2 style="margin: 0; font-size: 24px; color: #ffffff;">Novo Orçamento</h2>
<p style="margin: 5px 0 0 0; opacity: 0.8; color: #ffffff;">Recebido em $dataHoje</p>
</div>
<div style="padding: 30px;">
<h3 style="color: #1a459c; border-bottom: 2px solid #eee; padding-bottom: 10px;">Dados do Cliente</h3>
<p><strong>Nome / Empresa:</strong><br />$nome</p>
<table style="margin-bottom: 15px;" width="100%">
<tbody>
<tr>
<td width="50%"><strong>Telefone:</strong><br />$telefone</td>
<td width="50%"><strong>Email:</strong><br />$email</td>
</tr>
</tbody>
</table>
<h3 style="color: #1a459c; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 25px;">Dados da Viagem</h3>
<div style="background-color: #f0f4ff; padding: 15px; border-left: 5px solid #1a459c; margin-bottom: 20px;">
<p style="margin: 0;"><strong>De: </strong>$origem<br /><strong>Para:</strong> $destino</p>
</div>
<table width="100%">
<tbody>
<tr>
<td><strong>Ida:</strong><br />$dataIda</td>
<td><strong>Passageiros:</strong><br />$passageiros</td>
</tr>
</tbody>
</table>
<h3 style="color: #1a459c; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 25px;">Retorno</h3>
<p><strong>Data e Horário de Volta:</strong> $dataVolta</p>

<p><strong>Obs:</strong> {$data['message']}</p>

<div style="text-align: center; margin-top: 30px;">
    <a style="background-color: #25d366; color: white; padding: 12px 25px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;" 
       href="https://wa.me/55{$telefoneLimpo}?text=Olá%20{$nome},%20tudo%20bem?%20Recebi%20sua%20solicitação%20de%20orçamento%20no%20site%20da%20Rafa's%20Turismo%20e%20gostaria%20de%20conversar%20a%20respeito."> 
       Chamar no WhatsApp 
    </a>
</div>
</div>
<div style="background-color: #eee; padding: 10px; text-align: center; font-size: 12px; color: #666;">Enviado pelo site novo</div>
</div>
</div>
HTML;

// Headers para o Admin
$headersAdmin = "MIME-Version: 1.0" . "\r\n";
$headersAdmin .= "Content-type: text/html; charset=UTF-8" . "\r\n";
$headersAdmin .= "From: Site Rafas Turismo <no-reply@rafasturismo.com.br>" . "\r\n";
$headersAdmin .= "Reply-To: $email" . "\r\n"; // Ao responder, vai para o cliente

// Envia email Admin
$sentAdmin = mail($toAdmin, $subjectAdmin, $messageAdmin, $headersAdmin);

// ==================================================================
// EMAIL 2: AUTO RESPOSTA PARA O CLIENTE
// ==================================================================

$subjectClient = "Recebemos sua solicitação - Rafa's Turismo";

// Template HTML do Cliente
$messageClient = <<<HTML
<div style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
<div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
<div style="background-color: #1a459c; padding: 40px 30px; text-align: center;">
<h2 style="margin: 0; font-size: 26px; color: #ffffff;">Solicitação Recebida!</h2>
<p style="margin: 10px 0 0 0; font-size: 16px; color: #e0e0e0;">Obrigado pelo contato.</p>
</div>
<div style="padding: 40px 30px; text-align: center;">
<p style="font-size: 18px; color: #333; margin-bottom: 20px;">Olá, <strong>$nome</strong>!</p>
<p style="font-size: 16px; color: #555; line-height: 1.6; margin-bottom: 30px;">Recebemos seus dados com sucesso. Nossa equipe já está analisando sua solicitação de orçamento e verificando a disponibilidade.</p>
<div style="background-color: #f0f4ff; border-radius: 8px; padding: 20px; margin-bottom: 30px; text-align: left;">
<p style="margin: 0; color: #1a459c; font-weight: bold; font-size: 14px; text-transform: uppercase; margin-bottom: 10px;">O que acontece agora?</p>
<ul style="margin: 0; padding-left: 20px; color: #555; font-size: 15px; line-height: 1.5;">
<li style="margin-bottom: 8px;">Vamos calcular a melhor rota e valor para você.</li>
<li>Retornaremos o contato via <strong>WhatsApp</strong> ou <strong>E-mail</strong> o mais breve possível.</li>
</ul>
</div>
<p style="font-size: 14px; color: #888; margin-bottom: 30px;">Se for algo muito urgente ou se tiver esquecido de algum detalhe, você pode nos chamar diretamente clicando abaixo:</p>
<a style="background-color: #25d366; color: white; padding: 15px 35px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block; font-size: 16px; box-shadow: 0 4px 6px rgba(37, 211, 102, 0.2);" href="https://wa.me/5531986315775?text=Olá,%20acabei%20de%20enviar%20um%20orçamento%20pelo%20site%20e%20gostaria%20de%20falar%20com%20atendimento"> Falar com Atendente </a></div>
<div style="background-color: #eee; padding: 20px; text-align: center; font-size: 12px; color: #999;">
<p style="margin: 0;">Rafa's Turismo - Transporte e Viagens</p>
<p style="margin: 5px 0 0 0;">Este é um e-mail automático, por favor aguarde nosso retorno.</p>
</div>
</div>
</div>
HTML;

// Headers para o Cliente
$headersClient = "MIME-Version: 1.0" . "\r\n";
$headersClient .= "Content-type: text/html; charset=UTF-8" . "\r\n";
$headersClient .= "From: Rafa's Turismo <atendimento@rafasturismo.com.br>" . "\r\n"; // Seu email oficial
$headersClient .= "Reply-To: atendimento@rafasturismo.com.br" . "\r\n";

// Envia email Cliente
// O '@' antes do mail suprime erros visuais caso o servidor bloqueie envio para domínios externos,
// mas o script continua rodando para te dar o feedback do admin.
$sentClient = @mail($email, $subjectClient, $messageClient, $headersClient);

// 4. Retorno para o Javascript
if ($sentAdmin) {
    echo json_encode(['success' => true, 'message' => 'Enviado com sucesso']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar email interno']);
}
?>