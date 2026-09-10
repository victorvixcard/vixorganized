@extends('layouts.auth')
@section('title', 'Entrar')

@section('content')
@php $phases = array_keys(config('vix.template')); @endphp

<div class="grid min-h-[100dvh] lg:grid-cols-[5fr_6fr]">
    {{-- Painel de marca. Sempre escuro, independente do tema: é bloco de identidade, não superfície de operação. --}}
    <aside class="login-visual relative hidden flex-col justify-between overflow-hidden p-10 text-[hsl(190_30%_92%)] lg:flex xl:p-14" aria-hidden="true">
        <div class="relative flex items-center gap-2.5">
            <span class="grid h-8 w-8 place-items-center rounded-md bg-[hsl(190_60%_52%)] text-[hsl(195_55%_10%)]">
                <i data-lucide="kanban" class="h-4 w-4"></i>
            </span>
            <span class="font-display text-[15px] font-bold tracking-tight">VIXORGANIZE</span>
        </div>

        <div class="relative max-w-md">
            <p class="login-rise mb-4 text-[11px] font-semibold tracking-[0.18em] text-[hsl(190_60%_62%)] uppercase" style="--d: 0s">Gestão de projetos</p>
            <h2 class="login-rise font-display text-[clamp(1.75rem,2.6vw,2.4rem)] leading-[1.15] font-extrabold tracking-tight text-white" style="--d: .06s">
                Só um projeto<br>fica na posição 1.
            </h2>
            <p class="login-rise mt-4 max-w-sm text-sm leading-relaxed text-[hsl(190_20%_78%)]" style="--d: .12s">
                Fila com prioridade única, limite de trabalho em andamento e cada fase fechada com um check. Sem reunião pra saber onde cada coisa está.
            </p>

            <ol class="mt-9 space-y-0">
                @foreach ($phases as $i => $phase)
                    <li class="login-rise relative grid grid-cols-[1.5rem_1fr] gap-x-3.5 pb-4 {{ $loop->last ? '' : 'login-step-line' }}" style="--d: {{ 0.18 + $i * 0.07 }}s">
                        <span class="grid h-6 w-6 place-items-center rounded-full border border-[hsl(190_40%_35%)] bg-[hsl(190_40%_18%)] font-mono text-[11px] text-[hsl(190_60%_62%)]">{{ $i + 1 }}</span>
                        <span class="pt-0.5 text-sm font-medium text-[hsl(190_20%_90%)]">{{ $phase }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="relative flex items-center gap-2 text-xs text-[hsl(190_20%_70%)]">
            <span class="h-1.5 w-1.5 rounded-full bg-[hsl(152_55%_55%)] animate-pulse"></span>
            Limite de <span class="font-mono text-[hsl(190_20%_90%)]">{{ config('vix.wip_limit') }}</span> projetos em andamento ao mesmo tempo
        </div>
    </aside>

    {{-- Formulário --}}
    <div class="relative flex items-center justify-center px-6 py-12 lg:px-12">
        <button type="button" class="btn-ghost btn-icon absolute top-4 right-4" @click="$store.theme.toggle()" x-data
                :aria-label="$store.theme.dark ? 'Mudar para tema claro' : 'Mudar para tema escuro'" title="Alternar tema">
            <i data-lucide="sun" class="h-4 w-4" x-show="$store.theme.dark" x-cloak></i>
            <i data-lucide="moon" class="h-4 w-4" x-show="!$store.theme.dark"></i>
        </button>

        <div class="login-rise w-full max-w-sm" style="--d: .1s">
            <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                <span class="grid h-8 w-8 place-items-center rounded-md bg-primary text-primary-foreground">
                    <i data-lucide="kanban" class="h-4 w-4"></i>
                </span>
                <span class="font-display text-[15px] font-bold tracking-tight">VIXORGANIZE</span>
            </div>

            <h1 class="font-display text-2xl font-extrabold tracking-tight">Entrar</h1>
            <p class="mt-1.5 text-sm text-muted-foreground">Acesse a fila de projetos.</p>

            <form method="post" action="{{ route('login') }}" class="mt-8 space-y-5"
                  x-data="{ show: false, sending: false, fill(email) { $refs.email.value = email; $refs.password.value = {{ Js::from($demoPassword ?? '') }}; $refs.password.focus(); } }"
                  @submit="sending = true" novalidate>
                @csrf
                <div>
                    <label class="label" for="email">E-mail</label>
                    <div class="relative">
                        <i data-lucide="mail" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        <input x-ref="email" class="field h-11 pl-10 {{ $errors->has('email') ? 'border-destructive' : '' }}" id="email" name="email" type="email"
                               value="{{ old('email') }}" required autofocus autocomplete="email" inputmode="email" placeholder="voce@empresa.com.br">
                    </div>
                    @error('email')<p class="error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label" for="password">Senha</label>
                    <div class="relative">
                        <i data-lucide="key-round" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        <input x-ref="password" class="field h-11 pr-11 pl-10" id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password">
                        <button type="button" @click="show = !show" class="absolute top-1/2 right-1 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-md text-muted-foreground hover:text-foreground"
                                :aria-label="show ? 'Ocultar senha' : 'Mostrar senha'" :aria-pressed="show">
                            <i data-lucide="eye" class="h-4 w-4" x-show="!show"></i>
                            <i data-lucide="eye-off" class="h-4 w-4" x-show="show" x-cloak></i>
                        </button>
                    </div>
                    @error('password')<p class="error">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-muted-foreground">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-input accent-[hsl(var(--primary))]"> Manter conectado
                </label>

                <button class="btn-primary h-11 w-full text-[15px]" type="submit" :disabled="sending">
                    <span x-show="!sending">Entrar</span>
                    <span x-show="sending" x-cloak>Entrando</span>
                </button>

                @if (! empty($demo))
                    <div class="pt-3">
                        <div class="mb-2 flex items-center gap-3">
                            <span class="h-px flex-1 bg-border"></span>
                            <span class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">Acesso rápido (ambiente local)</span>
                            <span class="h-px flex-1 bg-border"></span>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($demo as $d)
                                <button type="button" @click="fill({{ Js::from($d['email']) }})"
                                        class="flex items-center gap-2.5 rounded-md border border-border bg-card px-3 py-2 text-left transition-colors hover:border-ring hover:bg-muted">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-primary/10 font-display text-sm font-bold text-primary">{{ mb_substr($d['name'], 0, 1) }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium">{{ $d['name'] }} <span class="font-normal text-muted-foreground">· {{ $d['role'] }}</span></span>
                                        <span class="block truncate font-mono text-[11px] text-muted-foreground">{{ $d['email'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        <p class="help">Senha de todos: <span class="font-mono text-foreground">{{ $demoPassword }}</span>. Clique no usuário para preencher.</p>
                    </div>
                @endif
            </form>

            @if (empty($demo))
                <p class="mt-8 text-xs text-muted-foreground">Sem conta? Peça acesso ao administrador.</p>
            @endif
        </div>
    </div>
</div>
@endsection
