<div class="nav-bar-background">
    <nav class="container" aria-label="Menu principal">
        <ul class="nav justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative notification-wrapper"
                     x-data="{ open: false }"
                     @click.outside="open = false">
                    
                    <button @click="open = !open" class="btn btn-link p-0">
                        <img src="{{ asset('img/mail.png') }}" alt="Notificações">
                        <span id="notification-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">
                            0
                        </span>
                    </button>

                    <div id="notifications-box"
                         class="card position-absolute shadow"
                         x-show="open"
                         x-transition
                         style="right: 0; width: 300px; z-index: 1000; display: none;">
                        
                        <div class="card-header">Notificações</div>
                        <div class="card-body p-0 notifications-content" style="max-height: 300px; overflow-y: auto;">
                            </div>
                        <div class="card-footer text-center">
                            <a href="{{ url('notifications') }}" class="small">Ver Tudo</a>
                        </div>
                    </div>
                </div>

                <!-- Theme Toggle -->
                <x-theme-toggle />

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-link" title="Logout">
                        <img src="{{ asset('img/exit.png') }}" alt="Sair">
                    </button>
                </form>
                
                </div>
        </ul>
    </nav>
</div>

<script>
    window.AppConfig = {
        userId: "{{ Auth::id() }}",
        notificationRoute: "{{ route('master.blink') }}",
        baseUrl: "{{ url('/') }}",
        assetImg: "{{ asset('img') }}"
    };
</script>
