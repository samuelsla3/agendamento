<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>Confirmar Cancelamento</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f8f9fa; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
        .btn-danger { background: #d32f2f; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-link { display: block; margin-top: 15px; color: #555; text-decoration: none; }
    </style>
<script src="{{ asset('js/operacoes.js') }}?v=20260924-1"></script>
    <style>
      @media (max-width: 767px) {
        *, *::before, *::after { box-sizing: border-box; }
        body { height: auto; min-height: 100vh; min-height: 100dvh; padding: 16px; }
        .card { width: 100%; min-width: 0; margin: auto; padding: 28px 20px; overflow-wrap: anywhere; }
        h1, h2 { font-size: 24px; line-height: 1.3; }
        p { line-height: 1.6; }
        button, .btn-link, .card > a { min-height: 44px; font-size: 16px; }
        .btn-danger { width: 100%; padding: 14px; }
        .btn-link, .card > a { display: flex; align-items: center; justify-content: center; }
      }
    </style>
</head>
<body>
    <div class="card">
        <h2>Confirmação de Cancelamento</h2>
        <p>Você está prestes a cancelar seu atendimento agendado para:</p>
        <p><strong>Data:</strong> {{ \Carbon\Carbon::parse($horario->data)->format('d/m/Y') }} às {{ $horario->hora }}</p>
        
        <form data-texto-envio="Cancelando atendimento…" action="{{ $urlExecutar }}" method="POST">
            @csrf
            @include('partials.operacao')
            <button type="submit" class="btn-danger">Sim, quero cancelar meu horário</button>
        </form>

        <a href="{{ url('/') }}" class="btn-link">Não, manter meu agendamento</a>
    </div>
</body>
</html>
