<x-layouts.app title="Data Pengguna" :menu="$menu">
 @if(session('toast'))
  <div id="toast-notif" 
       style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
              z-index: 2000; display: flex; justify-content: center; pointer-events: none;">

    <div class="toast-message"
         style="background: #fff; border-radius: 12px; padding: 14px 22px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15); text-align: center;
                min-width: 280px; max-width: 360px; transition: opacity .5s ease;">
      
      {{-- Judul (Hijau kalau success, Merah kalau error) --}}
      <div style="font-weight: 600; font-size: 16px; margin-bottom: 4px;
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
      setTimeout(() => toast?.remove(), 500); // biar smooth fade-out
    }, 3000);
  </script>
@endif


  <div class="page-body"> 
    <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-add-user" data-bs-toggle="modal" data-bs-target="#modalCreateUser">
  + Tambah Pengguna
</button>

       <button type="button" class="btn btn-add-user" data-bs-toggle="modal" data-bs-target="#modalCreateRole">
  + Tambah Role
</button>

    </div>
</div>


    <div class="card p-3">
        <h4 class="mb-3">Data Pengguna</h4>
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
                @foreach ($users as $index => $u)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $u->nama }}</td>
                        <td>{{ $u->username }}</td>
                        <td>{{ $u->role?->nama ?? '-' }}</td>
                        <td>{{ $u->bagian }}</td>
                        <td>
                          <div class="action-buttons">
                            <!-- Tombol Edit -->
                            <button type="button" 
                                    class="btn btn-warning btn-sm btn-action editUser"
                                    data-id="{{ $u->id }}"
                                    data-nama="{{ $u->nama }}"
                                    data-username="{{ $u->username }}"
                                    data-role-id="{{ $u->role_id }}"
                                    data-role-name="{{ $u->role?->nama }}"
                                    data-bagian="{{ $u->bagian }}"
                                    data-password="{{ $u->password }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditUser">
                                <i class="bi bi-pencil"></i> Edit
                            </button>

                            <!-- Tombol Delete -->
                            @if(!($u->id === auth()->id() && $u->role?->nama === 'Admin'))
                              <button type="button" 
                                      class="btn btn-danger btn-sm btn-action btnDelete" 
                                      data-id="{{ $u->id }}">
                                  <i class="bi bi-trash"></i> Hapus
                              </button>
                            @endif
                          </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
  </div>

  {{-- Modal Konfirmasi Hapus --}}
  @push('modals')
  <div class="modal fade" id="modalDeleteUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Konfirmasi Hapus</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          Apakah anda yakin untuk menghapus akun ini?
        </div>
        <div class="modal-footer">
          <div class="row w-100 g-2">
            <div class="col">
              <button type="button" class="btn btn-secondary w-100 py-2" data-bs-dismiss="modal">
                Cancel
              </button>
            </div>
            <div class="col">
              <form id="formDeleteUser" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger w-100 py-2">
                  Yes, Hapus
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endpush

