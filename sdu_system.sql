-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 03:57 PM
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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'Training Completed', 'Training marked completed: hbrgb', 0, '2025-11-30 11:38:18'),
(2, 7, 'Training Completed', 'Training marked completed: hbrgb', 0, '2025-11-30 11:38:18'),
(3, 1, 'Proof Uploaded', 'Proof of completion uploaded for: hbrgb', 0, '2025-11-30 11:38:37'),
(4, 7, 'Proof Uploaded', 'Proof of completion uploaded for: hbrgb', 0, '2025-11-30 11:38:37'),
(5, 1, 'Training Completed', 'Training marked completed: hdhiweihf', 0, '2025-11-30 11:40:27'),
(6, 7, 'Training Completed', 'Training marked completed: hdhiweihf', 0, '2025-11-30 11:40:27'),
(7, 1, 'Training Completed', 'Training marked completed: GERGE', 0, '2025-11-30 12:05:31'),
(8, 7, 'Training Completed', 'Training marked completed: GERGE', 0, '2025-11-30 12:05:31'),
(9, 1, 'Training Completed', 'Training marked completed: fhb', 0, '2025-11-30 12:07:17'),
(10, 7, 'Training Completed', 'Training marked completed: fhb', 0, '2025-11-30 12:07:17'),
(11, 1, 'Proof Uploaded', 'Proof of completion uploaded by Charlie Brown for: hdhiweihf', 0, '2025-11-30 12:07:29'),
(12, 7, 'Proof Uploaded', 'Proof of completion uploaded by Charlie Brown for: hdhiweihf', 0, '2025-11-30 12:07:29'),
(13, 3, 'Test Notification', 'This is a test notification sent from the test script.', 1, '2025-11-30 12:14:07'),
(14, 1, 'Training Completed', 'Training marked completed: Hello', 0, '2025-11-30 12:15:20'),
(15, 7, 'Training Completed', 'Training marked completed: Hello', 0, '2025-11-30 12:15:20'),
(16, 1, 'Proof Uploaded', 'Proof of completion uploaded by Charlie Brown for: GERGE', 0, '2025-11-30 13:04:12'),
(17, 7, 'Proof Uploaded', 'Proof of completion uploaded by Charlie Brown for: GERGE', 0, '2025-11-30 13:04:12'),
(18, 1, 'Proof Uploaded', 'Proof of completion uploaded by Charlie Brown for: ty', 0, '2025-11-30 14:33:19'),
(19, 7, 'Proof Uploaded', 'Proof of completion uploaded by Charlie Brown for: ty', 0, '2025-11-30 14:33:19');

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
-- Table structure for table `staff_details`
--

CREATE TABLE `staff_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `job_function` varchar(255) DEFAULT NULL,
  `office` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employment_status` varchar(50) DEFAULT NULL,
  `degree_attained` varchar(100) DEFAULT NULL,
  `degree_other` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_details`
--

INSERT INTO `staff_details` (`id`, `user_id`, `position`, `program`, `job_function`, `office`, `created_at`, `updated_at`, `employment_status`, `degree_attained`, `degree_other`) VALUES
(0, 8, 'Finance', 'Mathematics', 'IT Support', NULL, '2025-11-30 11:37:48', '2025-11-30 12:50:10', 'Permanent/Regular', 'Others', 'hello');

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

--
-- Dumping data for table `training_proofs`
--

INSERT INTO `training_proofs` (`id`, `training_id`, `user_id`, `file_path`, `status`, `reviewed_by`, `reviewed_at`, `uploaded_at`) VALUES
(1, 2, 8, 'uploads/proofs/proof_2_8_1764502716.jpg', 'pending', NULL, NULL, '2025-11-30 11:38:37'),
(2, 1, 8, 'uploads/proofs/proof_1_8_1764504449.png', 'pending', NULL, NULL, '2025-11-30 12:07:29'),
(3, 6, 8, 'uploads/proofs/proof_6_8_1764507852.jpg', 'pending', NULL, NULL, '2025-11-30 13:04:12'),
(4, 7, 8, 'uploads/proofs/proof_7_8_1764513199.png', 'pending', NULL, NULL, '2025-11-30 14:33:19');

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
  `venue` varchar(255) DEFAULT NULL,
  `proof_uploaded` tinyint(1) DEFAULT 0,
  `office_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nature` varchar(100) DEFAULT NULL,
  `scope` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_records`
