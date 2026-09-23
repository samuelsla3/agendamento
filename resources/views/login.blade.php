<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Aluno</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ time() }}">
    <!-- Ícones do Bootstrap para os olhos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input {
            width: 100%;
            padding-right: 40px; /* Garante espaço para o ícone */
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            cursor: pointer;
            color: #6c757d;
            font-size: 1.2rem;
            user-select: none;
        }
        .toggle-password:hover {
            color: #333;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <form method="POST" action="{{ route('login.post') }}">
        @csrf 

        <h2>Login com SUAP</h2>
        
        @if (session('sucesso'))
            <p style="color: green; text-align: center; font-weight: bold;">
                {{ session('sucesso') }}
            </p>
        @endif
        
        @if ($errors->any())
            <p style="color: red; text-align: center; font-weight: bold;">
                {{ $errors->first('matricula') ?? $errors->first('erro') }}
            </p>
        @endif

        <div class="form-group">
            <label for="matricula">Matrícula</label>
            <input type="text" id="matricula" name="matricula" value="{{ old('matricula') }}" placeholder="Digite sua matrícula" required />
        </div>

        <div class="form-group">
            <label for="senha">Senha</label>
            <div class="password-wrapper">
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha do SUAP" required>
                <i class="bi bi-eye-slash toggle-password" id="toggleIcon" onclick="toggleSenha()"></i>
            </div>
        </div>
        
        <button type="submit" class="btn">Entrar</button>
        
        {{-- Link de registro removido pois o cadastro é automático via SUAP --}}
    </form>
</div>

<script>
    function toggleSenha() {
        const inputSenha = document.getElementById('senha');
        const icone = document.getElementById('toggleIcon');

        if (inputSenha.type === 'password') {
            inputSenha.type = 'text';
            icone.classList.remove('bi-eye-slash');
            icone.classList.add('bi-eye');
        } else {
            inputSenha.type = 'password';
            icone.classList.remove('bi-eye');
            icone.classList.add('bi-eye-slash');
        }
    }
</script>

</body>
</html>