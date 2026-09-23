<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Cancelamento</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f8f9fa; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
        .btn-danger { background: #d32f2f; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-link { display: block; margin-top: 15px; color: #555; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Confirmação de Cancelamento</h2>
        <p>Você está prestes a cancelar seu atendimento agendado para:</p>
        <p><strong>Data:</strong> {{ \Carbon\Carbon::parse($horario->data)->format('d/m/Y') }} às {{ $horario->hora }}</p>
        
        <form action="{{ route('agendamento.cancelar.executar', $horario->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn-danger">Sim, quero cancelar meu horário</button>
        </form>

        <a href="{{ url('/') }}" class="btn-link">Não, manter meu agendamento</a>
    </div>
</body>
</html>