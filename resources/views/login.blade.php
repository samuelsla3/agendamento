<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Aluno</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ time() }}">
</head>
<body>

<div class="auth-container">
    <form method="POST" action="{{ route('login') }}">
        @csrf 

        <h2>Login do Aluno</h2>
        
        @if (session('sucesso'))
            <p style="color: green; text-align: center; font-weight: bold;">
                {{ session('sucesso') }}
            </p>
        @endif
        
        @if ($errors->any())
            <p style="color: red; text-align: center; font-weight: bold;">
                {{ $errors->first('erro') }}
            </p>
        @endif

        <div class="form-group">
            <label for="matricula">Matrícula</label>
            <input type="text" id="matricula" name="matricula" value="{{ old('matricula') }}" required />
        </div>
        <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        
        <button type="submit" class="btn">Entrar</button>
        
        <p class="auth-link">
            <a href="/registro">Criar conta</a>
        </p>
    </form>
</div>

</body>
</html>