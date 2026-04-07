<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data Siswa</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111827; margin: 24px; }
        .meta { margin-bottom: 12px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; }
        .text-center { text-align: center; }
        @media print { body { margin: 12px; } }
    </style>
</head>
<body>
    <h3>Data Siswa</h3>
    <div class="meta">
        <div><strong>Dicetak:</strong> {{ $printedAt }}</div>
        <div>
            <strong>Filter:</strong>
            Angkatan: {{ $filters['angkatan'] !== '' ? $filters['angkatan'] : 'Semua' }},
            Sekolah: {{ $filters['sekolah'] !== '' ? $filters['sekolah'] : 'Semua' }},
            Kelas: {{ $filters['kelas'] !== '' ? $filters['kelas'] : 'Semua' }},
            Siswa: {{ $filters['siswa'] !== '' ? $filters['siswa'] : 'Semua' }},
            Cari: {{ $filters['q'] !== '' ? $filters['q'] : 'Semua' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>NIS</th>
                <th>NO VA</th>
                <th>NAMA</th>
                <th>No Pendaftaran</th>
                <th>Unit</th>
                <th>Kelas</th>
                <th>Jenjang</th>
                <th>Angkatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['nocust'] ?? '-' }}</td>
                    <td>{{ '7510050' . preg_replace('/\D+/', '', (string) ($row['nocust'] ?? '')) }}</td>
                    <td>{{ $row['nmcust'] ?? '-' }}</td>
                    <td>{{ $row['num2nd'] ?? '-' }}</td>
                    <td>{{ $row['code02'] ?? '-' }}</td>
                    <td>{{ $row['desc02'] ?? '-' }}</td>
                    <td>{{ $row['desc03'] ?? '-' }}</td>
                    <td>{{ $row['desc04'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Data tidak ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
