<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .card { border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        .btn-cancelar { display: inline-block; padding: 10px 20px; background-color: #dc3545; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 15px; }
        .footer { margin-top: 20px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Olá, {{ $agendamento->aluno->name ?? 'Discente' }}!</h2>

        <p>Lembramos que você possui um atendimento psicológico agendado para <strong>amanhã</strong>.</p>

        <p>
    <strong>Data/Horário:</strong> 
    {{ \Carbon\Carbon::parse($agendamento->horario->data ?? $agendamento->data)->format('d/m/Y') }} 
    às {{ $agendamento->horario->hora ?? $agendamento->hora ?? $agendamento->horario->hora_inicio ?? '' }}<br>
    <strong>Local:</strong> Setor de Psicologia / IFBA
</p>

        <p>Se você <strong>puder comparecer</strong>, não precisa realizar nenhuma ação!</p>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="color: #c82333;"><strong>NÃO PODERÁ COMPARECER?</strong></p>
        <p>Por favor, cancele seu agendamento para liberar a vaga para outro estudante que precise:</p>

        <a href="{{ $urlCancelamento }}" class="btn-cancelar">
            Cancelar Meu Agendamento
        </a>

        <p class="footer">Este é um e-mail automático. Não responda a esta mensagem.</p>
    </div>
</body>
</html>