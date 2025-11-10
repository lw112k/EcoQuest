-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: ecoquest
-- ------------------------------------------------------
-- Server version	8.0.43-0ubuntu0.24.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Achievement`
--

DROP TABLE IF EXISTS `Achievement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Achievement` (
  `Achievement_id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Exp_point` int DEFAULT NULL,
  PRIMARY KEY (`Achievement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Achievement`
--

LOCK TABLES `Achievement` WRITE;
/*!40000 ALTER TABLE `Achievement` DISABLE KEYS */;
/*!40000 ALTER TABLE `Achievement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Admin`
--

DROP TABLE IF EXISTS `Admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Admin` (
  `Admin_id` int NOT NULL AUTO_INCREMENT,
  `User_id` int NOT NULL,
  PRIMARY KEY (`Admin_id`),
  KEY `Admin_fk_User_id` (`User_id`),
  CONSTRAINT `Admin_fk_User_id` FOREIGN KEY (`User_id`) REFERENCES `User` (`User_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Admin`
--

LOCK TABLES `Admin` WRITE;
/*!40000 ALTER TABLE `Admin` DISABLE KEYS */;
INSERT INTO `Admin` VALUES (1,2);
/*!40000 ALTER TABLE `Admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Badge`
--

DROP TABLE IF EXISTS `Badge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Badge` (
  `Badge_id` int NOT NULL AUTO_INCREMENT,
  `Badge_Name` varchar(255) DEFAULT NULL,
  `Badge_image` varchar(255) DEFAULT NULL,
  `Require_Exp_Points` int DEFAULT NULL,
  PRIMARY KEY (`Badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Badge`
--

LOCK TABLES `Badge` WRITE;
/*!40000 ALTER TABLE `Badge` DISABLE KEYS */;
/*!40000 ALTER TABLE `Badge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Comment`
--

DROP TABLE IF EXISTS `Comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Comment` (
  `Comment_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Post_id` int NOT NULL,
  `Comment` text,
  `Created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Comment_id`),
  KEY `Comment_fk_Student_id` (`Student_id`),
  KEY `Comment_fk_Post_id` (`Post_id`),
  CONSTRAINT `Comment_fk_Post_id` FOREIGN KEY (`Post_id`) REFERENCES `Post` (`Post_id`) ON DELETE CASCADE,
  CONSTRAINT `Comment_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Comment`
--

LOCK TABLES `Comment` WRITE;
/*!40000 ALTER TABLE `Comment` DISABLE KEYS */;
/*!40000 ALTER TABLE `Comment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Comment_report`
--

DROP TABLE IF EXISTS `Comment_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Comment_report` (
  `Comment_report_id` int NOT NULL AUTO_INCREMENT,
  `Comment_id` int NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Report_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` varchar(50) DEFAULT NULL,
  `View_by_admin` int DEFAULT NULL,
  `Review_time` datetime DEFAULT NULL,
  PRIMARY KEY (`Comment_report_id`),
  KEY `CommentReport_fk_Comment_id` (`Comment_id`),
  CONSTRAINT `CommentReport_fk_Comment_id` FOREIGN KEY (`Comment_id`) REFERENCES `Comment` (`Comment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Comment_report`
--

LOCK TABLES `Comment_report` WRITE;
/*!40000 ALTER TABLE `Comment_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `Comment_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Forum_Badges`
--

DROP TABLE IF EXISTS `Forum_Badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Forum_Badges` (
  `Badge_ID` int NOT NULL AUTO_INCREMENT,
  `Badge_Name` varchar(100) NOT NULL,
  `Description` text,
  `Icon_URL` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Badge_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Forum_Badges`
--

LOCK TABLES `Forum_Badges` WRITE;
/*!40000 ALTER TABLE `Forum_Badges` DISABLE KEYS */;
/*!40000 ALTER TABLE `Forum_Badges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Moderator`
--

DROP TABLE IF EXISTS `Moderator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Moderator` (
  `Moderator_id` int NOT NULL AUTO_INCREMENT,
  `User_id` int NOT NULL,
  `Moderator_records_id` int DEFAULT NULL,
  PRIMARY KEY (`Moderator_id`),
  KEY `Moderator_fk_User_id` (`User_id`),
  KEY `Moderator_fk_Records_id` (`Moderator_records_id`),
  CONSTRAINT `Moderator_fk_Records_id` FOREIGN KEY (`Moderator_records_id`) REFERENCES `Moderator_Records` (`Moderator_records_id`) ON DELETE SET NULL,
  CONSTRAINT `Moderator_fk_User_id` FOREIGN KEY (`User_id`) REFERENCES `User` (`User_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Moderator`
--

LOCK TABLES `Moderator` WRITE;
/*!40000 ALTER TABLE `Moderator` DISABLE KEYS */;
INSERT INTO `Moderator` VALUES (1,3,NULL);
/*!40000 ALTER TABLE `Moderator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Moderator_Records`
--

DROP TABLE IF EXISTS `Moderator_Records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Moderator_Records` (
  `Moderator_records_id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Date_Time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Moderator_records_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Moderator_Records`
--

LOCK TABLES `Moderator_Records` WRITE;
/*!40000 ALTER TABLE `Moderator_Records` DISABLE KEYS */;
/*!40000 ALTER TABLE `Moderator_Records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Post`
--

DROP TABLE IF EXISTS `Post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Post` (
  `Post_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Content` text,
  `Created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Post_id`),
  KEY `Post_fk_Student_id` (`Student_id`),
  CONSTRAINT `Post_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Post`
--

LOCK TABLES `Post` WRITE;
/*!40000 ALTER TABLE `Post` DISABLE KEYS */;
/*!40000 ALTER TABLE `Post` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Post_Likes`
--

DROP TABLE IF EXISTS `Post_Likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Post_Likes` (
  `Like_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Post_id` int NOT NULL,
  PRIMARY KEY (`Like_id`),
  UNIQUE KEY `student_post_like` (`Student_id`,`Post_id`),
  KEY `Like_fk_Post_id` (`Post_id`),
  CONSTRAINT `Like_fk_Post_id` FOREIGN KEY (`Post_id`) REFERENCES `Post` (`Post_id`) ON DELETE CASCADE,
  CONSTRAINT `Like_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Post_Likes`
--

LOCK TABLES `Post_Likes` WRITE;
/*!40000 ALTER TABLE `Post_Likes` DISABLE KEYS */;
/*!40000 ALTER TABLE `Post_Likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Post_report`
--

DROP TABLE IF EXISTS `Post_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Post_report` (
  `Post_report_id` int NOT NULL AUTO_INCREMENT,
  `Post_id` int NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Report_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` varchar(50) DEFAULT NULL,
  `View_by_admin` int DEFAULT NULL,
  `Review_time` datetime DEFAULT NULL,
  PRIMARY KEY (`Post_report_id`),
  KEY `PostReport_fk_Post_id` (`Post_id`),
  CONSTRAINT `PostReport_fk_Post_id` FOREIGN KEY (`Post_id`) REFERENCES `Post` (`Post_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Post_report`
--

LOCK TABLES `Post_report` WRITE;
/*!40000 ALTER TABLE `Post_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `Post_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Quest`
--

DROP TABLE IF EXISTS `Quest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Quest` (
  `Quest_id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Points_award` int DEFAULT NULL,
  `Category` varchar(100) DEFAULT NULL,
  `Proof_type` varchar(50) DEFAULT NULL,
  `Instructions` text,
  `Is_active` tinyint(1) DEFAULT '1',
  `Created_by` int DEFAULT NULL,
  `Created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Quest_id`),
  KEY `Quest_fk_Created_by` (`Created_by`),
  CONSTRAINT `Quest_fk_Created_by` FOREIGN KEY (`Created_by`) REFERENCES `Admin` (`Admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Quest`
--

LOCK TABLES `Quest` WRITE;
/*!40000 ALTER TABLE `Quest` DISABLE KEYS */;
INSERT INTO `Quest` VALUES (1,'Test','TESTING',1000,'Plastic Use','Image','TEST',1,1,'2025-11-04 01:38:37');
/*!40000 ALTER TABLE `Quest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Quest_Categories`
--

DROP TABLE IF EXISTS `Quest_Categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Quest_Categories` (
  `CategoryID` int NOT NULL AUTO_INCREMENT,
  `Category_Name` varchar(100) NOT NULL,
  PRIMARY KEY (`CategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Quest_Categories`
--

LOCK TABLES `Quest_Categories` WRITE;
/*!40000 ALTER TABLE `Quest_Categories` DISABLE KEYS */;
INSERT INTO `Quest_Categories` VALUES (1,'Plastic Use');
/*!40000 ALTER TABLE `Quest_Categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Quest_Progress`
--

DROP TABLE IF EXISTS `Quest_Progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Quest_Progress` (
  `Quest_Progress_id` int NOT NULL AUTO_INCREMENT,
  `Quest_id` int NOT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Completed_at` datetime DEFAULT NULL,
  `Student_id` int NOT NULL,
  PRIMARY KEY (`Quest_Progress_id`),
  KEY `QuestProgress_fk_Quest_id` (`Quest_id`),
  KEY `QuestProgress_fk_Student_id` (`Student_id`),
  CONSTRAINT `QuestProgress_fk_Quest_id` FOREIGN KEY (`Quest_id`) REFERENCES `Quest` (`Quest_id`) ON DELETE CASCADE,
  CONSTRAINT `QuestProgress_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Quest_Progress`
--

LOCK TABLES `Quest_Progress` WRITE;
/*!40000 ALTER TABLE `Quest_Progress` DISABLE KEYS */;
INSERT INTO `Quest_Progress` VALUES (1,1,'active',NULL,1);
/*!40000 ALTER TABLE `Quest_Progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Redemption_History`
--

DROP TABLE IF EXISTS `Redemption_History`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Redemption_History` (
  `Redemption_History_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Reward_id` int NOT NULL,
  `Points_used` int DEFAULT NULL,
  `Redemption_date` datetime DEFAULT NULL,
  PRIMARY KEY (`Redemption_History_id`),
  KEY `Redemption_fk_Student_id` (`Student_id`),
  KEY `Redemption_fk_Reward_id` (`Reward_id`),
  CONSTRAINT `Redemption_fk_Reward_id` FOREIGN KEY (`Reward_id`) REFERENCES `Reward` (`Reward_id`) ON DELETE CASCADE,
  CONSTRAINT `Redemption_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Redemption_History`
--

LOCK TABLES `Redemption_History` WRITE;
/*!40000 ALTER TABLE `Redemption_History` DISABLE KEYS */;
/*!40000 ALTER TABLE `Redemption_History` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Reward`
--

DROP TABLE IF EXISTS `Reward`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Reward` (
  `Reward_id` int NOT NULL AUTO_INCREMENT,
  `Reward_name` varchar(255) DEFAULT NULL,
  `Description` text,
  `Points_cost` int DEFAULT NULL,
  `Stock` int DEFAULT NULL,
  `Image_url` varchar(255) DEFAULT NULL,
  `Is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`Reward_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Reward`
--

LOCK TABLES `Reward` WRITE;
/*!40000 ALTER TABLE `Reward` DISABLE KEYS */;
/*!40000 ALTER TABLE `Reward` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student`
--

DROP TABLE IF EXISTS `Student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student` (
  `Student_id` int NOT NULL AUTO_INCREMENT,
  `User_id` int NOT NULL,
  `Ban_time` datetime DEFAULT NULL,
  `Mute_comment` datetime DEFAULT NULL,
  `Mute_post` datetime DEFAULT NULL,
  `Total_Exp_Point` int DEFAULT '0',
  `Total_point` int DEFAULT '0',
  `Quest_Progress_id` int DEFAULT NULL,
  PRIMARY KEY (`Student_id`),
  KEY `Student_fk_User_id` (`User_id`),
  CONSTRAINT `Student_fk_User_id` FOREIGN KEY (`User_id`) REFERENCES `User` (`User_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student`
--

LOCK TABLES `Student` WRITE;
/*!40000 ALTER TABLE `Student` DISABLE KEYS */;
INSERT INTO `Student` VALUES (1,1,NULL,NULL,NULL,0,0,NULL);
/*!40000 ALTER TABLE `Student` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student_Achievement`
--

DROP TABLE IF EXISTS `Student_Achievement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student_Achievement` (
  `Student_Achievement_id` int NOT NULL AUTO_INCREMENT,
  `Achievement_id` int NOT NULL,
  `Student_id` int NOT NULL,
  `Status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`Student_Achievement_id`),
  KEY `StudentAchievement_fk_Achievement_id` (`Achievement_id`),
  KEY `StudentAchievement_fk_Student_id` (`Student_id`),
  CONSTRAINT `StudentAchievement_fk_Achievement_id` FOREIGN KEY (`Achievement_id`) REFERENCES `Achievement` (`Achievement_id`) ON DELETE CASCADE,
  CONSTRAINT `StudentAchievement_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student_Achievement`
--

LOCK TABLES `Student_Achievement` WRITE;
/*!40000 ALTER TABLE `Student_Achievement` DISABLE KEYS */;
/*!40000 ALTER TABLE `Student_Achievement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student_Achievements`
--

DROP TABLE IF EXISTS `Student_Achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student_Achievements` (
  `user_achievement_id` int NOT NULL AUTO_INCREMENT,
  `Student_ID` int NOT NULL,
  `achievement_id` int NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_achievement_id`),
  UNIQUE KEY `user_achievement_unique` (`Student_ID`,`achievement_id`),
  KEY `achievement_id` (`achievement_id`),
  CONSTRAINT `Std_Achieve_fk_achievement_id` FOREIGN KEY (`achievement_id`) REFERENCES `Achievements` (`achievement_id`) ON DELETE CASCADE,
  CONSTRAINT `Std_Achieve_fk_Student_ID` FOREIGN KEY (`Student_ID`) REFERENCES `Student` (`Student_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student_Achievements`
--

LOCK TABLES `Student_Achievements` WRITE;
/*!40000 ALTER TABLE `Student_Achievements` DISABLE KEYS */;
/*!40000 ALTER TABLE `Student_Achievements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student_Badge`
--

DROP TABLE IF EXISTS `Student_Badge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student_Badge` (
  `Student_Badge_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Badge_id` int NOT NULL,
  `Earned_Date` datetime DEFAULT NULL,
  PRIMARY KEY (`Student_Badge_id`),
  KEY `StudentBadge_fk_Student_id` (`Student_id`),
  KEY `StudentBadge_fk_Badge_id` (`Badge_id`),
  CONSTRAINT `StudentBadge_fk_Badge_id` FOREIGN KEY (`Badge_id`) REFERENCES `Badge` (`Badge_id`) ON DELETE CASCADE,
  CONSTRAINT `StudentBadge_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student_Badge`
--

LOCK TABLES `Student_Badge` WRITE;
/*!40000 ALTER TABLE `Student_Badge` DISABLE KEYS */;
/*!40000 ALTER TABLE `Student_Badge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student_Badges`
--

DROP TABLE IF EXISTS `Student_Badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student_Badges` (
  `Student_Badge_ID` int NOT NULL AUTO_INCREMENT,
  `Student_ID` int NOT NULL,
  `Badge_ID` int NOT NULL,
  `Earned_Date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Student_Badge_ID`),
  KEY `Student_ID` (`Student_ID`),
  KEY `Badge_ID` (`Badge_ID`),
  CONSTRAINT `Std_Badge_fk_Badge_ID` FOREIGN KEY (`Badge_ID`) REFERENCES `Forum_Badges` (`Badge_ID`) ON DELETE CASCADE,
  CONSTRAINT `Std_Badge_fk_Student_ID` FOREIGN KEY (`Student_ID`) REFERENCES `Student` (`Student_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student_Badges`
--

LOCK TABLES `Student_Badges` WRITE;
/*!40000 ALTER TABLE `Student_Badges` DISABLE KEYS */;
/*!40000 ALTER TABLE `Student_Badges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student_Quest_Submissions`
--

DROP TABLE IF EXISTS `Student_Quest_Submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student_Quest_Submissions` (
  `Student_quest_submission_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Quest_id` int NOT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `Submission_date` datetime DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Moderator_id` int DEFAULT NULL,
  `Review_date` datetime DEFAULT NULL,
  `Review_feedback` text,
  PRIMARY KEY (`Student_quest_submission_id`),
  KEY `Submission_fk_Student_id` (`Student_id`),
  KEY `Submission_fk_Quest_id` (`Quest_id`),
  KEY `Submission_fk_Moderator_id` (`Moderator_id`),
  CONSTRAINT `Submission_fk_Moderator_id` FOREIGN KEY (`Moderator_id`) REFERENCES `Moderator` (`Moderator_id`) ON DELETE SET NULL,
  CONSTRAINT `Submission_fk_Quest_id` FOREIGN KEY (`Quest_id`) REFERENCES `Quest` (`Quest_id`) ON DELETE CASCADE,
  CONSTRAINT `Submission_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student_Quest_Submissions`
--

LOCK TABLES `Student_Quest_Submissions` WRITE;
/*!40000 ALTER TABLE `Student_Quest_Submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `Student_Quest_Submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student_Submissions`
--

DROP TABLE IF EXISTS `Student_Submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student_Submissions` (
  `submission_id` int NOT NULL AUTO_INCREMENT,
  `Student_ID` int NOT NULL,
  `quest_id` int NOT NULL,
  `status` enum('active','pending','completed','rejected') NOT NULL DEFAULT 'active',
  `proof_text` text,
  `proof_media_url` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewer_id` int DEFAULT NULL,
  `review_comment` text,
  PRIMARY KEY (`submission_id`),
  UNIQUE KEY `user_quest_unique` (`Student_ID`,`quest_id`),
  KEY `quest_id` (`quest_id`),
  KEY `reviewer_id` (`reviewer_id`),
  CONSTRAINT `Submissions_fk_quest_id` FOREIGN KEY (`quest_id`) REFERENCES `Quest` (`quest_id`) ON DELETE CASCADE,
  CONSTRAINT `Submissions_fk_reviewer_id` FOREIGN KEY (`reviewer_id`) REFERENCES `Moderator` (`Moderator_ID`) ON DELETE SET NULL,
  CONSTRAINT `Submissions_fk_Student_ID` FOREIGN KEY (`Student_ID`) REFERENCES `Student` (`Student_ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student_Submissions`
--

LOCK TABLES `Student_Submissions` WRITE;
/*!40000 ALTER TABLE `Student_Submissions` DISABLE KEYS */;
INSERT INTO `Student_Submissions` VALUES (1,1,1,'completed','CARTI','uploads/activities/proof_68ee3914aa4fc2.82662368_IMG_5261.JPG','2025-10-14 13:50:44','2025-10-14 13:51:18',NULL,'NICE'),(2,7,1,'pending','TEST','uploads/activities/proof_68ef44c7c750e9.55958178_IMG_5261.JPG','2025-10-15 08:52:55',NULL,NULL,NULL),(3,4,1,'active',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `Student_Submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student_feedback`
--

DROP TABLE IF EXISTS `Student_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student_feedback` (
  `Student_feedback_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Date_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Student_feedback_id`),
  KEY `Feedback_fk_Student_id` (`Student_id`),
  CONSTRAINT `Feedback_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student_feedback`
--

LOCK TABLES `Student_feedback` WRITE;
/*!40000 ALTER TABLE `Student_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `Student_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student_moderation_records`
--

DROP TABLE IF EXISTS `Student_moderation_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student_moderation_records` (
  `Student_moderation_records_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Admin_id` int NOT NULL,
  PRIMARY KEY (`Student_moderation_records_id`),
  KEY `ModRecord_fk_Student_id` (`Student_id`),
  KEY `ModRecord_fk_Admin_id` (`Admin_id`),
  CONSTRAINT `ModRecord_fk_Admin_id` FOREIGN KEY (`Admin_id`) REFERENCES `Admin` (`Admin_id`),
  CONSTRAINT `ModRecord_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student_moderation_records`
--

LOCK TABLES `Student_moderation_records` WRITE;
/*!40000 ALTER TABLE `Student_moderation_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `Student_moderation_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Student_report`
--

DROP TABLE IF EXISTS `Student_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Student_report` (
  `Student_report_id` int NOT NULL AUTO_INCREMENT,
  `Student_id` int NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` text,
  `Report_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` varchar(50) DEFAULT NULL,
  `Review_time` datetime DEFAULT NULL,
  `Moderator_id` int DEFAULT NULL,
  PRIMARY KEY (`Student_report_id`),
  KEY `Report_fk_Student_id` (`Student_id`),
  KEY `Report_fk_Moderator_id` (`Moderator_id`),
  CONSTRAINT `Report_fk_Moderator_id` FOREIGN KEY (`Moderator_id`) REFERENCES `Moderator` (`Moderator_id`) ON DELETE SET NULL,
  CONSTRAINT `Report_fk_Student_id` FOREIGN KEY (`Student_id`) REFERENCES `Student` (`Student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Student_report`
--

LOCK TABLES `Student_report` WRITE;
/*!40000 ALTER TABLE `Student_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `Student_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `User`
--

DROP TABLE IF EXISTS `User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `User` (
  `User_id` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Role` enum('student','moderator','admin') NOT NULL,
  `Password_hash` varchar(255) NOT NULL,
  `Created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`User_id`),
  UNIQUE KEY `Username_UNIQUE` (`Username`),
  UNIQUE KEY `Email_UNIQUE` (`Email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `User`
--

LOCK TABLES `User` WRITE;
/*!40000 ALTER TABLE `User` DISABLE KEYS */;
INSERT INTO `User` VALUES (1,'TP123456','TP123456@mail.apu.edu.my','student','$2y$10$fCrzR7hnNzYhwyMmawTuk.fL7G1/jUXDyjNCPbfYQHSkVbU2Gs1ue','2025-11-03 18:19:23'),(2,'AD123456','admin@mail.apu.edu.my','admin','$2y$10$fCrzR7hnNzYhwyMmawTuk.fL7G1/jUXDyjNCPbfYQHSkVbU2Gs1ue','2025-11-04 01:32:20'),(3,'MD123456','mod@mail.apu.edu.my','moderator','$2y$10$u3QRPAFVpUX3ymkI6/2l0OKgvmJV30HSwoPxc0cODPdZDsm92q6Y.','2025-11-04 01:32:55');
/*!40000 ALTER TABLE `User` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-10  4:36:55
