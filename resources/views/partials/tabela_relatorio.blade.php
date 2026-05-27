@if(count($registros) > 0)
    <h3>Total de registros encontrados: {{ count($registros) }}</h3>
    <p id="periodo-relatorio" style="font-style: italic; color: #555;">{{ $periodoStr }}</p>
    
    <table id="tabela-relatorio" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th>Aluno</th>
                <th>Matrícula</th>
                <th>Data</th>
                <th>Status</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registros as $reg)
                <tr>
                    <td>{{ $reg->nome_aluno }}</td>
                    <td>{{ $reg->matricula_aluno }}</td>
                    <td>{{ \Carbon\Carbon::parse($reg->data_atendimento)->format('d/m/Y') }}</td>
                    <td>
                        <strong class="{{ in_array($reg->status, ['Realizado']) ? 'text-success' : 'text-danger' }}">
                            {{ $reg->status }}
                        </strong>
                    </td>
                    <td>{{ $reg->observacao ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p id="sem-resultados" style="text-align: center; color: #555; margin-top: 15px;">
        Nenhum registro de atendimento encontrado para os filtros aplicados.
    </p>
@endif

<style>
    #tabela-relatorio th, #tabela-relatorio td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    .text-success { color: #00833D; }
    .text-danger { color: #dc3545; }
</style>