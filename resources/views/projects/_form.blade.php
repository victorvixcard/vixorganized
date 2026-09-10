{{-- Padrão único de formulário: label acima, ajuda opcional, erro abaixo. --}}
@csrf
<div>
    <label class="label" for="name">Nome do projeto</label>
    <input class="field {{ $errors->has('name') ? 'border-destructive' : '' }}" id="name" name="name" value="{{ old('name', $project->name ?? '') }}" required maxlength="120" autofocus>
    @error('name')<p class="error">{{ $message }}</p>@enderror
</div>

<div>
    <label class="label" for="description">Descrição e objetivo</label>
    <textarea class="field" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $project->description ?? '') }}</textarea>
    <p class="help">O que precisa existir no fim para o projeto ser considerado entregue.</p>
</div>

<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="label" for="status">Status</label>
        <select class="field" id="status" name="status">
            @foreach (config('vix.statuses') as $key => $st)
                <option value="{{ $key }}" @selected(old('status', $project->status ?? 'aguardando') === $key)>{{ $st['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="owner_id">Responsável</label>
        <select class="field" id="owner_id" name="owner_id">
            <option value="">Sem responsável</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected((int) old('owner_id', $project->owner_id ?? 0) === $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="due_date">Prazo</label>
        <input class="field font-mono" id="due_date" name="due_date" type="date" value="{{ old('due_date', isset($project) && $project->due_date ? $project->due_date->toDateString() : '') }}">
        @error('due_date')<p class="error">{{ $message }}</p>@enderror
    </div>
</div>
