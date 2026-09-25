<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8f9fa}.card{background:white;padding:30px;margin:20px;border-radius:8px;max-width:440px;text-align:center}p{line-height:1.6}a{display:inline-block;margin-top:15px;color:#1c7ed6}</style>
</head>
<body><main class="card"><h1>{{ $titulo }}</h1><p>{{ $mensagem }}</p><a href="{{ url('/') }}">Voltar para o site</a></main></body>
</html>
