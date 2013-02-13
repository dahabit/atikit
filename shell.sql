-- MySQL dump 10.13  Distrib 5.5.29, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: shell
-- ------------------------------------------------------
-- Server version	5.5.29-0ubuntu0.12.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(50) DEFAULT NULL,
  `company_address` varchar(100) DEFAULT NULL,
  `company_address2` varchar(30) DEFAULT NULL,
  `company_city` varchar(30) DEFAULT NULL,
  `company_state` varchar(2) DEFAULT NULL,
  `company_zip` varchar(10) DEFAULT NULL,
  `company_admin` int(11) DEFAULT NULL,
  `company_isprovider` tinyint(1) DEFAULT '0',
  `company_notes` varchar(1024) DEFAULT NULL,
  `company_stripetoken` varchar(100) DEFAULT NULL,
  `company_dwollatoken` varchar(100) DEFAULT NULL,
  `company_stripeid` varchar(100) DEFAULT NULL,
  `company_plan` int(11) NOT NULL,
  `company_phone` varchar(20) DEFAULT NULL,
  `company_vip` tinyint(1) DEFAULT '0',
  `company_since` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_loc` varchar(75) DEFAULT NULL,
  `file_title` varchar(75) DEFAULT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_ts` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `files`
--

LOCK TABLES `files` WRITE;
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
/*!40000 ALTER TABLE `files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `levels`
--

DROP TABLE IF EXISTS `levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level_name` varchar(30) DEFAULT NULL,
  `level_isbilling` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `levels`
--

LOCK TABLES `levels` WRITE;
/*!40000 ALTER TABLE `levels` DISABLE KEYS */;
/*!40000 ALTER TABLE `levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_ts` bigint(20) DEFAULT NULL,
  `notification_body` varchar(125) DEFAULT NULL,
  `notification_isadmin` tinyint(1) DEFAULT '0',
  `notification_isbilling` tinyint(1) DEFAULT '0',
  `notification_from` int(11) DEFAULT NULL,
  `notification_title` varchar(75) DEFAULT NULL,
  `notification_active` tinyint(1) DEFAULT '0',
  `notification_url` varchar(50) DEFAULT NULL,
  `notification_popped` tinyint(1) DEFAULT '0',
  `notification_viewed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` varchar(50) DEFAULT NULL,
  `plan_name` varchar(50) DEFAULT NULL,
  `plan_amount` double DEFAULT NULL,
  `plan_interval` int(11) DEFAULT NULL,
  `plan_trial` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `queues`
--

DROP TABLE IF EXISTS `queues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `queues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_name` varchar(30) DEFAULT NULL,
  `queue_levels` varchar(50) DEFAULT NULL,
  `queue_email` varchar(100) DEFAULT NULL,
  `queue_host` varchar(100) DEFAULT NULL,
  `queue_password` varchar(100) DEFAULT NULL,
  `queue_lastmessage` varchar(200) DEFAULT NULL,
  `queue_islocked` tinyint(1) DEFAULT '1',
  `queue_icon` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `queues`
--

LOCK TABLES `queues` WRITE;
/*!40000 ALTER TABLE `queues` DISABLE KEYS */;
/*!40000 ALTER TABLE `queues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `replies`
--

DROP TABLE IF EXISTS `replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) DEFAULT NULL,
  `reply_ts` bigint(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reply_body` varchar(2048) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `reply_isinternal` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `replies`
--

LOCK TABLES `replies` WRITE;
/*!40000 ALTER TABLE `replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_var` varchar(100) DEFAULT NULL,
  `setting_val` varchar(1024) DEFAULT NULL,
  `setting_desc` varchar(90) DEFAULT NULL,
  `setting_help` varchar(90) DEFAULT NULL,
  `setting_type` varchar(30) DEFAULT 'input',
  `setting_cat` varchar(30) DEFAULT NULL,
  `setting_span` int(11) DEFAULT '3',
  `setting_options` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'stripe_publish','','Stripe Publishable Key','This key is shown as the publishable key from your stripe account','input','Stripe',3,NULL),(2,'stripe_private','','Stripe Private Key','This key is shown as the PRIVATE key from your stripe account','input','Stripe',3,NULL),(3,'mycompany','','Your Company Name','This will be shown on invoices, etc.','input','General',3,NULL),(4,'defaultEmail','','Default E-mail Address:','Use an info address, or support, etc.','input','General',3,NULL),(5,'defaultName','','Default E-mail Name:','Your Company Name, Support, Info, Etc.','input','General',3,NULL),(6,'company_logo','','Company Logo for Invoices','This logo should be around 250x250 px','input','General',3,NULL),(7,'atikit_url','','aTikit URL','What is the default URL for aTikit. (ie. http://www.support.yourcompany.com/','input','General',3,NULL),(9,'dwolla_app_key','','Dwolla Application Key','Your Application Key from Dwolla','input','Dwolla',4,NULL),(10,'dwolla_app_secret','','Dwolla Application Secret','Your Application Secret from Dwolla','input','Dwolla',4,NULL),(11,'dwolla_id','','Dwolla Account ID','The ID of your Dwolla Account to receive money.','input','Dwolla',2,NULL),(12,'signature','','Default Signature','Default signature for outgoing emails from aTikit','textarea','General',3,NULL),(13,'vitelity_user','','Vitelity API Username','Your Vitelity API Username','input','Vitelity',3,NULL),(14,'vitelity_password','','Vitelity API Password','Your Vitelity API Password','password','Vitelity',3,NULL),(15,'vitelity_sms','','Vitelity SMS Number','Your SMS Enabled DID (1-xxx-xxx-xxxx)','input','Vitelity',3,NULL),(16,'notify_sms','','Numbers to SMS (separated by commas)','List all numbers for VIP texts (1-xxx-xxx-xxxx)','input','General',3,NULL);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sows`
--

DROP TABLE IF EXISTS `sows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) DEFAULT NULL,
  `sow_title` varchar(50) DEFAULT NULL,
  `sow_accepted` tinyint(1) DEFAULT '0',
  `sow_acceptuid` int(11) DEFAULT NULL,
  `sow_acceptts` bigint(20) DEFAULT NULL,
  `sow_meta` text,
  `sow_updated` bigint(20) DEFAULT NULL,
  `sow_updatedby` int(11) DEFAULT NULL,
  `sow_sent` tinyint(1) DEFAULT '0',
  `sow_hash` varchar(100) DEFAULT NULL,
  `sow_loc` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sows`
--

LOCK TABLES `sows` WRITE;
/*!40000 ALTER TABLE `sows` DISABLE KEYS */;
/*!40000 ALTER TABLE `sows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subtickets`
--

DROP TABLE IF EXISTS `subtickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subtickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) DEFAULT NULL,
  `subticket_title` varchar(100) DEFAULT NULL,
  `subticket_body` varchar(2048) DEFAULT NULL,
  `subticket_meta` text,
  `subticket_isclosed` tinyint(1) DEFAULT '0',
  `subticket_lastupdated` bigint(20) DEFAULT NULL,
  `subticket_assigned` int(11) DEFAULT NULL,
  `subticket_standing` varchar(500) DEFAULT NULL,
  `subticket_standinguid` int(11) DEFAULT NULL,
  `subticket_standingperc` int(11) DEFAULT NULL,
  `subticket_creator` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subtickets`
--

LOCK TABLES `subtickets` WRITE;
/*!40000 ALTER TABLE `subtickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `subtickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) NOT NULL,
  `ticket_title` varchar(100) DEFAULT NULL,
  `ticket_body` varchar(2048) DEFAULT NULL,
  `ticket_isclosed` tinyint(1) DEFAULT '0',
  `ticket_status` varchar(30) DEFAULT NULL,
  `ticket_opents` bigint(20) DEFAULT NULL,
  `ticket_lastupdated` bigint(20) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `ticket_assigned` int(11) DEFAULT NULL,
  `ticket_standing` varchar(500) DEFAULT NULL,
  `ticket_standinguid` int(11) DEFAULT NULL,
  `ticket_standingts` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_merchant_id` varchar(50) DEFAULT NULL,
  `transaction_ts` bigint(20) DEFAULT NULL,
  `transaction_amount` double DEFAULT NULL,
  `transaction_fee` double DEFAULT NULL,
  `transaction_net` double DEFAULT NULL,
  `transaction_source` varchar(30) DEFAULT NULL,
  `transaction_desc` varchar(90) DEFAULT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfers`
--

DROP TABLE IF EXISTS `transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_amt` double DEFAULT NULL,
  `transfer_ts` bigint(20) DEFAULT NULL,
  `transfer_source` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfers`
--

LOCK TABLES `transfers` WRITE;
/*!40000 ALTER TABLE `transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `user_email` varchar(150) DEFAULT NULL,
  `user_phone` varchar(25) DEFAULT NULL,
  `user_title` varchar(25) DEFAULT NULL,
  `user_password` varchar(50) DEFAULT NULL,
  `level_id` int(11) DEFAULT NULL,
  `user_isadmin` tinyint(1) DEFAULT '0',
  `user_altemails` varchar(200) DEFAULT NULL,
  `user_pic` varchar(75) DEFAULT NULL,
  `user_sms` varchar(20) DEFAULT NULL,
  `user_cansms` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-02-13 17:21:06
