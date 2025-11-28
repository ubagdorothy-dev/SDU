-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 28, 2025 at 01:38 PM
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'unassigned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`) VALUES
(1, 'SDU Director', 'director@sdu.edu.ph', '$2b$12$PDAPBeVIGaXns0FtDs8U0uzoRhmSjtl9WZhvKKO2vXpC9ZbMv8KGm', 'unit director'),
(2, 'Jane Doe', 'janedoe@gmail.com', '$2y$10$rvWFASVM6SJZpPEEVyFVPukE../pMDBTPuFTu7RcQXPtu7lJm4BM6', 'unassigned'),
(3, 'Jane Doe', 'janedoe.head@sdu.edu.ph', '$2y$10$YB9IHCM9B1amu/HxYoVgAuoX7GQlGDRdFHfxyzdXfDoEqdt5AxRmG', 'staff'),
(4, 'Jane Doe', 'head.janedoe@sdu.edu.ph', '$2y$10$7GRd/LBv9Dfqgqa2JFs.WeavB73aVN4YEQ639sLkI6d5.l8Zs.eMm', 'head'),
(5, 'John Doe', 'staff.joe@sdu.edu.ph', '$2y$10$vBN8gXP15P9ZKFVgXchy3OGUjQ5e8Nx0pdRLq2w9K5/yFEz9n9I.C', 'staff');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
