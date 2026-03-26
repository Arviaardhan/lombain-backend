## 📄 README.md - Backend (lombain-be)

```markdown
# lombain - Backend API 🧠

API inti untuk **lombain** yang menangani otentikasi, manajemen relasi tim yang kompleks, dan sistem notifikasi real-time.

## 🛠 Tech Stack
- **Framework**: [Laravel 11](https://laravel.com/)
- **PHP Version**: 8.2+
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **Notification**: Database & Mail (SMTP)
- **Log**: Laravel Log (Storage)

## 🚀 Fitur Backend
- **Advanced Pivot Management**: Tabel `team_user` dengan status dinamis (`pending`, `accepted`, `assigned`, `rejected`).
- **Role Assignment System**: Logika penempatan anggota ke role spesifik dalam tim.
- **Team Finalization**: Endpoint untuk mengunci status tim menjadi `locked` dan mengirim notifikasi masal.
- **Automated Rejection**: Sistem otomatis menolak pelamar tersisa saat tim dikunci.
- **Activity Log & Notifications**: Riwayat aktivitas user dan pengiriman notifikasi ke email.

## ⚙️ Instalasi Lokal
1. Clone repositori:
   ```bash
   git clone [https://github.com/username/lombain-be.git](https://github.com/username/lombain-be.git)
