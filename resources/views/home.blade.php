@extends('layout.basic')

@section('content')
    <div class="row flex-lg-row-reverse align-items-center py-3 px-3">
        <div class="col-lg">
            <h1 class="display-5 fw-bold lh-1 mb-3">Remote Password</h1>
            <p class="lead">Do you have a local script on your RaspberryPi that needs a hardcoded password?</p>
            <p class="lead">Want the ability to disable access to the credentials remotely?</p>
            <p class="lead">Remote Password might just be what you're looking for.</p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                @guest
                    @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-outline-dark">{{ __('Sign Up') }}</a>
                    @endif
                <a href="{{ route('login') }}" class="btn btn-outline-dark">{{ __('Login') }}</a>
                @endguest
                <a href="https://github.com/sadreck/RemotePassword" class="btn btn-outline-dark"><i class="fa-brands fa-github"></i> {{ __('Self-Host') }}</a>
            </div>
        </div>
    </div>

    <div class="row pt-5 row-cols-1 row-cols-lg-3">
        <div class="col d-flex align-items-start">
            <div class="icon-square bg-light text-dark flex-shrink-0 me-3">1</div>
            <div>
                <h2>GPG Password Encryption</h2>
                <p>On your local machine, encrypt your password using your own GPG keys.</p>
            </div>
        </div>

        <div class="col d-flex align-items-start">
            <div class="icon-square bg-light text-dark flex-shrink-0 me-3">2</div>
            <div>
                <h2>Store GPG Output</h2>
                <p>Store the GPG encrypted output on Remote Password.</p>
                <p>The only Private Key that can decrypt this is stored on your local machine.</p>
            </div>
        </div>

        <div class="col d-flex align-items-start">
            <div class="icon-square bg-light text-dark flex-shrink-0 me-3">3</div>
            <div>
                <h2>Fetch &amp; Decrypt</h2>
                <p>Using the RPass scripts to fetch, decrypt, and pass the password onto your script.</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="pt-5 pb-2 border-bottom">Features</h2>
        </div>
    </div>


    <div class="row mb-2">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-3">
            <div class="col d-flex align-items-start">
                <div class="flex-shrink-0 ps-2 pt-1 me-3 feature-icon text-center"><i class="fa-xl fa-solid fa-vault"></i></div>
                <div>
                    <h4 class="fw-bold mb-0">Secrecy</h4>
                    <p>Only the encrypted password is stored on the server, and only <b>you</b> have the key.</p>
                </div>
            </div>

            <div class="col d-flex align-items-start">
                <div class="flex-shrink-0 ps-2 pt-1 me-3 feature-icon text-center"><i class="fa-xl fa-solid fa-shield-halved"></i></div>
                <div>
                    <h4 class="fw-bold mb-0">Access Control</h4>
                    <p>Implement access controls based on Day/Date/Time, IP, User Agents, and Usage.</p>
                </div>
            </div>

            <div class="col d-flex align-items-start">
                <div class="flex-shrink-0 ps-2 pt-1 me-3 feature-icon text-center"><i class="fa-xl fa-solid fa-bell"></i></div>
                <div>
                    <h4 class="fw-bold mb-0">Notifications</h4>
                    <p>Get E-mail, Slack, and Discord notifications for logins, password updates, data access, and more.</p>
                </div>
            </div>

            <div class="col d-flex align-items-start">
                <div class="flex-shrink-0 ps-2 pt-1 me-3 feature-icon text-center"><i class="fa-xl fa-solid fa-key"></i></div>
                <div>
                    <h4 class="fw-bold mb-0">Public Keys</h4>
                    <p>Manage your Public Keys online, and encrypt your passwords client-side without hassle.</p>
                </div>
            </div>

            <div class="col d-flex align-items-start">
                <div class="flex-shrink-0 ps-2 pt-1 me-3 feature-icon text-center"><i class="fa-xl fa-solid fa-file-lines"></i></div>
                <div>
                    <h4 class="fw-bold mb-0">Logging</h4>
                    <p>Log all data access, whether it was successful or not.</p>
                </div>
            </div>

            <div class="col d-flex align-items-start">
                <div class="flex-shrink-0 ps-2 pt-1 me-3 feature-icon text-center"><i class="fa-xl fa-solid fa-code"></i></div>
                <div>
                    <h4 class="fw-bold mb-0">API<span class="badge bg-primary rounded-pill coming-soon">coming soon</span></h4>
                    <p>Use the API to manage all your data.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="pt-5 border-bottom">Examples</h2>
        </div>
    </div>

    <div class="row my-3">
        <div class="col">
            <h4>Mount External USB Disk</h4>
            <div class="alert alert-secondary font-monospace examples">
                <div class="mb-2">echo -n <span class="text-decoration-underline">$("/path/to/rpass" "myUsbDisk")</span> | sudo cryptsetup luksOpen "${DISK}" "${MAPNAME}"</div>
                <div>sudo mount "/dev/mapper/${MAPNAME}" "${MOUNTPATH}"</div>
            </div>
        </div>
    </div>

    <div class="row my-3">
        <div class="col">
            <h4>Mount VeraCrypt Container</h4>
            <div class="alert alert-secondary font-monospace examples">
                <div>veracrypt --non-interactive --mount-options=timestamp <span class="text-decoration-underline">--password=$(/path/to/rpass "personalContainer")</span> --mount "$container" "$target"</div>
            </div>
        </div>
    </div>

    <div class="row my-3">
        <div class="col">
            <h4>Backup Emails With <a href="https://pyropus.ca./software/getmail/">getmail</a></h4>
            <div class="alert alert-secondary font-monospace examples">
                <div class="mb-2"># getmail.conf</div>
                <div class="mb-2">...</div>
                <div>
                    [retriever]<br>
                    type = SimpleIMAPSSLRetriever<br>
                    server = imap.gmail.com<br>
                    mailboxes = ALL<br>
                    username = you@gmail.com<br>
                    <span class="text-decoration-underline">password_command = ("/path/to/rpass", "myEmailPassword")</span>
                </div>
                <div>...</div>
            </div>
        </div>
    </div>

    <div class="row my-3">
        <div class="col">
            <h4>MySQL Backup</h4>
            <div class="alert alert-secondary font-monospace examples">
                <div>mysqldump -u backup -p $(/path/to/rpass "MySQLBackupUser") > /var/backups/latest.sql</div>
            </div>
        </div>
    </div>

    <div class="row align-items-md-stretch mb-5">
        <div class="col-md">
            <div class="h-100 p-4 text-white bg-dark rounded-3">
                <h2>Try it out!</h2>
                @guest
                    @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-outline-light me-2">{{ __('Sign Up') }}</a>
                    @endif
                <a href="{{ route('login') }}" class="btn btn-outline-light me-2">{{ __('Login') }}</a>
                @endguest
                <a href="https://github.com/sadreck/RemotePassword" class="btn btn-outline-light"><i class="fa-brands fa-github"></i> {{ __('Self-Host') }}</a>
            </div>
        </div>
    </div>
@endsection
