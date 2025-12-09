<x-layouts.app title="Data Pengguna" :menu="$menu">
    <!-- Kode toast notification tetap sama -->
    @if (session('toast'))
        <div id="toast-notif"
            style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
              z-index: 2000; display: flex; justify-content: center; pointer-events: none;">

            <div class="toast-message"
                style="background: #fff; border-radius: 12px; padding: 14px 22px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15); text-align: center;
                min-width: 280px; max-width: 360px; transition: opacity .5s ease;">

                {{-- Judul (Hijau kalau success, Merah kalau error) --}}
                <div
                    style="font-weight: 600; font-size: 16px; margin-bottom: 4px;
                  color: {{ session('toast.type') === 'success' ? '#28a745' : '#dc3545' }};">
                    {{ session('toast.title') }}
                </div>

                {{-- Pesan kecil --}}
                <div style="color:#333; font-size: 14px; line-height: 1.4;">
                    {{ session('toast.message') }}
                </div>
            </div>
        </div>

        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast-notif');
                if (toast) toast.style.opacity = '0';
                setTimeout(() => toast?.remove(), 500);
            }, 3000);
        </script>
    @endif

    <div class="page-body">
        <!-- Header dengan judul dan tombol di sebelah kanan -->
        <div class="card-header-container">
            <h3 class="card-title">Data Pengguna</h3>
            <div class="button-group">
                <button type="button" class="btn btn-add-user" data-bs-toggle="modal"
                    data-bs-target="#modalCreateUser">
                    + Tambah Pengguna
                </button>

                {{-- <button type="button" class="btn btn-add-user" data-bs-toggle="modal"
                    data-bs-target="#modalCreateRole">
                    + Tambah Role
                </button> --}}
            </div>
        </div>

        <div class="card p-3">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Bagian</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $currentUserCount = 0;
                        $adminCount = 0;
                        $pengurusBarangPenggunaCount = 0;
                        $pengurusBarangPembantuCount = 0;
                        $otherCount = 0;
                    @endphp

                    {{-- Loop pertama untuk admin yang sedang login --}}
                    @foreach ($users as $index => $u)
                        @if ($u->id === auth()->id())
                            @php $currentUserCount++; @endphp
                            <tr>
                                <td>{{ $currentUserCount }}</td>
                                <td>{{ $u->nama }}</td>
                                <td>{{ $u->username }}</td>
                                <td>{{ $u->role?->nama ?? '-' }}</td>
                                <td>{{ $u->bagian?->nama ?? '-' }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-warning btn-sm btn-action editUser"
                                            data-id="{{ $u->id }}" data-nama="{{ $u->nama }}"
                                            data-username="{{ $u->username }}" data-role-id="{{ $u->role_id }}"
                                            data-role-name="{{ $u->role?->nama }}"
                                            data-bagian-id="{{ $u->bagian_id }}" data-email="{{ $u->email }}"
                                            {{-- TAMBAHKAN INI --}} data-password="{{ $u->password }}"
                                            data-bs-toggle="modal" data-bs-target="#modalEditUser">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    {{-- Loop kedua untuk admin lainnya --}}
                    @foreach ($users as $index => $u)
                        @if ($u->role?->nama === 'Admin' && $u->id !== auth()->id())
                            @php $adminCount++; @endphp
                            <tr>
                                <td>{{ $currentUserCount + $adminCount }}</td>
                                <td>{{ $u->nama }}</td>
                                <td>{{ $u->username }}</td>
                                <td>{{ $u->role?->nama ?? '-' }}</td>
                                <td>{{ $u->bagian?->nama ?? '-' }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-warning btn-sm btn-action editUser"
                                            data-id="{{ $u->id }}" data-nama="{{ $u->nama }}"
                                            data-username="{{ $u->username }}" data-role-id="{{ $u->role_id }}"
                                            data-role-name="{{ $u->role?->nama }}"
                                            data-bagian-id="{{ $u->bagian_id }}" data-email="{{ $u->email }}"
                                            {{-- TAMBAHKAN INI --}} data-password="{{ $u->password }}"
                                            data-bs-toggle="modal" data-bs-target="#modalEditUser">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>

                                        @if (!($u->id === auth()->id() && $u->role?->nama === 'Admin'))
                                            <button type="button" class="btn btn-danger btn-sm btn-action btnDelete"
                                                data-id="{{ $u->id }}">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    {{-- Loop ketiga untuk Pengurus Barang Pengguna --}}
                    @foreach ($users as $index => $u)
                        @if ($u->role?->nama === 'Pengurus Barang Pengguna')
                            @php $pengurusBarangPenggunaCount++; @endphp
                            <tr>
                                <td>{{ $currentUserCount + $adminCount + $pengurusBarangPenggunaCount }}</td>
                                <td>{{ $u->nama }}</td>
                                <td>{{ $u->username }}</td>
                                <td>{{ $u->role?->nama ?? '-' }}</td>
                                <td>{{ $u->bagian?->nama ?? '-' }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-warning btn-sm btn-action editUser"
                                            data-id="{{ $u->id }}" data-nama="{{ $u->nama }}"
                                            data-username="{{ $u->username }}" data-role-id="{{ $u->role_id }}"
                                            data-role-name="{{ $u->role?->nama }}"
                                            data-bagian-id="{{ $u->bagian_id }}" data-email="{{ $u->email }}"
                                            {{-- TAMBAHKAN INI --}} data-password="{{ $u->password }}"
                                            data-bs-toggle="modal" data-bs-target="#modalEditUser">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>

                                        <button type="button" class="btn btn-danger btn-sm btn-action btnDelete"
                                            data-id="{{ $u->id }}">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    {{-- Loop keempat untuk Pengurus Barang Pembantu --}}
                    @foreach ($users as $index => $u)
                        @if ($u->role?->nama === 'Pengurus Barang Pembantu')
                            @php $pengurusBarangPembantuCount++; @endphp
                            <tr>
                                <td>{{ $currentUserCount + $adminCount + $pengurusBarangPenggunaCount + $pengurusBarangPembantuCount }}
                                </td>
                                <td>{{ $u->nama }}</td>
                                <td>{{ $u->username }}</td>
                                <td>{{ $u->role?->nama ?? '-' }}</td>
                                <td>{{ $u->bagian?->nama ?? '-' }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-warning btn-sm btn-action editUser"
                                            data-id="{{ $u->id }}" data-nama="{{ $u->nama }}"
                                            data-username="{{ $u->username }}" data-role-id="{{ $u->role_id }}"
                                            data-role-name="{{ $u->role?->nama }}"
                                            data-bagian-id="{{ $u->bagian_id }}" data-email="{{ $u->email }}"
                                            {{-- TAMBAHKAN INI --}} data-password="{{ $u->password }}"
                                            data-bs-toggle="modal" data-bs-target="#modalEditUser">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>

                                        <button type="button" class="btn btn-danger btn-sm btn-action btnDelete"
                                            data-id="{{ $u->id }}">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    {{-- Loop kelima untuk role lainnya --}}
                    @foreach ($users as $index => $u)
                        @if (
                            $u->role?->nama !== 'Admin' &&
                                $u->role?->nama !== 'Pengurus Barang Pengguna' &&
                                $u->role?->nama !== 'Pengurus Barang Pembantu')
                            @php $otherCount++; @endphp
                            <tr>
                                <td>{{ $currentUserCount + $adminCount + $pengurusBarangPenggunaCount + $pengurusBarangPembantuCount + $otherCount }}
                                </td>
                                <td>{{ $u->nama }}</td>
                                <td>{{ $u->username }}</td>
                                <td>{{ $u->role?->nama ?? '-' }}</td>
                                <td>{{ $u->bagian?->nama ?? '-' }}</td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-warning btn-sm btn-action editUser"
                                            data-id="{{ $u->id }}" data-nama="{{ $u->nama }}"
                                            data-username="{{ $u->username }}" data-role-id="{{ $u->role_id }}"
                                            data-role-name="{{ $u->role?->nama }}"
                                            data-bagian-id="{{ $u->bagian_id }}" data-email="{{ $u->email }}"
                                            {{-- TAMBAHKAN INI --}} data-password="{{ $u->password }}"
                                            data-bs-toggle="modal" data-bs-target="#modalEditUser">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>

                                        <button type="button" class="btn btn-danger btn-sm btn-action btnDelete"
                                            data-id="{{ $u->id }}">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus --}}
    <div class="modal fade" id="modalDeleteUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    Apakah anda yakin untuk menghapus akun ini?
                </div>
                <div class="modal-footer">
                    <div class="row w-100 g-2">
                        <div class="col">
                            <button type="button" class="btn btn-secondary w-100 py-2" data-bs-dismiss="modal">
                                Batal
                            </button>
                        </div>
                        <div class="col">
                            <form id="formDeleteUser" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100 py-2">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Create User --}}
    <div class="modal fade" id="modalCreateUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formCreateUser" method="POST" action="{{ route('admin.users.store') }}"
                class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Tambah Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama</label><span class="text-danger">*</span>
                        <input type="text" class="form-control" name="nama" required
                            placeholder="Masukkan nama">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label><span class="text-danger">*</span>
                        <input type="text" class="form-control" name="username" required
                            placeholder="Masukkan username">
                        <div id="username-feedback" class="invalid-feedback"></div>
                    </div>

                    {{-- TAMBAHKAN INI ↓ --}}
                    <div class="mb-3" id="email-field-create" style="display:none">
                        <label class="form-label fw-semibold">Email</label><span class="text-danger">*</span>
                        <input type="email" class="form-control" name="email"
                            placeholder="Masukkan email admin">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label><span class="text-danger">*</span>
                        <div class="position-relative">
                            <input type="password" class="form-control pe-5" name="password" required
                                autocomplete="new-password" placeholder="Masukkan password"
                                pattern="^(?=.*[a-zA-Z])(?=.*\d).{8,}$">
                            <button type="button" class="pass-toggle" aria-label="Tampilkan password"
                                aria-pressed="false"
                                style="position:absolute; top:50%; right:10px; transform:translateY(-50%); display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; border:0; background:transparent; color:#6c757d;"
                                onclick="(function(btn){
                                    const i = btn.parentElement.querySelector('input[name=&quot;password&quot;]');
                                    const hidden = i.type === 'password';
                                    i.type = hidden ? 'text' : 'password';
                                    btn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
                                    const icon = btn.querySelector('i');
                                    if (icon) {
                                        icon.classList.toggle('bi-eye', hidden);
                                        icon.classList.toggle('bi-eye-slash', !hidden);
                                    }
                                    i.focus({ preventScroll: true });
                                })(this)">
                                <i class="bi bi-eye-slash" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="password-feedback" class="invalid-feedback"></div>
                        <small class="form-text text-muted">Minimal 8 karakter dan harus mengandung huruf dan
                            angka</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role</label><span class="text-danger">*</span>
                        <select class="form-select" name="role_id" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" {{ $role->nama === 'Admin' ? 'selected' : '' }}>
                                    {{ $role->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bagian</label><span class="text-danger">*</span>
                        <select class="form-select" name="bagian_id" required>
                            <option value="">-- Pilih Bagian --</option>
                            @foreach ($bagians as $bagian)
                                <option value="{{ $bagian->id }}">{{ $bagian->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit User --}}
    <div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formEditUser" method="POST" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Edit Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id" id="user_id">
                    <input type="hidden" name="role_id" id="user_role_hidden">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama</label>
                        <input type="text" class="form-control" name="nama" id="user_nama" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" class="form-control" name="username" id="user_username" required>
                        <div id="edit-username-feedback" class="invalid-feedback"></div>
                    </div>

                    {{-- TAMBAHKAN INI ↓ --}}
                    <div class="mb-3" id="email-field-edit" style="display:none">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" name="email" id="user_email"
                            placeholder="Masukkan email admin">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Lama</label>
                        <input type="text" class="form-control" id="user_old_password" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Password Baru <small class="text-muted">(kosongkan jika tidak diubah)</small>
                        </label>
                        <div class="position-relative">
                            <input type="password" class="form-control pe-5" name="password" id="user_password"
                                autocomplete="new-password" placeholder="Masukkan password baru (opsional)"
                                pattern="^(?=.*[a-zA-Z])(?=.*\d).{8,}$">
                            <button type="button" class="pass-toggle" id="toggle_user_password"
                                aria-label="Tampilkan password" aria-pressed="false"
                                style="position:absolute; top:50%; right:10px; transform:translateY(-50%); 
                                       display:inline-flex; align-items:center; justify-content:center; 
                                       width:2rem; height:2rem; border:0; background:transparent; color:#6c757d;">
                                <i class="bi bi-eye-slash" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="edit-password-feedback" class="invalid-feedback"></div>
                        <small class="form-text text-muted">Minimal 8 karakter dan harus mengandung huruf dan
                            angka</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role</label>
                        <select class="form-select" name="role_id_select" id="user_role" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bagian</label>
                        <select class="form-select" name="bagian_id" id="user_bagian" required>
                            <option value="">-- Pilih Bagian --</option>
                            @foreach ($bagians as $bagian)
                                <option value="{{ $bagian->id }}">{{ $bagian->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Create Role --}}
    <div class="modal fade" id="modalCreateRole" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formCreateRole" method="POST" action="{{ route('admin.roles.store') }}"
                class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Role</label>
                        <input type="text" class="form-control" name="nama" required
                            placeholder="Contoh: Pengelola Barang">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/data_pengguna.css') }}">
    @endpush
    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", () => {

                // === FUNGSI UTILITY ===
    function updateEmailFieldEdit(roleName) {
        const emailFieldEdit = document.getElementById('email-field-edit');
        const emailInputEdit = emailFieldEdit?.querySelector('input[name="email"]');
        
        if (roleName === 'Admin') {
            emailFieldEdit.style.display = 'block';
            if (emailInputEdit) {
                emailInputEdit.required = true;
                emailInputEdit.setAttribute('required', 'required');
            }
        } else {
            emailFieldEdit.style.display = 'none';
            if (emailInputEdit) {
                emailInputEdit.required = false;
                emailInputEdit.removeAttribute('required');
                emailInputEdit.value = '';
            }
        }
    }

    // Untuk Modal CREATE
    const roleSelectCreate = document.querySelector('#modalCreateUser select[name="role_id"]');
    const emailFieldCreate = document.getElementById('email-field-create');
    const emailInputCreate = emailFieldCreate?.querySelector('input[name="email"]');
    
    if (roleSelectCreate && emailFieldCreate) {
        roleSelectCreate.addEventListener('change', function() {
            const selectedText = this.options[this.selectedIndex].text;
            if (selectedText === 'Admin') {
                emailFieldCreate.style.display = 'block';
                if (emailInputCreate) emailInputCreate.required = true;
            } else {
                emailFieldCreate.style.display = 'none';
                if (emailInputCreate) {
                    emailInputCreate.required = false;
                    emailInputCreate.value = '';
                }
            }
        });

        // Trigger saat modal create dibuka pertama kali
        const modalCreate = document.getElementById('modalCreateUser');
        modalCreate?.addEventListener('shown.bs.modal', function() {
            roleSelectCreate.dispatchEvent(new Event('change'));
        });
    }

    // Untuk Modal EDIT
    const roleSelectEdit = document.querySelector('#modalEditUser select[name="role_id_select"]');
    
    if (roleSelectEdit) {
        roleSelectEdit.addEventListener('change', function() {
            const selectedText = this.options[this.selectedIndex].text;
            updateEmailFieldEdit(selectedText);
        });
    }

    // Trigger saat modal edit dibuka
    const modalEdit = document.getElementById('modalEditUser');
    modalEdit?.addEventListener('shown.bs.modal', function() {
        const roleSelect = this.querySelector('select[name="role_id_select"]');
        if (roleSelect) {
            const selectedText = roleSelect.options[roleSelect.selectedIndex].text;
            updateEmailFieldEdit(selectedText);
        }
    });

    // === EDIT USER ===
    document.querySelectorAll('.editUser').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const roleName = this.dataset.roleName;
            const roleId = this.dataset.roleId;
            const bagianId = this.dataset.bagianId;
            const email = this.dataset.email || '';
            const isCurrentAdmin = (parseInt(id) === {{ auth()->id() }});

            document.getElementById('user_id').value = id;
            document.getElementById('user_nama').value = this.dataset.nama || '';
            document.getElementById('user_username').value = this.dataset.username || '';
            document.getElementById('user_role').value = roleId || '';
            document.getElementById('user_bagian').value = bagianId || '';
            document.getElementById('user_old_password').value = this.dataset.password || '';
            document.getElementById('user_email').value = email;

            // Set nilai hidden field
            document.getElementById('user_role_hidden').value = roleId;

            // Update field email berdasarkan role yang ada
            updateEmailFieldEdit(roleName);

            // 🔒 Hanya disable ROLE untuk Admin yang sedang login
            if (isCurrentAdmin && roleName === "Admin") {
                document.getElementById('user_role').disabled = true;
                document.getElementById('user_role').name = 'role_id_select';
                document.getElementById('user_role_hidden').name = 'role_id';
            } else {
                document.getElementById('user_role').disabled = false;
                document.getElementById('user_role').name = 'role_id';
                document.getElementById('user_role_hidden').name = '';
            }

            document.getElementById('formEditUser').action =
                "{{ route('admin.users.update', ':id') }}".replace(':id', id);
        });
    });

                // === DELETE USER ===
                document.querySelectorAll('.btnDelete').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.id;
                        const formDelete = document.getElementById('formDeleteUser');
                        formDelete.action = "{{ route('admin.users.destroy', ':id') }}".replace(':id',
                            id);

                        const modal = new bootstrap.Modal(document.getElementById('modalDeleteUser'));
                        modal.show();
                    });
                });

                // Validasi form tambah pengguna
                const formCreateUser = document.getElementById('formCreateUser');
                if (formCreateUser) {
                    formCreateUser.addEventListener('submit', function(e) {
                        const password = this.querySelector('input[name="password"]');
                        const username = this.querySelector('input[name="username"]');
                        let isValid = true;

                        // Reset feedback
                        document.getElementById('password-feedback').textContent = '';
                        document.getElementById('username-feedback').textContent = '';
                        password.classList.remove('is-invalid');
                        username.classList.remove('is-invalid');

                        // Validasi password - perbolehkan simbol
                        if (password.value.length < 8) {
                            password.classList.add('is-invalid');
                            document.getElementById('password-feedback').textContent =
                                'Password minimal 8 karakter';
                            isValid = false;
                        } else if (!/(?=.*[a-zA-Z])(?=.*\d)/.test(password.value)) {
                            // Hanya memeriksa minimal 1 huruf dan 1 angka
                            password.classList.add('is-invalid');
                            document.getElementById('password-feedback').textContent =
                                'Password harus mengandung huruf dan angka';
                            isValid = false;
                        }

                        if (!isValid) {
                            e.preventDefault();
                        }
                    });
                }

                // Validasi form edit pengguna
                const formEditUser = document.getElementById('formEditUser');
                if (formEditUser) {
                    formEditUser.addEventListener('submit', function(e) {
                        const password = this.querySelector('input[name="password"]');
                        const username = this.querySelector('input[name="username"]');
                        let isValid = true;

                        // Reset feedback
                        document.getElementById('edit-password-feedback').textContent = '';
                        document.getElementById('edit-username-feedback').textContent = '';
                        password.classList.remove('is-invalid');
                        username.classList.remove('is-invalid');

                        // Validasi password hanya jika diisi
                        if (password.value.length > 0) {
                            if (password.value.length < 8) {
                                password.classList.add('is-invalid');
                                document.getElementById('edit-password-feedback').textContent =
                                    'Password minimal 8 karakter';
                                isValid = false;
                            } else if (!/(?=.*[a-zA-Z])(?=.*\d)/.test(password.value)) {
                                password.classList.add('is-invalid');
                                document.getElementById('edit-password-feedback').textContent =
                                    'Password harus mengandung huruf dan angka';
                                isValid = false;
                            }
                        }

                        if (!isValid) {
                            e.preventDefault();
                        }
                    });
                }
            });

            // Toggle show/hide Password Baru
            (function() {
                const passInput = document.getElementById('user_password');
                const toggleBtn = document.getElementById('toggle_user_password');
                const modalEl = document.getElementById('modalEditUser');

                if (!passInput || !toggleBtn || !modalEl) return;

                toggleBtn.addEventListener('click', function() {
                    const hidden = passInput.type === 'password';
                    passInput.type = hidden ? 'text' : 'password';
                    this.setAttribute('aria-pressed', String(hidden));

                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('bi-eye', hidden);
                        icon.classList.toggle('bi-eye-slash', !hidden);
                    }

                    passInput.focus({
                        preventScroll: true
                    });
                });

                modalEl.addEventListener('hidden.bs.modal', () => {
                    passInput.type = 'password';
                    toggleBtn.setAttribute('aria-pressed', 'false');
                    const icon = toggleBtn.querySelector('i');
                    if (icon) {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                    passInput.value = '';

                    // Reset semua disabled state saat modal ditutup
                    const roleField = document.getElementById('user_role');
                    if (roleField) roleField.disabled = false;

                    const usernameField = document.getElementById('user_username');
                    if (usernameField) usernameField.disabled = false;

                    const namaField = document.getElementById('user_nama');
                    if (namaField) namaField.disabled = false;

                    const bagianField = document.getElementById('user_bagian');
                    if (bagianField) bagianField.disabled = false;
                });
            })();
        </script>
    @endpush
</x-layouts.app>
