classDiagram

class Broadcast {
  +int id
  +string judul
  +string pesan
  +string jenis
  +datetime jadwal_kirim
  +date tanggal_acara
  +string lokasi
  +bool mention_user
  +datetime terkirim
  +int dibuat_oleh
  +broadcastUsers()
  +creator()
  +pengepulans()
}

class BroadcastUser {
  +int id
  +int broadcast_id
  +int user_id
  +string status_kirim
  +datetime waktu_kirim
  +string deskripsi
  +string message_id
  +broadcast()
  +user()
}

class User {
  +int id
  +string username
  +string email
  -string password
  +string alamat
  +string no_hp
  +decimal saldo
  +role()
  +transaksi()
  +penarikan()
  +pengepulanPengguna()
  +pengepulanPetugas()
  +broadcasts()
  +broadcastUsers()
}

class JenisSampah {
  +int id
  +string nama
  +string deskripsi
  +sampahs()
}

class Kas {
  +int id
  +decimal total_saldo
  +datetime last_updated
  +kasTransaksi()
  +updateTotalSaldo()
}

class KasTransaksi {
  +int id
  +int kas_id
  +decimal jumlah
  +string tipe
  +string deskripsi
  +int transaksable_id
  +string transaksable_type
  +kas()
  +transaksable()
}

class Penarikan {
  +int id
  +int user_id
  +decimal jumlah
  +string status
  +datetime tanggal_pengajuan
  +user()
  +transaksiUser()
  +kasTransaksiOrg()
}

class Pengepulan {
  +int id
  +int user_id
  +int petugas_id
  +int broadcast_id
  +string metode_pengambilan
  +string lokasi
  +string status
  +decimal total_harga
  +date tanggal
  +user()
  +petugas()
  +broadcast()
  +pengepulanSampah()
  +transaksiUser()
  +sampahTransaksi()
}

class PengepulanSampah {
  +int id
  +int pengepulan_id
  +int sampah_id
  +decimal qty
  +pengepulan()
  +sampah()
}

class Penjualan {
  +int id
  +int petugas_id
  +decimal total_harga
  +date tanggal
  +string deskripsi
  +string status
  +petugas()
  +penjualanSampah()
  +kasTransaksiOrg()
  +sampahTransaksi()
}

class PenjualanSampah {
  +int id
  +int penjualan_id
  +int sampah_id
  +decimal qty
  +decimal harga_per_unit
  +penjualan()
  +sampah()
}

class Sampah {
  +int id
  +string nama
  +string image
  +int jenis_sampah_id
  +int satuan_id
  +decimal stock
  +decimal harga
  +string deskripsi
  +jenis()
  +satuan()
  +pengepulanSampah()
  +sampahTransaksi()
}

class SampahTransaksi {
  +int id
  +int sampah_id
  +string tipe
  +decimal jumlah
  +string deskripsi
  +int transactable_id
  +string transactable_type
  +sampah()
  +transactable()
}

class Satuan {
  +int id
  +string nama
  +sampahs()
}

class Transaksi {
  +int id
  +int user_id
  +decimal jumlah
  +string tipe
  +string deskripsi
  +int transactable_id
  +string transactable_type
  +user()
  +transactable()
  +booted()
  +applyBalanceChange()
}

' Relationships

Broadcast "1" -- "0..*" BroadcastUser : broadcastUsers
BroadcastUser "*" -- "1" Broadcast : broadcast
BroadcastUser "*" -- "1" User : user
Broadcast "0..*" -- "1" User : creator

User "1" -- "0..*" Broadcast : broadcasts
User "1" -- "0..*" BroadcastUser : broadcastUsers
User "1" -- "0..*" Penarikan : penarikan
User "1" -- "0..*" Pengepulan : pengepulanPengguna
User "1" -- "0..*" Pengepulan : pengepulanPetugas
User "1" -- "0..*" Penjualan : petugas
User "1" -- "0..*" Transaksi : transaksi

JenisSampah "1" -- "0..*" Sampah : sampahs
Satuan "1" -- "0..*" Sampah : sampahs

Kas "1" -- "0..*" KasTransaksi : kasTransaksi
KasTransaksi "*" -- "1" Kas : kas

Penarikan "*" -- "1" User : user
Penarikan "1" -- "*" Transaksi : transaksiUser (polymorphic)
Penarikan "1" -- "*" KasTransaksi : kasTransaksiOrg (polymorphic)

Pengepulan "*" -- "1" User : user
Pengepulan "*" -- "1" User : petugas
Pengepulan "*" -- "1" Broadcast : broadcast
Pengepulan "1" -- "*" PengepulanSampah : pengepulanSampah
Pengepulan "1" -- "*" Transaksi : transaksiUser (polymorphic)
Pengepulan "1" -- "*" SampahTransaksi : sampahTransaksi (polymorphic)

PengepulanSampah "*" -- "1" Pengepulan : pengepulan
PengepulanSampah "*" -- "1" Sampah : sampah

Penjualan "*" -- "1" User : petugas
Penjualan "1" -- "*" PenjualanSampah : penjualanSampah
Penjualan "1" -- "*" KasTransaksi : kasTransaksiOrg (polymorphic)
Penjualan "1" -- "*" SampahTransaksi : sampahTransaksi (polymorphic)

PenjualanSampah "*" -- "1" Penjualan : penjualan
PenjualanSampah "*" -- "1" Sampah : sampah

Sampah "1" -- "0..*" PengepulanSampah : pengepulanSampah
Sampah "1" -- "0..*" SampahTransaksi : sampahTransaksi
Sampah "1" -- "1" JenisSampah : jenis
Sampah "1" -- "1" Satuan : satuan

SampahTransaksi "*" -- "1" Sampah : sampah
SampahTransaksi "*" -- "1" transactable : transactable (polymorphic)

Transaksi "*" -- "1" User : user
Transaksi "*" -- "1" transactable : transactable (polymorphic)

@endmermaid
