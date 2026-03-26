Untuk Backend, isinya akan serupa agar informasi tim dan institusi tetap konsisten, namun pada bagian deskripsi kita berikan sedikit penekanan pada sisi fungsionalitas API dan pengelolaan data agar juri tahu bahwa ini adalah sistem yang kompleks di balik layar.

Berikut adalah file README.md untuk folder lombain-backend:

lombain (Backend API)
Institusi
Universitas Muria Kudus

Anggota Tim
Ketua: Arvia Faustina Ardhan

Anggota 1: Muammad La'azidannak Rusda

Anggota 2: Abiyan Ilzam Pratama

Deskripsi Karya
Sisi Backend dari lombain merupakan pusat pemrosesan data (Engine) yang dibangun menggunakan Framework Laravel. Backend ini bertanggung jawab atas seluruh logika bisnis, keamanan, dan integrasi database untuk mendukung kolaborasi mahasiswa.

Latar Belakang & Tujuan:
Dalam sebuah platform kolaborasi, validasi data dan manajemen status anggota adalah hal yang krusial. Backend ini dibuat untuk memastikan bahwa setiap "Join Request" dikelola dengan aman, pembagian role anggota tidak melebihi kapasitas (slot), dan penguncian tim dilakukan secara sistematis guna menghindari kecurangan data saat kompetisi dimulai.

Fitur Teknis Utama:

Dynamic Role Management: Menangani alokasi anggota ke peran spesifik secara real-time.

Waiting List System: Logika pemisahan antara pendaftar yang sudah diterima di tim namun belum memiliki posisi tetap.

Automated Notification: Pengiriman notifikasi ke email dan sistem database setiap kali ada perubahan status aplikasi atau undangan tim.

Team Finalization: Sistem pengamanan data yang mengunci formasi tim secara permanen untuk integritas data kompetisi.

Secure Authentication: Menggunakan Laravel Sanctum untuk memastikan data pengguna dan tim terlindungi dengan enkripsi standar industri.

Pemilihan Subtema:
Backend ini mendukung subtema "Inovasi Teknologi Digital untuk Kolaborasi Akademik" dengan menyediakan infrastruktur data yang tangguh dan terukur, memungkinkan platform untuk menampung ribuan user mahasiswa dengan performa yang tetap terjaga.

Link Website
https://lombain.my.id (API Endpoint: https://lombain.my.id/api)