{{-- Modal Create User --}}
@push('modals')
<div class="modal fade" id="modalCreateUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formCreateUser" method="POST" action="{{ route('admin.users.store') }}" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Tambah Pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama</label>
          <input type="text" class="form-control" name="nama" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" name="username" required>
        </div>

        {{-- 🔐 Tambahan: Password (Tambah Pengguna) + tombol eye toggle (inline, tidak menambah <script> baru) --}}
         <div class="mb-3">
           <label class="form-label">Password</label>
           <div class="position-relative">
             <input
               type="password"
               class="form-control pe-5"
               name="password"            {{-- penting: name tidak diubah agar tetap tersubmit --}}
               required
               autocomplete="new-password"
               placeholder="Masukkan password"
             >
             <button
               type="button"
               class="pass-toggle"
               aria-label="Tampilkan password"
               aria-pressed="false"
               style="position:absolute; top:50%; right:10px; transform:translateY(-50%); display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; border:0; background:transparent; color:#6c757d;"
               {{-- onclick inline supaya tidak mengganggu submit form --}}
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
               })(this)"
             >
               <i class="bi bi-eye-slash" aria-hidden="true"></i>
             </button>
           </div>
         </div>

        <div class="mb-3">
          <label class="form-label">Role</label>
          <select class="form-select" name="role_id" required>
            @foreach ($roles as $role)
              <option value="{{ $role->id }}">{{ $role->nama }}</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Bagian</label>
          <input type="text" class="form-control" name="bagian">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endpush

  {{-- Modal Edit User --}}
  @push('modals')
  <div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <form id="formEditUser" method="POST" class="modal-content">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Edit Pengguna</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="user_id">

          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control" name="nama" id="user_nama" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" id="user_username" required>
          </div>

          {{-- Password lama (ditampilkan dari DB) --}}
          <div class="mb-3">
            <label class="form-label">Password Lama</label>
            <input type="text" class="form-control" id="user_old_password" readonly>
          </div>

          {{-- Password baru --}}
          <div class="mb-3">
            <label class="form-label">
              Password Baru <small class="text-muted">(kosongkan jika tidak diubah)</small>
            </label>
          
            <!-- ikon mata overlay di dalam input -->
            <div class="field-pass position-relative">
              <input
                type="password"
                class="form-control pe-5"
                name="password"
                id="user_password"
                autocomplete="new-password"
                placeholder="Masukkan password baru (opsional)"
              >
              <button
                type="button"
                class="pass-toggle"
                id="toggle_user_password"
                aria-label="Tampilkan password"
                aria-pressed="false"
              >
                <i class="bi bi-eye-slash" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          
          

          <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role_id" id="user_role" required>
              @foreach ($roles as $role)
                <option value="{{ $role->id }}">{{ $role->nama }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Bagian</label>
            <input type="text" class="form-control" name="bagian" id="user_bagian">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
  @endpush

  {{-- Modal Create Role --}}
@push('modals')
<div class="modal fade" id="modalCreateRole" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="formCreateRole" method="POST" action="{{ route('admin.roles.store') }}" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Tambah Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama Role</label>
          <input type="text" class="form-control" name="nama" required placeholder="Contoh: Pengelola Barang">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endpush


  @push('scripts')
  <script>
  document.addEventListener("DOMContentLoaded", () => {
    // === EDIT USER ===
    document.querySelectorAll('.editUser').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const roleName = this.dataset.roleName;
        const isAdmin = (roleName === "Admin" && parseInt(id) === {{ auth()->id() }});

        document.getElementById('user_id').value = id;
        document.getElementById('user_nama').value = this.dataset.nama || '';
        document.getElementById('user_username').value = this.dataset.username || '';
        document.getElementById('user_role').value = this.dataset.roleId || '';
        document.getElementById('user_bagian').value = this.dataset.bagian || '';

        document.getElementById('user_old_password').value = this.dataset.password || '';

        // Kalau admin sendiri, disable username & role
        document.getElementById('user_username').disabled = isAdmin;
        document.getElementById('user_role').disabled = isAdmin;

        document.getElementById('formEditUser').action =
          "{{ route('admin.users.update', ':id') }}".replace(':id', id);
      });
    });

    // === DELETE USER ===
    document.querySelectorAll('.btnDelete').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const formDelete = document.getElementById('formDeleteUser');
        formDelete.action = "{{ route('admin.users.destroy', ':id') }}".replace(':id', id);

        const modal = new bootstrap.Modal(document.getElementById('modalDeleteUser'));
        modal.show();
      });
    });
  });

   // 🔍 Toggle show/hide Password Baru (eye / eye-slash)
 // Toggle show/hide untuk "Password Baru" di modal Edit
 (function () {
    const passInput = document.getElementById('user_password');
    const toggleBtn = document.getElementById('toggle_user_password');
    const modalEl   = document.getElementById('modalEditUser');

    if (!passInput || !toggleBtn || !modalEl) return;

    toggleBtn.addEventListener('click', function () {
      const hidden = passInput.type === 'password';
      passInput.type = hidden ? 'text' : 'password';
      this.setAttribute('aria-pressed', String(hidden));

      const icon = this.querySelector('i');
      if (icon) {
        icon.classList.toggle('bi-eye', hidden);
        icon.classList.toggle('bi-eye-slash', !hidden);
      }

      passInput.focus({ preventScroll: true });
    });

    // Reset saat modal ditutup
    modalEl.addEventListener('hidden.bs.modal', () => {
      passInput.type = 'password';
      toggleBtn.setAttribute('aria-pressed', 'false');
      const icon = toggleBtn.querySelector('i');
      if (icon) {
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      }
      passInput.value = ''; // tetap kosong agar tak tersubmit tanpa sengaja
    });
  })();
  </script>
  @endpush
</x-layouts.app>
