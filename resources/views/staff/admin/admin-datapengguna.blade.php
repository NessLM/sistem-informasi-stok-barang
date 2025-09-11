<x-layouts.app title="Data Pengguna" :menu="$menu">
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
                @forelse ($users as $index => $u)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $u->nama }}</td>
                        <td>{{ $u->username }}</td>
                        <td>{{ $u->role }}</td>
                        <td>{{ $u->bagian }}</td>
                        <td>
    <a href="{{ route('admin.users.edit', $u->id) }}" class="btn btn-warning btn-action">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-action" onclick="return confirm('Yakin hapus pengguna ini?')">
            <i class="bi bi-trash"></i> Hapus
        </button>
    </form>
</td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data pengguna</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
