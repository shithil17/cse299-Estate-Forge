-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 03:27 PM
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
-- Database: `eduor`
--

-- --------------------------------------------------------

--
-- Table structure for table `2fa`
--

CREATE TABLE `2fa` (
  `UserID` varchar(12) DEFAULT NULL,
  `TwoFASecret` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `2fa`
--

INSERT INTO `2fa` (`UserID`, `TwoFASecret`) VALUES
('1234', '7ILHEJSLNXUIPCF6');

-- --------------------------------------------------------

--
-- Table structure for table `field`
--

CREATE TABLE `field` (
  `FieldID` varchar(12) NOT NULL,
  `UserID` varchar(12) NOT NULL,
  `FieldName` varchar(100) NOT NULL,
  `FieldSubTitle` varchar(100) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `SetField1` varchar(150) DEFAULT NULL,
  `SetField2` varchar(150) DEFAULT NULL,
  `SetField3` varchar(150) DEFAULT NULL,
  `Description` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `field`
--

INSERT INTO `field` (`FieldID`, `UserID`, `FieldName`, `FieldSubTitle`, `Date`, `SetField1`, `SetField2`, `SetField3`, `Description`) VALUES
('', '2023', 'Research Interests', '', NULL, NULL, NULL, NULL, 'Network Security\r\nWeb Security\r\nBrowser Security\r\nMedical Device Security and Health IoT'),
('230820251751', '2022', 'Assessing climate-induced agricultural vulnerable coastal communities of Bangladesh using machine le', '', NULL, '', '', '', 'TRTASAS');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `UserID` varchar(12) NOT NULL,
  `CompanyName` varchar(100) NOT NULL,
  `CompanyID` varchar(12) NOT NULL,
  `PageLink` varchar(300) NOT NULL,
  `ContactEmail` varchar(100) NOT NULL,
  `ContactNumber` varchar(20) NOT NULL,
  `Office` varchar(500) NOT NULL,
  `LogoAddress` varchar(300) NOT NULL,
  `SecondaryImage` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `groups`
--
DELIMITER $$
CREATE TRIGGER `GroupsBeforeUpdate` BEFORE UPDATE ON `groups` FOR EACH ROW BEGIN
    INSERT INTO TriggerGroup (
        UserID, CompanyName, CompanyID, PageLink,
        ContactEmail, ContactNumber, Office,
        LogoAddress, SecondaryImage
    )
    VALUES (
        OLD.UserID, OLD.CompanyName, OLD.CompanyID, OLD.PageLink,
        OLD.ContactEmail, OLD.ContactNumber, OLD.Office,
        OLD.LogoAddress, OLD.SecondaryImage
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `CompanyID` varchar(12) NOT NULL,
  `UserID` varchar(12) NOT NULL,
  `Permissions` varchar(12) NOT NULL,
  `Role` varchar(12) NOT NULL,
  `Description` varchar(300) NOT NULL,
  `Allegations` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` varchar(12) NOT NULL,
  `Info` varchar(250) DEFAULT NULL,
  `Amount` int(10) DEFAULT NULL,
  `UserID` varchar(12) DEFAULT NULL,
  `Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `ProductID` varchar(20) NOT NULL,
  `UserID` varchar(12) NOT NULL,
  `CompanyID` varchar(12) NOT NULL,
  `ProductTitle` varchar(100) NOT NULL,
  `ProductCategory` varchar(30) NOT NULL,
  `ProductDescription` varchar(1500) NOT NULL,
  `ProductImage1` varchar(500) NOT NULL,
  `ProductImage2` varchar(500) DEFAULT NULL,
  `ProductImage3` varchar(500) DEFAULT NULL,
  `ProductImage4` varchar(500) DEFAULT NULL,
  `ProductImage5` varchar(500) DEFAULT NULL,
  `ProductPrice` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `products`
--
DELIMITER $$
CREATE TRIGGER `ProductsBeforeUpdate` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
    INSERT INTO TriggerProduct (
        ProductID, UserID, CompanyID, ProductTitle, ProductCategory,
        ProductDescription, ProductImage1, ProductImage2, ProductImage3,
        ProductImage4, ProductImage5, ProductPrice
    )
    VALUES (
        OLD.ProductID, OLD.UserID, OLD.CompanyID, OLD.ProductTitle, OLD.ProductCategory,
        OLD.ProductDescription, OLD.ProductImage1, OLD.ProductImage2, OLD.ProductImage3,
        OLD.ProductImage4, OLD.ProductImage5, OLD.ProductPrice
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE `session` (
  `SessionID` varchar(30) NOT NULL,
  `UserID` varchar(12) DEFAULT NULL,
  `LastLogin` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `LastPageLink` varchar(200) DEFAULT NULL,
  `StayLoggedIn` int(11) DEFAULT NULL CHECK (`StayLoggedIn` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `TicketID` varchar(40) NOT NULL,
  `Description` varchar(100) DEFAULT NULL,
  `FromUserID` varchar(12) DEFAULT NULL,
  `Status` enum('SOLVED','PENDING') DEFAULT NULL,
  `Feedback` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`TicketID`, `Description`, `FromUserID`, `Status`, `Feedback`) VALUES
('230820250481', 'My advising system is not working', '1234', 'PENDING', NULL),
('230820259374', 'Test', '1234', 'PENDING', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `triggergroup`
--

CREATE TABLE `triggergroup` (
  `ChangeTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `UserID` varchar(12) DEFAULT NULL,
  `CompanyName` varchar(100) DEFAULT NULL,
  `CompanyID` varchar(12) DEFAULT NULL,
  `PageLink` varchar(300) DEFAULT NULL,
  `ContactEmail` varchar(100) DEFAULT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `Office` varchar(500) DEFAULT NULL,
  `LogoAddress` varchar(300) DEFAULT NULL,
  `SecondaryImage` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `triggerproduct`
--

CREATE TABLE `triggerproduct` (
  `ChangeTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `ProductID` varchar(20) DEFAULT NULL,
  `UserID` varchar(12) DEFAULT NULL,
  `CompanyID` varchar(12) DEFAULT NULL,
  `ProductTitle` varchar(100) DEFAULT NULL,
  `ProductCategory` varchar(30) DEFAULT NULL,
  `ProductDescription` varchar(1500) DEFAULT NULL,
  `ProductImage1` varchar(500) DEFAULT NULL,
  `ProductImage2` varchar(500) DEFAULT NULL,
  `ProductImage3` varchar(500) DEFAULT NULL,
  `ProductImage4` varchar(500) DEFAULT NULL,
  `ProductImage5` varchar(500) DEFAULT NULL,
  `ProductPrice` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `triggeruser`
--

CREATE TABLE `triggeruser` (
  `ChangeTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `UserID` varchar(12) DEFAULT NULL,
  `Email` varchar(50) DEFAULT NULL,
  `Password` varchar(50) DEFAULT NULL,
  `UserFlag` int(1) DEFAULT NULL,
  `2fa` int(1) DEFAULT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Bio` varchar(50) NOT NULL,
  `Birth` date NOT NULL,
  `Occupation` varchar(50) DEFAULT NULL,
  `Role` varchar(10) DEFAULT NULL,
  `Avatar` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `triggeruser`
--

INSERT INTO `triggeruser` (`ChangeTime`, `UserID`, `Email`, `Password`, `UserFlag`, `2fa`, `FirstName`, `LastName`, `Bio`, `Birth`, `Occupation`, `Role`, `Avatar`) VALUES
('2026-04-28 13:10:15', 'U001', 'test@example.com', 'pass123', 1, 0, 'John', 'Doe', 'Hello world', '1990-01-01', 'Engineer', 'Admin', 'avatar.png');

-- --------------------------------------------------------

--
-- Table structure for table `userinfo`
--

CREATE TABLE `userinfo` (
  `UserID` varchar(12) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Bio` varchar(50) NOT NULL,
  `Birth` date NOT NULL,
  `Occupation` varchar(50) DEFAULT NULL,
  `Role` varchar(10) DEFAULT NULL,
  `Avatar` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `userinfo`
--

INSERT INTO `userinfo` (`UserID`, `FirstName`, `LastName`, `Bio`, `Birth`, `Occupation`, `Role`, `Avatar`) VALUES
('U001', 'John', 'Doe', 'Hello world', '1990-01-01', 'Engineer', 'Admin', 'avatar.png');

--
-- Triggers `userinfo`
--
DELIMITER $$
CREATE TRIGGER `UserInfoBeforeUpdate` BEFORE UPDATE ON `userinfo` FOR EACH ROW BEGIN
    INSERT INTO TriggerUser (
        UserID, Email, Password, UserFlag, `2fa`,
        FirstName, LastName, Bio, Birth, Occupation, Role, Avatar
    )
    SELECT
        u.UserID, u.Email, u.Password, u.UserFlag, u.`2fa`,
        OLD.FirstName, OLD.LastName, OLD.Bio, OLD.Birth,
        OLD.Occupation, OLD.Role, OLD.Avatar
    FROM Users u
    WHERE u.UserID = OLD.UserID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` varchar(12) NOT NULL,
  `Phone` varchar(12) DEFAULT NULL,
  `Email` varchar(50) DEFAULT NULL,
  `Password` varchar(50) DEFAULT NULL,
  `UserFlag` int(1) DEFAULT NULL,
  `2fa` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Phone`, `Email`, `Password`, `UserFlag`, `2fa`) VALUES
('1001', '10991', 'ftmed@gmail.com', 'ftmed', 1, 0),
('1011', '01899114', 'adminfarhan@eduor.edu', 'h123', 3, 0),
('1222', '019141211', 'pritom.deb@eduor.edu', 'pritom', 1, 0),
('1234', '1241', 'farhan.tahmeed12@gmail.com', 'h1234', 1, 1),
('1441', '0131551551', 'osman.gani@eduor.edu', 'osman', 0, 0),
('2022', '019191241', 'mle@eduor.edu', 'mlesir', 2, 0),
('2023', '0165122412', 'iqn@eduor.edu', 'iqnsir', 2, 0),
('220820253571', '914', 'samia.mozumder@eduor.edu', 'samia12', 1, 0),
('U001', NULL, 'newmail@example.com', 'pass123', 1, 0);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `UsersBeforeUpdate` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    INSERT INTO TriggerUser (
        UserID, Email, Password, UserFlag, `2fa`,
        FirstName, LastName, Bio, Birth, Occupation, Role, Avatar
    )
    SELECT
        OLD.UserID, OLD.Email, OLD.Password, OLD.UserFlag, OLD.`2fa`,
        uinfo.FirstName, uinfo.LastName, uinfo.Bio, uinfo.Birth,
        uinfo.Occupation, uinfo.Role, uinfo.Avatar
    FROM UserInfo uinfo
    WHERE uinfo.UserID = OLD.UserID;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `2fa`
--
ALTER TABLE `2fa`
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `field`
--
ALTER TABLE `field`
  ADD PRIMARY KEY (`FieldID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`CompanyID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD KEY `CompanyID` (`CompanyID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `CompanyID` (`CompanyID`);

--
-- Indexes for table `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`SessionID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`TicketID`),
  ADD KEY `FromUserID` (`FromUserID`);

--
-- Indexes for table `userinfo`
--
ALTER TABLE `userinfo`
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `2fa`
--
ALTER TABLE `2fa`
  ADD CONSTRAINT `2fa_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `field`
--
ALTER TABLE `field`
  ADD CONSTRAINT `field_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`CompanyID`) REFERENCES `groups` (`CompanyID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `members_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`CompanyID`) REFERENCES `groups` (`CompanyID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `session`
--
ALTER TABLE `session`
  ADD CONSTRAINT `session_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`FromUserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `userinfo`
--
ALTER TABLE `userinfo`
  ADD CONSTRAINT `userinfo_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
