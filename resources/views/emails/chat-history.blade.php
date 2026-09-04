<div class="message-log">
    <h3 class="h6 font-weight-bold">Mensagens</h3>
    <ul class="list-group list-group-flush">
        @forelse($log as $message)
            <li class="list-group-item px-0 bg-transparent border-0">
                <span class="text-secondary small">{{ $message->created_at->format('H:i') }} -</span>
                <span class="message-content">{{ $message->content }}</span>
            </li>
        @empty
            <li class="list-group-item px-0 bg-transparent border-0 text-muted italic">
                Nenhuma mensagem registrada.
            </li>
        @endforelse
    </ul>
</div>
