@if(session('sucesso'))
    <div class="seguranca-aviso seguranca-sucesso" role="status">{{ session('sucesso') }}</div>
@endif
@if($errors->any())
    <div class="seguranca-aviso seguranca-erro" role="alert">
        @foreach($errors->all() as $erro)<p>{{ $erro }}</p>@endforeach
    </div>
@endif
@if($usuario->psicologa_conta_teste)
    <p class="seguranca-aviso seguranca-teste">Conta de testes. Este acesso está disponível somente no ambiente local autorizado.</p>
@endif
