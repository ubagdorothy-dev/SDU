-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 06:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sdu`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `name`, `code`) VALUES
(1, 'Ateneo Center for Culture & the Arts', 'ACCA'),
(2, 'Ateneo Center for Environment & Sustainability', 'ACES'),
(3, 'Ateneo Center for Leadership & Governance', 'ACLG'),
(4, 'Ateneo Peace Center', 'APC'),
(5, 'Center for Community Extension Services', 'CCES'),
(6, 'Ateneo Learning and Teaching Excellence Center', 'ALTEC');

-- --------------------------------------------------------

--
-- Table structure for table `training_proofs`
--

CREATE TABLE `training_proofs` (
  `id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_path` varchar(1024) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_records`
--

CREATE TABLE `training_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'upcoming',
  `employment_status` varchar(50) DEFAULT NULL,
  `degree_attained` varchar(100) DEFAULT NULL,
  `degree_other` varchar(255) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `proof_uploaded` tinyint(1) DEFAULT 0,
  `office_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'unassigned',
  `office_code` varchar(50) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `office_code`, `is_approved`, `created_at`) VALUES
(1, 'SDU Director', 'director@sdu.edu.ph', '$2b$12$PDAPBeVIGaXns0FtDs8U0uzoRhmSjtl9WZhvKKO2vXpC9ZbMv8KGm', 'unit director', NULL, 1, '2025-11-30 05:31:03'),
(2, 'Jane Doe', 'janedoe@gmail.com', '$2y$10$rvWFASVM6SJZpPEEVyFVPukE../pMDBTPuFTu7RcQXPtu7lJm4BM6', 'unassigned', NULL, 0, '2025-11-30 05:31:03'),
(3, 'Jane Doe', 'janedoe.head@sdu.edu.ph', '$2y$10$YB9IHCM9B1amu/HxYoVgAuoX7GQlGDRdFHfxyzdXfDoEqdt5AxRmG', 'staff', NULL, 1, '2025-11-30 05:31:03'),
(4, 'Jane Doe', 'head.janedoe@sdu.edu.ph', '$2y$10$7GRd/LBv9Dfqgqa2JFs.WeavB73aVN4YEQ639sLkI6d5.l8Zs.eMm', 'head', NULL, 1, '2025-11-30 05:31:03'),
(5, 'John Doe', 'staff.joe@sdu.edu.ph', '$2y$10$vBN8gXP15P9ZKFVgXchy3OGUjQ5e8Nx0pdRLq2w9K5/yFEz9n9I.C', 'staff', NULL, 1, '2025-11-30 05:31:03'),
(6, 'Maria Santos', 'staff.maria@sdu.edu.ph', '$2y$10$4C.90Qy3rvW10geYYpFe4.c6F0kP9A0GnnYI1MHjvwHS5sPYE3Qn6', 'staff', 'ACCA', 1, '2025-11-30 05:31:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `training_proofs`
--
ALTER TABLE `training_proofs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `training_id` (`training_id`);

--
-- Indexes for table `training_records`
--
ALTER TABLE `training_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `office_code` (`office_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `training_proofs`
--
ALTER TABLE `training_proofs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_records`
--
ALTER TABLE `training_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`office_code`) REFERENCES `offices` (`code`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
