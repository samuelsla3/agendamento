<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prontuário - {{ $aluno->nome ?? $aluno->name }}</title>
    @vite('resources/css/app.css')
<script src="{{ asset('js/operacoes.js') }}?v=20260924-1"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<div class="container mx-auto p-6 max-w-4xl">
    
    <div class="mb-4">
        <a href="{{ route('psicologa.index') }}" class="text-gray-600 hover:text-gray-900 font-semibold text-sm flex items-center gap-1">
            &larr; Voltar para a Agenda
        </a>
    </div>

    @if(session('sucesso'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('sucesso') }}
        </div>
    @endif

    <div class="bg-white p-6 rounded-lg shadow mb-6 border-l-4 border-[#00833D]">
        <h2 class="text-2xl font-bold text-gray-800">Prontuário de Atendimento</h2>
        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-gray-700">
            <p><strong>Aluno:</strong> {{ $aluno->nome ?? $aluno->name }}</p>
            <p><strong>Matrícula:</strong> {{ $aluno->matricula ?? 'Não informada' }}</p>
            <p><strong>E-mail:</strong> {{ $aluno->email }}</p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Registrar Nova Sessão</h3>
        
        <form data-texto-envio="Processando registro…" action="{{ route('prontuarios.store', $aluno->id) }}" method="POST">
            @csrf
            @include('partials.operacao')

            <div class="mb-4">
                <label for="data_sessao" class="block text-sm font-medium text-gray-700 mb-1">Data da Sessão</label>
                <input type="date" name="data_sessao" id="data_sessao" 
                       value="{{ date('Y-m-d') }}" 
                       class="w-full md:w-1/3 p-2 border border-gray-300 rounded-lg focus:ring-[#00833D] focus:border-[#00833D]" required>
            </div>

            <div class="mb-4">
                <label for="anotacoes" class="block text-sm font-medium text-gray-700 mb-1">Anotações</label>
                <textarea name="anotacoes" id="anotacoes" rows="5" 
                          class="w-full p-3 border border-gray-300 rounded-lg focus:ring-[#00833D] focus:border-[#00833D]" 
                          placeholder="Digite aqui as observações, encaminhamentos e evolução do atendimento..." required></textarea>
            </div>

            <button type="submit" class="bg-[#00833D] hover:bg-[#006630] text-white font-semibold px-5 py-2 rounded-lg shadow transition">
                Salvar Anotação
            </button>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Histórico de Sessões</h3>

        @forelse($sessoes as $sessao)
            <div class="border-b border-gray-200 pb-4 mb-4 last:border-b-0 last:pb-0 last:mb-0">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-bold text-[#00833D] bg-green-50 px-2.5 py-0.5 rounded">
                        Sessão de {{ \Carbon\Carbon::parse($sessao->data_sessao)->format('d/m/Y') }}
                    </span>

                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-400">
                            Registrado em {{ $sessao->created_at ? $sessao->created_at->format('d/m/Y H:i') : '' }}
                        </span>

                        <button type="button" 
        data-sessao="{{ json_encode(['id' => $sessao->id, 'data' => \Carbon\Carbon::parse($sessao->data_sessao)->format('Y-m-d'), 'anotacoes' => $sessao->anotacoes], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) }}" onclick="abrirModalEdicao(this)" 
        class="text-amber-500 hover:text-amber-700 p-1 rounded transition" 
        title="Editar Sessão">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
    </svg>
</button>

                        <form data-texto-envio="Processando registro…" action="{{ route('prontuarios.destroy', $sessao->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja apagar este registro?')">
                            @csrf
            @include('partials.operacao')
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 p-1 rounded transition" title="Apagar Sessão">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="text-gray-700 whitespace-pre-line bg-gray-50 p-4 rounded-lg border border-gray-100">
                    {{ $sessao->anotacoes }}
                </div>
            </div>
        @empty
            <p class="text-gray-500 italic text-center py-4">
                Nenhum registro de atendimento encontrado para este aluno.
            </p>
        @endforelse
    </div>

</div>

<div id="modalEditarSessao" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white p-6 rounded-lg max-w-lg w-full shadow-xl">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Editar Registro da Sessão</h3>
        
        <form data-texto-envio="Processando registro…" id="formEditarSessao" method="POST">
            @csrf
            @include('partials.operacao')
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Data da Sessão</label>
                <input type="date" name="data_sessao" id="edit_data_sessao" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-[#00833D] focus:border-[#00833D]" required>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Anotações</label>
                <textarea name="anotacoes" id="edit_anotacoes" rows="5" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-[#00833D] focus:border-[#00833D]" required></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="fecharModalEdicao()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-[#00833D] hover:bg-[#006630] text-white rounded-lg transition">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<div id="modalSenhaProntuario" class="fixed inset-0 bg-black/60 {{ session('pedir_senha') || $errors->has('senha') ? 'flex' : 'hidden' }} items-center justify-center z-50 p-4">
    <div class="bg-white p-6 rounded-lg max-w-md w-full shadow-2xl border-t-4 border-[#00833D]">
        <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-green-100 rounded-full text-[#00833D]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800">Acesso Restrito ao Prontuário</h3>
        </div>

        <p class="text-sm text-gray-600 mb-4">
            Por medida de segurança e sigilo profissional, digite a sua senha de acesso para visualizar o prontuário.
        </p>

        <form data-texto-envio="Processando registro…" action="{{ route('prontuarios.validar-senha') }}" method="POST">
            @csrf
            @include('partials.operacao')
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha da Psicóloga</label>
                <input type="password" name="senha" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-[#00833D] focus:border-[#00833D]" placeholder="••••••••" required autofocus>
                @error('senha')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modalSenhaProntuario').classList.add('hidden')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-[#00833D] hover:bg-[#006630] text-white font-semibold rounded-lg transition">Confirmar e Acessar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalEdicao(botao) {
    const {id, data, anotacoes} = JSON.parse(botao.dataset.sessao);
    document.getElementById('formEditarSessao').action = '/prontuarios/sessao/' + id;
    
    document.getElementById('edit_data_sessao').value = data;
    document.getElementById('edit_anotacoes').value = anotacoes;
    
    const modal = document.getElementById('modalEditarSessao');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function fecharModalEdicao() {
    const modal = document.getElementById('modalEditarSessao');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function fecharModalSenha() {
    document.getElementById('modalSenhaProntuario').classList.add('hidden');
    document.getElementById('modalSenhaProntuario').classList.remove('flex');
}
</script>

</body>
</html>
