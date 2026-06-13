-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2026 at 02:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

DROP DATABASE IF EXISTS `ssas_db`;
CREATE DATABASE `ssas_db`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;
USE `ssas_db`;

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
(3, '24WMR08049', '1129', '2026-06-01 23:41:13', 'System Auto-Match');

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
(1, '2026-06-01 23:15:00', '2026-06-01 23:20:00', '2026-06-02 06:10:45');

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
(1, '24WMR08054', '1129', 'Talent', '/FYPSupervisorSelectionAndAllocationSystem/storage/proposals/24WMR08054_20260526191326.pdf', '2026-05-27 01:13:26', '2026-06-03 01:13:26', 'Accepted', ''),
(2, '24WMR08049', '1129', 'Doue', '../storage/proposals/24WMR08049_20260604040305.pdf', '2026-06-04 04:03:05', '2026-06-07 04:03:05', 'Accepted', 'good job');

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
(1, 'A001', '2026-06-02 00:15:34', '2026-06-01 23:20:00', 0, 0, 0, 'NO_UNASSIGNED', 'No unassigned eligible students found.');

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
(1, 2.00, 'Y2S3', 'EF', '2026-05-27 19:40:59');

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
(2, '1129', 'PSG Champions League', 2026, 'Luis Enrique', '2 star, back 2 back champion', '../storage/past_projects/1129_20260602133016_567dbe31.pdf', '../storage/past_project_images/1129_20260602173241_ba5a93ae.jpg');

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
('24WMR08049', 'yongcx-wp23@student.tarc.edu.my', NULL, 'YONG CHONG XIN', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, 1, '2026-05-27 19:31:48'),
('24WMR08050', 'tankc-wp23@student.tarc.edu.my', NULL, 'TAN KAI CHUN', 'RSW', '2024', 'Y2S2', 'EP', 1.9000, 0, '2026-05-27 19:31:48'),
('24WMR08051', 'limml-wp23@student.tarc.edu.my', NULL, 'LIM MEI LING', 'RSD', '2024', 'Y1S2', 'EP', 3.5000, 0, '2026-05-27 19:31:48'),
('24WMR08052', 'leejh-wp23@student.tarc.edu.my', NULL, 'LEE JUN HAO', 'RSW', '2024', 'Y2S2', 'EF', 2.8000, 0, '2026-05-27 19:31:48'),
('24WMR08053', 'nuraina-wp23@student.tarc.edu.my', NULL, 'NUR AINA BINTI AZMAN', 'RIT', '2024', 'Y2S2', 'EP', 3.7500, 1, '2026-05-27 19:31:48'),
('24WMR08054', 'kumarv-wp23@student.tarc.edu.my', NULL, 'KUMAR VELAN', 'RSW', '2024', 'Y2S2', 'EP', 2.0100, 1, '2026-05-27 19:31:48');

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
('24WMR08049', 'RSW', '2024', 'Y2S2', 'EP', 3.2000, '010-7181 7181', 'I am kvaratskelia', '/FYPSupervisorSelectionAndAllocationSystem/storage/profile_photos/24WMR08049_20260603163803.jpg', '', '', '', 1),
('24WMR08053', 'RIT', '2024', 'Y2S2', 'EP', 3.7500, NULL, NULL, NULL, NULL, NULL, NULL, 1),
('24WMR08054', 'RSW', '2024', 'Y2S2', 'EP', 2.0100, NULL, NULL, NULL, NULL, NULL, NULL, 1);

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
('24WMR08049', 1),
('24WMR08049', 17);

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
('1129', 1, 28, 'Full-Time Lecturer', 'Consultation by appointment', 'https://www.youtube.com/watch?v=MPzP6S3ZHP4', 'RSW', 'Specializing in RSW, I guide students through applied research and final year project development at TAR UMT. (hehe)', 'hehe', 'published'),
('5836', 2, 10, 'Part-Time Lecturer', 'Consultation by appointment', NULL, 'RSW', NULL, NULL, 'draft');

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
(1, 3, '24WMR08049', 5, 'Good', 0);

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
('1129', 1),
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
(3, 'Review Period', '2026-06-02 06:15:00', '2026-06-03 06:11:00');

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
('1129', 'Lee Zi Qing', 'leezq1129@tarc.edu.my', 'Supervisor', 1, '/FYPSupervisorSelectionAndAllocationSystem/storage/profile_photos/1129_20260525163616.jpg', NULL, NULL, '$2y$10$gr/a/8N2eIuQ33eZ1N4zZOjRaqJgcMkl9ztIqcr4BmJ/vTJfUSMVi'),
('24WMR08049', 'YONG CHONG XIN', 'yongcx-wp23@student.tarc.edu.my', 'Student', 1, '/FYPSupervisorSelectionAndAllocationSystem/storage/profile_photos/24WMR08049_20260603163803.jpg', NULL, NULL, '$2y$10$C0azaY6F0.ThQejlu226n.wQyeV2q3DfvnIyO1ziOwm4SUmO3/lJm'),
('24WMR08053', 'NUR AINA BINTI AZMAN', 'nuraina-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$ScH.VgbNZfispW6.hEAJZenE9NWfYtYISk9ERQiN.PmwmdPYQ0eIG'),
('24WMR08054', 'KUMAR VELAN', 'kumarv-wp23@student.tarc.edu.my', 'Student', 1, NULL, NULL, NULL, '$2y$10$yf81W5Fl5EfwULOUGkEqVuoTSLbdkJlvZ2AQf1d.aNXYUv8FZT7Ya'),
('5836', 'Barcola', 'barcola@tarc.edu.my', 'Supervisor', 1, NULL, NULL, NULL, '$2y$10$zYvQ/ki4ZDx.WfbyLbavlOm5HVREj3gOrRIkvQ28cApdq9MSWu9vu'),
('A001', 'Yong Chong Xin', 'admin@tarc.edu.my', 'Administrator', 1, NULL, NULL, NULL, '123456'),
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
('1129', 'Supervisor', '2026-06-11 20:13:23', 0),
('24WMR08049', 'Student', '2026-06-11 20:13:47', 0),
('5836', 'Supervisor', '2026-06-04 06:02:17', 0),
('A001', 'Administrator', '2026-06-11 20:12:48', 0),
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
    MODIFY `allocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `application_request`
--
ALTER TABLE `application_request`
    MODIFY `requestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `auto_allocation_log`
--
ALTER TABLE `auto_allocation_log`
    MODIFY `logID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `auto_allocation_notification`
--
ALTER TABLE `auto_allocation_notification`
    MODIFY `notificationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `past_project`
--
ALTER TABLE `past_project`
    MODIFY `projectID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
    MODIFY `reviewID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
