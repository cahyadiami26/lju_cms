-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 04, 2026 at 01:00 PM
-- Server version: 11.4.13-MariaDB-cll-lve-log
-- PHP Version: 8.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ljucoid_cms`
--

-- --------------------------------------------------------

--
-- Table structure for table `berita`
--

CREATE TABLE `berita` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `no_urut` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `tag` varchar(60) NOT NULL,
  `short_title` varchar(150) NOT NULL,
  `full_title` varchar(255) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `news_content` mediumtext DEFAULT NULL,
  `front_image` varchar(255) DEFAULT NULL,
  `main_image` varchar(255) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `berita`
--

INSERT INTO `berita` (`id`, `no_urut`, `tag`, `short_title`, `full_title`, `slug`, `news_content`, `front_image`, `main_image`, `status`, `published_at`, `created_by`, `created_at`, `updated_at`) VALUES
(4, 1, 'Energy', 'PT LJU Provinsi Lampung Helat Sosialisasi Rencana Pembangunan PTLS', 'PT LJU Provinsi Lampung Helat Sosialisasi Rencana Pembangunan PTLS di Desa Sumberjaya', 'pt-lju-provinsi-lampung-helat-sosialisasi-rencana-pembangunan-ptls', '<p class=\"text-body-2  mb_40\">LAMPUNG &ndash; Badan Usaha Milik Daerah (BUMD) anak Usaha PT. Lampung Jasa Utama (LJU) Provinsi Lampung menghelat sosialisaai pembangunan Pembangkit Listrik Tenaga Surya (PLTS) di Balai Desa Sumberjaya, ke amatan Jati Agung, Kabupaten Lampung Selatan pada Jumat (18/9/2026) pagi.</p>\r\n<p class=\"text-body-2  mb_40\">Hadir dalam acara tersebut Babinkantibmas Aiptu Suparno, Babinsa Serda Richard, Kepala Desa Sumberjaya Idham, Kasi Trantib Andi Darmawan, SE, Ketua BPD Budi Waluyo, aparatur desa setempat dan masyarakat. Kepala Desa Sumberjaya Idham menyambut gembira dengan adanya rencana pembangunan Pembangkit Listrik Tenaga Surya (PLTS) di Balai Desa Sumberjaya. Namun demikian, semua tergantung hasil kesepakatan musyawarah dengan masyakat.</p>\r\n<div class=\"quote mb_40\">\r\n<p class=\"text-body-2 text_primary mb_24\">\"Pada dasarnya kami menyambut gembira dengan adanya rencana pembangunan PLTS di Balai Desa Sumberjaya, tapi semua saya serahkan kepada masyarakat\"</p>\r\n<div class=\"author\">\r\n<h5 class=\"text_primary\">Idham -&nbsp;Kepala Desa Sumberjaya</h5>\r\n</div>\r\n</div>\r\n<p class=\"text-body-2  mb_40\">Idham mengungkapkan sosialisasi tersebut masih batas perkenalan awal, belum pada, substansi yang lebih dalam lagi. Pada sosialisasi yang akan datang diharapkan dapat menghadirkan para narasumber yang dapat menjelaskan lebih dalam lagi.</p>\r\n<p class=\"text-body-2  mb_40\">Sementara itu, Direktur Operasional PT. LJU Amri Zamani, S. Pi., M. B. A menjelaskan perusahaan yang dipimpinnya dipercaya untuk menyosialisasikan rencana pembangunan PLTS di Desa Sumberjaya.</p>\r\n<div class=\"quote mb_40\">\r\n<p class=\"text-body-2 text_primary mb_24\">\"Hari ini lami menyosialisasikan rencana pembangunan PLTS kepada masyarakat. Dan alhamdulilah sosisialisasi berjalan dengan baik. Namun demikian sosialisasi akan dilakukan secara periodik dan berkesinambungan\"</p>\r\n<div class=\"author\">\r\n<h5 class=\"text_primary\">Amri Zamani -&nbsp;Direktur Operasional PT. Lampung Jasa Utama</h5>\r\n</div>\r\n</div>\r\n<p class=\"text-body-2  mb_40\">Lebih lanjut dia mengungkapkan bahwa PLTS adalah sistem pembangkit listrik yang mengubah energi matahari menjadi listrik menggunakan panel fotovoltaik (PV) tanpa proses pembakaran dan tanpa emisi karbon selama beroperasi. Pembangkit Listrik Tenaga Surya (PLTS) adalah pembangkit listrik yang memanfaatkan energi dari radiasi matahari untuk menghasilkan energi listrik.</p>\r\n<p class=\"text-body-2  mb_40\">Komponen utama dari PLTS adalah panel surya fotovoltaik yang dapat mengkonversi energi matahari menjadi energi listrik sehingga dapat digunakan untuk kebutuhan listrik sehari-hari. Arus listrik yang dihasilkan oleh panel surya fotovoltaik adalah arus listrik searah (DC) sehingga dibutuhkan komponen lainnya seperti inverter untuk mengkonversi arus listrik searah (DC) menjadi arus listrik bolak-balik (AC).</p>\r\n<p>&nbsp;</p>\r\n<p class=\"text-body-2  mb_40\">*Dikutip dari Harian Satelit</p>', 'front-20261004-7fa63c6550e8.png', 'main-20261004-a9f9c9572b40.png', 'published', NULL, 7, '2026-10-04 05:39:16', '2026-10-04 05:39:40'),
(6, 2, 'Technology', 'Satelit Lampung-1 Resmi Mengangkasa', 'Satelit Lampung-1 Resmi Mengangkasa: Bawa Teknologi AI untuk Revolusi Pertanian dan Pembangunan Daerah', 'satelit-lampung-1-resmi-mengangkasa', '<div class=\"passive  d-grid gap_12 mb_28\">\r\n<h4>Mengenal Lebih Jauh Satelit Lampung-1</h4>\r\n<div class=\"text_secondary\">\r\n<p class=\"text-body-2\">Satelit Lampung-1 merupakan satelit jenis hiperspektral yang mampu menangkap informasi spektral dan spasial dari suatu target di permukaan bumi secara detail. Menariknya, penamaan \"Lampung-1\" merupakan bentuk kerja sama dengan STAR.VISION Aerospace asal China, yang memegang kepemilikan dan kendali atas satelit ini.</p>\r\n<p class=\"text-body-2\">Berikut adalah spesifikasi teknis dari Satelit Lampung-1:</p>\r\n<ul class=\"text-body-2\">\r\n<li>Bobot: 300 kg</li>\r\n<li>Resolusi Spasial: 5 meter</li>\r\n<li>Cakupan Pencitraan: 300 km</li>\r\n<li>Saluran: 22 saluran spektral</li>\r\n<li>Panjang Gelombang: 400 - 1.650 nanometer</li>\r\n<li>Kapasitas Komputasi (AI): 400 TOPS</li>\r\n</ul>\r\n<p>&nbsp;</p>\r\n<h4>Alur Pemanfaatan Data: Dari Luar Angkasa ke Meja Kebijakan</h4>\r\n<p class=\"text-body-2\">Untuk memastikan data yang dihasilkan akurat dan dapat digunakan, Pemprov Lampung bekerja sama dengan Badan Riset dan Inovasi Nasional (BRIN). Proses pemanfaatan data berjalan melalui tiga tahapan utama:</p>\r\n<ul class=\"text-body-2\">\r\n<li>1. Pengiriman Data: Satelit Lampung-1 mengumpulkan dan mengirimkan data spasial maupun data spektral lainnya dari orbit.</li>\r\n<li>2. Pemrosesan oleh BRIN: BRIN mengambil peran krusial untuk melakukan kalibrasi, memvalidasi kualitas data, menganalisis, dan menerjemahkannya menjadi informasi yang matang.</li>\r\n<li>3. Pemanfaatan oleh Pemprov: Pemprov Lampung menerima informasi yang sudah siap pakai untuk perencanaan dan pengambilan kebijakan daerah.</li>\r\n</ul>\r\n</div>\r\n</div>\r\n<div class=\"passive d-grid gap_12 mb_40\">\r\n<h4>Fokus Pemantauan: Pertanian hingga Mitigasi Bencana</h4>\r\n<div class=\"text_secondary\">\r\n<p class=\"text-body-2\">Informasi yang dikumpulkan oleh satelit ini akan sangat vital untuk berbagai sektor, terutama:</p>\r\n<p class=\"text-body-2\">Sektor Pertanian :</p>\r\n<ul class=\"text-body-2\">\r\n<li>Pemetaan lahan secara akurat.</li>\r\n<li>Pemantauan kondisi dan perkembangan tanaman.</li>\r\n<li>Identifikasi dini terhadap kekurangan air pada area pertanian.</li>\r\n</ul>\r\n<p class=\"text-body-2\">Lingkungan :</p>\r\n<ul class=\"text-body-2\">\r\n<li>Pemantauan wilayah pesisir dan kelautan Lampung.</li>\r\n<li>Pemantauan terhadap perubahan tutupan lahan dari waktu ke waktu.</li>\r\n</ul>\r\n<p class=\"text-body-2\">Selain itu, tujuan utama dari pemanfaatan Satelit Lampung-1 mencakup dukungan terhadap pembangunan daerah melalui pengambilan keputusan presisi berdasarkan data, deteksi dini potensi bencana alam (seperti banjir, kekeringan, dan longsor), serta memperkuat talenta sumber daya manusia Indonesia di bidang teknologi satelit dan penginderaan jauh.</p>\r\n</div>\r\n</div>\r\n<div class=\"passive d-grid gap_12 mb_40\">\r\n<h4>Teknologi Pendamping, Bukan Pengganti Petani</h4>\r\n<div class=\"text_secondary\">\r\n<p class=\"text-body-2\">Gubernur Lampung, Rahmat Mirzani Djausal, menegaskan bahwa kehadiran teknologi luar angkasa ini bukan bertujuan untuk menggeser peran manusia di lapangan.</p>\r\n</div>\r\n</div>\r\n<div class=\"quote mb_40\">\r\n<p class=\"text-body-2 text_primary mb_24\">\"Teknologi ini bukan menggantikan petani, melainkan membantu petani dan pemerintah mengambil keputusan lebih baik, lebih cepat, dan lebih tepat sasaran\"</p>\r\n<div class=\"author\">\r\n<h5 class=\"text_primary\">Rahmat Mirzani Djausal -&nbsp;Gubernur Lampung</h5>\r\n</div>\r\n</div>\r\n<div class=\"passive d-grid gap_12 mb_40\">\r\n<div>\r\n<p class=\"text-body-2 text_secondary\">Dengan beroperasinya Satelit Lampung-1, Provinsi Lampung kini melangkah lebih maju dalam memanfaatkan teknologi kedirgantaraan demi kesejahteraan petani dan keamanan lingkungan di masa depan.</p>\r\n</div>\r\n</div>', 'front-20261004-a14cd23cf9a2.jpg', 'main-20261004-7d5a83f5dc38.jpg', 'published', '2026-10-04 12:47:39', 7, '2026-10-04 05:47:36', '2026-10-04 05:47:39'),
(7, 3, 'Transportation', 'KMP Dalom-1 Mengarungi Selat Sunda', 'KMP Dalom-1 Resmi Mengarungi Selat Sunda', 'kmp-dalom-1-mengarungi-selat-sunda', '<div class=\"heading-title mb_40\">\r\n<h3 class=\"title text-capitalize mb_16\">KMP Dalom-1 Resmi Mengarungi Selat Sunda</h3>\r\n</div>\r\n<p class=\"text-body-2  mb_40\">Pemerintah Provinsi (Pemprov) Lampung telah resmi meluncurkan Kapal Motor Penumpang (KMP) Dalom 1 pada Jumat, 14 November 2025, yang dilangsungkan di Dermaga Eksekutif Pelabuhan Bakauheni. Peresmian ini dilakukan secara langsung oleh Gubernur Lampung, Rahmat Mirzani Djausal. Kapal eksekutif ini mencetak sejarah sebagai kapal Roll-on/Roll-off (RoRo) pertama yang sepenuhnya dimiliki oleh Pemerintah Provinsi Lampung untuk melayani rute Bakauheni&ndash;Merak.</p>\r\n<div class=\"passive  d-grid gap_12 mb_28\">\r\n<h4>Fakta-Fakta Seputar KMP Dalom-1</h4>\r\n<div class=\"text_secondary\">\r\n<p class=\"text-body-2\">Pengelolaan Berbasis BUMD: Kapal ini dioperasikan oleh Badan Usaha Milik Daerah (BUMD) PT Lampung Jasa Utama (LJU) yang bekerja sama dengan PT Damai Lautan Nusantara. Skema pengadaan dan operasional ini dilakukan melalui kerja sama BUMD tanpa membebani Anggaran Pendapatan dan Belanja Daerah (APBD), sekaligus diharapkan mampu mendongkrak Pendapatan Asli Daerah (PAD).</p>\r\n<p class=\"text-body-2\">Spesifikasi dan Kapasitas Besar: Dibangun di Fuzhou, Tiongkok mulai Januari 2024 hingga April 2025, kapal ini memiliki panjang 114,75 meter. Ukuran tersebut menempatkan KMP Dalom 1 sebagai kapal terbesar ketiga di lintas penyeberangan Merak&ndash;Bakauheni. Kapal ini mampu menampung kapasitas hingga 270 orang penumpang dan 200 unit kendaraan campuran.</p>\r\n<p class=\"text-body-2\">Diklaim Sebagai Kapal Tercepat: Gubernur Lampung juga menyebutkan bahwa KMP Dalom 1 merupakan kapal dengan laju tercepat yang saat ini beroperasi di rute perairan Selat Sunda.</p>\r\n</div>\r\n</div>\r\n<div class=\"passive d-grid gap_12 mb_40\">\r\n<h4>Fasilitas Eksekutif dengan Sentuhan Budaya Lokal</h4>\r\n<div class=\"text_secondary\">\r\n<p class=\"text-body-2\">Sebagai kapal berstandar eksekutif modern, KMP Dalom 1 didesain untuk menghadirkan pelayanan yang inklusif dan nyaman bagi para penumpang lintas pulau. Fasilitas unggulan yang tersedia di dalam kapal antara lain:</p>\r\n<ul class=\"text-body-2\">\r\n<li>Beragam pilihan ruang tunggu seperti VIP lounge, ruang keluarga (family room), ruang lesehan, hingga co-working space.</li>\r\n<li>Ruang khusus yang ramah bagi penumpang disabilitas, area bermain anak, musala, ruang menyusui, serta ruang pengobatan.</li>\r\n<li>Desain interior yang kental dengan identitas daerah, menampilkan nuansa khas Lampung melalui sentuhan motif kain tapis, ukiran tradisional, serta corak budaya lokal lainnya.</li>\r\n</ul>\r\n<p class=\"text-body-2\">Kehadiran KMP Dalom 1 diharapkan tidak hanya mempermudah mobilitas masyarakat, tetapi juga menjadi langkah strategis guna memperkuat konektivitas logistik dan pertumbuhan ekonomi antara Pulau Sumatra dan Jawa.</p>\r\n</div>\r\n</div>', 'front-20261004-e0a84443c2a9.png', NULL, 'published', '2026-10-04 12:48:50', 7, '2026-10-04 05:48:48', '2026-10-04 05:48:50'),
(8, 4, 'Logistics', 'Coba berita', 'Ini adalah berita uji coba', 'coba-berita', '<p>fffhfhfh</p>', 'front-20261004-f61fc229c6c9.webp', NULL, 'draft', NULL, 7, '2026-10-04 10:56:32', '2026-10-04 10:56:32'),
(9, 4, 'Logistics', 'Coba berita', 'Ini adalah berita uji coba', 'coba-berita-2', '<p>fffhfhfh</p>', 'front-20261004-d8fb5c6931e2.webp', NULL, 'draft', NULL, 7, '2026-10-04 10:56:41', '2026-10-04 10:56:41'),
(10, 0, 'Technology', 'TEs', 'Tes', 'tes', '<p>Tes</p>', 'front-20261007-e83d43b737de.jpg', 'main-20261007-a25c1737b782.jpg', 'draft', NULL, 8, '2026-10-07 04:03:39', '2026-10-07 06:20:13');

-- --------------------------------------------------------

--
-- Table structure for table `cta_jadwal`
--

CREATE TABLE `cta_jadwal` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `heading_text` varchar(255) NOT NULL,
  `button_text` varchar(100) NOT NULL,
  `jadwal_image` varchar(255) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `cta_jadwal`
--

INSERT INTO `cta_jadwal` (`id`, `heading_text`, `button_text`, `jadwal_image`, `status`, `published_at`, `created_by`, `updated_at`) VALUES
(1, 'Jadwal Pelayaran KMP DALOM 1', 'Jadwal Bulan Ini', 'jadwal-20261004-9a6e37c8d71f.png', 'draft', NULL, NULL, '2026-10-04 11:52:20'),
(2, 'Jadwal Pelayaran KMP DALOM 1', 'Jadwal Sepember 2026', 'jadwal-20261004-db08ba9b78cb.jpg', 'draft', NULL, 7, '2026-10-09 03:09:35'),
(3, 'Jadwal Kapal Dalom Oktober 2026', 'Jadwal Oktober 2026', 'jadwal-20261009-305ad30f55f0.png', 'published', '2026-10-09 10:09:42', 7, '2026-10-09 03:09:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `berita`
--
ALTER TABLE `berita`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_slug` (`slug`),
  ADD KEY `idx_status` (`status`,`published_at`),
  ADD KEY `idx_no_urut` (`no_urut`);

--
-- Indexes for table `cta_jadwal`
--
ALTER TABLE `cta_jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`,`published_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `berita`
--
ALTER TABLE `berita`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cta_jadwal`
--
ALTER TABLE `cta_jadwal`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
