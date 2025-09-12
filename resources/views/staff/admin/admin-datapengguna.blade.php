<x-layouts.app title="Data Pengguna" :menu="$menu">
  <div class="page-body"> 
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('admin.users.create') }}" class="btn btn-add-user">+ Tambah Pengguna</a>
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
                        <td>{{ $u->role }}</td>
                        <td>{{ $u->bagian }}</td>
                        <td>
                          <div class="action-buttons">
                            <!-- Tombol Edit -->
                            <button type="button" 
                                    class="btn btn-warning btn-sm btn-action editUser"
                                    data-id="{{ $u->id }}"
                                    data-nama="{{ $u->nama }}"
                                    data-username="{{ $u->username }}"
                                    data-role="{{ $u->role }}"
                                    data-bagian="{{ $u->bagian }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditUser">
                                <i class="bi bi-pencil"></i> Edit
                            </button>

                            <!-- Tombol Delete -->
                            <button type="button" 
                                    class="btn btn-danger btn-sm btn-action btnDelete" 
                                    data-id="{{ $u->id }}">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
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

          <div class="mb-3">
            <label class="form-label">Password <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
            <input type="password" class="form-control" name="password" id="user_password">
          </div>

          <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role" id="user_role" required>
              <option value="Admin">Admin</option>
              <option value="User">User</option>
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

  @push('scripts')
  <script>
  document.addEventListener("DOMContentLoaded", () => {
    // === EDIT USER ===
    document.querySelectorAll('.editUser').forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.dataset.id;
        document.getElementById('user_id').value = id;
        document.getElementById('user_nama').value = this.dataset.nama || '';
        document.getElementById('user_username').value = this.dataset.username || '';
        document.getElementById('user_role').value = this.dataset.role || 'User';
        document.getElementById('user_bagian').value = this.dataset.bagian || '';

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
  </script>
  @endpush
</x-layouts.app>
