-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 12:24 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `projectmanagementdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `gamification`
--

CREATE TABLE `gamification` (
  `Gamification_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Points_Earned` int(11) DEFAULT 0,
  `Badges_Achieved` text DEFAULT NULL,
  `Leaderboard_Rank` int(11) DEFAULT NULL,
  `Level` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `redemptions`
--

CREATE TABLE `redemptions` (
  `Redemption_ID` int(11) NOT NULL,
  `Reward_ID` int(11) DEFAULT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `Redeemed_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `Reward_ID` int(11) NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Image_URL` text DEFAULT NULL,
  `Point_Cost` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`Reward_ID`, `Name`, `Description`, `Image_URL`, `Point_Cost`) VALUES
(1, 'Starbucks gift card', 'Gift Card', '', 20),
(2, 'Movie Tickets', '2x Movie Tickets ', '', 100);



--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `Task_ID` int(11) NOT NULL,
  `Task_Name` varchar(255) NOT NULL,
  `Description` text NOT NULL,
  `Assigned_To` int(11) DEFAULT NULL,
  `Points` int(11) NOT NULL DEFAULT 0,
  `Status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `Priority` enum('Low','Medium','High') DEFAULT 'Medium'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `tasksubmissions`
--

CREATE TABLE `tasksubmissions` (
  `Submission_ID` int(11) NOT NULL,
  `Task_ID` int(11) NOT NULL,
  `Submitted_By` int(11) NOT NULL,
  `Status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `Submission_Date` timestamp NOT NULL DEFAULT current_timestamp(),
  `Description` text DEFAULT NULL,
  `FilePath` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('Admin','Manager','Member') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_ID`, `Name`, `Email`, `Password`, `Role`) VALUES
(1, 'Admin User', 'admin@example.com', 'admin123', 'Admin'),
(2, 'Manager User', 'manager@example.com', 'manager123', 'Manager'),
(3, 'Member User', 'member@example.com', 'member123', 'Member'),
(4, 'Alice Developer', 'alice@example.com', 'password123', 'Member'),
(5, 'Bob Analyst', 'bob@example.com', 'password123', 'Member'),
(6, 'Carol Tester', 'carol@example.com', 'password123', 'Member');


--
-- Indexes for table `gamification`
--
ALTER TABLE `gamification`
  ADD PRIMARY KEY (`Gamification_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `redemptions`
--
ALTER TABLE `redemptions`
  ADD PRIMARY KEY (`Redemption_ID`),
  ADD KEY `Reward_ID` (`Reward_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`Reward_ID`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`Task_ID`),
  ADD KEY `Assigned_To` (`Assigned_To`);

--
-- Indexes for table `tasksubmissions`
--
ALTER TABLE `tasksubmissions`
  ADD PRIMARY KEY (`Submission_ID`),
  ADD KEY `Task_ID` (`Task_ID`),
  ADD KEY `Submitted_By` (`Submitted_By`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gamification`
--
ALTER TABLE `gamification`
  MODIFY `Gamification_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `redemptions`
--
ALTER TABLE `redemptions`
  MODIFY `Redemption_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `Reward_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `Task_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `tasksubmissions`
--
ALTER TABLE `tasksubmissions`
  MODIFY `Submission_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;


--
-- Constraints for table `gamification`
--
ALTER TABLE `gamification`
  ADD CONSTRAINT `gamification_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `redemptions`
--
ALTER TABLE `redemptions`
  ADD CONSTRAINT `redemptions_ibfk_1` FOREIGN KEY (`Reward_ID`) REFERENCES `rewards` (`Reward_ID`),
  ADD CONSTRAINT `redemptions_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`Assigned_To`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `tasksubmissions`
--
ALTER TABLE `tasksubmissions`
  ADD CONSTRAINT `tasksubmissions_ibfk_1` FOREIGN KEY (`Task_ID`) REFERENCES `tasks` (`Task_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasksubmissions_ibfk_2` FOREIGN KEY (`Submitted_By`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
