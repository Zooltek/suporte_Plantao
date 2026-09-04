@props(['level' => ''])

@php
    $color = match ($level) {
        'low'    => 'green',
        'high'   => '#ec971f',
        'urgent' => 'red',
        default  => null,
    };

    $title = match ($level) {
        'low'    => 'Prioridade: Baixa',
        'high'   => 'Prioridade: Média',
        'urgent' => 'Prioridade: Urgente',
        default  => '',
    };
@endphp

@if($color)
    <i class="fa fa-circle" title="{{ $title }}" style="color: {{ $color }}; opacity: 0.7"></i>
@endif