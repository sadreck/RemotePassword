<footer class="footer py-2 mt-auto bg-light d-flex flex-wrap justify-content-between align-items-center border-top">
    <div class="col-md-4 d-flex align-items-center">
        <a href="/" class="mb-3 me-2 mb-md-0 text-muted text-decoration-none lh-1">
            <svg class="bi" width="30" height="24"><use xlink:href="#bootstrap"/></svg>
        </a>
        <span class="text-muted">&copy; {{ now()->year }} {{ Config::get('app.name') }} v{{ Config::get('app.version') }} - {{ Config::get('app.release') }}</span>
    </div>

    <ul class="nav col-md-4 justify-content-end list-unstyled d-flex">
        <li class="me-4"><a class="text-muted" href="https://github.com/sadreck/RemotePassword"><i class="fa-brands fa-github"></i></a></li>
    </ul>
</footer>
