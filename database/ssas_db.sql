-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 20, 2026 at 05:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ssas_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `allocation_record`
--

CREATE TABLE `allocation_record` (
  `allocationID` int(11) NOT NULL,
  `studentID` varchar(20) NOT NULL,
  `supervisorID` varchar(20) NOT NULL,
  `allocationDate` datetime NOT NULL,
  `allocationMethod` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allocation_record`
--

INSERT INTO `allocation_record` (`allocationID`, `studentID`, `supervisorID`, `allocationDate`, `allocationMethod`) VALUES
(1, '24WMR08054', '1129', '2026-05-27 01:13:26', 'Supervisor Decision'),
(2, '24WMR08053', '1129', '2026-06-01 23:41:13', 'System Auto-Match'),
(3, '24WMR08049', '1129', '2026-06-01 23:41:13', 'System Auto-Match'),
(4, '24WMR08139', '1113', '2026-07-12 10:41:29', 'Approved Request'),
(5, '24WMR09139', '1234', '2026-07-13 12:03:03', 'Approved Request'),
(6, '24WMR03435', '9383', '2026-07-27 13:31:08', 'Approved Request'),
(7, '24WMR01123', '9383', '2026-08-08 02:11:00', 'System Auto-Match'),
(8, '24WMR08119', '9383', '2026-08-08 02:11:00', 'System Auto-Match'),
(9, '24WMR08120', '1129', '2026-08-08 23:07:42', 'Approved Request'),
(10, '24WMR08110', '1129', '2026-08-08 23:14:30', 'Approved Request'),
(11, '24WMR08100', '1129', '2026-08-09 18:15:15', 'Approved Request'),
(12, '24WMR08130', '1129', '2026-08-09 18:44:53', 'Approved Request'),
(13, '24WMR08140', '1129', '2026-08-09 22:28:35', 'Approved Request');

-- --------------------------------------------------------

--
-- Table structure for table `allocation_window_config`
--

CREATE TABLE `allocation_window_config` (
  `configID` int(11) NOT NULL,
  `initialAllocationDate` datetime NOT NULL,
  `finalAllocationDate` datetime NOT NULL,
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allocation_window_config`
--

INSERT INTO `allocation_window_config` (`configID`, `initialAllocationDate`, `finalAllocationDate`, `updatedAt`) VALUES
(1, '2026-08-16 08:15:00', '2026-08-19 12:59:00', '2026-08-16 11:55:31');

-- --------------------------------------------------------

--
-- Table structure for table `application_request`
--

CREATE TABLE `application_request` (
  `requestID` int(11) NOT NULL,
  `studentID` varchar(20) NOT NULL,
  `supervisorID` varchar(20) NOT NULL,
  `projectTitle` varchar(255) NOT NULL,
  `proposalPDFPath` varchar(255) NOT NULL,
  `applicationDate` datetime NOT NULL,
  `ttlExpirationTimestamp` datetime NOT NULL,
  `decisionStatus` varchar(50) NOT NULL,
  `supervisorComment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_request`
--

INSERT INTO `application_request` (`requestID`, `studentID`, `supervisorID`, `projectTitle`, `proposalPDFPath`, `applicationDate`, `ttlExpirationTimestamp`, `decisionStatus`, `supervisorComment`) VALUES
(1, '24WMR08054', '1129', 'Talent', '../../storage/proposals/24WMR08054_20260526191326.pdf', '2026-05-27 01:13:26', '2026-06-03 01:13:26', 'Accepted', ''),
(2, '24WMR08049', '1129', 'Doue', '../storage/proposals/24WMR08049_20260604040305.pdf', '2026-06-04 04:03:05', '2026-06-07 04:03:05', 'Accepted', 'good job'),
(3, '24WMR08053', '1129', 'Proposal Requested', '', '2026-07-12 10:02:47', '2026-07-19 10:02:47', 'Proposal Requested', 'Supervisor requested your project proposal after auto-allocation.'),
(4, '24WMR08139', '1113', 'Thermal Punch', 'storage/proposals/24WMR08139_20260712103931.pdf', '2026-07-12 10:39:31', '2026-07-15 10:39:31', 'Rejected', 'Change this one a bit'),
(5, '24WMR08139', '1113', 'Thermal Punch -v2', 'storage/proposals/24WMR08139_20260712104101.pdf', '2026-07-12 10:41:01', '2026-07-15 10:41:01', 'Accepted', 'So goooood!'),
(6, '24WMR09139', '1234', 'Themral Punch', 'storage/proposals/24WMR09139_20260713120208.pdf', '2026-07-13 12:02:08', '2026-07-16 12:02:08', 'Accepted', 'good'),
(7, '24WMR03435', '5836', 'Thermal Punch', 'storage/proposals/24WMR03435_20260727104942.pdf', '2026-07-27 10:49:42', '2026-07-30 10:49:42', 'Withdrawn', 'Automatically withdrawn because another proposal was accepted.'),
(8, '24WMR03435', '9383', 'Thermal Punch', 'storage/proposals/24WMR03435_20260727105006.pdf', '2026-07-27 10:50:06', '2026-07-30 10:50:06', 'Accepted', 'Very good'),
(9, '24WMR03435', '1129', 'Thermal Punch', 'storage/proposals/24WMR03435_20260727105028.pdf', '2026-07-27 10:50:28', '2026-07-30 10:50:28', 'Withdrawn', 'Automatically withdrawn because another proposal was accepted.'),
(10, '24WMR08120', '1129', 'Thermal Punch', 'storage/proposals/24WMR08120_20260808224341.pdf', '2026-08-08 22:43:41', '2026-08-11 22:43:41', 'Accepted', 'Automated testing: Proposal looks good. Approved.'),
(11, '24WMR08110', '1129', 'Thermal Punch -v2', 'storage/proposals/24WMR08110_20260808231048.pdf', '2026-08-08 23:10:48', '2026-08-11 23:10:48', 'Accepted', 'Automated testing: Proposal looks good. Approved.'),
(28, '24WMR08100', '1129', 'Thermal Punch', 'storage/proposals/24WMR08100_20260809181357.pdf', '2026-08-09 18:13:57', '2026-08-12 18:13:57', 'Accepted', 'Nice'),
(29, '24WMR08130', '1129', 'Testing', 'storage/proposals/24WMR08130_20260809184426.pdf', '2026-08-09 18:44:26', '2026-08-12 18:44:26', 'Accepted', 'So Good'),
(30, '24WMR08140', '1129', 'PSG Champions League', 'storage/proposals/24WMR08140_20260809222804.pdf', '2026-08-09 22:28:04', '2026-08-12 22:28:04', 'Accepted', 'Very good'),
(31, '24WMR08150', '1129', 'NFR Performance Test', 'storage/proposals/24WMR08150_20260816123901.pdf', '2026-08-16 12:39:01', '2026-08-19 12:39:01', 'Rejected', 'The proposal is not suitable for my current supervision area. Please revise the topic and resubmit.');

-- --------------------------------------------------------

--
-- Table structure for table `auto_allocation_log`
--

CREATE TABLE `auto_allocation_log` (
  `logID` int(11) NOT NULL,
  `triggeredByAdminID` varchar(20) DEFAULT NULL,
  `triggeredAt` datetime NOT NULL DEFAULT current_timestamp(),
  `finalAllocationDate` datetime DEFAULT NULL,
  `eligibleCount` int(11) NOT NULL DEFAULT 0,
  `matchedCount` int(11) NOT NULL DEFAULT 0,
  `unassignedCount` int(11) NOT NULL DEFAULT 0,
  `logStatus` varchar(30) NOT NULL,
  `resultMessage` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auto_allocation_log`
--

INSERT INTO `auto_allocation_log` (`logID`, `triggeredByAdminID`, `triggeredAt`, `finalAllocationDate`, `eligibleCount`, `matchedCount`, `unassignedCount`, `logStatus`, `resultMessage`) VALUES
(1, 'A001', '2026-06-02 00:15:34', '2026-06-01 23:20:00', 0, 0, 0, 'NO_UNASSIGNED', 'No unassigned eligible students found.'),
(2, 'A001', '2026-06-22 01:33:12', '2026-06-01 23:20:00', 0, 0, 0, 'NO_UNASSIGNED', 'No unassigned eligible students found.'),
(3, 'A001', '2026-06-22 02:11:29', '2026-06-01 23:20:00', 0, 0, 0, 'NO_UNASSIGNED', 'No unassigned eligible students found.'),
(4, 'A001', '2026-06-22 02:13:11', '2026-06-01 23:20:00', 0, 0, 0, 'NO_UNASSIGNED', 'No unassigned eligible students found.'),
(5, 'A001', '2026-07-11 06:12:27', '2026-06-01 23:20:00', 0, 0, 0, 'NO_UNASSIGNED', 'No unassigned eligible students found.'),
(6, 'A001', '2026-07-11 11:29:03', '2026-06-01 23:20:00', 0, 0, 0, 'NO_UNASSIGNED', 'No unassigned eligible students found.'),
(7, 'A001', '2026-07-12 10:08:42', '2026-06-01 23:20:00', 0, 0, 0, 'NO_UNASSIGNED', 'No unassigned eligible students found.'),
(8, 'A001', '2026-08-08 02:11:00', '2026-07-27 12:59:00', 2, 2, 0, 'COMPLETED', 'Auto-Allocation Complete: 2 students successfully matched. Notification records generated.'),
(9, 'A001', '2026-08-08 02:41:08', '2026-07-27 12:59:00', 0, 0, 0, 'NO_UNASSIGNED', 'No unassigned eligible students found.');

-- --------------------------------------------------------

--
-- Table structure for table `auto_allocation_notification`
--

CREATE TABLE `auto_allocation_notification` (
  `notificationID` int(11) NOT NULL,
  `logID` int(11) NOT NULL,
  `recipientUserID` varchar(20) NOT NULL,
  `notificationType` varchar(50) NOT NULL,
  `notificationMessage` varchar(500) NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp(),
  `readStatus` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auto_allocation_notification`
--

INSERT INTO `auto_allocation_notification` (`notificationID`, `logID`, `recipientUserID`, `notificationType`, `notificationMessage`, `createdAt`, `readStatus`) VALUES
(1, 8, '24WMR01123', 'StudentAutoAllocation', 'You have been auto-allocated to supervisor 9383.', '2026-08-08 02:11:00', 0),
(2, 8, '9383', 'SupervisorAutoAllocation', 'Student 24WMR01123 has been auto-allocated to your supervision list.', '2026-08-08 02:11:00', 0),
(3, 8, '24WMR08119', 'StudentAutoAllocation', 'You have been auto-allocated to supervisor 9383.', '2026-08-08 02:11:00', 0),
(4, 8, '9383', 'SupervisorAutoAllocation', 'Student 24WMR08119 has been auto-allocated to your supervision list.', '2026-08-08 02:11:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `eligibility_rule_configuration`
--

CREATE TABLE `eligibility_rule_configuration` (
  `ruleID` int(11) NOT NULL,
  `minimumCGPA` decimal(3,2) NOT NULL DEFAULT 2.00,
  `requiredNextSemester` varchar(10) NOT NULL DEFAULT 'Y2S3',
  `blockedAcademicStatus` varchar(50) NOT NULL DEFAULT 'EF',
  `updatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eligibility_rule_configuration`
--

INSERT INTO `eligibility_rule_configuration` (`ruleID`, `minimumCGPA`, `requiredNextSemester`, `blockedAcademicStatus`, `updatedAt`) VALUES
(1, 2.00, 'Y2S3', 'EF', '2026-08-08 02:16:02');

-- --------------------------------------------------------

--
-- Table structure for table `past_project`
--

CREATE TABLE `past_project` (
  `projectID` int(11) NOT NULL,
  `supervisorID` varchar(20) NOT NULL,
  `projectTitle` varchar(255) NOT NULL,
  `completionYear` int(11) NOT NULL,
  `alumniName` varchar(100) NOT NULL,
  `projectDescription` text DEFAULT NULL,
  `projectPDFPath` varchar(255) DEFAULT NULL,
  `projectImagePath` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `past_project`
--

INSERT INTO `past_project` (`projectID`, `supervisorID`, `projectTitle`, `completionYear`, `alumniName`, `projectDescription`, `projectPDFPath`, `projectImagePath`) VALUES
(2, '1129', 'PSG Champions League', 2026, 'Luis Enrique', '2 star, back 2 back championff', 'storage/past_projects/1129_20260602133016_567dbe31.pdf', 'storage/past_project_images/1129_20260602173241_ba5a93ae.jpg'),
(3, '1129', 'Katalon Test Past Project 1786884239784', 2025, 'Test Alumni', 'This project demonstrates a web-based final year project system with database integration, validation, and reporting features.', 'storage/past_projects/1129_20260816144424_ee04b3f1.pdf', 'storage/past_project_images/1129_20260816144424_92d12477.png');

-- --------------------------------------------------------

--
-- Table structure for table `quota_configuration`
--

CREATE TABLE `quota_configuration` (
  `quotaID` int(11) NOT NULL,
  `quotaTierName` varchar(50) NOT NULL,
  `maxSuperviseesAllowed` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quota_configuration`
--

INSERT INTO `quota_configuration` (`quotaID`, `quotaTierName`, `maxSuperviseesAllowed`) VALUES
(1, 'Tier 1 - Full-Time Lecturer', 30),
(2, 'Tier 2 - Part-Time Lecturer', 10),
(3, 'Tier 3 - Administrative Position Lecturer', 15);

-- --------------------------------------------------------

--
-- Table structure for table `research_tag`
--

CREATE TABLE `research_tag` (
  `tagID` int(11) NOT NULL,
  `tagName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `research_tag`
--

INSERT INTO `research_tag` (`tagID`, `tagName`) VALUES
(1, 'Artificial Intelligence'),
(17, 'Blockchain'),
(10, 'Cloud Computing'),
(12, 'Computer Vision'),
(6, 'Cybersecurity'),
(4, 'Data Analytics'),
(5, 'Data Science'),
(14, 'Database Systems'),
(3, 'Deep Learning'),
(16, 'Game Development'),
(15, 'Human Computer Interaction'),
(19, 'Information Systems'),
(11, 'Internet of Things'),
(2, 'Machine Learning'),
(9, 'Mobile Application Development'),
(13, 'Natural Language Processing'),
(18, 'Network Security'),
(20, 'Project Management'),
(7, 'Software Engineering'),
(8, 'Web Development');

-- --------------------------------------------------------

--
-- Table structure for table `student_eligibility_record`
--

CREATE TABLE `student_eligibility_record` (
  `studentID` varchar(20) NOT NULL,
  `universityEmail` varchar(100) NOT NULL,
  `icNumber` varchar(30) DEFAULT NULL,
  `fullName` varchar(100) NOT NULL,
  `programme` varchar(100) NOT NULL,
  `intakeBatch` varchar(20) NOT NULL,
  `currentSem` varchar(10) NOT NULL,
  `academicStatus` varchar(50) NOT NULL,
  `cgpa` decimal(5,4) NOT NULL,
  `eligibilityStatus` tinyint(1) NOT NULL DEFAULT 0,
  `importedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_eligibility_record`
--

INSERT INTO `student_eligibility_record` (`studentID`, `universityEmail`, `icNumber`, `fullName`, `programme`, `intakeBatch`, `currentSem`, `academicStatus`, `cgpa`, `eligibilityStatus`, `importedAt`) VALUES
('24WMR01123', 'james-wp23@student.tarc.edu.my', '9.48293E+11', 'JAMES', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, 1, '2026-07-27 06:18:27'),
('24WMR01344', 'kevin-wp23@student.tarc.edu.my', '50234567234', 'KEVIN', 'RSW', '2024', 'Y2S2', 'EP', 1.9000, 0, '2026-07-27 06:18:27'),
('24WMR03435', 'janice-wp23@student.tarc.edu.my', '50223456678', 'JANICE', 'RSD', '2024', 'Y2S2', 'EP', 3.5000, 1, '2026-07-27 06:18:27'),
('24WMR08039', 'yenhs-wp23@student.tarc.edu.my', '50505101847', 'Yen Han Soon', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, 1, '2026-08-20 23:45:31'),
('24WMR08049', 'yongcx-wp23@student.tarc.edu.my', '50517101847', 'YONG CHONG XIN', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, 1, '2026-08-20 23:45:31'),
('24WMR08050', 'tankc-wp23@student.tarc.edu.my', '50101101234', 'TAN KAI CHUN', 'RSW', '2024', 'Y2S2', 'EP', 1.9000, 0, '2026-08-20 23:45:31'),
('24WMR08051', 'limml-wp23@student.tarc.edu.my', '50202105678', 'LIM MEI LING', 'RSD', '2024', 'Y2S2', 'EP', 3.5000, 1, '2026-08-20 23:45:31'),
('24WMR08052', 'leejh-wp23@student.tarc.edu.my', '50303109999', 'LEE JUN HAO', 'RSW', '2024', 'Y2S2', 'EF', 2.8000, 0, '2026-08-20 23:45:31'),
('24WMR08053', 'nuraina-wp23@student.tarc.edu.my', '50404106543', 'NUR AINA BINTI AZMAN', 'RIT', '2024', 'Y2S2', 'EP', 3.7500, 1, '2026-08-20 23:45:31'),
('24WMR08054', 'kumarv-wp23@student.tarc.edu.my', '50505108888', 'KUMAR VELAN', 'RSW', '2024', 'Y2S2', 'EP', 2.0100, 1, '2026-08-20 23:45:31'),
('24WMR08059', 'giannis-wp23@student.tarc.edu.my', '50518101847', 'Giannis', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, 1, '2026-08-20 23:45:31'),
('24WMR08100', 'marshell-wp23@student.tarc.edu.my', '51117103447', 'MARSHELL', 'RSW', '2024', 'Y2S2', 'EP', 2.8000, 1, '2026-08-08 22:34:48'),
('24WMR08110', 'johndoe-wp23@student.tarc.edu.my', '50517433447', 'JOHN DOE', 'RSW', '2024', 'Y2S2', 'EP', 3.0000, 1, '2026-08-08 22:34:48'),
('24WMR08119', 'jackie-wp23@student.tarc.edu.my', '51010110033', 'JACKIE', 'RIT', '2024', 'Y2S2', 'EP', 3.2000, 1, '2026-07-12 10:15:06'),
('24WMR08120', 'joshua-wp23@student.tarc.edu.my', '50517103447', 'JOSHUA', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, 1, '2026-08-08 22:34:48'),
('24WMR08130', 'rayden-wp23@student.tarc.edu.my', '50517101237', 'RAYDEN', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, 1, '2026-08-09 18:31:03'),
('24WMR08139', 'jay-wp23@student.tarc.edu.my', '123456789', 'JAY', 'RIT', '2024', 'Y2S2', 'EP', 4.0000, 1, '2026-07-13 11:53:49'),
('24WMR08140', 'sherry-wp23@student.tarc.edu.my', '50517444447', 'SHERRY', 'RSW', '2024', 'Y2S2', 'EP', 3.0000, 1, '2026-08-09 18:31:03'),
('24WMR08150', 'jelly-wp23@student.tarc.edu.my', '51123453447', 'JELLY', 'RSW', '2024', 'Y2S2', 'EP', 2.8000, 1, '2026-08-09 18:31:03'),
('24WMR09139', 'henry-wp23@student.tarc.edu.my', '123456789', 'HENRY', 'RIT', '2024', 'Y2S2', 'EP', 4.0000, 1, '2026-07-13 12:00:49');

-- --------------------------------------------------------

--
-- Table structure for table `student_profile`
--

CREATE TABLE `student_profile` (
  `studentID` varchar(20) NOT NULL,
  `programme` varchar(100) NOT NULL,
  `intakeBatch` varchar(20) NOT NULL,
  `currentSem` varchar(10) NOT NULL DEFAULT 'Y1S1',
  `academicStatus` varchar(50) NOT NULL,
  `cgpa` decimal(5,4) NOT NULL,
  `contactNumber` varchar(20) DEFAULT NULL,
  `personalBio` varchar(500) DEFAULT NULL,
  `avatarFilePath` varchar(255) DEFAULT NULL,
  `linkedInURL` varchar(255) DEFAULT NULL,
  `githubURL` varchar(255) DEFAULT NULL,
  `portfolioURL` varchar(255) DEFAULT NULL,
  `eligibilityStatus` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_profile`
--

INSERT INTO `student_profile` (`studentID`, `programme`, `intakeBatch`, `currentSem`, `academicStatus`, `cgpa`, `contactNumber`, `personalBio`, `avatarFilePath`, `linkedInURL`, `githubURL`, `portfolioURL`, `eligibilityStatus`) VALUES
('24WMR01123', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR03435', 'RSD', '2024', 'Y2S2', 'EP', 3.5000, '', 'My name is Janice', NULL, '', '', '', 1),
('24WMR08039', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08049', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, '012-333 4444', 'Hello, My name is Yongfdfsadffdafsfda', '../../storage/profile_photos/24WMR08049_20260807121626.jpg', 'https://Ting', 'https://Ting', 'http://Ting', 1),
('24WMR08051', 'RSD', '2024', 'Y2S2', 'EP', 3.5000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08053', 'RIT', '2024', 'Y2S2', 'EP', 3.7500, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08054', 'RSW', '2024', 'Y2S2', 'EP', 2.0100, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08059', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08100', 'RSW', '2024', 'Y2S2', 'EP', 2.8000, '012-345 6789', 'Software Engineering final year student passionate about UI/UX anffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffd software testing.', '../../storage/profile_photos/24WMR08100_20260809103914.jpg', 'https://linkedin.com/in/username', 'https://github.com/username', 'https://yourportfolio.com', 1),
('24WMR08110', 'RSW', '2024', 'Y2S2', 'EP', 3.0000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08119', 'RIT', '2024', 'Y2S2', 'EP', 3.2000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08120', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08130', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08139', 'RIT', '2024', 'Y2S2', 'EP', 4.0000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08140', 'RSW', '2024', 'Y2S2', 'EP', 3.0000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08150', 'RSW', '2024', 'Y2S2', 'EP', 2.8000, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR09139', 'RIT', '2024', 'Y2S2', 'EP', 4.0000, NULL, NULL, NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_tag_selection`
--

CREATE TABLE `student_tag_selection` (
  `studentID` varchar(20) NOT NULL,
  `tagID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_tag_selection`
--

INSERT INTO `student_tag_selection` (`studentID`, `tagID`) VALUES
('24WMR03435', 6),
('24WMR08049', 2),
('24WMR08049', 4),
('24WMR08049', 14),
('24WMR08049', 16),
('24WMR08049', 19),
('24WMR08100', 1),
('24WMR08100', 10),
('24WMR08100', 17);

-- --------------------------------------------------------

--
-- Table structure for table `supervisor_profile`
--

CREATE TABLE `supervisor_profile` (
  `supervisorID` varchar(20) NOT NULL,
  `quotaID` int(11) NOT NULL,
  `assignedQuotaLimit` int(11) DEFAULT NULL,
  `employmentCategory` varchar(50) NOT NULL,
  `activeTime` varchar(100) DEFAULT NULL,
  `introVideoLink` varchar(255) DEFAULT NULL,
  `programme` varchar(100) NOT NULL,
  `supervisorBio` varchar(500) DEFAULT NULL,
  `introVideoDescription` varchar(500) DEFAULT NULL,
  `introVideoStatus` varchar(20) NOT NULL DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor_profile`
--

INSERT INTO `supervisor_profile` (`supervisorID`, `quotaID`, `assignedQuotaLimit`, `employmentCategory`, `activeTime`, `introVideoLink`, `programme`, `supervisorBio`, `introVideoDescription`, `introVideoStatus`) VALUES
('1113', 2, 1, 'Part-Time Lecturer', NULL, NULL, 'RIT', NULL, NULL, 'draft'),
('1129', 1, 28, 'Full-Time Lecturer', 'Monday 2:00 PM - 4:00 PM', 'https://youtu.be/iI34LYmJ1Fs?si=_ssHbfvB8vdbTF5z', 'RSW', 'I supervise final year projects related to web application development, database design, and applied software engineering.', 'This introductory video explains my supervision approach, research interests, and expectations for final year project students.', 'published'),
('1234', 2, 1, 'Part-Time Lecturer', NULL, NULL, 'RSW', NULL, NULL, 'draft'),
('5836', 1, 9, 'Full-Time Lecturer', 'Consultation by appointment', NULL, 'RSW', NULL, NULL, 'draft'),
('9383', 1, 30, 'Full-Time Lecturer', NULL, NULL, 'RSW', NULL, NULL, 'draft'),
('9484', 3, 14, 'Academic Director', NULL, NULL, 'RDS', NULL, NULL, 'draft');

-- --------------------------------------------------------

--
-- Table structure for table `supervisor_review`
--

CREATE TABLE `supervisor_review` (
  `reviewID` int(11) NOT NULL,
  `allocationID` int(11) NOT NULL,
  `trueStudentID` varchar(20) NOT NULL,
  `starRating` int(11) NOT NULL CHECK (`starRating` >= 1 and `starRating` <= 5),
  `textFeedback` varchar(1000) DEFAULT NULL,
  `isAnonymous` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor_review`
--

INSERT INTO `supervisor_review` (`reviewID`, `allocationID`, `trueStudentID`, `starRating`, `textFeedback`, `isAnonymous`) VALUES
(1, 3, '24WMR08049', 5, 'Good', 0),
(2, 6, '24WMR03435', 2, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 1),
(3, 11, '24WMR08100', 5, 'Excellent guidance and support throughout the project. Highly recommended supervisor.', 0),
(5, 12, '24WMR08130', 3, 'Great supervisor support.', 1),
(6, 13, '24WMR08140', 5, 'Excellent supervision.', 0);

-- --------------------------------------------------------

--
-- Table structure for table `supervisor_tag_selection`
--

CREATE TABLE `supervisor_tag_selection` (
  `supervisorID` varchar(20) NOT NULL,
  `tagID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor_tag_selection`
--

INSERT INTO `supervisor_tag_selection` (`supervisorID`, `tagID`) VALUES
('1113', 2),
('1113', 4),
('1113', 14),
('1113', 16),
('1113', 19),
('1129', 1),
('1129', 6),
('1129', 10),
('1129', 12),
('1129', 17);

-- --------------------------------------------------------

--
-- Table structure for table `system_phase_timeline`
--

CREATE TABLE `system_phase_timeline` (
  `phaseID` int(11) NOT NULL,
  `phaseName` varchar(50) NOT NULL,
  `startTimestamp` datetime NOT NULL,
  `endTimestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_phase_timeline`
--

INSERT INTO `system_phase_timeline` (`phaseID`, `phaseName`, `startTimestamp`, `endTimestamp`) VALUES
(1, 'Submission Phase', '2026-08-16 08:15:00', '2026-08-19 12:59:00'),
(2, 'Auto-Allocation Phase', '2026-08-19 12:59:00', '2026-08-20 13:01:00'),
(3, 'Review Period', '2026-08-20 13:01:00', '2026-08-21 18:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userID` varchar(20) NOT NULL,
  `fullName` varchar(100) NOT NULL,
  `universityEmail` varchar(100) NOT NULL,
  `systemRole` varchar(50) NOT NULL,
  `activeStatus` tinyint(1) NOT NULL DEFAULT 1,
  `profilePhotoPath` varchar(255) DEFAULT NULL,
  `resetToken` varchar(64) DEFAULT NULL,
  `resetExpires` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userID`, `fullName`, `universityEmail`, `systemRole`, `activeStatus`, `profilePhotoPath`, `resetToken`, `resetExpires`, `password`) VALUES
('1113', 'Robin', 'robin@tarc.edu.my', 'Supervisor', 1, NULL, NULL, NULL, '$2y$10$kGRPSYoA.Hq3T9lYiSrOo.lUVI0qx2ddAKe0arTQ98vC.oYXVQpOa'),
('1129', 'Lee Zi Qing', 'leezq1129@tarc.edu.my', 'Supervisor', 1, '../../storage/profile_photos/1129_20260525163616.jpg', NULL, NULL, '$2y$10$aoNUSlUmYjBiCRL34IfdDeReESSTRitm2L1jSElVbmLZ1Fd6ok02O'),
('1234', 'Hiii', 'hiii@tarc.edu.my', 'Supervisor', 1, NULL, NULL, NULL, '$2y$10$tU7RdDOTIgJzzaPd420H2.wPvXSFHBPeMSrItZVnAPm3cQzTzL.8O'),
('24WMR01123', 'JAMES', 'james-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$Ajg4qOT9fyU.8eZQnLne2.DhNte.zG38SPtsPAVKMMvpRo0Kx3Zdm'),
('24WMR03435', 'JANICE', 'janice-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$UzwLmPlu6a2QaS0rgnCU4.4uPxCV2.NjU5aS3BdYlWIwhV57DvE.u'),
('24WMR08039', 'Yen Han Soon', 'yenhs-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$KCGyjYkk/nmcBz9gleM.3uWvnATyk.F/exHKFMcGpIHFzKnpoaclO'),
('24WMR08049', 'YONG CHONG XIN', 'yongcx-wp23@student.tarc.edu.my', 'Student', 1, '../../storage/profile_photos/24WMR08049_20260807122103.png', NULL, NULL, '$2y$10$PZCq.e7gI.YUdn8I5UZigu.5jKc8x.x/wODmoXkSSvIpIlFMzYScC'),
('24WMR08051', 'LIM MEI LING', 'limml-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$QulI2Tng8s85OjCkBAY8..y7/ouWphD7A9LPd0CW53s7ECOCVHW8C'),
('24WMR08053', 'NUR AINA BINTI AZMAN', 'nuraina-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$ScH.VgbNZfispW6.hEAJZenE9NWfYtYISk9ERQiN.PmwmdPYQ0eIG'),
('24WMR08054', 'KUMAR VELAN', 'kumarv-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$gmq.hlzn8/oeB323GtBjZuezk6hxfrDSw47PSrOMx/CQrNOmpK81W'),
('24WMR08059', 'Giannis', 'giannis-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$ngcsW3zBJzZg7bL0ZXvFTOiey2BtuXDyIwSQC4ODmbesRi1CIK17m'),
('24WMR08100', 'MARSHELL', 'marshell-wp23@student.tarc.edu.my', 'Student', 1, '../../storage/profile_photos/24WMR08100_20260809103914.jpg', NULL, NULL, '$2y$10$TBOviGg.uF.hONky6zK3wOgWRNeFml6Jauh6MHEPEzl3JsoHUatvO'),
('24WMR08110', 'JOHN DOE', 'johndoe-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$hDd7DXkDjig7YjAG4U5.z.3wsbvgTrT.q0GA2lAo2ZSNXrjvsB2jW'),
('24WMR08119', 'JACKIE', 'jackie-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$bBN2.pPO16naPSk4aGdSYO.0W0iJ5FMASFQAyK1I7aqllwVq37tmu'),
('24WMR08120', 'JOSHUA', 'joshua-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$cAbsjk9SiGlyrYrzpDh95.C.XysMYTArL7f/LFRukmhz7CdKxTDuu'),
('24WMR08130', 'RAYDEN', 'rayden-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$uepgoW48eDnNEeczoGjTY.IyeIwEwTzVDIPUv/pbn0W3ZC2iTrNZy'),
('24WMR08139', 'JAY', 'jay-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$k8HWqas/7wxp/nBpK0GwUeHXGMbHbZiCVQHVhq9S.DWK07kpS3HPu'),
('24WMR08140', 'SHERRY', 'sherry-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$Uc.lUtn9y62L7aD/F89Rleb26TcL8sgrNELXLEA1BGnVtWnV34.y2'),
('24WMR08150', 'JELLY', 'jelly-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$u9i.uB01vu93MPciVTdPUu.zBfzXePcUTvBFIiKeKJenXcGYUfwiK'),
('24WMR09139', 'HENRY', 'henry-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$HBIAxLKq5qUXrDJKPRbqJ.ZnKo9fY46AW.Rc96EQ.CPIzXsjAfRr.'),
('5836', 'Barcola', 'barcola@tarc.edu.my', 'Supervisor', 1, NULL, NULL, NULL, '$2y$10$zYvQ/ki4ZDx.WfbyLbavlOm5HVREj3gOrRIkvQ28cApdq9MSWu9vu'),
('9383', 'DAVISH', 'davish@tarc.edu.my', 'Supervisor', 1, NULL, NULL, NULL, '$2y$10$oQRb5t03gdSQRHoMMBFmluwGVHVdf34JaM8FP1TyrmsBh5wGKckfO'),
('9484', 'Dracola', 'dracola@tarc.edu.my', 'Supervisor', 1, NULL, NULL, NULL, '$2y$10$ThVkgPlnhq9kxkAx6PWIcefAWMM9BVqiCXLMOm1F.iKCORfqYusjK'),
('A001', 'Yong Chong Xin', 'admin@tarc.edu.my', 'Administrator', 1, NULL, NULL, NULL, '$2y$10$GuPiJskB7MUsh6zcVF6kNetwlXajWqooBplIoDkgMCYXB4KWZv2BS'),
('S001', 'Dr Supervisor', 'supervisor@tarc.edu.my', 'Supervisor', 1, NULL, NULL, NULL, '123456'),
('ST001', 'Student User', 'student@tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '123456');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_status`
--

CREATE TABLE `user_activity_status` (
  `userID` varchar(20) NOT NULL,
  `systemRole` varchar(50) NOT NULL,
  `lastSeenAt` datetime NOT NULL DEFAULT current_timestamp(),
  `isOnline` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_status`
--

INSERT INTO `user_activity_status` (`userID`, `systemRole`, `lastSeenAt`, `isOnline`) VALUES
('1113', 'Supervisor', '2026-08-09 12:23:31', 0),
('1129', 'Supervisor', '2026-08-16 20:52:08', 1),
('1234', 'Supervisor', '2026-07-13 12:03:06', 1),
('24WMR01123', 'Student', '2026-08-05 19:32:18', 0),
('24WMR03435', 'Student', '2026-08-09 22:06:01', 0),
('24WMR08039', 'Student', '2026-08-20 23:46:24', 0),
('24WMR08049', 'Student', '2026-08-20 23:46:34', 0),
('24WMR08053', 'Student', '2026-06-21 13:40:43', 0),
('24WMR08054', 'Student', '2026-08-09 15:03:08', 0),
('24WMR08100', 'Student', '2026-08-16 20:56:13', 1),
('24WMR08110', 'Student', '2026-08-09 08:07:10', 0),
('24WMR08120', 'Student', '2026-08-08 23:10:11', 0),
('24WMR08130', 'Student', '2026-08-09 23:23:15', 0),
('24WMR08139', 'Student', '2026-07-13 12:01:10', 1),
('24WMR08140', 'Student', '2026-08-09 22:42:51', 0),
('24WMR08150', 'Student', '2026-08-20 20:58:55', 0),
('24WMR09139', 'Student', '2026-07-13 12:05:14', 1),
('5836', 'Supervisor', '2026-06-04 06:02:17', 0),
('9383', 'Supervisor', '2026-08-09 22:17:26', 0),
('A001', 'Administrator', '2026-08-20 23:45:55', 0),
('ST001', 'Student', '2026-06-04 12:13:15', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allocation_record`
--
ALTER TABLE `allocation_record`
  ADD PRIMARY KEY (`allocationID`),
  ADD UNIQUE KEY `studentID` (`studentID`),
  ADD KEY `supervisorID` (`supervisorID`);

--
-- Indexes for table `allocation_window_config`
--
ALTER TABLE `allocation_window_config`
  ADD PRIMARY KEY (`configID`);

--
-- Indexes for table `application_request`
--
ALTER TABLE `application_request`
  ADD PRIMARY KEY (`requestID`),
  ADD KEY `studentID` (`studentID`),
  ADD KEY `supervisorID` (`supervisorID`);

--
-- Indexes for table `auto_allocation_log`
--
ALTER TABLE `auto_allocation_log`
  ADD PRIMARY KEY (`logID`),
  ADD KEY `triggeredByAdminID` (`triggeredByAdminID`);

--
-- Indexes for table `auto_allocation_notification`
--
ALTER TABLE `auto_allocation_notification`
  ADD PRIMARY KEY (`notificationID`),
  ADD KEY `logID` (`logID`),
  ADD KEY `recipientUserID` (`recipientUserID`);

--
-- Indexes for table `eligibility_rule_configuration`
--
ALTER TABLE `eligibility_rule_configuration`
  ADD PRIMARY KEY (`ruleID`);

--
-- Indexes for table `past_project`
--
ALTER TABLE `past_project`
  ADD PRIMARY KEY (`projectID`),
  ADD KEY `supervisorID` (`supervisorID`);

--
-- Indexes for table `quota_configuration`
--
ALTER TABLE `quota_configuration`
  ADD PRIMARY KEY (`quotaID`);

--
-- Indexes for table `research_tag`
--
ALTER TABLE `research_tag`
  ADD PRIMARY KEY (`tagID`),
  ADD UNIQUE KEY `tagName` (`tagName`);

--
-- Indexes for table `student_eligibility_record`
--
ALTER TABLE `student_eligibility_record`
  ADD PRIMARY KEY (`studentID`);

--
-- Indexes for table `student_profile`
--
ALTER TABLE `student_profile`
  ADD PRIMARY KEY (`studentID`);

--
-- Indexes for table `student_tag_selection`
--
ALTER TABLE `student_tag_selection`
  ADD PRIMARY KEY (`studentID`,`tagID`),
  ADD KEY `tagID` (`tagID`);

--
-- Indexes for table `supervisor_profile`
--
ALTER TABLE `supervisor_profile`
  ADD PRIMARY KEY (`supervisorID`),
  ADD KEY `quotaID` (`quotaID`);

--
-- Indexes for table `supervisor_review`
--
ALTER TABLE `supervisor_review`
  ADD PRIMARY KEY (`reviewID`),
  ADD UNIQUE KEY `allocationID` (`allocationID`),
  ADD KEY `trueStudentID` (`trueStudentID`);

--
-- Indexes for table `supervisor_tag_selection`
--
ALTER TABLE `supervisor_tag_selection`
  ADD PRIMARY KEY (`supervisorID`,`tagID`),
  ADD KEY `tagID` (`tagID`);

--
-- Indexes for table `system_phase_timeline`
--
ALTER TABLE `system_phase_timeline`
  ADD PRIMARY KEY (`phaseID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `universityEmail` (`universityEmail`);

--
-- Indexes for table `user_activity_status`
--
ALTER TABLE `user_activity_status`
  ADD PRIMARY KEY (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allocation_record`
--
ALTER TABLE `allocation_record`
  MODIFY `allocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `application_request`
--
ALTER TABLE `application_request`
  MODIFY `requestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `auto_allocation_log`
--
ALTER TABLE `auto_allocation_log`
  MODIFY `logID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `auto_allocation_notification`
--
ALTER TABLE `auto_allocation_notification`
  MODIFY `notificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `past_project`
--
ALTER TABLE `past_project`
  MODIFY `projectID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `quota_configuration`
--
ALTER TABLE `quota_configuration`
  MODIFY `quotaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `research_tag`
--
ALTER TABLE `research_tag`
  MODIFY `tagID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `supervisor_review`
--
ALTER TABLE `supervisor_review`
  MODIFY `reviewID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `allocation_record`
--
ALTER TABLE `allocation_record`
  ADD CONSTRAINT `allocation_record_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `student_profile` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `allocation_record_ibfk_2` FOREIGN KEY (`supervisorID`) REFERENCES `supervisor_profile` (`supervisorID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `application_request`
--
ALTER TABLE `application_request`
  ADD CONSTRAINT `application_request_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `student_profile` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `application_request_ibfk_2` FOREIGN KEY (`supervisorID`) REFERENCES `supervisor_profile` (`supervisorID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `auto_allocation_log`
--
ALTER TABLE `auto_allocation_log`
  ADD CONSTRAINT `auto_allocation_log_ibfk_1` FOREIGN KEY (`triggeredByAdminID`) REFERENCES `user` (`userID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `auto_allocation_notification`
--
ALTER TABLE `auto_allocation_notification`
  ADD CONSTRAINT `auto_allocation_notification_ibfk_1` FOREIGN KEY (`logID`) REFERENCES `auto_allocation_log` (`logID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `auto_allocation_notification_ibfk_2` FOREIGN KEY (`recipientUserID`) REFERENCES `user` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `past_project`
--
ALTER TABLE `past_project`
  ADD CONSTRAINT `past_project_ibfk_1` FOREIGN KEY (`supervisorID`) REFERENCES `supervisor_profile` (`supervisorID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_profile`
--
ALTER TABLE `student_profile`
  ADD CONSTRAINT `student_profile_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `user` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_tag_selection`
--
ALTER TABLE `student_tag_selection`
  ADD CONSTRAINT `student_tag_selection_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `student_profile` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_tag_selection_ibfk_2` FOREIGN KEY (`tagID`) REFERENCES `research_tag` (`tagID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `supervisor_profile`
--
ALTER TABLE `supervisor_profile`
  ADD CONSTRAINT `supervisor_profile_ibfk_1` FOREIGN KEY (`supervisorID`) REFERENCES `user` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `supervisor_profile_ibfk_2` FOREIGN KEY (`quotaID`) REFERENCES `quota_configuration` (`quotaID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `supervisor_review`
--
ALTER TABLE `supervisor_review`
  ADD CONSTRAINT `supervisor_review_ibfk_1` FOREIGN KEY (`allocationID`) REFERENCES `allocation_record` (`allocationID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `supervisor_review_ibfk_2` FOREIGN KEY (`trueStudentID`) REFERENCES `student_profile` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `supervisor_tag_selection`
--
ALTER TABLE `supervisor_tag_selection`
  ADD CONSTRAINT `supervisor_tag_selection_ibfk_1` FOREIGN KEY (`supervisorID`) REFERENCES `supervisor_profile` (`supervisorID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `supervisor_tag_selection_ibfk_2` FOREIGN KEY (`tagID`) REFERENCES `research_tag` (`tagID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_activity_status`
--
ALTER TABLE `user_activity_status`
  ADD CONSTRAINT `user_activity_status_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