--

INSERT INTO `training_records` (`id`, `user_id`, `title`, `description`, `start_date`, `end_date`, `status`, `venue`, `proof_uploaded`, `office_code`, `created_at`, `nature`, `scope`) VALUES
(1, 8, 'hdhiweihf', 'ehbrh', '2025-11-20', '2025-12-03', 'completed', 'Astoria', 1, 'ACCA', '2025-11-30 11:37:48', 'Probationary', 'Local'),
(2, 8, 'hbrgb', 'bvedfbhebh', '2025-11-19', '2025-11-29', 'completed', 'Astoria', 1, 'ACCA', '2025-11-30 11:38:17', NULL, NULL),
(3, 8, 'fhb', 'wrgreg', '2025-11-15', '2025-11-29', 'completed', 'Astoria', 0, 'ACCA', '2025-11-30 11:52:35', '', ''),
(4, 8, 'wgewrg', 'dfr', '2025-11-15', '2025-12-05', 'completed', 'Astoria', 0, 'ACCA', '2025-11-30 11:53:10', NULL, NULL),
(5, 7, 'Hello', 'dffg', '2025-11-28', '2025-11-29', 'completed', 'Astoria', 0, 'ACCA', '2025-11-30 11:58:36', 'Probationary', 'Regional'),
(6, 8, 'GERGE', 'GSDFG', '2025-11-20', '2025-11-29', 'completed', 'Astoria', 1, 'ACCA', '2025-11-30 12:05:09', 'Probationary', 'Regional'),
(7, 8, 'ty', 'eherth', '2025-11-29', '2025-11-29', 'completed', 'Astoria', 1, 'ACCA', '2025-11-30 12:29:57', 'Internal', 'Local'),
(8, 8, 'herg', 'erhgerg', '2025-11-30', '2025-12-03', 'ongoing', 'Astoria', 0, 'ACCA', '2025-11-30 12:30:14', '', 'Local'),
(10, 8, 'fd', 'sgr', '2025-11-30', '2025-12-02', 'ongoing', 'Astoria', 0, 'ACCA', '2025-11-30 14:46:29', 'External', 'Regional');

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
(6, 'Maria Santos', 'staff.maria@sdu.edu.ph', '$2y$10$4C.90Qy3rvW10geYYpFe4.c6F0kP9A0GnnYI1MHjvwHS5sPYE3Qn6', 'staff', 'ACCA', 1, '2025-11-30 05:31:03'),
(7, 'Max Verstappen', 'head.max@sdu.edu.ph', '$2y$10$Y8/hkvR/ZkxKR/17MTtuVumzBPiYUlBWTYxBE6MUNF9y6sme5DjD.', 'head', 'ACCA', 1, '2025-11-30 08:37:17'),
(8, 'Charlie Brown', 'staff.brown@sdu.edu.ph', '$2y$10$2XD7HGkaRk932dpFwaRmquXe.P.vxLVZS1g3W7t1BGyDKzjRqb6R.', 'staff', 'ACCA', 1, '2025-11-30 10:00:22'),
(9, 'Mae Mae', 'staff.ma@sdu.edu.ph', '$2y$10$ZCYfv88BPpAJZmzaRHkjsej87I1OFiizlfw9ZpXt1iJNcfWD15DL6', 'staff', 'ACES', 0, '2025-11-30 14:50:17');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `training_proofs`
--
ALTER TABLE `training_proofs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `training_records`
--
ALTER TABLE `training_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
