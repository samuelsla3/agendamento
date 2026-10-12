@php($idCampo = $identificador ?? $campo)
<div class="form-group">
    <label for="{{ $idCampo }}">{{ $rotulo }}</label>
    <div class="seguranca-campo-senha">
        <input type="password" id="{{ $idCampo }}" name="{{ $campo }}" required
            autocomplete="{{ ($nova ?? false) ? 'new-password' : 'current-password' }}"
            @if($nova ?? false) minlength="12" maxlength="64" @else maxlength="512" @endif>
        <button type="button" data-mostrar-senha="{{ $idCampo }}" aria-controls="{{ $idCampo }}" aria-pressed="false">Mostrar</button>
    </div>
</div>
