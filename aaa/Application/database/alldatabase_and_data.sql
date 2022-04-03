-- MySQL dump 10.13  Distrib 5.6.50, for Linux (x86_64)
--
-- Host: localhost    Database: huanxin_80
-- ------------------------------------------------------
-- Server version	5.6.50-log

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
-- Table structure for table `db_orderqr`
--

DROP TABLE IF EXISTS `db_orderqr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_orderqr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mark_sell` varchar(60) NOT NULL,
  `money` varchar(20) NOT NULL,
  `end_time` varchar(60) NOT NULL,
  `status` tinyint(3) NOT NULL,
  `order_id` varchar(255) DEFAULT NULL,
  `qrurl` varchar(255) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `notifyurl` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `db_orderqr`
--

LOCK TABLES `db_orderqr` WRITE;
/*!40000 ALTER TABLE `db_orderqr` DISABLE KEYS */;
/*!40000 ALTER TABLE `db_orderqr` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `name` varchar(20) DEFAULT NULL,
  `score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_account_day_stat`
--

DROP TABLE IF EXISTS `pay_account_day_stat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_account_day_stat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `order_number` int(10) unsigned NOT NULL,
  `payed_number` int(10) unsigned DEFAULT '0',
  `pay_amount` decimal(15,4) DEFAULT '0.0000',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=168941 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_account_day_stat`
--

LOCK TABLES `pay_account_day_stat` WRITE;
/*!40000 ALTER TABLE `pay_account_day_stat` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_account_day_stat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_account_switch_log`
--

DROP TABLE IF EXISTS `pay_account_switch_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_account_switch_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int(11) unsigned NOT NULL COMMENT '收款账号pay_channel_account.id，',
  `optype` tinyint(3) unsigned NOT NULL COMMENT '操作：0管理员关，1管理员开，3码商关，4码商开，5心跳关',
  `ctime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `switchtype` tinyint(3) unsigned NOT NULL COMMENT '开关类型：0账号状态，1心跳开关，2测试状态,',
  `logstr` varchar(128) DEFAULT NULL COMMENT '日志字符串',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=210653 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_account_switch_log`
--

LOCK TABLES `pay_account_switch_log` WRITE;
/*!40000 ALTER TABLE `pay_account_switch_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_account_switch_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_admin`
--

DROP TABLE IF EXISTS `pay_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
  `username` varchar(50) NOT NULL COMMENT '后台用户名',
  `password` varchar(32) NOT NULL COMMENT '后台用户密码',
  `groupid` tinyint(1) unsigned DEFAULT '0' COMMENT '用户组',
  `createtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `google_secret_key` varchar(128) NOT NULL DEFAULT '' COMMENT '谷歌令牌密钥',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '手机号码',
  `session_random` varchar(50) NOT NULL DEFAULT '' COMMENT 'session随机字符串',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_admin`
--

LOCK TABLES `pay_admin` WRITE;
/*!40000 ALTER TABLE `pay_admin` DISABLE KEYS */;
INSERT INTO `pay_admin` VALUES (1,'admin','7fa2bf18316ac352d78b30f0995b7106',1,0,'R5G5QT6R66BTOKWN','13025280078','9pS9iTchNpeFlLBvgfgtQzTzVmHrelZb'),(33,'nami','d5a5cc2b5174f2f5b24c47c8eb060e41',1,1574646290,'R5G5QT6R66BTOKWN','','AQWOWTc7bCwsy1l8zDdZEIMnx8rVbDIy'),(34,'orton','a18318fe9426cc6810ae6c5337979f7c',1,1574646326,'R5G5QT6R66BTOKWN','','0oiq0UpJepwC2xtbD3GRGxxKbS54jwbH'),(35,'aken','79a15477289262253e07e44719032e05',2,1574646353,'R5G5QT6R66BTOKWN','','blrkkWpjoHaRNOOb3ZXLjL0ug0JfRi34'),(36,'vivian','8603addf36c16886198978497aa7e9b4',2,1574646381,'R5G5QT6R66BTOKWN','','kF5lkbmeq3SVxs62V050B9u1sWkIMWFe'),(37,'kaidi','46d2337965d0eacf08378f5ca66f7f48',1,1576122558,'R5G5QT6R66BTOKWN','','hRawuvfybprX85dbOhitZXBhJelwpCT4');
/*!40000 ALTER TABLE `pay_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_amount_water_order`
--

DROP TABLE IF EXISTS `pay_amount_water_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_amount_water_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '商户id，member.id',
  `orderid` int(10) unsigned DEFAULT NULL COMMENT '订单表的id，扣手续费的时候用',
  `ymoney` decimal(15,4) NOT NULL COMMENT '旧值',
  `money` decimal(15,4) NOT NULL COMMENT '变更量',
  `gmoney` decimal(15,4) NOT NULL COMMENT '新值',
  `type` tinyint(1) unsigned NOT NULL COMMENT '1增加，2减少，3订单成功增加，4订单商户提现减少',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `ctime` int(11) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2449835 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_amount_water_order`
--

LOCK TABLES `pay_amount_water_order` WRITE;
/*!40000 ALTER TABLE `pay_amount_water_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_amount_water_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_apimoney`
--

DROP TABLE IF EXISTS `pay_apimoney`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_apimoney` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT '0',
  `payapiid` int(11) DEFAULT NULL,
  `money` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `freezemoney` decimal(15,3) NOT NULL DEFAULT '0.000' COMMENT '冻结金额',
  `status` smallint(6) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_apimoney`
--

LOCK TABLES `pay_apimoney` WRITE;
/*!40000 ALTER TABLE `pay_apimoney` DISABLE KEYS */;
INSERT INTO `pay_apimoney` VALUES (10,6,207,18000.0000,0.000,1);
/*!40000 ALTER TABLE `pay_apimoney` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_arrearage`
--

DROP TABLE IF EXISTS `pay_arrearage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_arrearage` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '商户id，member.id, 要支付给谁， 0表示平台',
  `from_id` int(11) unsigned NOT NULL COMMENT '商户id，member.id，收款商户是谁',
  `balance` decimal(15,4) NOT NULL COMMENT '未付值，付了要减小',
  `confirm_balance` decimal(15,4) DEFAULT '0.0000' COMMENT '累计已确认收到提款',
  `dj_balance` decimal(15,4) DEFAULT '0.0000' COMMENT '提现冻结',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `userid` (`user_id`,`from_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6741 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_arrearage`
--

LOCK TABLES `pay_arrearage` WRITE;
/*!40000 ALTER TABLE `pay_arrearage` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_arrearage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_article`
--

DROP TABLE IF EXISTS `pay_article`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_article` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `catid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `groupid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '分组  0：所有 1：商户 2：代理',
  `title` varchar(300) NOT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `createtime` int(11) unsigned NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL COMMENT '描述',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1显示 0 不显示',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_article`
--

LOCK TABLES `pay_article` WRITE;
/*!40000 ALTER TABLE `pay_article` DISABLE KEYS */;
INSERT INTO `pay_article` VALUES (16,3,0,'客户告知书','<p></p><p>尊敬的商户代理：  </p><p>           <br /></p><p>       本公司郑重告知，请各客户加强业务自审，勿涉及网络诈骗等；否则后果自负。        </p><p><br /></p><p style=\"text-align:right;\"> <br /></p><p><br /></p><p><br /></p><p><br /></p><p><br /></p><p></p><p style=\"text-align:right;\">                                                                                                                              <br />                                                                                                                2019/2/12 8:41:07</p>',1549931619,'',1,1554528369),(17,3,0,'系统升级公告','<p>尊敬的商户：    　　</p><p>    为提高本平台的服务能力，我方于2019年4月06日早上（06:00-08:00）将进行服务器系统升级，期间请暂停提交一切订单，我方会争取用最短的时间完成，恢复后会第一时间通知您，为此给您带来的不便，我们深表歉意，敬请谅解！感谢您的支持和配合！特此公告！  </p>',1554456069,'系统升级公告',1,1554528342);
/*!40000 ALTER TABLE `pay_article` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_attachment`
--

DROP TABLE IF EXISTS `pay_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_attachment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `filename` varchar(100) NOT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=53 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_attachment`
--

LOCK TABLES `pay_attachment` WRITE;
/*!40000 ALTER TABLE `pay_attachment` DISABLE KEYS */;
INSERT INTO `pay_attachment` VALUES (48,2,'242dd42a2834349b88359f1eccea15ce36d3be7e.jpg','Uploads/verifyinfo/59a2b65d0816c.jpg'),(46,2,'6-140F316125V44.jpg','Uploads/verifyinfo/59a2b65cd9877.jpg'),(47,2,'6-140F316132J02.jpg','Uploads/verifyinfo/59a2b65cea2ec.jpg'),(49,180768718,'20180628155233_54225.jpg','Uploads/verifyinfo/5b969d7b3b32d.jpg'),(50,180768718,'20180628155327_47820.png','Uploads/verifyinfo/5b969d8a76e7e.png'),(51,180768718,'20180628155147_55535.png','Uploads/verifyinfo/5b969d8ab6a07.png'),(52,180768718,'20180628155430_18014.png','Uploads/verifyinfo/5b969d8b08fd5.png');
/*!40000 ALTER TABLE `pay_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auth_error_log`
--

DROP TABLE IF EXISTS `pay_auth_error_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auth_error_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `auth_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0：商户登录 1：后台登录 2：商户短信验证 3：后台短信验证 4：谷歌令牌验证 5：支付密码验证 ',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7527 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auth_error_log`
--

LOCK TABLES `pay_auth_error_log` WRITE;
/*!40000 ALTER TABLE `pay_auth_error_log` DISABLE KEYS */;
INSERT INTO `pay_auth_error_log` VALUES (6082,0,210549766,1621591127),(6083,0,210549766,1621591154),(6168,0,210531389,1622180795),(6169,0,210531389,1622180817),(6174,0,210580323,1622421231),(6183,0,210461004,1622445987),(6235,0,210397635,1623030831),(6236,0,210342504,1623030906),(6240,6,210527192,1623043336),(6279,0,210598251,1623681517),(6280,0,210598251,1623681523),(6281,0,210598251,1623681544),(6282,0,210598251,1623681544),(6283,0,210598251,1623681544),(6284,0,210598251,1623681544),(6285,0,210598251,1623681544),(6286,0,210598251,1623681544),(6287,0,210598251,1623681544),(6288,0,210598251,1623681544),(6289,0,210598251,1623681545),(6290,0,210598251,1623681545),(6291,0,210598251,1623681545),(6292,0,210598251,1623681545),(6293,0,210598251,1623681545),(6330,0,210652743,1623832784),(6331,0,210652743,1623832789),(6332,0,210652743,1623832802),(6333,0,210652743,1623832802),(6334,0,210652743,1623832802),(6335,0,210652743,1623832802),(6336,0,210652743,1623832802),(6337,0,210652743,1623832803),(6338,0,210652743,1623832803),(6339,0,210652743,1623832803),(6340,0,210652743,1623832803),(6341,0,210652743,1623832803),(6342,0,210652743,1623832803),(6343,0,210652743,1623832803),(6344,0,210652743,1623832803),(6347,0,210671689,1623833027),(6348,0,210671689,1623833031),(6349,0,210626329,1623835639),(6371,0,210635320,1624019067),(6433,0,210572137,1625121643),(6434,0,210572137,1625121653),(6482,0,200870209,1625963150),(6499,0,210780052,1626624011),(6556,0,210833669,1628077128),(6557,0,210833669,1628077141),(6561,0,191286206,1628166325),(6562,0,191286206,1628166365),(6590,0,210539777,1629282166),(6614,0,200111547,1629750458),(6615,0,201152348,1629887702),(6616,0,201152348,1629887708),(6724,0,210353219,1633867515),(6725,0,210353219,1633867522),(6727,0,210577411,1633886850),(6728,0,210577411,1633886867),(6729,0,210577411,1633886886),(6803,0,200135669,1634085413),(6826,0,211083573,1634200771),(6827,0,211083573,1634200771),(6828,0,211083573,1634200771),(6829,0,211083573,1634200771),(6830,0,211083573,1634200771),(6831,0,211083573,1634200771),(6832,0,211083573,1634200772),(6833,0,211083573,1634200772),(6834,0,211083573,1634200772),(6835,0,211083573,1634200772),(6836,0,211083573,1634200772),(6837,0,211083573,1634200772),(6838,0,211083573,1634200772),(6839,0,211083573,1634200772),(6840,0,211083573,1634200772),(6860,0,210555233,1634268724),(6865,0,210555233,1634300832),(6882,0,211074010,1634347801),(6887,0,211071194,1634364341),(6888,0,211071194,1634364352),(6900,0,211062290,1634441242),(6901,0,211062290,1634441256),(6902,0,211062290,1634441283),(6930,0,210434482,1634605856),(6931,0,210434482,1634605869),(6932,0,210434482,1634605897),(6933,0,210434482,1634605897),(6934,0,210434482,1634605897),(6935,0,210434482,1634605897),(6936,0,210434482,1634605897),(6937,0,210434482,1634605897),(6938,0,210434482,1634605897),(6939,0,210434482,1634605897),(6940,0,210434482,1634605897),(6941,0,210434482,1634605897),(6942,0,210434482,1634605897),(6943,0,210434482,1634605897),(6944,0,210434482,1634605897),(6959,0,210855215,1634743764),(6960,0,210855215,1634743770),(6961,0,210855215,1634743775),(6962,0,210855215,1634743782),(6986,0,211021128,1634916375),(6987,0,211021128,1634916384),(6997,0,210575063,1635030780),(6998,0,210575063,1635030787),(6999,0,210575063,1635030793),(7000,0,210575063,1635030799),(7009,0,210657081,1635253305),(7021,0,211058931,1635571959),(7028,0,200841093,1635699449),(7029,0,200841093,1635699491),(7030,0,200841093,1635699498),(7031,0,200841093,1635699508),(7046,0,210761030,1635954862),(7049,0,210557025,1635962726),(7050,0,210557025,1635962763),(7072,0,211147143,1636114356),(7079,0,210288885,1636219130),(7095,0,210522153,1636445298),(7096,0,210522153,1636445299),(7097,0,210522153,1636445299),(7098,0,210522153,1636445300),(7099,0,210522153,1636445301),(7100,0,210522153,1636445301),(7101,0,210522153,1636445302),(7102,0,210522153,1636445303),(7103,0,210522153,1636445303),(7104,0,210522153,1636445304),(7105,0,210522153,1636445304),(7106,0,210522153,1636445305),(7107,0,210522153,1636445306),(7108,0,210522153,1636445306),(7109,0,210522153,1636445307),(7110,0,63,1636445459),(7111,0,63,1636445460),(7112,0,63,1636445460),(7113,0,63,1636445461),(7114,0,63,1636445462),(7115,0,63,1636445463),(7116,0,63,1636445463),(7117,0,63,1636445464),(7118,0,63,1636445465),(7119,0,63,1636445465),(7120,0,63,1636445466),(7121,0,63,1636445467),(7122,0,63,1636445467),(7123,0,63,1636445468),(7124,0,63,1636445468),(7172,0,210994696,1636566374),(7173,0,210994696,1636566380),(7174,0,210994696,1636566393),(7178,0,211181306,1636617930),(7179,0,211181306,1636617946),(7180,0,211181306,1636618108),(7201,0,210651258,1636769731),(7202,0,210651258,1636769732),(7237,0,211179974,1637650959),(7238,0,211179974,1637651006),(7249,0,201132141,1638373481),(7250,0,210595178,1638379932),(7251,0,210595178,1638379938),(7252,0,211265729,1638382710),(7257,0,210524899,1638524352),(7264,0,211280702,1638854183),(7267,0,211249734,1639049783),(7276,0,210647313,1639099560),(7317,0,211229886,1639317916),(7318,0,210595178,1639320097),(7373,0,200154790,1641913840),(7376,0,211164215,1642228752),(7377,0,211164215,1642228762),(7378,0,211164215,1642228792),(7379,0,211164215,1642228792),(7380,0,211164215,1642228792),(7381,0,211164215,1642228793),(7382,0,211164215,1642228793),(7383,0,211164215,1642228793),(7384,0,211164215,1642228793),(7385,0,211164215,1642228793),(7386,0,211164215,1642228793),(7387,0,211164215,1642228793),(7388,0,211164215,1642228793),(7389,0,211164215,1642228793),(7390,0,211164215,1642228794),(7398,0,220197338,1642443020),(7399,0,220197338,1642443028),(7409,0,220122219,1642508534),(7410,0,220122219,1642508562),(7411,0,220122219,1642508586),(7455,0,210542798,1642870429),(7456,0,210542798,1642870436),(7458,0,200292156,1642995111),(7459,0,200292156,1642995114),(7462,0,211061134,1643175289),(7463,0,211061134,1643175306),(7466,0,220240222,1645288580),(7467,0,220237477,1645543406),(7468,0,220237477,1645543438),(7469,0,210189048,1645600260),(7471,0,220252530,1645716188),(7472,0,220252530,1645716205),(7479,0,210887179,1646455775),(7487,0,210875691,1647026224),(7488,0,210875691,1647026233),(7489,0,200211905,1647099361),(7492,0,211060832,1647253788),(7511,0,211173238,1647503828),(7512,0,211173238,1647503882),(7513,0,211173238,1647503886),(7514,0,211173238,1647504374),(7515,0,210188420,1647664897),(7518,0,210534444,1648422601),(7519,0,200712944,1648521849),(7523,0,220338036,1648631579),(7524,0,220338036,1648631587),(7525,0,220338036,1648631624),(7526,0,220338036,1648631635);
/*!40000 ALTER TABLE `pay_auth_error_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auth_group`
--

DROP TABLE IF EXISTS `pay_auth_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auth_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `is_manager` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1需要验证权限 0 不需要验证权限',
  `rules` varchar(1000) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auth_group`
--

LOCK TABLES `pay_auth_group` WRITE;
/*!40000 ALTER TABLE `pay_auth_group` DISABLE KEYS */;
INSERT INTO `pay_auth_group` VALUES (1,'超级管理员',1,0,'1,170,2,126,3,51,4,57,5,59,58,56,55,6,44,53,52,48,70,54,7,8,62,61,60,9,66,65,64,63,10,69,68,67,11,136,12,96,97,95,94,93,98,99,169,120,101,100,91,90,83,82,81,80,84,85,89,88,87,86,79,13,14,15,92,16,78,77,76,73,17,18,72,19,75,71,74,20,22,125,127,23,115,114,24,25,26,46,31,132,156,157,158,153,152,149,150,151,148,32,162,163,161,160,159,154,33,168,167,166,165,164,139,27,29,105,104,102,30,128,119,107,106,103,28,129,111,110,109,108,134,135,140,141,38,39,113,40,112,41,42,45,47,116,122,117,123,118,124,155,171'),(2,'运营管理员',1,0,'1,170,11,136,12,96,97,95,94,93,98,99,120,101,100,91,90,83,82,81,80,84,85,89,88,87,86,79,13,14,15,92,16,78,77,76,73,17,18,72,19,75,71,74,20,22,125,127,23,24,25,26,46,31,132,153,152,149,150,151,148,32,154,33,139,27,29,105,104,102,30,128,119,107,106,103,28,129,111,110,109,108,134,135,140,141,38,39,113,40,112,41,42,45,47,116,122,117,123,118,124,155,171'),(3,'财务管理员',1,1,'1,2,126,3,51,4,57,5,59,58,56,55,6,44,53,52,48,70,54,22,125,127,23,115,114,24,25,26,31,151,152,153,154,150,149,132,148,32,33,139'),(4,'普通商户',1,1,'114,115'),(5,'普通代理商',1,1,'114,115'),(6,'中级代理商户',1,1,''),(7,'高级代理商户',1,1,'');
/*!40000 ALTER TABLE `pay_auth_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auth_group_access`
--

DROP TABLE IF EXISTS `pay_auth_group_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auth_group_access` (
  `uid` mediumint(8) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `uid` (`uid`) USING BTREE,
  KEY `group_id` (`group_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auth_group_access`
--

LOCK TABLES `pay_auth_group_access` WRITE;
/*!40000 ALTER TABLE `pay_auth_group_access` DISABLE KEYS */;
INSERT INTO `pay_auth_group_access` VALUES (1,1),(3,1),(7,1),(29,1),(30,1),(31,1),(32,1),(33,2),(34,2),(35,2),(36,2),(37,2);
/*!40000 ALTER TABLE `pay_auth_group_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auth_rule`
--

DROP TABLE IF EXISTS `pay_auth_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(100) DEFAULT '' COMMENT '图标',
  `menu_name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则唯一标识Controller/action',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `pid` tinyint(5) unsigned NOT NULL DEFAULT '0' COMMENT '菜单ID ',
  `is_menu` tinyint(1) unsigned DEFAULT '0' COMMENT '1:是主菜单 0否',
  `is_race_menu` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1:是 0:不是',
  `type` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `condition` char(100) NOT NULL DEFAULT '',
  `sortno` int(11) DEFAULT '0' COMMENT '排序号',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=172 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auth_rule`
--

LOCK TABLES `pay_auth_rule` WRITE;
/*!40000 ALTER TABLE `pay_auth_rule` DISABLE KEYS */;
INSERT INTO `pay_auth_rule` VALUES (1,'fa fa-home','Index/index','管理首页',0,1,0,1,1,'',0),(2,'fa fa-cogs','System/#','系统设置',0,1,0,1,1,'',1),(3,'fa fa-cogs','System/base','基本设置',2,1,0,1,1,'',0),(4,'fa fa-envelope-o','System/email','邮件设置',2,1,0,1,1,'',1),(5,'fa fa-send','System/smssz','短信设置',2,1,0,1,1,'',2),(6,'fa fa-pencil-square-o','System/planning','计划任务',2,1,0,1,1,'',3),(7,'fa fa-user-circle','Admin/#','管理员管理',0,1,0,1,1,'',2),(8,'fa fa-vcard ','Admin/index','管理员信息',7,1,0,1,1,'',0),(9,'fa fa-life-ring','Auth/index','角色配置',7,1,0,1,1,'',0),(10,'fa fa-universal-access','Menu/index','菜单配置',7,1,0,1,1,'',3),(11,'fa fa-users','User/#','用户管理',0,1,0,1,1,'',3),(12,'fa fa-user','User/index?status=1&authorized=1','已认证用户',11,1,0,1,1,'',1),(13,'fa fa-user-o','User/index?status=1&authorized=2','待认证用户',11,1,0,1,1,'',2),(14,'fa fa-user-plus','User/index?status=1&authorized=0','未认证用户',11,1,0,1,1,'',3),(15,'fa fa-user-times','User/index?status=0','冻结用户',11,1,0,1,1,'',4),(16,'fa fa-gift','User/invitecode','邀请码',11,1,0,1,1,'',5),(17,'fa fa-address-book','User/loginrecord','登录记录',11,1,0,1,1,'',6),(18,'fa fa-user-circle','Agent/#','代理管理',0,1,0,1,1,'',4),(19,'fa fa-hand-lizard-o','User/agentList','代理列表',18,1,0,1,1,'',0),(20,'fa fa-signing','Order/changeRecord?bank=9','佣金记录',18,1,0,1,1,'',1),(22,'fa fa-reorder','User/#','订单管理',0,1,0,1,1,'',5),(23,'fa fa-indent','Order/changeRecord','流水记录',22,1,0,1,1,'',1),(24,'fa fa-thumbs-up','Order/index?status=1or2','成功订单',22,1,0,1,1,'',2),(25,'fa fa-thumbs-down','Order/index?status=0','未支付订单',22,1,0,1,1,'',3),(26,'fa fa-hand-o-right','Order/index?status=1','异常订单',22,1,0,1,1,'',4),(27,'fa fa-user-secret','Withdrawal/','提款管理',0,1,0,1,1,'',7),(28,'fa fa-wrench','Withdrawal/setting','提款设置',27,1,0,1,1,'',2),(29,'fa fa-asl-interpreting','Withdrawal/index','手动结算订单',27,1,0,1,1,'',0),(30,'fa fa-window-restore','Withdrawal/payment','代付结算订单',27,1,0,1,1,'',1),(31,'fa fa-bank','Channel/#','通道管理',0,1,0,1,1,'',6),(32,'fa fa-product-hunt','Channel/index','入金渠道设置',31,1,0,1,1,'',1),(33,'fa fa-sitemap','Channel/product','支付产品设置',31,1,0,1,1,'',2),(35,'fa fa-book','Content/#','文章管理',0,1,0,1,1,'',9),(36,'fa fa-tags','Content/category','栏目列表',35,1,0,1,1,'',1),(37,'fa fa-list-alt','Content/article','文章列表',35,1,0,1,1,'',0),(38,'fa fa-line-chart','Statistics/#','财务分析',0,1,0,1,1,'',8),(39,'fa fa-bar-chart-o','Statistics/index','交易统计',38,1,0,1,1,'',0),(40,'fa fa-area-chart','Statistics/userFinance','商户交易统计',38,1,0,1,1,'',1),(41,'fa fa-industry','Statistics/userFinance?groupid=agent','代理商交易统计',38,1,0,1,1,'',2),(42,'fa fa-pie-chart','Statistics/channelFinance','接口交易统计',38,1,0,1,1,'',3),(43,'fa fa-cubes','Template/index','模板设置',2,1,0,1,0,'',0),(44,'fa fa-qq','System/mobile','手机设置',2,1,0,1,1,'',4),(45,'fa fa-signal','Statistics/chargeRank','充值排行榜',38,1,0,1,1,'',4),(46,'fa fa-first-order','Deposit/index','投诉保证金设置',22,1,0,1,1,'',5),(47,'fa fa-asterisk','Statistics/complaintsDeposit','投诉保证金统计',38,1,0,1,1,'',5),(48,'fa fa-magnet','System/clearData','数据清理',2,1,0,1,1,'',5),(51,'','System/SaveBase','保存设置',3,0,0,1,1,'',0),(52,'','System/BindMobileShow','绑定手机号码',44,0,0,1,1,'',0),(53,'','System/editMobileShow','手机修改',44,0,0,1,1,'',0),(54,'fa fa-wrench','System/editPassword','修改密码',2,1,0,1,1,'',6),(55,'','System/editSmstemplate','短信模板',5,0,0,1,1,'',0),(56,'','System/saveSmstemplate','保存短信模板',5,0,0,1,1,'',0),(57,'','System/saveEmail','邮件保存',4,0,0,1,1,'',0),(58,'','System/testMobile','测试短信',5,0,0,1,1,'',0),(59,'','System/deleteAdmin','删除短信模板',5,0,0,1,1,'',0),(60,'','Admin/addAdmin','管理员添加',8,0,0,1,1,'',0),(61,'','Admin/editAdmin','管理员修改',8,0,0,1,1,'',0),(62,'','Admin/deleteAdmin','管理员删除',8,0,0,1,1,'',0),(63,'','Auth/addGroup','添加角色',9,0,0,1,1,'',0),(64,'','Auth/editGroup','修改角色',9,0,0,1,1,'',0),(65,'','Auth/giveRole','选择角色',9,0,0,1,1,'',0),(66,'','Auth/ruleGroup','分配权限',9,0,0,1,1,'',0),(67,'','Menu/addMenu','添加菜单',10,0,0,1,1,'',0),(68,'','Menu/editMenu','修改菜单',10,0,0,1,1,'',0),(69,'','Menu/delMenu','删除菜单',10,0,0,1,1,'',0),(70,'','System/clearDataSend','数据清理提交',48,0,0,1,1,'',0),(71,'','User/addAgentCate','代理级别',19,0,0,1,1,'',0),(72,'','User/saveAgentCate','保存代理级别',18,0,0,1,1,'',0),(73,'','User/addInvitecode','添加激活码',16,0,0,1,1,'',0),(74,'','User/EditAgentCate','修改代理分类',18,0,0,1,1,'',0),(75,'','User/deleteAgentCate','删除代理分类',19,0,0,1,1,'',0),(76,'','User/setInvite','邀请码设置',16,0,0,1,1,'',0),(77,'','User/addInvite','创建邀请码',16,0,0,1,1,'',0),(78,'','User/delInvitecode','删除邀请码',16,0,0,1,1,'',0),(79,'','User/editUser','用户编辑',12,0,0,1,1,'',0),(80,'','User/changeuser','修改状态',12,0,0,1,1,'',0),(81,'','User/authorize','用户认证',12,0,0,1,1,'',0),(82,'','User/usermoney','用户资金管理',12,0,0,1,1,'',0),(83,'','User/userWithdrawal','用户提现设置',12,0,0,1,1,'',0),(84,'','User/userRateEdit','用户费率设置',12,0,0,1,1,'',0),(85,'','User/editPassword','用户密码修改',12,0,0,1,1,'',0),(86,'','User/editStatus','用户状态修改',12,0,0,1,1,'',0),(87,'','User/delUser','用户删除',12,0,0,1,1,'',0),(88,'','User/thawingFunds','T1解冻任务管理',12,0,0,1,1,'',0),(89,'','User/exportuser','导出用户',12,0,0,1,1,'',0),(90,'','User/editAuthoize','修改用户认证',12,0,0,1,1,'',0),(91,'','User/getRandstr','切换商户密钥',12,0,0,1,1,'',0),(92,'','User/suoding','用户锁定',15,0,0,1,1,'',0),(93,'','User/editbankcard','银行卡管理',12,0,0,1,1,'',0),(94,'','User/saveUser','添加用户',12,0,0,1,1,'',0),(95,'','User/saveUserProduct','保存用户产品',12,0,0,1,1,'',0),(96,'','User/saveUserRate','保存用户费率',12,0,0,1,1,'',0),(97,'','User/edittongdao','编辑通道',12,0,0,1,1,'',0),(98,'','User/frozenMoney','用户资金冻结',12,0,0,1,1,'',0),(99,'','User/unfrozenHandles','T1资金解冻',12,0,0,1,1,'',0),(100,'','User/frozenOrder','冻结订单列表',12,0,0,1,1,'',0),(101,'','User/frozenHandles','T1订单解冻展示',12,0,0,1,1,'',0),(102,'','Withdrawal/editStatus','操作状态',29,0,0,1,1,'',0),(103,'','Withdrawal/editwtStatus','操作订单状态',30,0,0,1,1,'',0),(104,'','Withdrawal/exportorder','导出数据',29,0,0,1,1,'',0),(105,'','Withdrawal/editwtAllStatus','批量修改提款状态',29,0,0,1,1,'',0),(106,'','Withdrawal/exportweituo','导出委托提现',30,0,0,1,1,'',0),(107,'','Payment/index','提交上游',30,0,0,1,1,'',0),(108,'','Withdrawal/saveWithdrawal','保存设置',28,0,0,1,1,'',0),(109,'','Withdrawal/AddHoliday','添加假日',28,0,0,1,1,'',0),(110,'','Withdrawal/settimeEdit','编辑提款时间',28,0,0,1,1,'',0),(111,'','Withdrawal/delHoliday','删除节假日',28,0,0,1,1,'',0),(112,'','Statistics/exportorder','订单数据导出',40,0,0,1,1,'',0),(113,'','Statistics/details','查看详情',39,0,0,1,1,'',0),(114,'','Order/exportorder','订单导出',23,0,0,1,1,'',0),(115,'','Order/exceldownload','记录导出',23,0,0,1,1,'',0),(116,'fa fa-area-chart','Statistics/platformReport','平台报表',38,1,0,1,1,'',6),(117,'fa fa-area-chart','Statistics/merchantReport','商户报表',38,1,0,1,1,'',7),(118,'fa fa-area-chart','Statistics/agentReport','代理报表',38,1,0,1,1,'',8),(119,'','Withdrawal/submitDf','代付提交',30,0,0,1,1,'',0),(120,'','User/editUserProduct','分配用户通道',12,0,0,1,1,'',0),(139,'fa fa-wrench','Transaction/index','风控设置',31,1,0,1,1,'',3),(122,'','Statistics/exportPlatform','导出平台报表',116,0,0,1,1,'',0),(123,'','Statistics/exportMerchant','导出商户报表',117,0,0,1,1,'',0),(124,'','Statistics/exportAgent','导出代理报表',118,0,0,1,1,'',0),(125,'','Order/show','查看订单',22,0,0,1,1,'',0),(126,'fa fa-cog','Withdrawal/checkNotice','提现申请声音提示',2,0,0,1,1,'',0),(127,'fa fa-bars','Order/index','全部订单',22,1,0,1,1,'',0),(128,'','Withdrawal/rejectAllDf','批量驳回代付',30,0,0,1,1,'',0),(129,'','User/saveWithdrawal','保存用户提款设置',28,0,0,1,1,'',0),(132,'fa fa-product-hunt','Channel/accounts','收款码汇总',31,1,0,1,1,'',0),(134,'fa fa-thumbs-up','Withdrawal/retire','回款管理',27,1,0,1,1,'',3),(135,'fa fa-thumbs-up','Withdrawal/retireLog','回款日志',27,1,0,1,1,'',4),(136,'fa fa-user','User/index','所有用户',11,1,0,1,1,'',0),(140,'fa fa-sellsy','Order/dfApiOrderList','代付接口订单',27,1,0,1,1,'',5),(141,'fa fa-sliders','PayForAnother/index','代付接口设置',27,1,0,1,1,'',6),(142,'fa fa-user-circle','Test/','其他功能',0,1,0,1,1,'',10),(143,'fa fa-vcard','Order/frozenOrder','异常订单排查',142,1,0,1,1,'',0),(144,'fa fa-vcard','Auth/nodes','手动订单系统',142,1,0,1,1,'',1),(145,'fa fa-vcard','Template/index','模板管理',142,1,0,1,1,'',2),(146,'fa fa-vcard','User/agentCateList','用户分类管理',142,1,0,1,1,'',3),(147,'fa fa-vcard','Index/clearCache','清除缓存',142,1,0,1,1,'',4),(148,'','Channel/editAccount','编辑收款码',132,0,0,1,1,'',0),(149,'','Channel/editAccountStatus','切换账户状态',132,0,0,1,1,'',0),(150,'','Channel/editAccountSwitchHeartBeat','切换心跳开关',132,0,0,1,1,'',0),(151,'','Channel/editSwitchManual','切换手动开关',132,0,0,1,1,'',0),(152,'','Channel/editAccountTestStatus','切换测试开关',132,0,0,1,1,'',0),(153,'','Channel/saveEditAccount','保存账户',132,0,0,1,1,'',0),(154,'','Channel/editStatus','入金渠道开关',32,0,0,1,1,'',0),(155,'fa fa-asterisk','Statistics/profitFinance','渠道利润统计',38,1,0,1,1,'',9),(156,'','Channel/editAccountControl','收款码风控',132,0,0,1,1,'',0),(157,'','Channel/editAccountRate','收款码费率',132,0,0,1,1,'',0),(158,'','Channel/delAccount','删除收款码',132,0,0,1,1,'',0),(159,'','Channel/editRate','入金渠道费率',32,0,0,1,1,'',0),(160,'','Channel/editControl','入金渠道风控',32,0,0,1,1,'',0),(161,'','Channel/editSupplier','编辑入金渠道',32,0,0,1,1,'',0),(162,'','Channel/addSupplier','添加供应商',32,0,0,1,1,'',0),(163,'','Channel/delSupplier','删除入金渠道',32,0,0,1,1,'',0),(164,'','Channel/prodStatus','状态开关',33,0,0,1,1,'',0),(165,'','Channel/prodDisplay','用户端开关',33,0,0,1,1,'',0),(166,'','Channel/addProduct','添加支付产品',33,0,0,1,1,'',0),(167,'','Channel/editProduct','编辑支付产品',33,0,0,1,1,'',0),(168,'','Channel/delProduct','删除支付产品',33,0,0,1,1,'',0),(169,'','User/incrTdBalance','用户押金操作',12,0,0,1,1,'',0),(170,'','Index/main','首页数据显示',1,0,0,1,1,'',0),(171,'fa fa-area-chart','Statistics/merchantprofit','商户余额统计',38,1,0,1,1,'',10);
/*!40000 ALTER TABLE `pay_auth_rule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auto_df_log`
--

DROP TABLE IF EXISTS `pay_auto_df_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auto_df_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `df_id` int(11) NOT NULL DEFAULT '0' COMMENT '代付ID',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型：1提交 2查询',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '结果 0：提交失败 1：提交成功 2：代付成功 3：代付失败',
  `msg` varchar(255) DEFAULT '' COMMENT '描述',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '提交时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auto_df_log`
--

LOCK TABLES `pay_auto_df_log` WRITE;
/*!40000 ALTER TABLE `pay_auto_df_log` DISABLE KEYS */;
INSERT INTO `pay_auto_df_log` VALUES (1,19,2,0,'代付通道文件不存在',1537373341),(2,19,2,1,NULL,1537373522),(3,19,2,1,NULL,1537373701),(4,19,2,2,NULL,1537373881);
/*!40000 ALTER TABLE `pay_auto_df_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auto_unfrozen_order`
--

DROP TABLE IF EXISTS `pay_auto_unfrozen_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auto_unfrozen_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `freeze_money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '冻结金额',
  `unfreeze_time` int(11) NOT NULL DEFAULT '0' COMMENT '计划解冻时间',
  `real_unfreeze_time` int(11) NOT NULL DEFAULT '0' COMMENT '实际解冻时间',
  `is_pause` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否暂停解冻 0正常解冻 1暂停解冻',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '解冻状态 0未解冻 1已解冻',
  `create_at` int(11) NOT NULL COMMENT '记录创建时间',
  `update_at` int(11) NOT NULL COMMENT '记录更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_unfreezeing` (`status`,`is_pause`,`unfreeze_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='投诉保证金余额';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auto_unfrozen_order`
--

LOCK TABLES `pay_auto_unfrozen_order` WRITE;
/*!40000 ALTER TABLE `pay_auto_unfrozen_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_auto_unfrozen_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_bankcard`
--

DROP TABLE IF EXISTS `pay_bankcard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_bankcard` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `bankname` varchar(100) NOT NULL COMMENT '银行名称',
  `subbranch` varchar(100) NOT NULL COMMENT '支行名称',
  `accountname` varchar(100) NOT NULL COMMENT '开户名',
  `cardnumber` varchar(100) NOT NULL COMMENT '银行卡号',
  `province` varchar(100) NOT NULL COMMENT '所属省',
  `city` varchar(100) NOT NULL COMMENT '所属市',
  `ip` varchar(100) DEFAULT NULL COMMENT '上次修改IP',
  `ipaddress` varchar(300) DEFAULT NULL COMMENT 'IP地址',
  `alias` varchar(255) DEFAULT '' COMMENT '备注',
  `isdefault` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认 1是 0 否',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `IND_UID` (`userid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=8805 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_bankcard`
--

LOCK TABLES `pay_bankcard` WRITE;
/*!40000 ALTER TABLE `pay_bankcard` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_bankcard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_blockedlog`
--

DROP TABLE IF EXISTS `pay_blockedlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_blockedlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` varchar(100) NOT NULL COMMENT '订单号',
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '冻结金额',
  `thawtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '解冻时间',
  `pid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '商户支付通道',
  `createtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1 解冻 0 冻结',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='资金冻结待解冻记录';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_blockedlog`
--

LOCK TABLES `pay_blockedlog` WRITE;
/*!40000 ALTER TABLE `pay_blockedlog` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_blockedlog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_browserecord`
--

DROP TABLE IF EXISTS `pay_browserecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_browserecord` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `articleid` int(11) NOT NULL DEFAULT '0',
  `userid` int(11) NOT NULL DEFAULT '0',
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_browserecord`
--

LOCK TABLES `pay_browserecord` WRITE;
/*!40000 ALTER TABLE `pay_browserecord` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_browserecord` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_category`
--

DROP TABLE IF EXISTS `pay_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `pid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='文章栏目';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_category`
--

LOCK TABLES `pay_category` WRITE;
/*!40000 ALTER TABLE `pay_category` DISABLE KEYS */;
INSERT INTO `pay_category` VALUES (1,'最新资讯',0,1),(2,'公司新闻',0,1),(3,'公告通知',0,1),(4,'站内公告',3,1),(5,'公司新闻',3,1);
/*!40000 ALTER TABLE `pay_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_channel`
--

DROP TABLE IF EXISTS `pay_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_channel` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '供应商通道ID',
  `code` varchar(200) DEFAULT NULL COMMENT '供应商通道英文编码',
  `title` varchar(200) DEFAULT NULL COMMENT '供应商通道名称',
  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号',
  `signkey` varchar(500) DEFAULT NULL COMMENT '签文密钥',
  `appid` varchar(100) DEFAULT NULL COMMENT '应用APPID',
  `appsecret` varchar(100) DEFAULT NULL COMMENT '安全密钥',
  `gateway` varchar(300) DEFAULT NULL COMMENT '网关地址',
  `pagereturn` varchar(255) DEFAULT NULL COMMENT '页面跳转网址',
  `serverreturn` varchar(255) DEFAULT NULL COMMENT '服务器通知网址',
  `defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '下家费率',
  `fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '封顶手续费',
  `rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '银行费率',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次更改时间',
  `unlockdomain` varchar(100) NOT NULL COMMENT '防封域名',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 微信扫码 2 微信H5 3 支付宝扫码 4 支付宝H5 5网银跳转 6网银直连 7百度钱包 8 QQ钱包 9 京东钱包',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `paying_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当天交易金额',
  `all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当天上游可交易量',
  `last_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后交易时间',
  `min_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最小交易额',
  `max_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最大交易额',
  `control_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '风控状态:0否1是',
  `offline_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '通道上线状态:0已下线，1上线',
  `t0defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0运营费率',
  `t0fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0封顶手续费',
  `t0rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0成本费率',
  `fail_limit` int(10) unsigned DEFAULT '0' COMMENT '下线阀值，连续支付失败超过此值就将收款账号下线',
  `pay_delay` int(10) unsigned DEFAULT '0' COMMENT '加好友N秒之后再发收款',
  `money_list` varchar(1000) DEFAULT NULL COMMENT '金额区间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=561 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='供应商列表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_channel`
--

LOCK TABLES `pay_channel` WRITE;
/*!40000 ALTER TABLE `pay_channel` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_channel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_channel_account`
--

DROP TABLE IF EXISTS `pay_channel_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_channel_account` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '供应商通道账号ID',
  `channel_id` smallint(6) unsigned NOT NULL COMMENT '通道id',
  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号',
  `signkey` varchar(500) DEFAULT NULL COMMENT '签文密钥',
  `appid` varchar(100) DEFAULT NULL COMMENT '应用APPID',
  `appsecret` varchar(2500) DEFAULT NULL COMMENT '安全密钥',
  `defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '下家费率',
  `fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '封顶手续费',
  `rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '银行费率',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次更改时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  `test_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '测试状态 1通过 0未通过',
  `title` varchar(100) DEFAULT NULL COMMENT '账户标题',
  `weight` tinyint(2) DEFAULT NULL COMMENT '轮询权重',
  `custom_rate` tinyint(1) DEFAULT NULL COMMENT '是否自定义费率',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始交易时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `last_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后一笔交易时间',
  `paying_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当天交易金额',
  `all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单日可交易金额',
  `max_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔交易最大金额',
  `min_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔交易最小金额',
  `offline_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '上线状态-1上线,0下线',
  `control_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '风控状态-0不风控,1风控中',
  `is_defined` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否自定义:1-是,0-否',
  `unit_frist_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间第一笔交易时间',
  `unit_paying_number` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '单时间交易笔数',
  `unit_paying_amount` decimal(11,0) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间交易金额',
  `unit_interval` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间数值',
  `time_unit` char(1) NOT NULL DEFAULT 's' COMMENT '限制时间单位',
  `unit_number` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间次数',
  `unit_all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单位时间金额',
  `t0defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0运营费率',
  `t0fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0封顶手续费',
  `t0rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0成本费率',
  `unlockdomain` varchar(255) NOT NULL DEFAULT '' COMMENT '防封域名',
  `last_monitor` int(11) NOT NULL DEFAULT '0' COMMENT '上一次监控时间',
  `memberid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '该收款账号属于哪个pay_member.id, 0表示系统共用账号',
  `fail_times` int(10) unsigned DEFAULT '0' COMMENT '订单连续失败次数',
  `heartbeat` int(10) unsigned DEFAULT '0' COMMENT '监控心跳',
  `heartbeat_switch` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '心跳状态 1开启 0因心跳超时而关闭',
  `manual_switch` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '商户开关 1开启 0因心跳超时而关闭',
  `xingming` varchar(100) DEFAULT NULL COMMENT '账号姓名',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `pdd_amount` decimal(18,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '拼多多店铺成功流水',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2970 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='供应商账号列表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_channel_account`
--

LOCK TABLES `pay_channel_account` WRITE;
/*!40000 ALTER TABLE `pay_channel_account` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_channel_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_complaints_deposit`
--

DROP TABLE IF EXISTS `pay_complaints_deposit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_complaints_deposit` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `pay_orderid` varchar(100) NOT NULL DEFAULT '0' COMMENT '系统订单号',
  `out_trade_id` varchar(50) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `freeze_money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '冻结保证金额',
  `unfreeze_time` int(11) NOT NULL DEFAULT '0' COMMENT '计划解冻时间',
  `real_unfreeze_time` int(11) NOT NULL DEFAULT '0' COMMENT '实际解冻时间',
  `is_pause` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否暂停解冻 0正常解冻 1暂停解冻',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '解冻状态 0未解冻 1已解冻',
  `create_at` int(11) NOT NULL COMMENT '记录创建时间',
  `update_at` int(11) NOT NULL COMMENT '记录更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_unfreezeing` (`status`,`is_pause`,`unfreeze_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='投诉保证金余额';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_complaints_deposit`
--

LOCK TABLES `pay_complaints_deposit` WRITE;
/*!40000 ALTER TABLE `pay_complaints_deposit` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_complaints_deposit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_complaints_deposit_rule`
--

DROP TABLE IF EXISTS `pay_complaints_deposit_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_complaints_deposit_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `is_system` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否系统规则 1是 0否',
  `ratio` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '投诉保证金比例（百分比）',
  `freeze_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '冻结时间（秒）',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '规则是否开启 1开启 0关闭',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='投诉保证金规则表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_complaints_deposit_rule`
--

LOCK TABLES `pay_complaints_deposit_rule` WRITE;
/*!40000 ALTER TABLE `pay_complaints_deposit_rule` DISABLE KEYS */;
INSERT INTO `pay_complaints_deposit_rule` VALUES (1,180586943,1,0.00,0,0),(2,1,0,0.00,0,0),(3,46,0,0.10,1800,1);
/*!40000 ALTER TABLE `pay_complaints_deposit_rule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_df_api_order`
--

DROP TABLE IF EXISTS `pay_df_api_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_df_api_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '平台订单号',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '商户订单号',
  `money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '金额',
  `bankname` varchar(100) NOT NULL DEFAULT '' COMMENT '银行名称',
  `subbranch` varchar(100) NOT NULL DEFAULT '' COMMENT '支行名称',
  `accountname` varchar(100) NOT NULL DEFAULT '' COMMENT '开户名',
  `cardnumber` varchar(100) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `province` varchar(100) NOT NULL DEFAULT '' COMMENT '所属省',
  `city` varchar(100) NOT NULL DEFAULT '' COMMENT '所属市',
  `ip` varchar(100) DEFAULT '' COMMENT 'IP地址',
  `check_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0：待审核 1：已提交后台审核 2：审核驳回',
  `extends` text COMMENT '扩展字段',
  `df_id` int(11) NOT NULL DEFAULT '0' COMMENT '代付ID',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `reject_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '驳回原因',
  `check_time` int(11) NOT NULL DEFAULT '0' COMMENT '审核时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `IND_UID` (`userid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_df_api_order`
--

LOCK TABLES `pay_df_api_order` WRITE;
/*!40000 ALTER TABLE `pay_df_api_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_df_api_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_email`
--

DROP TABLE IF EXISTS `pay_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_email` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `smtp_host` varchar(300) DEFAULT NULL,
  `smtp_port` varchar(300) DEFAULT NULL,
  `smtp_user` varchar(300) DEFAULT NULL,
  `smtp_pass` varchar(300) DEFAULT NULL,
  `smtp_email` varchar(300) DEFAULT NULL,
  `smtp_name` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_email`
--

LOCK TABLES `pay_email` WRITE;
/*!40000 ALTER TABLE `pay_email` DISABLE KEYS */;
INSERT INTO `pay_email` VALUES (1,'smtp.163.com','465','1233456@163.com','1233456','1233456@163.com','大通支付');
/*!40000 ALTER TABLE `pay_email` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_invitecode`
--

DROP TABLE IF EXISTS `pay_invitecode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_invitecode` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invitecode` varchar(32) NOT NULL,
  `fmusernameid` int(11) unsigned NOT NULL DEFAULT '0',
  `syusernameid` int(11) NOT NULL DEFAULT '0',
  `regtype` tinyint(1) unsigned NOT NULL DEFAULT '4' COMMENT '用户组 4 普通用户 5 代理商',
  `fbdatetime` int(11) unsigned NOT NULL DEFAULT '0',
  `yxdatetime` int(11) unsigned NOT NULL DEFAULT '0',
  `sydatetime` int(11) unsigned DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '邀请码状态 0 禁用 1 未使用 2 已使用',
  `is_admin` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否管理员添加',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `invitecode` (`invitecode`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_invitecode`
--

LOCK TABLES `pay_invitecode` WRITE;
/*!40000 ALTER TABLE `pay_invitecode` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_invitecode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_inviteconfig`
--

DROP TABLE IF EXISTS `pay_inviteconfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_inviteconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invitezt` tinyint(1) unsigned DEFAULT '1',
  `invitetype2number` int(11) NOT NULL DEFAULT '20',
  `invitetype2ff` smallint(6) NOT NULL DEFAULT '1',
  `invitetype5number` int(11) NOT NULL DEFAULT '20',
  `invitetype5ff` smallint(6) NOT NULL DEFAULT '1',
  `invitetype6number` int(11) NOT NULL DEFAULT '20',
  `invitetype6ff` smallint(6) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_inviteconfig`
--

LOCK TABLES `pay_inviteconfig` WRITE;
/*!40000 ALTER TABLE `pay_inviteconfig` DISABLE KEYS */;
INSERT INTO `pay_inviteconfig` VALUES (1,0,0,0,100,0,0,0);
/*!40000 ALTER TABLE `pay_inviteconfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_loginrecord`
--

DROP TABLE IF EXISTS `pay_loginrecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_loginrecord` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0',
  `logindatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `loginip` varchar(100) NOT NULL,
  `loginaddress` varchar(300) DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型：0：前台用户 1：后台用户',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=72238 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_loginrecord`
--

LOCK TABLES `pay_loginrecord` WRITE;
/*!40000 ALTER TABLE `pay_loginrecord` DISABLE KEYS */;
INSERT INTO `pay_loginrecord` VALUES (72237,33,'2022-04-02 19:32:31','171.22.195.20','英国',1);
/*!40000 ALTER TABLE `pay_loginrecord` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_member`
--

DROP TABLE IF EXISTS `pay_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_member` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(32) NOT NULL COMMENT '密码',
  `groupid` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户组',
  `salt` varchar(10) NOT NULL COMMENT '密码随机字符',
  `parentid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '代理ID',
  `agent_cate` int(11) NOT NULL DEFAULT '0' COMMENT '代理级别',
  `balance` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '可用余额',
  `blockedbalance` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '冻结可用余额',
  `email` varchar(100) NOT NULL,
  `activate` varchar(200) NOT NULL,
  `regdatetime` int(11) unsigned NOT NULL DEFAULT '0',
  `activatedatetime` int(11) unsigned NOT NULL DEFAULT '0',
  `realname` varchar(50) DEFAULT NULL COMMENT '姓名',
  `sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '性别',
  `birthday` int(11) NOT NULL DEFAULT '0',
  `sfznumber` varchar(20) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL COMMENT '联系电话',
  `qq` varchar(15) DEFAULT NULL COMMENT 'QQ',
  `address` varchar(200) DEFAULT NULL COMMENT '联系地址',
  `paypassword` varchar(32) DEFAULT NULL COMMENT '支付密码',
  `authorized` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 已认证 0 未认证 2 待审核',
  `apidomain` varchar(500) DEFAULT NULL COMMENT '授权访问域名',
  `apikey` varchar(32) NOT NULL COMMENT 'APIKEY',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1激活 0未激活',
  `receiver` varchar(255) DEFAULT NULL COMMENT '台卡显示的收款人信息',
  `unit_paying_number` int(15) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间已交易次数',
  `unit_paying_amount` decimal(21,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '单位时间已交易金额',
  `unit_frist_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间已交易的第一笔时间',
  `last_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当天最后一笔已交易时间',
  `paying_money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '当天已交易金额',
  `login_ip` varchar(255) NOT NULL DEFAULT ' ' COMMENT '登录IP',
  `last_error_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录错误时间',
  `login_error_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '错误登录次数',
  `google_auth` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启谷歌身份验证登录',
  `df_api` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启代付API',
  `open_charge` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启充值功能',
  `open_channel_account` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启添加子账号功能',
  `df_domain` text NOT NULL COMMENT '代付域名报备',
  `df_auto_check` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API自动审核',
  `google_secret_key` varchar(255) NOT NULL DEFAULT '' COMMENT '谷歌密钥',
  `df_ip` text NOT NULL COMMENT '代付域名报备IP',
  `session_random` varchar(50) NOT NULL DEFAULT '' COMMENT 'session随机字符串',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `edit_tongdao` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否可编辑其名下的通道',
  `td_sxf` decimal(15,4) DEFAULT '0.0000' COMMENT '自供收款账号的手续费',
  `can_bf` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否可以补发订单的权限  1可以补发  0不可以补发',
  `td_balance` decimal(15,4) DEFAULT '0.0000' COMMENT '供号商户余额',
  `collect_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '收款类型，0:默认，1:商户自供,2:供号商户',
  `amount_water` decimal(15,4) DEFAULT '0.0000' COMMENT '供号商户的交易流水总额，不能超过td_balance，交易额度',
  `dj_amount_water` decimal(15,4) DEFAULT '0.0000' COMMENT '供号商户的冻结额度',
  `aliasname` varchar(255) DEFAULT NULL COMMENT '客户设置的别名',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `pay_member_parentid_index` (`parentid`)
) ENGINE=InnoDB AUTO_INCREMENT=220399199 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_member`
--

LOCK TABLES `pay_member` WRITE;
/*!40000 ALTER TABLE `pay_member` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_member_agent_cate`
--

DROP TABLE IF EXISTS `pay_member_agent_cate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_member_agent_cate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cate_name` varchar(50) DEFAULT NULL COMMENT '等级名',
  `desc` varchar(255) DEFAULT NULL COMMENT '等级描述',
  `ctime` int(11) DEFAULT '0' COMMENT '添加时间',
  `sort` int(11) DEFAULT '99' COMMENT '排序',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_member_agent_cate`
--

LOCK TABLES `pay_member_agent_cate` WRITE;
/*!40000 ALTER TABLE `pay_member_agent_cate` DISABLE KEYS */;
INSERT INTO `pay_member_agent_cate` VALUES (4,'普通商户','',1522638122,99),(5,'普通代理商户','',1522638122,99),(6,'中级代理商户','',1522638122,99),(7,'高级代理商户','',1522638122,99);
/*!40000 ALTER TABLE `pay_member_agent_cate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_moneychange`
--

DROP TABLE IF EXISTS `pay_moneychange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_moneychange` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `ymoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '原金额',
  `money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '变动金额',
  `gmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '变动后金额',
  `datetime` datetime DEFAULT NULL COMMENT '修改时间',
  `transid` varchar(50) DEFAULT NULL COMMENT '交易流水号',
  `tongdao` smallint(6) unsigned DEFAULT '0' COMMENT '支付通道ID',
  `lx` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '类型',
  `tcuserid` int(11) DEFAULT NULL,
  `tcdengji` int(11) DEFAULT NULL,
  `orderid` varchar(50) DEFAULT NULL COMMENT '订单号',
  `contentstr` varchar(255) DEFAULT NULL COMMENT '备注',
  `t` int(4) NOT NULL DEFAULT '0' COMMENT '结算方式',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=98673 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_moneychange`
--

LOCK TABLES `pay_moneychange` WRITE;
/*!40000 ALTER TABLE `pay_moneychange` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_moneychange` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_notify`
--

DROP TABLE IF EXISTS `pay_notify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_notify` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_orderid` varchar(100) NOT NULL COMMENT '系统订单号',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IND_ORD` (`pay_orderid`)
) ENGINE=InnoDB AUTO_INCREMENT=307 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_notify`
--

LOCK TABLES `pay_notify` WRITE;
/*!40000 ALTER TABLE `pay_notify` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_notify` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order`
--

DROP TABLE IF EXISTS `pay_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` varchar(100) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(100) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `cost` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `actual_amount` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '浮动差价金额',
  `expire_time` int(11) NOT NULL DEFAULT '0',
  `appsecret` varchar(255) NOT NULL DEFAULT '',
  `istest` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '测试订单: 0 不是 1 用来测试收款账号的',
  `margins` decimal(10,4) unsigned DEFAULT '0.0000' COMMENT '收款账号要打给平台的钱',
  `bill_account` varchar(100) DEFAULT NULL COMMENT '付款账号',
  `pay_profit` decimal(10,4) unsigned DEFAULT '0.0000' COMMENT '供码分润',
  `brokerage1` decimal(10,4) unsigned DEFAULT '0.0000' COMMENT '上级分佣',
  `brokerage2` decimal(10,4) unsigned DEFAULT '0.0000' COMMENT '上级的上级分佣',
  `provider_cost` decimal(10,4) unsigned DEFAULT '0.0000' COMMENT '供码成本',
  `brokerage3` decimal(10,4) unsigned DEFAULT '0.0000' COMMENT '三级分佣',
  `upstream_order` varchar(100) DEFAULT NULL COMMENT '上游订单号',
  `pay_order_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '订单面额',
  `pay_orderamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '下单金额',
  `pay_device` varchar(255) DEFAULT NULL COMMENT '设备信息',
  `pay_ip` varchar(255) DEFAULT NULL COMMENT 'ip',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=221120 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order`
--

LOCK TABLES `pay_order` WRITE;
/*!40000 ALTER TABLE `pay_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_pay_channel_extend_fields`
--

DROP TABLE IF EXISTS `pay_pay_channel_extend_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_pay_channel_extend_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) NOT NULL DEFAULT '0' COMMENT '代付渠道ID',
  `code` varchar(64) NOT NULL DEFAULT '' COMMENT '代付渠道代码',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '扩展字段名',
  `alias` varchar(50) NOT NULL DEFAULT '' COMMENT '扩展字段别名',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `etime` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_pay_channel_extend_fields`
--

LOCK TABLES `pay_pay_channel_extend_fields` WRITE;
/*!40000 ALTER TABLE `pay_pay_channel_extend_fields` DISABLE KEYS */;
INSERT INTO `pay_pay_channel_extend_fields` VALUES (1,1,'Yibao','bankProvinceName','银行卡的所在省名称','',1533622880,1533622880),(2,1,'Yibao','bankProvinceCode','银行卡的所在省编码','',1533622891,1533622891),(3,1,'Yibao','bankCityName','银行卡的所在市名称','',1533622901,1533622901),(4,1,'Yibao','bankCityCode','银行卡的所在市编码','',1533622911,1533622911),(5,1,'Yibao','bankAreaName','银行卡的所在区名称','',1533622932,1533622932),(6,1,'Yibao','bankAreaCode','银行卡的所在区编码','',1533622945,1533622945),(7,1,'Yibao','bankId','银行卡的开户行编号','',1533622956,1533622956),(8,1,'Yibao','bankUserCert','银行卡的持卡人身份证','',1533622969,1533622969),(9,1,'Yibao','bankUserPhone','银行卡的预留手机号','',1533622991,1533622991),(11,2,'qtpay','lhh','联行号','必填',1537721443,1537721443),(12,2,'qtpay','测试','试试','烦烦烦',1551173073,1551173073);
/*!40000 ALTER TABLE `pay_pay_channel_extend_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_pay_for_another`
--

DROP TABLE IF EXISTS `pay_pay_for_another`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_pay_for_another` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `code` varchar(64) NOT NULL COMMENT '代付代码',
  `title` varchar(64) NOT NULL COMMENT '代付名称',
  `mch_id` varchar(255) NOT NULL DEFAULT ' ' COMMENT '商户号',
  `appid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '应用APPID',
  `appsecret` varchar(100) NOT NULL DEFAULT ' ' COMMENT '应用密钥',
  `signkey` varchar(500) NOT NULL DEFAULT ' ' COMMENT '加密的秘钥',
  `public_key` varchar(1000) NOT NULL DEFAULT '  ' COMMENT '加密的公钥',
  `private_key` varchar(1000) NOT NULL DEFAULT '  ' COMMENT '加密的私钥',
  `exec_gateway` varchar(255) NOT NULL DEFAULT ' ' COMMENT '请求代付的地址',
  `query_gateway` varchar(255) NOT NULL DEFAULT ' ' COMMENT '查询代付的地址',
  `serverreturn` varchar(255) NOT NULL DEFAULT ' ' COMMENT '服务器通知网址',
  `unlockdomain` varchar(255) NOT NULL DEFAULT ' ' COMMENT '防封域名',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更改时间',
  `status` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  `is_default` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认：1是，0否',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `code` (`code`) USING BTREE,
  KEY `updatetime` (`updatetime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='代付通道表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_pay_for_another`
--

LOCK TABLES `pay_pay_for_another` WRITE;
/*!40000 ALTER TABLE `pay_pay_for_another` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_pay_for_another` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_paylog`
--

DROP TABLE IF EXISTS `pay_paylog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_paylog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `out_trade_no` varchar(50) NOT NULL,
  `result_code` varchar(50) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `fromuser` varchar(50) NOT NULL,
  `time_end` int(11) unsigned NOT NULL DEFAULT '0',
  `total_fee` smallint(6) unsigned NOT NULL DEFAULT '0',
  `payname` varchar(50) NOT NULL,
  `bank_type` varchar(20) DEFAULT NULL,
  `trade_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `IND_TRD` (`transaction_id`) USING BTREE,
  UNIQUE KEY `IND_ORD` (`out_trade_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_paylog`
--

LOCK TABLES `pay_paylog` WRITE;
/*!40000 ALTER TABLE `pay_paylog` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_paylog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_product`
--

DROP TABLE IF EXISTS `pay_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '通道名称',
  `alias_name` varchar(100) DEFAULT NULL COMMENT '通道别名，给玩家显示',
  `alias_name_icon` varchar(100) NOT NULL COMMENT '聚合通道icon',
  `code` varchar(50) NOT NULL COMMENT '通道代码',
  `polling` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '接口模式 0 单独 1 轮询',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付类型 1 微信扫码 2 微信H5 3 支付宝扫码 4 支付宝H5 5 网银跳转 6网银直连  7 百度钱包  8 QQ钱包 9 京东钱包',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `isdisplay` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户端显示 1 显示 0 不显示',
  `channel` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '通道ID',
  `weight` text COMMENT '平台默认通道权重',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1060 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_product`
--

LOCK TABLES `pay_product` WRITE;
/*!40000 ALTER TABLE `pay_product` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_product_user`
--

DROP TABLE IF EXISTS `pay_product_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_product_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT ' ',
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商户通道ID',
  `polling` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '接口模式：0 单独 1 轮询',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '通道状态 0 关闭 1 启用',
  `channel` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '指定单独通道ID',
  `weight` varchar(255) DEFAULT NULL COMMENT '通道权重',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=14956 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_product_user`
--

LOCK TABLES `pay_product_user` WRITE;
/*!40000 ALTER TABLE `pay_product_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_product_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_reconciliation`
--

DROP TABLE IF EXISTS `pay_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT '0' COMMENT '用户ID',
  `order_total_count` int(11) DEFAULT '0' COMMENT '总订单数',
  `order_success_count` int(11) DEFAULT '0' COMMENT '成功订单数',
  `order_fail_count` int(11) DEFAULT '0' COMMENT '未支付订单数',
  `order_total_amount` decimal(11,4) DEFAULT '0.0000' COMMENT '订单总额',
  `order_success_amount` decimal(11,4) DEFAULT '0.0000' COMMENT '订单实付总额',
  `date` date DEFAULT NULL COMMENT '日期',
  `ctime` int(11) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10821 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_reconciliation`
--

LOCK TABLES `pay_reconciliation` WRITE;
/*!40000 ALTER TABLE `pay_reconciliation` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_reconciliation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_redo_order`
--

DROP TABLE IF EXISTS `pay_redo_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_redo_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作管理员',
  `money` decimal(15,4) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1：增加 2：减少',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '冲正备注',
  `date` datetime NOT NULL COMMENT '冲正周期',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1694 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_redo_order`
--

LOCK TABLES `pay_redo_order` WRITE;
/*!40000 ALTER TABLE `pay_redo_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_redo_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_retire_log`
--

DROP TABLE IF EXISTS `pay_retire_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_retire_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `from_id` int(11) unsigned NOT NULL COMMENT '商户id，member.id，收款商户是谁',
  `addvalue` decimal(15,4) NOT NULL COMMENT '增加的已回款值',
  `old_balance` decimal(15,4) DEFAULT '0.0000' COMMENT '旧未回款',
  `new_balance` decimal(15,4) DEFAULT '0.0000' COMMENT '新未回款',
  `old_confirm_balance` decimal(15,4) DEFAULT '0.0000' COMMENT '旧已回款',
  `new_confirm_balance` decimal(15,4) DEFAULT '0.0000' COMMENT '新已回款',
  `ctime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_retire_log`
--

LOCK TABLES `pay_retire_log` WRITE;
/*!40000 ALTER TABLE `pay_retire_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_retire_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_route`
--

DROP TABLE IF EXISTS `pay_route`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_route` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `urlstr` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_route`
--

LOCK TABLES `pay_route` WRITE;
/*!40000 ALTER TABLE `pay_route` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_route` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_session`
--

DROP TABLE IF EXISTS `pay_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_session` (
  `session_id` varchar(128) NOT NULL,
  `session_expire` int(11) NOT NULL,
  `session_data` blob,
  UNIQUE KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_session`
--

LOCK TABLES `pay_session` WRITE;
/*!40000 ALTER TABLE `pay_session` DISABLE KEYS */;
INSERT INTO `pay_session` VALUES ('0nk5tsup0jr3de2q8u28g5m867',1649007686,''),('0vttgtjt646f73gu1u6o4dttm3',1648976979,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"cafd985b94576d9a7c75b582bd31f3fb\";}'),('12rve1tajqtrtt481dgforkp10',1648981095,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"4995139b161b66e1f793262268e9e38e\";}'),('1cau92b987cmtehtlch5lqkt64',1648998596,'__hash__|a:3:{s:32:\"257f2945b0f222049612285c0ed010ee\";s:32:\"9c6234b5b103b4d146dbd2befff990e1\";s:32:\"3b6c75caa0f284a6c51f97b7b2f631df\";s:32:\"c96516f5061268c32d022b4859e584ea\";s:32:\"3ef82d5e73414184a740ed678e951ba4\";s:32:\"03b6bd8d6f04a77c74a919483a370c17\";}'),('1icroc8hq8g8kptf0uii5j8l07',1649008359,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"bf5fa2ad5fa5cd30da8b2023f615e2d6\";}'),('2vsmrvv0nseguur6fe74var443',1648981948,''),('3c2fvffa4c3416tta01l3ldmd0',1648999730,'__hash__|a:1:{s:32:\"14c82c81d638f0f612097bed5d3c40eb\";s:32:\"73b2ba0e5a03cbb98b3ba8d015d802f2\";}'),('3fb4fmgbpmj6v5ki3ssqhu5ho6',1648991396,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"c6dc3084ab12e31b08f2ed67a8444604\";}'),('3idrovbsur0o31pq1f7iqv9rn2',1648976576,'__hash__|a:1:{s:32:\"bd6c4a991bafa14e2038aeead6d9e65f\";s:32:\"a11f5160d05f39869ba19d6e01e07a0e\";}'),('4qc42ak7je93q7iu5932c4heh4',1648964272,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"07280cd6323c2a622ef7405ec53d92f9\";}'),('54mq3reuo2gkah6reh2j9s6sv7',1649009560,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"20dbb0a94adc49f19d74f4dd0ca4aba0\";}'),('6p7ajgkmgke3tqoe1so95ffva1',1648976979,'__hash__|a:1:{s:32:\"257f2945b0f222049612285c0ed010ee\";s:32:\"47e00ddb45f453ad693dcca4a8a042f0\";}'),('8c1dpdkmfg5uhv6bc35urbsjt1',1648976979,'__hash__|a:1:{s:32:\"bd6c4a991bafa14e2038aeead6d9e65f\";s:32:\"d66722d475827856fcc21f976af04d25\";}'),('8qt6oj5qpn4ea59u5i2393d467',1648970129,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"b9f33338fc59d86b99d64bbe1e67d123\";}'),('8rqj5mdeai8f4qscj1os0o6837',1648976578,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"8696b78865eaa7fae47ff739aab16951\";}'),('9pq5nsqvv3gkftj1rm3kpvu3f7',1648967341,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"6549b92643254be5ce270c82dab5da0d\";}'),('9rqahtunh1cttgrimmklij14t4',1648976980,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"a8c93b7da419abff0d27750bd19adb26\";}'),('a4m08cmc242qjb7j9p6pvijhh7',1648976576,'__hash__|a:1:{s:32:\"3ef82d5e73414184a740ed678e951ba4\";s:32:\"54436f4b7d36d04baeb3c62f20537574\";}'),('aiuq8cjvh70bjk5n1ko5no2c85',1648976578,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"84dcd957e0dd7442d9e42f2528c1f051\";}'),('ao43ime28o5833mn9pnkd7mmp5',1648976578,'__hash__|a:1:{s:32:\"8828a66d703dbab5545cf335728ff8db\";s:32:\"298e960c223420c18e9d19c1183ca570\";}'),('auj5e4hf2n04r5k3uoefdkdva1',1648979549,''),('bkb8edg9mt5a7q9fe9cccm1gj3',1648976576,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"3336dbeb51a5ec4f6200f5405f1ac101\";}'),('bmvuft9h6ennopqhmu6f8j3vq5',1648964270,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"e94771df88249385867c5a368658d29b\";}'),('bt6blt5tpjg34f2sfclcacttc7',1648976576,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"c13e0404ef5155bd15e9e6b3efd475a9\";}'),('cioi6ckfctn1mueiisjago99p4',1648976980,'__hash__|a:1:{s:32:\"3ef82d5e73414184a740ed678e951ba4\";s:32:\"d79c51fac650fb891391f9995e7fcc26\";}'),('cle1g65cktrmo3l3d25jaivit3',1649007855,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"2a509305e711c9ce9cde6b670322e135\";}'),('coo7nmak3bdibm86kib5ojpmi4',1648976979,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"b98bc466b48b1a6ce5127475b324d994\";}'),('cshe0qiho3vgn8ogqtl9u56nt0',1648997147,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"4954dec051aced8dcd41757a430a16cb\";}'),('dp409hfr138itvv8igdkf6a9c6',1648976578,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"8696b78865eaa7fae47ff739aab16951\";}'),('dsv8qhn4bpe9ntuh3gf4f6u046',1648976578,'__hash__|a:1:{s:32:\"3b6c75caa0f284a6c51f97b7b2f631df\";s:32:\"bb29bf5077e716a79a66298bf548ad5d\";}'),('f57qq76qsqf4jntvdm29efidu0',1648961605,'__hash__|a:8:{s:32:\"257f2945b0f222049612285c0ed010ee\";s:32:\"dd7e3b00c6b1e4de564ec5e4f47ffc8c\";s:32:\"bd6c4a991bafa14e2038aeead6d9e65f\";s:32:\"cf376ffd53a13751e305f42527c1226b\";s:32:\"14c82c81d638f0f612097bed5d3c40eb\";s:32:\"eedab618fe46399041e6fe343b7a2992\";s:32:\"24c313c431e52e19205da1f80674fc92\";s:32:\"9aaaa2db9dbe25add80c83497abdb92c\";s:32:\"c71a252306aefbbe04b70b81c6496fd7\";s:32:\"0d6e6c7fe32450e7ff187391afdd48b4\";s:32:\"80456bb903baa2180ab1364ce4e0becf\";s:32:\"935a7ec5bd9bd6180460b2064c7d329e\";s:32:\"581dac61e6009f14e819219357871b97\";s:32:\"25bec873938d149c2052b55ba5981885\";s:32:\"b4964f612a514479c3c9ab47631f11cf\";s:32:\"154b86448c11a5cf983d8faf705d7f74\";}admin_auth|a:5:{s:3:\"uid\";s:2:\"33\";s:8:\"username\";s:4:\"nami\";s:7:\"groupid\";s:1:\"1\";s:8:\"password\";s:32:\"d5a5cc2b5174f2f5b24c47c8eb060e41\";s:14:\"session_random\";s:32:\"qlOytgFCR9UfkAeuIA9sV8jUxYa1iKSM\";}admin_auth_sign|s:40:\"c4bdcbee79aaa03a735f9d4fbb87414562ffeed3\";admin_auth_login_ip|s:15:\"172.104.105.209\";google_auth|s:6:\"640899\";'),('frcudvr0klhgvk6va0i23dnvq2',1648976576,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"7f465e8a985ee0fbc6052ae32ab3a3ac\";}'),('fvska2qbc1isa1i905i4ks2u01',1648967790,''),('g20ef02ao70iall8rt6h8b98v7',1648976574,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"7a8bd3595e7aca8b137a7796511dd265\";}'),('gd51kl40gve5vhbt5noo7pm1d4',1649007685,''),('gnfch780uqjnrin2m21nbuht73',1648976978,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"6b3c821d2014e3eb6b93abd63ecbb3ee\";}'),('h3c16l426nfgvd679pgf0i76p3',1648962851,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"aafaec263f3400ff18d4dc0a05f4c753\";}'),('h9b8t36ofleer1bl66e1tm85m5',1648976979,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"615b89a6bc072e660b52ae2284a5bdd8\";}'),('hdgql5jrs6820i1cdmgkafnl30',1648989124,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"a83c1b016179da2362daf79cffb3df70\";}'),('idjogvdv46s4vpu93naj9vrvj0',1648976576,'__hash__|a:1:{s:32:\"3b6c75caa0f284a6c51f97b7b2f631df\";s:32:\"35b4a3eae056e3d18eb6571ec57b0e97\";}'),('io6h269pedosrlf7196mmo5243',1648980230,'__hash__|a:7:{s:32:\"14c82c81d638f0f612097bed5d3c40eb\";s:32:\"0600c061736638699995acfacbc7a60a\";s:32:\"24c313c431e52e19205da1f80674fc92\";s:32:\"dd4ac79a28dffe5b12a5384f7818e9f5\";s:32:\"c71a252306aefbbe04b70b81c6496fd7\";s:32:\"38adfdaa23577716503a257e1d66ef84\";s:32:\"80456bb903baa2180ab1364ce4e0becf\";s:32:\"fde7bf4f72602b40ae7f7714ada59d5d\";s:32:\"89f89485261de514fc4691fd65f2707e\";s:32:\"58a2e7cdc109c933ab981899c0dd7f9f\";s:32:\"802c19f48332631431a8175b0ae931e5\";s:32:\"e2522c4d5e44ec4b9823ca3230b104ae\";s:32:\"53adb89756abe2800c959a386b807079\";s:32:\"738b8a821f9809d2cef2112fbe6e2272\";}admin_auth|a:5:{s:3:\"uid\";s:2:\"33\";s:8:\"username\";s:4:\"nami\";s:7:\"groupid\";s:1:\"1\";s:8:\"password\";s:32:\"d5a5cc2b5174f2f5b24c47c8eb060e41\";s:14:\"session_random\";s:32:\"AQWOWTc7bCwsy1l8zDdZEIMnx8rVbDIy\";}admin_auth_sign|s:40:\"fefad22da3a04c40721b78abda6a285c17f34cd1\";admin_auth_login_ip|s:13:\"171.22.195.20\";google_auth|s:6:\"062840\";'),('ipmpqgkm83rqdag6tikh987ji5',1648976578,'__hash__|a:1:{s:32:\"3ef82d5e73414184a740ed678e951ba4\";s:32:\"9077701917dc2649e98b734c7804d92e\";}'),('jopi7iobqaoncvci5bi9ce6fa1',1648989284,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"7da1effec18cd58b9c82683d4c16f82e\";}'),('k0inqv9nk8v41sf5113sn0vf76',1648976578,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"46794fb423682b24dba1c7d7b7189873\";}'),('k3omor0c2ll56lr6273k8psnu0',1648964118,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"918c9cbe532316d37d09c21e8f78312d\";}'),('lan7jeeqsica76sr21u9i32nt3',1649007636,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"170c22f8c1b508cc4ba4e9468baf6fcf\";}'),('lsm227jvlnmufvfpp024lcu9k3',1648976979,'__hash__|a:1:{s:32:\"3b6c75caa0f284a6c51f97b7b2f631df\";s:32:\"37fe416311db870c43f485868792eab3\";}'),('mbg4b0dd28g2uhvnl3ttb0vsi2',1649009668,'__hash__|a:1:{s:32:\"4c32bae28011a93db1ccf846c4dd9109\";s:32:\"47a7605effb1233113f401a6eed713d5\";}d2d977c58444271d9c780187e93f80e5|a:2:{s:11:\"verify_code\";s:32:\"eb4cef4939089403b544356a4a30db5b\";s:11:\"verify_time\";i:1648959263;}'),('ne2173ev43lhrl38l8hvq02o43',1648976576,'__hash__|a:1:{s:32:\"3ef82d5e73414184a740ed678e951ba4\";s:32:\"b7986881baf41d9cb55a4a73e28c3985\";}'),('nmncqehj9mh73mbl62v45a27h3',1649007916,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"01918c72fde0c388bada59bbb0924997\";}'),('o4cgbrtpa2db01lp74a2hout60',1648959699,'__hash__|a:5:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"d702b286010f04d6b3d0717cbc968e14\";s:32:\"257f2945b0f222049612285c0ed010ee\";s:32:\"f0098905038e6a5531940eebfa8ca44c\";s:32:\"bd6c4a991bafa14e2038aeead6d9e65f\";s:32:\"fb36ba8c5fc7cf05fc9a89202fbbc22e\";s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"22dcf4fbbfb6a023ee1a28a84155d706\";s:32:\"14c82c81d638f0f612097bed5d3c40eb\";s:32:\"c2a8657d466ffbc8ca097a6acabcee7c\";}'),('oknnmk0oq7qeslfgl7k09hv4l5',1648978295,'__hash__|a:2:{s:32:\"bd6c4a991bafa14e2038aeead6d9e65f\";s:32:\"224e295070c4678846dd4da09e81d37d\";s:32:\"257f2945b0f222049612285c0ed010ee\";s:32:\"6a5fef7506e1e312ccb4d0d68f7fbaa4\";}'),('p3sjiu1muu996omehg9aus3j32',1648976576,'__hash__|a:1:{s:32:\"257f2945b0f222049612285c0ed010ee\";s:32:\"acbfddaccfda566d8c5c42bc3fcc0e20\";}'),('q1bua3eadg9avf96t455i5po25',1649007686,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"7149c73db7d1fb975b82aa6d0a9edd26\";}'),('q9htp03voonglhlouajeg4vec1',1648976979,'__hash__|a:1:{s:32:\"8828a66d703dbab5545cf335728ff8db\";s:32:\"f75c476816a2fa8bc916ab5b2c2fab3e\";}'),('rr12g3knumv2liil26n76f45v6',1648976577,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"c2c06232dd2d7268779eb8a9bc1a80c9\";}'),('sfc7s3jadhtt48ib60htkpir85',1648976578,'__hash__|a:1:{s:32:\"bd6c4a991bafa14e2038aeead6d9e65f\";s:32:\"0c96f29ad4853fd937550e708f0ca588\";}'),('si0tcaj2gkb2l97kd7qnlefsg7',1648976980,'__hash__|a:1:{s:32:\"3ef82d5e73414184a740ed678e951ba4\";s:32:\"e1f303685651a27bc6dadd620487f412\";}'),('sui79t61tbj5k8sln5k5iradl5',1649007460,'__hash__|a:1:{s:32:\"3ef82d5e73414184a740ed678e951ba4\";s:32:\"ee0bc6b6f4d703f3365dbf774d642711\";}'),('t96e9e87p2qojkm4q8ghemvd13',1648976578,'__hash__|a:1:{s:32:\"3ef82d5e73414184a740ed678e951ba4\";s:32:\"8ee80ef7a2ad05712071a0fc1001be65\";}'),('taolqu21ppjd49v4etg7p54td6',1648990027,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"02f65d6fb753cb5354e67174ed99e1db\";}'),('te6t3o22lgp3f14gpfqn30n3p6',1648976578,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"0202960d7dc4159a312d5bf6d386e643\";}'),('ti8vtq5gj89po0acd1ljrt1rh1',1648995035,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"11af484163029a6c04e54f6e230eed39\";}'),('tidts4j3icsv3e0uhbaqflgfq7',1648968906,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"810ab45364f5a46d94ef1deb62365677\";}'),('u2tcsh11bc0n0lo9hm1q4bn3l7',1648976576,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"8719a5d0a90b20a6aa153656e919c574\";}'),('u5ekjqqq8i7t0vt2h1l2bdlvd1',1648978113,'__hash__|a:1:{s:32:\"6666cd76f96956469e7be39d750cc7d9\";s:32:\"5a1150c97ddd8767524741152c6a89ef\";}'),('u7v91m1ncvs0fj2teigs2cesk1',1648976576,'__hash__|a:1:{s:32:\"8828a66d703dbab5545cf335728ff8db\";s:32:\"6cd601d835c581702c965929deed0060\";}'),('ubpi8hgfun8hvknb20v2p98j00',1648976979,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"66583dae2ba83302d1627eefb9b47618\";}'),('utftn675jtbqjr3cv4hv5gkp72',1648968301,'__hash__|a:22:{s:32:\"14c82c81d638f0f612097bed5d3c40eb\";s:32:\"7f34897bbc5a7ad91730149a4043ebe1\";s:32:\"24c313c431e52e19205da1f80674fc92\";s:32:\"8a7f851bfc7e92d82b1b8abb2491c693\";s:32:\"c71a252306aefbbe04b70b81c6496fd7\";s:32:\"e2480fe849d73bb59213978881669e51\";s:32:\"80456bb903baa2180ab1364ce4e0becf\";s:32:\"734dd0ec4b6bb47bb2f36f67685f0213\";s:32:\"eb05bb1b6030b160a89f465632624fa4\";s:32:\"d7866cc0976f8c70828cc1f7942c9c75\";s:32:\"53adb89756abe2800c959a386b807079\";s:32:\"e1ea7e648b0ae028c6b098c66c47c8a9\";s:32:\"b43db4051393949d674745d0cab21a70\";s:32:\"165e65337aa2ea125406593746fb3cd9\";s:32:\"ddbd4c519c18fb73ac443160def0432a\";s:32:\"8617d7d39e9b985ca19c58888b1330f6\";s:32:\"4353018d590db3b8c59ea0be86e006fc\";s:32:\"c0968cd3385db89805b2391fd793be3b\";s:32:\"71571f5d986c2ce73e51d3d56ccf7e70\";s:32:\"04162f02c8b490b3239d2806a0044f01\";s:32:\"da25ae2e0a45cb48c34c60d6eaebe51f\";s:32:\"1eb84f9ca2695d9554e82fd8ed973435\";s:32:\"89f89485261de514fc4691fd65f2707e\";s:32:\"5be1fb6edeca87ca50bf7b7bce007414\";s:32:\"802c19f48332631431a8175b0ae931e5\";s:32:\"269597a8eceda8fd16ed0e4551013ccd\";s:32:\"7d15a416815260d666605c77b2ddf01e\";s:32:\"51dfcce025b87f8d8df408dd3b86ec0d\";s:32:\"d7eaca8eefca9800803fd5874b48e74d\";s:32:\"2f68272cde732c811a607e862714d974\";s:32:\"f1034f78d60405799904a04c36fdf59e\";s:32:\"1d2e267c96f2bab868146e806d19a3c1\";s:32:\"8dbd7448f6666aaff4b4258a51cb9a0e\";s:32:\"e8caf6b9fddd7bf2a0b5be01cca1edc4\";s:32:\"fe7a90703545b1c94d674bdf46f9ae5f\";s:32:\"79ac35702b4f09b378b7b80dc2ab71fe\";s:32:\"e6e689406682448e9853bd31e5da78b7\";s:32:\"65f206b0b2a02a1bac644da3451a013c\";s:32:\"3ac6faea4a6abb636a67fade6f526292\";s:32:\"b00c74090b38c1e8c5b237c7d1b67cef\";s:32:\"a2b801728b816945dce5cc885e8a0fa3\";s:32:\"b9d968fbf91de410328a47cb091f8414\";s:32:\"2c7fce3843a1da9176598155bab5102a\";s:32:\"671c77fb314ef3737ac999ad44ae1b51\";}admin_auth|a:5:{s:3:\"uid\";s:2:\"33\";s:8:\"username\";s:4:\"nami\";s:7:\"groupid\";s:1:\"1\";s:8:\"password\";s:32:\"d5a5cc2b5174f2f5b24c47c8eb060e41\";s:14:\"session_random\";s:32:\"m9Ga4dFcq8foYb3zoY4Rw4R1xC4P4TQt\";}admin_auth_sign|s:40:\"227ea31acf4ab498930c958ae2de000a77d41c58\";admin_auth_login_ip|s:15:\"172.104.105.209\";google_auth|s:6:\"183166\";'),('v7uhtbhi02j92pavkfmesnmk05',1648976576,'__hash__|a:1:{s:32:\"4a64b3eb49fdb95f2c9fc3618dcfd5a8\";s:32:\"a876f28c2593d08e7832ab2defb4a306\";}'),('vkls0qmhlsiim1lq6k765hau06',1648976578,'__hash__|a:1:{s:32:\"257f2945b0f222049612285c0ed010ee\";s:32:\"f82591a0a782297675c10e1738b19c5b\";}');
/*!40000 ALTER TABLE `pay_session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_sms`
--

DROP TABLE IF EXISTS `pay_sms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_sms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_key` varchar(255) DEFAULT NULL COMMENT 'App Key',
  `app_secret` varchar(255) DEFAULT NULL COMMENT 'App Secret',
  `sign_name` varchar(255) DEFAULT NULL COMMENT '默认签名',
  `is_open` int(11) DEFAULT '0' COMMENT '是否开启，0关闭，1开启',
  `admin_mobile` varchar(255) DEFAULT NULL COMMENT '管理员接收手机',
  `is_receive` int(11) DEFAULT '0' COMMENT '是否开启，0关闭，1开启',
  `sms_channel` varchar(20) NOT NULL DEFAULT 'aliyun' COMMENT '短信通道',
  `smsbao_user` varchar(50) NOT NULL DEFAULT '' COMMENT '短信宝账号',
  `smsbao_pass` varchar(50) NOT NULL DEFAULT '' COMMENT '短信宝密码',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_sms`
--

LOCK TABLES `pay_sms` WRITE;
/*!40000 ALTER TABLE `pay_sms` DISABLE KEYS */;
INSERT INTO `pay_sms` VALUES (4,'LTAIscrqZaYn0mDd','eGXhWxsjFdwYVjkY1EJtGk80GlVdXD','手机钱包',0,NULL,0,'aliyun','','');
/*!40000 ALTER TABLE `pay_sms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_sms_template`
--

DROP TABLE IF EXISTS `pay_sms_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_sms_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `template_code` varchar(255) DEFAULT NULL COMMENT '模板代码',
  `call_index` varchar(255) DEFAULT NULL COMMENT '调用字符串',
  `template_content` text COMMENT '模板内容',
  `ctime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_sms_template`
--

LOCK TABLES `pay_sms_template` WRITE;
/*!40000 ALTER TABLE `pay_sms_template` DISABLE KEYS */;
INSERT INTO `pay_sms_template` VALUES (3,'修改支付密码','SMS_70465305','editPayPassword','验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！',1512202260),(4,'修改登录密码','SMS_159930422','editPassword','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1512190115),(5,'异地登录','SMS_159773703','loginWarning','检测到您的账号登录异常，如非本人操纵，请及时修改账号密码。',1512202260),(6,'申请结算','SMS_159930422','clearing','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1512202260),(7,'委托结算','SMS_159930422','entrusted','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1512202260),(8,'绑定手机','SMS_159930422','bindMobile','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1514534290),(9,'更新手机','SMS_159930422','editMobile','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1514535688),(10,'更新银行卡 ','SMS_159930422','addBankcardSend','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1514535688),(11,'修改个人资料','SMS_159930422','saveProfile','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',151453568),(12,'绑定管理员手机号码','SMS_159930422','adminbindMobile','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1527670734),(13,'修改管理员手机号码','SMS_159930422','admineditMobile','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1527670734),(14,'批量删除订单','SMS_159930422','delOrderSend','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1527670734),(15,'解绑谷歌身份验证器','SMS_70465305 ','unbindGoogle','验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！',1527670734),(16,'设置订单为已支付','SMS_159930422','setOrderPaidSend','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1527670734),(17,'清理数据','SMS_159930422','clearDataSend','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1527670734),(18,'增加/减少余额（冲正）','SMS_70465305 ','adjustUserMoneySend','验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！',1527670734),(19,'提交代付','SMS_159930422','submitDfSend','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1527670734),(20,'测试短信','SMS_159930422','test','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1527670734),(21,'系统配置','SMS_159930422','sysconfigSend','您的验证码${code}，该验证码5分钟内有效，请勿泄漏于他人！',1527670734),(22,'客户提现提醒','SMS_159773712','tixian','平台有客户申请提现，请及时处理！',1536649511);
/*!40000 ALTER TABLE `pay_sms_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_systembank`
--

DROP TABLE IF EXISTS `pay_systembank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_systembank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bankcode` varchar(100) DEFAULT NULL,
  `bankname` varchar(300) DEFAULT NULL,
  `images` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=198 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='结算银行';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_systembank`
--

LOCK TABLES `pay_systembank` WRITE;
/*!40000 ALTER TABLE `pay_systembank` DISABLE KEYS */;
INSERT INTO `pay_systembank` VALUES (162,'BOB','北京银行','BOB.gif'),(164,'BEA','东亚银行','BEA.gif'),(165,'ICBC','中国工商银行','ICBC.gif'),(166,'CEB','中国光大银行','CEB.gif'),(167,'GDB','广发银行','GDB.gif'),(168,'HXB','华夏银行','HXB.gif'),(169,'CCB','中国建设银行','CCB.gif'),(170,'BCM','交通银行','BCM.gif'),(171,'CMSB','中国民生银行','CMSB.gif'),(172,'NJCB','南京银行','NJCB.gif'),(173,'NBCB','宁波银行','NBCB.gif'),(174,'ABC','中国农业银行','5414c87492ad8.gif'),(175,'PAB','平安银行','5414c0929a632.gif'),(176,'BOS','上海银行','BOS.gif'),(177,'SPDB','上海浦东发展银行','SPDB.gif'),(178,'SDB','深圳发展银行','SDB.gif'),(179,'CIB','兴业银行','CIB.gif'),(180,'PSBC','中国邮政储蓄银行','PSBC.gif'),(181,'CMBC','招商银行','CMBC.gif'),(182,'CZB','浙商银行','CZB.gif'),(183,'BOC','中国银行','BOC.gif'),(184,'CNCB','中信银行','CNCB.gif'),(193,'ALIPAY','支付宝','58b83a5820644.jpg'),(194,'WXZF','微信','58b83a757a298.jpg');
/*!40000 ALTER TABLE `pay_systembank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tdbalance_order`
--

DROP TABLE IF EXISTS `pay_tdbalance_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tdbalance_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '商户id，member.id',
  `orderid` int(10) unsigned DEFAULT NULL COMMENT '订单表的id，扣手续费的时候用',
  `admin_id` int(11) unsigned DEFAULT NULL COMMENT '操作管理员id',
  `rmb` decimal(15,4) DEFAULT NULL COMMENT '从供号商户手抖的实际钱',
  `ymoney` decimal(15,4) NOT NULL COMMENT '旧值',
  `money` decimal(15,4) NOT NULL COMMENT '变更量',
  `gmoney` decimal(15,4) NOT NULL COMMENT '新值',
  `type` tinyint(1) unsigned NOT NULL COMMENT '1增加，2减少，3订单成功消耗',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `ctime` int(11) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2562 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tdbalance_order`
--

LOCK TABLES `pay_tdbalance_order` WRITE;
/*!40000 ALTER TABLE `pay_tdbalance_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_tdbalance_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tdsxf_order`
--

DROP TABLE IF EXISTS `pay_tdsxf_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tdsxf_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '商户id，member.id',
  `orderid` int(10) unsigned DEFAULT NULL COMMENT '订单表的id，扣手续费的时候用',
  `admin_id` int(11) unsigned DEFAULT NULL COMMENT '操作管理员id',
  `ymoney` decimal(15,4) NOT NULL COMMENT '旧值',
  `money` decimal(15,4) NOT NULL COMMENT '变更量',
  `gmoney` decimal(15,4) NOT NULL COMMENT '新值',
  `type` tinyint(1) unsigned NOT NULL COMMENT '1增加，2减少，3订单成功消耗',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `ctime` int(11) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tdsxf_order`
--

LOCK TABLES `pay_tdsxf_order` WRITE;
/*!40000 ALTER TABLE `pay_tdsxf_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_tdsxf_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_template`
--

DROP TABLE IF EXISTS `pay_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT ' ' COMMENT '模板名称',
  `theme` varchar(255) NOT NULL DEFAULT ' ' COMMENT '模板代码',
  `is_default` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认模板:1是，0否',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `remarks` varchar(255) NOT NULL DEFAULT ' ' COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='模板表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_template`
--

LOCK TABLES `pay_template` WRITE;
/*!40000 ALTER TABLE `pay_template` DISABLE KEYS */;
INSERT INTO `pay_template` VALUES (1,' 默认模板','default',0,1524299660,1524299660,' 默认模板'),(2,'2018新模板','view4',1,1542881243,1542881243,'123456'),(3,'模板二','view2',0,1539869388,1539869388,'默认模板二'),(4,'模板三','view3',0,1539869326,1539869326,'雀付模板'),(5,'模板五','view5',0,1539869357,1539869357,'无首页-只有登录页'),(6,'九州支付','view6',0,1550138165,1550138165,'九州支付');
/*!40000 ALTER TABLE `pay_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tikuanconfig`
--

DROP TABLE IF EXISTS `pay_tikuanconfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tikuanconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `tkzxmoney` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最小提款金额',
  `tkzdmoney` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最大提款金额',
  `dayzdmoney` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当日提款最大总金额',
  `dayzdnum` int(11) NOT NULL DEFAULT '0' COMMENT '当日提款最大次数',
  `t1zt` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT 'T+1 ：1开启 0 关闭',
  `t0zt` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'T+0 ：1开启 0 关闭',
  `gmt0` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '购买T0',
  `tkzt` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '提款设置 1 开启 0 关闭',
  `tktype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '提款手续费类型 1 每笔 0 比例 ',
  `systemxz` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 系统规则 1 用户规则',
  `sxfrate` varchar(20) DEFAULT NULL COMMENT '单笔提款比例',
  `sxffixed` varchar(20) DEFAULT NULL COMMENT '单笔提款手续费',
  `issystem` tinyint(1) unsigned DEFAULT '0' COMMENT '平台规则 1 是 0 否',
  `allowstart` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '提款允许开始时间',
  `allowend` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '提款允许结束时间',
  `daycardzdmoney` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单人单卡单日最高提现额',
  `auto_df_switch` tinyint(1) NOT NULL DEFAULT '0' COMMENT '自动代付开关',
  `auto_df_maxmoney` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单笔代付最大金额限制',
  `auto_df_stime` varchar(20) NOT NULL DEFAULT '' COMMENT '自动代付开始时间',
  `auto_df_etime` varchar(20) NOT NULL DEFAULT '' COMMENT '自动代付结束时间',
  `auto_df_max_count` int(11) NOT NULL DEFAULT '0' COMMENT '商户每天自动代付笔数限制',
  `auto_df_max_sum` int(11) NOT NULL DEFAULT '0' COMMENT '商户每天自动代付最大总金额限制',
  `tk_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `IND_UID` (`userid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=39 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tikuanconfig`
--

LOCK TABLES `pay_tikuanconfig` WRITE;
/*!40000 ALTER TABLE `pay_tikuanconfig` DISABLE KEYS */;
INSERT INTO `pay_tikuanconfig` VALUES (35,1,5.00,20000000.00,99999999.99,10000,0,0,0.00,1,0,0,'0','0',1,0,0,40000000.00,0,0.00,'','',0,0,0),(36,64,1000.00,100000.00,10000000.00,1000,0,0,0.00,1,1,1,'','3',0,0,0,0.00,0,0.00,'','',0,0,0),(37,191277692,5.00,100000.00,10000000.00,1000,0,0,0.00,1,1,1,'','3',0,0,0,0.00,0,0.00,'','',0,0,0),(38,191249803,0.00,1000000.00,0.00,0,0,0,0.00,0,0,1,'','',0,0,0,0.00,0,0.00,'','',0,0,0);
/*!40000 ALTER TABLE `pay_tikuanconfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tikuanholiday`
--

DROP TABLE IF EXISTS `pay_tikuanholiday`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tikuanholiday` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排除日期',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED COMMENT='排除节假日';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tikuanholiday`
--

LOCK TABLES `pay_tikuanholiday` WRITE;
/*!40000 ALTER TABLE `pay_tikuanholiday` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_tikuanholiday` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tikuanmoney`
--

DROP TABLE IF EXISTS `pay_tikuanmoney`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tikuanmoney` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT '0' COMMENT '结算用户ID',
  `websiteid` int(11) NOT NULL DEFAULT '0',
  `payapiid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '结算通道ID',
  `t` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '结算方式: 1 T+1 ,0 T+0',
  `money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  `datetype` varchar(2) NOT NULL,
  `createtime` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=691 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tikuanmoney`
--

LOCK TABLES `pay_tikuanmoney` WRITE;
/*!40000 ALTER TABLE `pay_tikuanmoney` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_tikuanmoney` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tikuantime`
--

DROP TABLE IF EXISTS `pay_tikuantime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tikuantime` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `baiks` tinyint(2) unsigned DEFAULT '0' COMMENT '白天提款开始时间',
  `baijs` tinyint(2) unsigned DEFAULT '0' COMMENT '白天提款结束时间',
  `wanks` tinyint(2) unsigned DEFAULT '0' COMMENT '晚间提款开始时间',
  `wanjs` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED COMMENT='提款时间';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tikuantime`
--

LOCK TABLES `pay_tikuantime` WRITE;
/*!40000 ALTER TABLE `pay_tikuantime` DISABLE KEYS */;
INSERT INTO `pay_tikuantime` VALUES (1,24,17,18,24);
/*!40000 ALTER TABLE `pay_tikuantime` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tklist`
--

DROP TABLE IF EXISTS `pay_tklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tklist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) NOT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `sheng` varchar(300) NOT NULL,
  `shi` varchar(300) NOT NULL,
  `sqdatetime` datetime DEFAULT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `t` int(4) NOT NULL DEFAULT '1',
  `payapiid` int(11) NOT NULL DEFAULT '0',
  `memo` text COMMENT '备注',
  `tk_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `from_id` int(11) unsigned DEFAULT NULL COMMENT '从哪个商户支付',
  `confirm_datetime` datetime DEFAULT NULL COMMENT '确认时间',
  `op_userid` int(11) unsigned DEFAULT NULL COMMENT '哪个商户打款了',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=24259 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tklist`
--

LOCK TABLES `pay_tklist` WRITE;
/*!40000 ALTER TABLE `pay_tklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_tklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_updatelog`
--

DROP TABLE IF EXISTS `pay_updatelog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_updatelog` (
  `version` varchar(20) NOT NULL,
  `lastupdate` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_updatelog`
--

LOCK TABLES `pay_updatelog` WRITE;
/*!40000 ALTER TABLE `pay_updatelog` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_updatelog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_user_channel_account`
--

DROP TABLE IF EXISTS `pay_user_channel_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_user_channel_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `account_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '子账号id',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启指定账号',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `userid` (`userid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='用户指定指账号';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_user_channel_account`
--

LOCK TABLES `pay_user_channel_account` WRITE;
/*!40000 ALTER TABLE `pay_user_channel_account` DISABLE KEYS */;
INSERT INTO `pay_user_channel_account` VALUES (11,93,'',0);
/*!40000 ALTER TABLE `pay_user_channel_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_user_code`
--

DROP TABLE IF EXISTS `pay_user_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_user_code` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(11) DEFAULT '0' COMMENT '0找回密码',
  `code` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `status` int(11) DEFAULT '0',
  `ctime` int(11) DEFAULT NULL,
  `uptime` int(11) DEFAULT NULL COMMENT '更新时间',
  `endtime` int(11) DEFAULT NULL COMMENT '有效时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_user_code`
--

LOCK TABLES `pay_user_code` WRITE;
/*!40000 ALTER TABLE `pay_user_code` DISABLE KEYS */;
INSERT INTO `pay_user_code` VALUES (6,0,'36989','11210980','11210980@qq.com',NULL,0,1538813477,NULL,1538814077),(7,0,'67722','786qp','745319777@qq.com',NULL,0,1578458148,NULL,1578458748);
/*!40000 ALTER TABLE `pay_user_code` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_user_riskcontrol_config`
--

DROP TABLE IF EXISTS `pay_user_riskcontrol_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_user_riskcontrol_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `min_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最小金额',
  `max_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最大金额',
  `unit_all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单位时间内交易总金额',
  `all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当天交易总金额',
  `start_time` tinyint(10) unsigned NOT NULL DEFAULT '0' COMMENT '一天交易开始时间',
  `end_time` tinyint(10) unsigned NOT NULL DEFAULT '0' COMMENT '一天交易结束时间',
  `unit_number` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间内交易的总笔数',
  `is_system` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否平台规则',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态:1开通，0关闭',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `edit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `time_unit` char(1) NOT NULL DEFAULT 'i' COMMENT '限制的时间单位',
  `unit_interval` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间值',
  `domain` varchar(255) NOT NULL DEFAULT ' ' COMMENT '防封域名',
  `systemxz` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 系统规则 1 用户规则',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uid` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT COMMENT='交易配置';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_user_riskcontrol_config`
--

LOCK TABLES `pay_user_riskcontrol_config` WRITE;
/*!40000 ALTER TABLE `pay_user_riskcontrol_config` DISABLE KEYS */;
INSERT INTO `pay_user_riskcontrol_config` VALUES (1,0,10.00,50000.00,0.00,0.00,0,0,0,1,0,1554114101,0,'i',0,'',0),(2,180751041,0.00,10000.00,0.00,0.00,0,0,0,0,0,1533759190,1532768653,'s',0,'',1),(3,180768684,1.00,10000.00,0.00,299972.00,0,0,0,0,1,1532846143,1532774264,'s',0,'',1),(4,180762223,10.00,5000.00,0.00,0.00,0,0,0,0,0,1536964058,1532774447,'s',0,'',0),(5,28,20.00,8000.00,0.00,100000.00,0,0,0,0,1,0,1547554843,'s',0,'',1);
/*!40000 ALTER TABLE `pay_user_riskcontrol_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_userrate`
--

DROP TABLE IF EXISTS `pay_userrate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_userrate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `payapiid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '通道ID',
  `feilv` decimal(10,4) unsigned DEFAULT '0.0000' COMMENT '运营费率',
  `fengding` decimal(10,4) unsigned DEFAULT '0.0000' COMMENT '封顶费率',
  `t0feilv` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0运营费率',
  `t0fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0封顶费率',
  `t0feilv_night` decimal(10,4) DEFAULT '0.0000' COMMENT 'T0晚间运营费率',
  `t0fengding_night` decimal(10,4) DEFAULT '0.0000' COMMENT 'T0晚间封顶费率',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=35279 DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED COMMENT='商户通道费率';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_userrate`
--

LOCK TABLES `pay_userrate` WRITE;
/*!40000 ALTER TABLE `pay_userrate` DISABLE KEYS */;
INSERT INTO `pay_userrate` VALUES (32888,220219890,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32889,220219890,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32890,220219890,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32891,220219890,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32892,220219890,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32893,220219890,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32894,220219890,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32895,220219890,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32896,220219890,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32897,220219890,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32898,220219890,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32899,220219890,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32900,220219890,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32901,220219890,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32902,220219890,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32903,220219890,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32904,220219890,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32905,220219890,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32906,220219890,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32907,220219890,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32908,220219890,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32909,220219890,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32910,220272410,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32911,220272410,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32912,220272410,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32913,220272410,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32914,220272410,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32915,220272410,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32916,220272410,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32917,220272410,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32918,220272410,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32919,220272410,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32920,220272410,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32921,220272410,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32922,220272410,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32923,220272410,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32924,220272410,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32925,220272410,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32926,220272410,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32927,220272410,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32928,220272410,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32929,220272410,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32930,220272410,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32931,220272410,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32932,220272410,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32933,220272410,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32934,220272410,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32935,220272410,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32936,220272410,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32937,220272410,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32938,220272410,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32939,220272410,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32940,220272410,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32941,220272410,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32942,220272410,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32943,220272410,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32944,220272410,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32945,220272410,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32946,220272410,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32947,220272410,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32948,220272410,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32949,220272410,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32950,220272410,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32951,220272410,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32952,220272410,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32953,220272410,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32954,220272410,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32955,220272410,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32956,220272410,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32957,220272410,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32958,220272410,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32959,220272410,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32960,220272410,1052,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32961,220278387,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32962,220278387,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32963,220278387,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32964,220278387,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32965,220278387,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32966,220278387,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32967,220278387,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32968,220278387,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32969,220278387,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32970,220278387,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32971,220278387,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32972,220278387,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32973,220278387,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32974,220278387,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32975,220278387,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32976,220278387,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32977,220278387,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32978,220278387,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32979,220278387,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32980,220278387,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32981,220278387,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32982,220278387,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32983,220278387,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32984,220278387,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32985,220278387,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32986,220278387,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32987,220278387,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32988,220278387,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32989,220278387,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32990,220278387,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32991,220278387,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32992,220278387,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32993,220278387,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32994,220278387,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32995,220278387,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32996,220278387,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32997,220278387,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32998,220278387,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(32999,220278387,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33000,220278387,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33001,220278387,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33002,220278387,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33003,220278387,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33004,220278387,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33005,220278387,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33006,220278387,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33007,220278387,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33008,220278387,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33009,220278387,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33010,220278387,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33011,220278387,1052,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33012,210572137,1048,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33013,210572137,1049,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33014,210572137,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33015,210572137,1052,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33016,210536969,1048,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33017,210536969,1049,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33018,210536969,1051,NULL,NULL,0.1050,0.1050,0.0000,0.0000),(33019,210536969,1052,NULL,NULL,0.1100,0.1100,0.0000,0.0000),(33020,210887179,1048,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33021,210887179,1049,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33022,210887179,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33023,210887179,1052,NULL,NULL,0.1180,0.1180,0.0000,0.0000),(33024,210118109,1048,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33025,210118109,1049,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33026,210118109,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33027,210118109,1052,NULL,NULL,0.1180,0.1180,0.0000,0.0000),(33028,210188420,1048,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33029,210188420,1049,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33030,210188420,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33031,210188420,1052,NULL,NULL,0.1180,0.1180,0.0000,0.0000),(33032,210189048,1048,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33033,210189048,1049,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33034,210189048,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33035,210189048,1052,NULL,NULL,0.1180,0.1180,0.0000,0.0000),(33036,200841093,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33037,200841093,1052,NULL,NULL,0.1180,0.1180,0.0000,0.0000),(33038,220260988,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33039,220260988,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33040,220260988,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33041,220260988,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33042,220260988,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33043,220260988,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33044,220260988,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33045,220260988,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33046,220260988,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33047,220260988,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33048,220260988,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33049,220260988,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33050,220260988,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33051,220260988,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33052,220260988,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33053,220260988,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33054,220260988,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33055,220260988,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33056,220260988,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33057,220260988,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33058,220260988,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33059,220260988,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33060,220260988,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33061,220260988,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33062,220260988,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33063,220260988,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33064,220260988,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33065,220260988,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33066,220260988,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33067,220260988,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33068,220260988,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33069,220260988,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33070,220260988,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33071,220260988,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33072,220260988,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33073,220260988,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33074,220260988,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33075,220260988,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33076,220260988,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33077,220260988,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33078,220260988,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33079,220260988,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33080,220260988,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33081,220260988,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33082,220260988,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33083,220260988,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33084,220260988,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33085,220260988,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33086,220260988,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33087,220260988,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33088,220291098,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33089,220291098,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33090,220291098,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33091,220291098,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33092,220291098,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33093,220291098,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33094,220291098,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33095,220291098,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33096,220291098,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33097,220291098,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33098,220291098,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33099,220291098,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33100,220291098,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33101,220291098,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33102,220291098,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33103,220291098,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33104,220291098,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33105,220291098,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33106,220291098,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33107,220291098,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33108,220291098,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33109,220291098,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33110,220291098,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33111,220291098,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33112,220291098,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33113,220291098,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33114,220291098,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33115,220291098,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33116,220291098,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33117,220291098,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33118,220291098,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33119,220291098,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33120,220291098,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33121,220291098,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33122,220291098,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33123,220291098,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33124,220291098,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33125,220291098,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33126,220291098,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33127,220291098,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33128,220291098,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33129,220291098,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33130,220291098,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33131,220291098,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33132,220291098,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33133,220291098,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33134,220291098,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33135,220291098,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33136,220291098,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33137,220291098,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33138,220277408,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33139,220277408,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33140,220277408,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33141,220277408,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33142,220277408,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33143,220277408,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33144,220277408,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33145,220277408,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33146,220277408,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33147,220277408,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33148,220277408,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33149,220277408,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33150,220277408,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33151,220277408,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33152,220277408,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33153,220277408,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33154,220277408,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33155,220277408,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33156,220277408,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33157,220277408,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33158,220277408,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33159,220277408,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33160,220277408,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33161,220277408,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33162,220277408,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33163,220277408,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33164,220277408,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33165,220277408,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33166,220277408,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33167,220277408,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33168,220277408,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33169,220277408,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33170,220277408,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33171,220277408,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33172,220277408,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33173,220277408,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33174,220277408,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33175,220277408,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33176,220277408,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33177,220277408,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33178,220277408,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33179,220277408,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33180,220277408,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33181,220277408,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33182,220277408,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33183,220277408,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33184,220277408,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33185,220277408,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33186,220277408,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33187,220277408,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33188,220223966,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33189,220223966,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33190,220223966,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33191,220223966,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33192,220223966,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33193,220223966,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33194,220223966,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33195,220223966,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33196,220223966,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33197,220223966,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33198,220223966,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33199,220223966,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33200,220223966,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33201,220223966,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33202,220223966,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33203,220223966,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33204,220223966,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33205,220223966,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33206,220223966,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33207,220223966,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33208,220223966,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33209,220223966,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33210,220223966,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33211,220223966,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33212,220223966,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33213,220223966,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33214,220223966,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33215,220223966,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33216,220223966,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33217,220223966,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33218,220223966,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33219,220223966,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33220,220223966,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33221,220223966,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33222,220223966,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33223,220223966,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33224,220223966,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33225,220223966,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33226,220223966,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33227,220223966,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33228,220223966,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33229,220223966,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33230,220223966,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33231,220223966,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33232,220223966,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33233,220223966,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33234,220223966,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33235,220223966,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33236,220223966,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33237,220223966,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33238,220288131,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33239,220288131,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33240,220288131,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33241,220288131,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33242,220288131,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33243,220288131,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33244,220288131,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33245,220288131,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33246,220288131,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33247,220288131,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33248,220288131,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33249,220288131,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33250,220288131,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33251,220288131,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33252,220288131,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33253,220288131,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33254,220288131,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33255,220288131,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33256,220288131,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33257,220288131,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33258,220288131,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33259,220288131,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33260,220288131,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33261,220288131,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33262,220288131,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33263,220288131,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33264,220288131,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33265,220288131,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33266,220288131,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33267,220288131,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33268,220288131,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33269,220288131,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33270,220288131,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33271,220288131,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33272,220288131,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33273,220288131,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33274,220288131,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33275,220288131,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33276,220288131,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33277,220288131,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33278,220288131,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33279,220288131,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33280,220288131,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33281,220288131,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33282,220288131,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33283,220288131,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33284,220288131,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33285,220288131,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33286,220288131,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33287,220288131,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33288,220288131,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33289,210572137,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33290,210536969,1053,NULL,NULL,0.1050,0.1050,0.0000,0.0000),(33291,210189048,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33292,210188420,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33293,210118109,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33294,210887179,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33295,210534444,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33296,210534444,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33297,210573017,1051,NULL,NULL,0.1050,0.1050,0.0000,0.0000),(33298,210573017,1053,NULL,NULL,0.1050,0.1050,0.0000,0.0000),(33299,210524899,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33300,210524899,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33301,210635320,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33302,210635320,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33303,210575063,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33304,210575063,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33305,210542798,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33306,210542798,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33307,220263678,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33308,220263678,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33309,220263678,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33310,220263678,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33311,220263678,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33312,220263678,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33313,220263678,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33314,220263678,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33315,220263678,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33316,220263678,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33317,220263678,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33318,220263678,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33319,220263678,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33320,220263678,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33321,220263678,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33322,220263678,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33323,220263678,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33324,220263678,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33325,220263678,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33326,220263678,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33327,220263678,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33328,220263678,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33329,220263678,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33330,220263678,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33331,220263678,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33332,220263678,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33333,220263678,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33334,220263678,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33335,220263678,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33336,220263678,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33337,220263678,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33338,220263678,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33339,220263678,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33340,220263678,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33341,220263678,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33342,220263678,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33343,220263678,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33344,220263678,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33345,220263678,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33346,220263678,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33347,220263678,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33348,220263678,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33349,220263678,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33350,220263678,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33351,220263678,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33352,220263678,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33353,220263678,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33354,220263678,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33355,220263678,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33356,220263678,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33357,220263678,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33358,220263678,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33359,220255068,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33360,220255068,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33361,220255068,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33362,220255068,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33363,220255068,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33364,220255068,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33365,220255068,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33366,220255068,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33367,220255068,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33368,220255068,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33369,220255068,1032,NULL,NULL,0.1800,0.1800,0.0000,0.0000),(33370,220255068,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33371,220255068,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33372,220255068,974,NULL,NULL,0.0500,0.0500,0.0000,0.0000),(33373,220255068,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33374,220255068,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33375,220255068,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33376,220255068,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33377,220255068,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33378,220255068,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33379,220255068,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33380,220255068,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33381,220255068,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33382,220255068,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33383,220255068,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33384,220255068,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33385,220255068,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33386,220255068,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33387,220255068,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33388,220255068,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33389,220255068,1017,NULL,NULL,0.1050,0.1050,0.0000,0.0000),(33390,220255068,1018,NULL,NULL,0.1050,0.1050,0.0000,0.0000),(33391,220255068,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33392,220255068,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33393,220255068,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33394,220255068,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33395,220255068,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33396,220255068,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33397,220255068,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33398,220255068,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33399,220255068,1028,NULL,NULL,0.0500,0.0500,0.0000,0.0000),(33400,220255068,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33401,220255068,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33402,220255068,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33403,220255068,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33404,220255068,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33405,220255068,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33406,220255068,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33407,220255068,1050,NULL,NULL,0.1700,0.1700,0.0000,0.0000),(33408,220255068,1051,NULL,NULL,0.1100,0.1100,0.0000,0.0000),(33409,220255068,1053,NULL,NULL,0.1100,0.1100,0.0000,0.0000),(33410,220255068,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33411,210647313,1051,NULL,NULL,0.1100,0.1100,0.0000,0.0000),(33412,210647313,1053,NULL,NULL,0.1100,0.1100,0.0000,0.0000),(33413,210652743,1051,NULL,NULL,0.1080,0.1080,0.0000,0.0000),(33414,210652743,1053,NULL,NULL,0.1080,0.1080,0.0000,0.0000),(33415,200211905,1051,NULL,NULL,0.1100,0.1100,0.0000,0.0000),(33416,200211905,1053,NULL,NULL,0.1100,0.1100,0.0000,0.0000),(33417,211060832,1051,NULL,NULL,0.1080,0.1080,0.0000,0.0000),(33418,211060832,1053,NULL,NULL,0.1080,0.1080,0.0000,0.0000),(33419,200292156,1051,NULL,NULL,0.1120,0.1120,0.0000,0.0000),(33420,200292156,1053,NULL,NULL,0.1120,0.1120,0.0000,0.0000),(33421,211081748,1051,NULL,NULL,0.1100,0.1100,0.0000,0.0000),(33422,211081748,1053,NULL,NULL,0.1100,0.1100,0.0000,0.0000),(33423,220393998,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33424,220393998,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33425,220393998,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33426,220393998,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33427,220393998,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33428,220393998,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33429,220393998,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33430,220393998,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33431,220393998,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33432,220393998,990,NULL,NULL,0.0500,0.0500,0.0000,0.0000),(33433,220393998,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33434,220393998,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33435,220393998,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33436,220393998,974,NULL,NULL,0.0500,0.0500,0.0000,0.0000),(33437,220393998,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33438,220393998,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33439,220393998,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33440,220393998,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33441,220393998,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33442,220393998,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33443,220393998,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33444,220393998,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33445,220393998,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33446,220393998,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33447,220393998,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33448,220393998,1010,NULL,NULL,0.0500,0.0500,0.0000,0.0000),(33449,220393998,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33450,220393998,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33451,220393998,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33452,220393998,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33453,220393998,1017,NULL,NULL,0.1050,0.1050,0.0000,0.0000),(33454,220393998,1018,NULL,NULL,0.1050,0.1050,0.0000,0.0000),(33455,220393998,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33456,220393998,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33457,220393998,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33458,220393998,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33459,220393998,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33460,220393998,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33461,220393998,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33462,220393998,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33463,220393998,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33464,220393998,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33465,220393998,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33466,220393998,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33467,220393998,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33468,220393998,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33469,220393998,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33470,220393998,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33471,220393998,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33472,220393998,1051,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33473,220393998,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33474,220393998,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33475,210874595,1051,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33476,210874595,1053,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33477,210890730,1051,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33478,210890730,1053,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33479,211015312,1051,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33480,211015312,1053,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33481,200841093,1053,NULL,NULL,0.1150,0.1150,0.0000,0.0000),(33482,210340329,1051,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33483,210340329,1053,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33484,210577411,1051,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33485,210577411,1053,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33486,220372978,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33487,220372978,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33488,220372978,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33489,220372978,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33490,220372978,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33491,220372978,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33492,220372978,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33493,220372978,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33494,220372978,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33495,220372978,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33496,220372978,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33497,220372978,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33498,220372978,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33499,220372978,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33500,220372978,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33501,220372978,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33502,220372978,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33503,220372978,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33504,220372978,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33505,220372978,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33506,220372978,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33507,220372978,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33508,220372978,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33509,220372978,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33510,220372978,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33511,220372978,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33512,220372978,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33513,220372978,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33514,220372978,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33515,220372978,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33516,220372978,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33517,220372978,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33518,220372978,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33519,220372978,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33520,220372978,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33521,220372978,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33522,220372978,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33523,220372978,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33524,220372978,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33525,220372978,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33526,220372978,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33527,220372978,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33528,220372978,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33529,220372978,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33530,220372978,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33531,220372978,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33532,220372978,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33533,220372978,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33534,220372978,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33535,220372978,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33536,220372978,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33537,220372978,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33538,210875691,1032,NULL,NULL,0.1800,0.1800,0.0000,0.0000),(33539,211129168,1032,NULL,NULL,0.1700,0.1700,0.0000,0.0000),(33540,211229886,1032,NULL,NULL,0.1750,0.1750,0.0000,0.0000),(33541,210875691,1050,NULL,NULL,0.1800,0.1800,0.0000,0.0000),(33542,210789847,1050,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(33543,211229886,1050,NULL,NULL,0.1750,0.1750,0.0000,0.0000),(33544,211129168,1050,NULL,NULL,0.1700,0.1700,0.0000,0.0000),(33545,210524899,1050,NULL,NULL,0.1850,0.1850,0.0000,0.0000),(33546,210536969,1050,NULL,NULL,0.1830,0.1830,0.0000,0.0000),(33547,211218889,1032,NULL,NULL,0.1800,0.1800,0.0000,0.0000),(33548,211218889,1050,NULL,NULL,0.1750,0.1750,0.0000,0.0000),(33549,220317000,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33550,220317000,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33551,220317000,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33552,220317000,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33553,220317000,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33554,220317000,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33555,220317000,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33556,220317000,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33557,220317000,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33558,220317000,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33559,220317000,1032,NULL,NULL,0.1700,0.1700,0.0000,0.0000),(33560,220317000,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33561,220317000,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33562,220317000,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33563,220317000,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33564,220317000,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33565,220317000,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33566,220317000,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33567,220317000,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33568,220317000,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33569,220317000,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33570,220317000,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33571,220317000,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33572,220317000,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33573,220317000,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33574,220317000,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33575,220317000,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33576,220317000,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33577,220317000,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33578,220317000,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33579,220317000,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33580,220317000,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33581,220317000,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33582,220317000,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33583,220317000,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33584,220317000,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33585,220317000,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33586,220317000,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33587,220317000,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33588,220317000,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33589,220317000,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33590,220317000,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33591,220317000,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33592,220317000,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33593,220317000,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33594,220317000,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33595,220317000,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33596,220317000,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33597,220317000,1050,NULL,NULL,0.1700,0.1700,0.0000,0.0000),(33598,220317000,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33599,220317000,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33600,220317000,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33601,211176965,1032,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(33602,211176965,1050,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(33603,210647313,1050,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33604,210652743,1050,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33605,211229886,1051,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33606,211229886,1053,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33607,220112340,1050,NULL,NULL,0.1750,0.1750,0.0000,0.0000),(33608,220112340,1051,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33609,220112340,1053,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(33610,220335868,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33611,220335868,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33612,220335868,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33613,220335868,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33614,220335868,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33615,220335868,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33616,220335868,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33617,220335868,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33618,220335868,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33619,220335868,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33620,220335868,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33621,220335868,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33622,220335868,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33623,220335868,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33624,220335868,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33625,220335868,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33626,220335868,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33627,220335868,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33628,220335868,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33629,220335868,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33630,220335868,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33631,220335868,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33632,220335868,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33633,220335868,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33634,220335868,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33635,220335868,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33636,220335868,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33637,220335868,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33638,220335868,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33639,220335868,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33640,220335868,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33641,220335868,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33642,220335868,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33643,220335868,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33644,220335868,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33645,220335868,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33646,220335868,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33647,220335868,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33648,220335868,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33649,220335868,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33650,220335868,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33651,220335868,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33652,220335868,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33653,220335868,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33654,220335868,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33655,220335868,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33656,220335868,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33657,220335868,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33658,220335868,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33659,220335868,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33660,220335868,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33661,220335868,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33662,220373501,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33663,220373501,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33664,220373501,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33665,220373501,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33666,220373501,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33667,220373501,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33668,220373501,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33669,220373501,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33670,220373501,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33671,220373501,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33672,220373501,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33673,220373501,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33674,220373501,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33675,220373501,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33676,220373501,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33677,220373501,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33678,220373501,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33679,220373501,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33680,220373501,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33681,220373501,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33682,220373501,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33683,220373501,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33684,220373501,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33685,220373501,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33686,220373501,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33687,220373501,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33688,220373501,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33689,220373501,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33690,220373501,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33691,220373501,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33692,220373501,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33693,220373501,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33694,220373501,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33695,220373501,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33696,220373501,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33697,220373501,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33698,220373501,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33699,220373501,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33700,220373501,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33701,220373501,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33702,220373501,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33703,220373501,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33704,220373501,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33705,220373501,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33706,220373501,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33707,220373501,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33708,220373501,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33709,220373501,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33710,220373501,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33711,220373501,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33712,220373501,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33713,220373501,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33714,210340329,1032,NULL,NULL,0.1850,0.1850,0.0000,0.0000),(33715,210340329,1050,NULL,NULL,0.1850,0.1850,0.0000,0.0000),(33716,210874595,1050,NULL,NULL,0.1850,0.1850,0.0000,0.0000),(33717,210890730,1050,NULL,NULL,0.1830,0.1830,0.0000,0.0000),(33718,210245946,1050,NULL,NULL,0.1820,0.1820,0.0000,0.0000),(33719,210189048,1050,NULL,NULL,0.1800,0.1800,0.0000,0.0000),(33720,220328321,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33721,220328321,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33722,220328321,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33723,220328321,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33724,220328321,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33725,220328321,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33726,220328321,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33727,220328321,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33728,220328321,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33729,220328321,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33730,220328321,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33731,220328321,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33732,220328321,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33733,220328321,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33734,220328321,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33735,220328321,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33736,220328321,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33737,220328321,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33738,220328321,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33739,220328321,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33740,220328321,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33741,220328321,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33742,220328321,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33743,220328321,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33744,220328321,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33745,220328321,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33746,220328321,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33747,220328321,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33748,220328321,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33749,220328321,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33750,220328321,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33751,220328321,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33752,220328321,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33753,220328321,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33754,220328321,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33755,220328321,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33756,220328321,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33757,220328321,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33758,220328321,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33759,220328321,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33760,220328321,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33761,220328321,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33762,220328321,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33763,220328321,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33764,220328321,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33765,220328321,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33766,220328321,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33767,220328321,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33768,220328321,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33769,220328321,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33770,220328321,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33771,220328321,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33772,220329907,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33773,220329907,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33774,220329907,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33775,220329907,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33776,220329907,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33777,220329907,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33778,220329907,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33779,220329907,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33780,220329907,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33781,220329907,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33782,220329907,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33783,220329907,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33784,220329907,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33785,220329907,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33786,220329907,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33787,220329907,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33788,220329907,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33789,220329907,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33790,220329907,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33791,220329907,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33792,220329907,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33793,220329907,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33794,220329907,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33795,220329907,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33796,220329907,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33797,220329907,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33798,220329907,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33799,220329907,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33800,220329907,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33801,220329907,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33802,220329907,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33803,220329907,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33804,220329907,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33805,220329907,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33806,220329907,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33807,220329907,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33808,220329907,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33809,220329907,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33810,220329907,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33811,220329907,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33812,220329907,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33813,220329907,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33814,220329907,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33815,220329907,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33816,220329907,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33817,220329907,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33818,220329907,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33819,220329907,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33820,220329907,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33821,220329907,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33822,220329907,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33823,220329907,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33824,220395934,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33825,220395934,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33826,220395934,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33827,220395934,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33828,220395934,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33829,220395934,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33830,220395934,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33831,220395934,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33832,220395934,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33833,220395934,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33834,220395934,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33835,220395934,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33836,220395934,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33837,220395934,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33838,220395934,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33839,220395934,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33840,220395934,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33841,220395934,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33842,220395934,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33843,220395934,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33844,220395934,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33845,220395934,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33846,220395934,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33847,220395934,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33848,220395934,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33849,220395934,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33850,220395934,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33851,220395934,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33852,220395934,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33853,220395934,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33854,220395934,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33855,220395934,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33856,220395934,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33857,220395934,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33858,220395934,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33859,220395934,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33860,220395934,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33861,220395934,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33862,220395934,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33863,220395934,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33864,220395934,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33865,220395934,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33866,220395934,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33867,220395934,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33868,220395934,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33869,220395934,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33870,220395934,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33871,220395934,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33872,220395934,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33873,220395934,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33874,220395934,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33875,220395934,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33876,220395934,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33877,220337626,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33878,220337626,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33879,220337626,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33880,220337626,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33881,220337626,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33882,220337626,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33883,220337626,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33884,220337626,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33885,220337626,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33886,220337626,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33887,220337626,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33888,220337626,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33889,220337626,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33890,220337626,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33891,220337626,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33892,220337626,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33893,220337626,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33894,220337626,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33895,220337626,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33896,220337626,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33897,220337626,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33898,220337626,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33899,220337626,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33900,220337626,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33901,220337626,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33902,220337626,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33903,220337626,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33904,220337626,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33905,220337626,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33906,220337626,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33907,220337626,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33908,220337626,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33909,220337626,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33910,220337626,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33911,220337626,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33912,220337626,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33913,220337626,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33914,220337626,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33915,220337626,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33916,220337626,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33917,220337626,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33918,220337626,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33919,220337626,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33920,220337626,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33921,220337626,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33922,220337626,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33923,220337626,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33924,220337626,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33925,220337626,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33926,220337626,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33927,220337626,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33928,220337626,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33929,220337626,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33930,210875691,1056,NULL,NULL,0.1850,0.1850,0.0000,0.0000),(33931,210789847,1056,NULL,NULL,0.1830,0.1830,0.0000,0.0000),(33932,220319930,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33933,220319930,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33934,220319930,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33935,220319930,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33936,220319930,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33937,220319930,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33938,220319930,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33939,220319930,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33940,220319930,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33941,220319930,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33942,220319930,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33943,220319930,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33944,220319930,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33945,220319930,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33946,220319930,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33947,220319930,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33948,220319930,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33949,220319930,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33950,220319930,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33951,220319930,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33952,220319930,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33953,220319930,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33954,220319930,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33955,220319930,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33956,220319930,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33957,220319930,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33958,220319930,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33959,220319930,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33960,220319930,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33961,220319930,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33962,220319930,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33963,220319930,1018,NULL,NULL,0.1400,0.1400,0.0000,0.0000),(33964,220319930,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33965,220319930,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33966,220319930,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33967,220319930,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33968,220319930,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33969,220319930,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33970,220319930,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33971,220319930,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33972,220319930,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33973,220319930,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33974,220319930,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33975,220319930,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33976,220319930,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33977,220319930,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33978,220319930,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33979,220319930,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33980,220319930,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33981,220319930,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33982,220319930,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33983,220319930,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33984,220319930,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33985,220394634,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33986,220394634,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33987,220394634,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33988,220394634,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33989,220394634,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33990,220394634,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33991,220394634,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33992,220394634,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33993,220394634,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33994,220394634,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33995,220394634,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33996,220394634,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33997,220394634,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33998,220394634,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(33999,220394634,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34000,220394634,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34001,220394634,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34002,220394634,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34003,220394634,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34004,220394634,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34005,220394634,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34006,220394634,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34007,220394634,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34008,220394634,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34009,220394634,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34010,220394634,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34011,220394634,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34012,220394634,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34013,220394634,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34014,220394634,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34015,220394634,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34016,220394634,1018,NULL,NULL,0.1200,0.1200,0.0000,0.0000),(34017,220394634,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34018,220394634,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34019,220394634,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34020,220394634,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34021,220394634,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34022,220394634,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34023,220394634,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34024,220394634,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34025,220394634,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34026,220394634,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34027,220394634,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34028,220394634,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34029,220394634,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34030,220394634,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34031,220394634,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34032,220394634,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34033,220394634,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34034,220394634,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34035,220394634,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34036,220394634,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34037,220394634,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34038,220363936,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34039,220363936,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34040,220363936,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34041,220363936,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34042,220363936,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34043,220363936,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34044,220363936,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34045,220363936,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34046,220363936,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34047,220363936,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34048,220363936,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34049,220363936,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34050,220363936,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34051,220363936,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34052,220363936,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34053,220363936,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34054,220363936,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34055,220363936,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34056,220363936,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34057,220363936,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34058,220363936,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34059,220363936,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34060,220363936,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34061,220363936,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34062,220363936,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34063,220363936,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34064,220363936,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34065,220363936,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34066,220363936,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34067,220363936,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34068,220363936,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34069,220363936,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34070,220363936,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34071,220363936,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34072,220363936,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34073,220363936,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34074,220363936,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34075,220363936,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34076,220363936,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34077,220363936,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34078,220363936,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34079,220363936,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34080,220363936,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34081,220363936,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34082,220363936,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34083,220363936,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34084,220363936,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34085,220363936,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34086,220363936,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34087,220363936,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34088,220363936,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34089,220363936,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34090,220363936,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34091,220363936,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34092,220399083,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34093,220399083,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34094,220399083,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34095,220399083,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34096,220399083,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34097,220399083,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34098,220399083,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34099,220399083,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34100,220399083,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34101,220399083,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34102,220399083,1032,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(34103,220399083,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34104,220399083,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34105,220399083,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34106,220399083,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34107,220399083,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34108,220399083,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34109,220399083,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34110,220399083,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34111,220399083,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34112,220399083,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34113,220399083,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34114,220399083,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34115,220399083,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34116,220399083,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34117,220399083,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34118,220399083,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34119,220399083,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34120,220399083,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34121,220399083,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34122,220399083,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34123,220399083,1018,NULL,NULL,0.0500,0.0500,0.0000,0.0000),(34124,220399083,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34125,220399083,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34126,220399083,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34127,220399083,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34128,220399083,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34129,220399083,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34130,220399083,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34131,220399083,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34132,220399083,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34133,220399083,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34134,220399083,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34135,220399083,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34136,220399083,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34137,220399083,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34138,220399083,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34139,220399083,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34140,220399083,1050,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(34141,220399083,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34142,220399083,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34143,220399083,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34144,220399083,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34145,220399083,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34146,220324362,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34147,220324362,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34148,220324362,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34149,220324362,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34150,220324362,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34151,220324362,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34152,220324362,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34153,220324362,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34154,220324362,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34155,220324362,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34156,220324362,1032,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(34157,220324362,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34158,220324362,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34159,220324362,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34160,220324362,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34161,220324362,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34162,220324362,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34163,220324362,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34164,220324362,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34165,220324362,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34166,220324362,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34167,220324362,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34168,220324362,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34169,220324362,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34170,220324362,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34171,220324362,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34172,220324362,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34173,220324362,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34174,220324362,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34175,220324362,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34176,220324362,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34177,220324362,1018,NULL,NULL,0.0500,0.0500,0.0000,0.0000),(34178,220324362,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34179,220324362,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34180,220324362,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34181,220324362,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34182,220324362,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34183,220324362,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34184,220324362,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34185,220324362,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34186,220324362,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34187,220324362,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34188,220324362,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34189,220324362,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34190,220324362,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34191,220324362,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34192,220324362,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34193,220324362,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34194,220324362,1050,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(34195,220324362,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34196,220324362,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34197,220324362,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34198,220324362,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34199,220324362,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34200,220377230,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34201,220377230,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34202,220377230,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34203,220377230,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34204,220377230,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34205,220377230,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34206,220377230,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34207,220377230,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34208,220377230,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34209,220377230,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34210,220377230,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34211,220377230,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34212,220377230,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34213,220377230,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34214,220377230,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34215,220377230,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34216,220377230,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34217,220377230,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34218,220377230,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34219,220377230,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34220,220377230,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34221,220377230,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34222,220377230,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34223,220377230,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34224,220377230,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34225,220377230,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34226,220377230,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34227,220377230,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34228,220377230,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34229,220377230,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34230,220377230,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34231,220377230,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34232,220377230,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34233,220377230,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34234,220377230,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34235,220377230,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34236,220377230,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34237,220377230,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34238,220377230,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34239,220377230,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34240,220377230,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34241,220377230,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34242,220377230,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34243,220377230,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34244,220377230,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34245,220377230,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34246,220377230,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34247,220377230,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34248,220377230,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34249,220377230,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34250,220377230,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34251,220377230,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34252,220377230,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34253,220377230,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34254,220377230,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34255,220331098,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34256,220331098,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34257,220331098,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34258,220331098,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34259,220331098,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34260,220331098,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34261,220331098,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34262,220331098,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34263,220331098,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34264,220331098,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34265,220331098,1032,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(34266,220331098,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34267,220331098,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34268,220331098,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34269,220331098,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34270,220331098,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34271,220331098,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34272,220331098,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34273,220331098,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34274,220331098,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34275,220331098,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34276,220331098,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34277,220331098,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34278,220331098,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34279,220331098,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34280,220331098,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34281,220331098,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34282,220331098,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34283,220331098,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34284,220331098,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34285,220331098,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34286,220331098,1018,NULL,NULL,0.0500,0.0500,0.0000,0.0000),(34287,220331098,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34288,220331098,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34289,220331098,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34290,220331098,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34291,220331098,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34292,220331098,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34293,220331098,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34294,220331098,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34295,220331098,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34296,220331098,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34297,220331098,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34298,220331098,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34299,220331098,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34300,220331098,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34301,220331098,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34302,220331098,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34303,220331098,1050,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(34304,220331098,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34305,220331098,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34306,220331098,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34307,220331098,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34308,220331098,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34309,220331098,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34310,220399198,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34311,220399198,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34312,220399198,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34313,220399198,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34314,220399198,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34315,220399198,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34316,220399198,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34317,220399198,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34318,220399198,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34319,220399198,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34320,220399198,1032,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(34321,220399198,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34322,220399198,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34323,220399198,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34324,220399198,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34325,220399198,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34326,220399198,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34327,220399198,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34328,220399198,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34329,220399198,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34330,220399198,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34331,220399198,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34332,220399198,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34333,220399198,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34334,220399198,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34335,220399198,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34336,220399198,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34337,220399198,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34338,220399198,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34339,220399198,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34340,220399198,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34341,220399198,1018,NULL,NULL,0.0500,0.0500,0.0000,0.0000),(34342,220399198,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34343,220399198,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34344,220399198,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34345,220399198,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34346,220399198,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34347,220399198,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34348,220399198,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34349,220399198,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34350,220399198,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34351,220399198,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34352,220399198,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34353,220399198,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34354,220399198,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34355,220399198,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34356,220399198,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34357,220399198,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34358,220399198,1050,NULL,NULL,0.1780,0.1780,0.0000,0.0000),(34359,220399198,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34360,220399198,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34361,220399198,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34362,220399198,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34363,220399198,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34364,220399198,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34365,210887179,1050,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(34366,210887179,1058,NULL,NULL,0.1200,0.1200,0.0000,0.0000),(34367,210118109,1050,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(34368,210118109,1058,NULL,NULL,0.1200,0.1200,0.0000,0.0000),(34369,210188420,1050,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(34370,210188420,1058,NULL,NULL,0.1200,0.1200,0.0000,0.0000),(34371,210189048,1058,NULL,NULL,0.1200,0.1200,0.0000,0.0000),(34372,210875691,1058,NULL,NULL,0.1200,0.1200,0.0000,0.0000),(34373,210789847,1058,NULL,NULL,0.1180,0.1180,0.0000,0.0000),(34374,210572137,1050,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(34375,210572137,1058,NULL,NULL,0.1200,0.1200,0.0000,0.0000),(34376,210536969,1058,NULL,NULL,0.1180,0.1180,0.0000,0.0000),(34377,220356993,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34378,220356993,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34379,220356993,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34380,220356993,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34381,220356993,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34382,220356993,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34383,220356993,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34384,220356993,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34385,220356993,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34386,220356993,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34387,220356993,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34388,220356993,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34389,220356993,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34390,220356993,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34391,220356993,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34392,220356993,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34393,220356993,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34394,220356993,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34395,220356993,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34396,220356993,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34397,220356993,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34398,220356993,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34399,220356993,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34400,220356993,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34401,220356993,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34402,220356993,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34403,220356993,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34404,220356993,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34405,220356993,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34406,220356993,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34407,220356993,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34408,220356993,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34409,220356993,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34410,220356993,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34411,220356993,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34412,220356993,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34413,220356993,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34414,220356993,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34415,220356993,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34416,220356993,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34417,220356993,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34418,220356993,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34419,220356993,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34420,220356993,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34421,220356993,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34422,220356993,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34423,220356993,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34424,220356993,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34425,220356993,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34426,220356993,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34427,220356993,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34428,220356993,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34429,220356993,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34430,220356993,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34431,220356993,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34432,220359830,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34433,220359830,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34434,220359830,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34435,220359830,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34436,220359830,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34437,220359830,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34438,220359830,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34439,220359830,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34440,220359830,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34441,220359830,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34442,220359830,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34443,220359830,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34444,220359830,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34445,220359830,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34446,220359830,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34447,220359830,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34448,220359830,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34449,220359830,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34450,220359830,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34451,220359830,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34452,220359830,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34453,220359830,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34454,220359830,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34455,220359830,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34456,220359830,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34457,220359830,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34458,220359830,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34459,220359830,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34460,220359830,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34461,220359830,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34462,220359830,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34463,220359830,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34464,220359830,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34465,220359830,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34466,220359830,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34467,220359830,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34468,220359830,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34469,220359830,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34470,220359830,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34471,220359830,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34472,220359830,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34473,220359830,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34474,220359830,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34475,220359830,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34476,220359830,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34477,220359830,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34478,220359830,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34479,220359830,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34480,220359830,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34481,220359830,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34482,220359830,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34483,220359830,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34484,220359830,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34485,220359830,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34486,220359830,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34487,220389649,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34488,220389649,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34489,220389649,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34490,220389649,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34491,220389649,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34492,220389649,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34493,220389649,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34494,220389649,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34495,220389649,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34496,220389649,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34497,220389649,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34498,220389649,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34499,220389649,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34500,220389649,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34501,220389649,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34502,220389649,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34503,220389649,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34504,220389649,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34505,220389649,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34506,220389649,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34507,220389649,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34508,220389649,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34509,220389649,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34510,220389649,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34511,220389649,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34512,220389649,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34513,220389649,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34514,220389649,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34515,220389649,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34516,220389649,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34517,220389649,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34518,220389649,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34519,220389649,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34520,220389649,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34521,220389649,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34522,220389649,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34523,220389649,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34524,220389649,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34525,220389649,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34526,220389649,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34527,220389649,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34528,220389649,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34529,220389649,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34530,220389649,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34531,220389649,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34532,220389649,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34533,220389649,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34534,220389649,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34535,220389649,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34536,220389649,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34537,220389649,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34538,220389649,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34539,220389649,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34540,220389649,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34541,220389649,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34542,220335541,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34543,220335541,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34544,220335541,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34545,220335541,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34546,220335541,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34547,220335541,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34548,220335541,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34549,220335541,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34550,220335541,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34551,220335541,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34552,220335541,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34553,220335541,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34554,220335541,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34555,220335541,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34556,220335541,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34557,220335541,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34558,220335541,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34559,220335541,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34560,220335541,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34561,220335541,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34562,220335541,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34563,220335541,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34564,220335541,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34565,220335541,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34566,220335541,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34567,220335541,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34568,220335541,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34569,220335541,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34570,220335541,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34571,220335541,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34572,220335541,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34573,220335541,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34574,220335541,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34575,220335541,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34576,220335541,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34577,220335541,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34578,220335541,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34579,220335541,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34580,220335541,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34581,220335541,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34582,220335541,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34583,220335541,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34584,220335541,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34585,220335541,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34586,220335541,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34587,220335541,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34588,220335541,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34589,220335541,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34590,220335541,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34591,220335541,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34592,220335541,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34593,220335541,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34594,220335541,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34595,220335541,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34596,220335541,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34597,211021128,1042,NULL,NULL,0.1850,0.1850,0.0000,0.0000),(34598,211021128,1032,NULL,NULL,0.1850,0.1850,0.0000,0.0000),(34599,211021128,1050,NULL,NULL,0.1850,0.1850,0.0000,0.0000),(34600,211058931,1042,NULL,NULL,0.1810,0.1810,0.0000,0.0000),(34601,211058931,1050,NULL,NULL,0.1840,0.1840,0.0000,0.0000),(34602,211015312,1042,NULL,NULL,0.1800,0.1800,0.0000,0.0000),(34603,211015312,1050,NULL,NULL,0.1830,0.1830,0.0000,0.0000),(34604,211099333,1042,NULL,NULL,0.1820,0.1820,0.0000,0.0000),(34605,211099333,1050,NULL,NULL,0.0000,0.0000,0.0000,0.0000),(34606,220344546,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34607,220344546,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34608,220344546,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34609,220344546,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34610,220344546,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34611,220344546,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34612,220344546,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34613,220344546,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34614,220344546,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34615,220344546,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34616,220344546,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34617,220344546,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34618,220344546,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34619,220344546,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34620,220344546,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34621,220344546,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34622,220344546,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34623,220344546,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34624,220344546,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34625,220344546,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34626,220344546,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34627,220344546,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34628,220344546,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34629,220344546,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34630,220344546,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34631,220344546,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34632,220344546,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34633,220344546,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34634,220344546,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34635,220344546,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34636,220344546,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34637,220344546,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34638,220344546,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34639,220344546,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34640,220344546,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34641,220344546,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34642,220344546,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34643,220344546,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34644,220344546,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34645,220344546,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34646,220344546,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34647,220344546,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34648,220344546,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34649,220344546,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34650,220344546,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34651,220344546,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34652,220344546,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34653,220344546,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34654,220344546,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34655,220344546,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34656,220344546,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34657,220344546,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34658,220344546,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34659,220344546,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34660,220344546,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34661,220344546,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34662,200712944,1050,NULL,NULL,0.1750,0.1750,0.0000,0.0000),(34663,220313809,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34664,220313809,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34665,220313809,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34666,220313809,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34667,220313809,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34668,220313809,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34669,220313809,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34670,220313809,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34671,220313809,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34672,220313809,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34673,220313809,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34674,220313809,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34675,220313809,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34676,220313809,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34677,220313809,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34678,220313809,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34679,220313809,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34680,220313809,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34681,220313809,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34682,220313809,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34683,220313809,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34684,220313809,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34685,220313809,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34686,220313809,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34687,220313809,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34688,220313809,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34689,220313809,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34690,220313809,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34691,220313809,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34692,220313809,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34693,220313809,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34694,220313809,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34695,220313809,1018,NULL,NULL,0.0500,0.0500,0.0000,0.0000),(34696,220313809,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34697,220313809,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34698,220313809,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34699,220313809,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34700,220313809,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34701,220313809,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34702,220313809,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34703,220313809,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34704,220313809,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34705,220313809,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34706,220313809,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34707,220313809,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34708,220313809,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34709,220313809,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34710,220313809,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34711,220313809,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34712,220313809,1050,NULL,NULL,0.1750,0.1750,0.0000,0.0000),(34713,220313809,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34714,220313809,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34715,220313809,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34716,220313809,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34717,220313809,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34718,220313809,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34719,220324307,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34720,220324307,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34721,220324307,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34722,220324307,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34723,220324307,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34724,220324307,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34725,220324307,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34726,220324307,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34727,220324307,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34728,220324307,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34729,220324307,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34730,220324307,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34731,220324307,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34732,220324307,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34733,220324307,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34734,220324307,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34735,220324307,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34736,220324307,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34737,220324307,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34738,220324307,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34739,220324307,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34740,220324307,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34741,220324307,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34742,220324307,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34743,220324307,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34744,220324307,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34745,220324307,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34746,220324307,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34747,220324307,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34748,220324307,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34749,220324307,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34750,220324307,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34751,220324307,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34752,220324307,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34753,220324307,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34754,220324307,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34755,220324307,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34756,220324307,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34757,220324307,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34758,220324307,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34759,220324307,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34760,220324307,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34761,220324307,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34762,220324307,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34763,220324307,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34764,220324307,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34765,220324307,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34766,220324307,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34767,220324307,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34768,220324307,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34769,220324307,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34770,220324307,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34771,220324307,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34772,220324307,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34773,220324307,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34774,220324307,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34775,220387020,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34776,220387020,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34777,220387020,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34778,220387020,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34779,220387020,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34780,220387020,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34781,220387020,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34782,220387020,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34783,220387020,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34784,220387020,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34785,220387020,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34786,220387020,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34787,220387020,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34788,220387020,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34789,220387020,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34790,220387020,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34791,220387020,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34792,220387020,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34793,220387020,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34794,220387020,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34795,220387020,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34796,220387020,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34797,220387020,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34798,220387020,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34799,220387020,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34800,220387020,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34801,220387020,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34802,220387020,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34803,220387020,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34804,220387020,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34805,220387020,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34806,220387020,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34807,220387020,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34808,220387020,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34809,220387020,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34810,220387020,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34811,220387020,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34812,220387020,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34813,220387020,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34814,220387020,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34815,220387020,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34816,220387020,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34817,220387020,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34818,220387020,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34819,220387020,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34820,220387020,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34821,220387020,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34822,220387020,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34823,220387020,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34824,220387020,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34825,220387020,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34826,220387020,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34827,220387020,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34828,220387020,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34829,220387020,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34830,220387020,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34831,220350057,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34832,220350057,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34833,220350057,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34834,220350057,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34835,220350057,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34836,220350057,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34837,220350057,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34838,220350057,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34839,220350057,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34840,220350057,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34841,220350057,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34842,220350057,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34843,220350057,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34844,220350057,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34845,220350057,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34846,220350057,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34847,220350057,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34848,220350057,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34849,220350057,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34850,220350057,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34851,220350057,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34852,220350057,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34853,220350057,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34854,220350057,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34855,220350057,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34856,220350057,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34857,220350057,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34858,220350057,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34859,220350057,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34860,220350057,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34861,220350057,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34862,220350057,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34863,220350057,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34864,220350057,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34865,220350057,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34866,220350057,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34867,220350057,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34868,220350057,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34869,220350057,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34870,220350057,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34871,220350057,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34872,220350057,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34873,220350057,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34874,220350057,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34875,220350057,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34876,220350057,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34877,220350057,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34878,220350057,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34879,220350057,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34880,220350057,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34881,220350057,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34882,220350057,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34883,220350057,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34884,220350057,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34885,220350057,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34886,220350057,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34887,220382311,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34888,220382311,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34889,220382311,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34890,220382311,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34891,220382311,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34892,220382311,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34893,220382311,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34894,220382311,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34895,220382311,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34896,220382311,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34897,220382311,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34898,220382311,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34899,220382311,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34900,220382311,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34901,220382311,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34902,220382311,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34903,220382311,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34904,220382311,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34905,220382311,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34906,220382311,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34907,220382311,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34908,220382311,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34909,220382311,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34910,220382311,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34911,220382311,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34912,220382311,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34913,220382311,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34914,220382311,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34915,220382311,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34916,220382311,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34917,220382311,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34918,220382311,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34919,220382311,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34920,220382311,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34921,220382311,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34922,220382311,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34923,220382311,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34924,220382311,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34925,220382311,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34926,220382311,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34927,220382311,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34928,220382311,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34929,220382311,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34930,220382311,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34931,220382311,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34932,220382311,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34933,220382311,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34934,220382311,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34935,220382311,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34936,220382311,1050,NULL,NULL,0.1750,0.1750,0.0000,0.0000),(34937,220382311,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34938,220382311,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34939,220382311,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34940,220382311,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34941,220382311,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34942,220382311,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34943,220356178,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34944,220356178,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34945,220356178,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34946,220356178,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34947,220356178,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34948,220356178,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34949,220356178,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34950,220356178,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34951,220356178,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34952,220356178,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34953,220356178,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34954,220356178,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34955,220356178,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34956,220356178,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34957,220356178,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34958,220356178,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34959,220356178,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34960,220356178,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34961,220356178,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34962,220356178,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34963,220356178,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34964,220356178,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34965,220356178,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34966,220356178,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34967,220356178,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34968,220356178,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34969,220356178,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34970,220356178,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34971,220356178,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34972,220356178,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34973,220356178,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34974,220356178,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34975,220356178,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34976,220356178,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34977,220356178,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34978,220356178,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34979,220356178,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34980,220356178,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34981,220356178,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34982,220356178,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34983,220356178,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34984,220356178,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34985,220356178,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34986,220356178,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34987,220356178,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34988,220356178,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34989,220356178,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34990,220356178,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34991,220356178,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34992,220356178,1050,NULL,NULL,0.1760,0.1760,0.0000,0.0000),(34993,220356178,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34994,220356178,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34995,220356178,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34996,220356178,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34997,220356178,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34998,220356178,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(34999,220373464,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35000,220373464,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35001,220373464,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35002,220373464,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35003,220373464,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35004,220373464,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35005,220373464,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35006,220373464,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35007,220373464,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35008,220373464,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35009,220373464,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35010,220373464,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35011,220373464,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35012,220373464,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35013,220373464,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35014,220373464,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35015,220373464,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35016,220373464,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35017,220373464,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35018,220373464,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35019,220373464,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35020,220373464,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35021,220373464,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35022,220373464,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35023,220373464,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35024,220373464,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35025,220373464,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35026,220373464,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35027,220373464,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35028,220373464,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35029,220373464,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35030,220373464,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35031,220373464,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35032,220373464,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35033,220373464,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35034,220373464,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35035,220373464,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35036,220373464,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35037,220373464,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35038,220373464,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35039,220373464,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35040,220373464,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35041,220373464,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35042,220373464,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35043,220373464,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35044,220373464,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35045,220373464,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35046,220373464,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35047,220373464,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35048,220373464,1050,NULL,NULL,0.1750,0.1750,0.0000,0.0000),(35049,220373464,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35050,220373464,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35051,220373464,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35052,220373464,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35053,220373464,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35054,220373464,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35055,220323232,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35056,220323232,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35057,220323232,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35058,220323232,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35059,220323232,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35060,220323232,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35061,220323232,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35062,220323232,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35063,220323232,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35064,220323232,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35065,220323232,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35066,220323232,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35067,220323232,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35068,220323232,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35069,220323232,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35070,220323232,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35071,220323232,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35072,220323232,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35073,220323232,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35074,220323232,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35075,220323232,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35076,220323232,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35077,220323232,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35078,220323232,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35079,220323232,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35080,220323232,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35081,220323232,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35082,220323232,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35083,220323232,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35084,220323232,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35085,220323232,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35086,220323232,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35087,220323232,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35088,220323232,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35089,220323232,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35090,220323232,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35091,220323232,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35092,220323232,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35093,220323232,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35094,220323232,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35095,220323232,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35096,220323232,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35097,220323232,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35098,220323232,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35099,220323232,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35100,220323232,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35101,220323232,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35102,220323232,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35103,220323232,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35104,220323232,1050,NULL,NULL,0.1750,0.1750,0.0000,0.0000),(35105,220323232,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35106,220323232,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35107,220323232,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35108,220323232,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35109,220323232,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35110,220323232,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35111,220347101,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35112,220347101,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35113,220347101,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35114,220347101,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35115,220347101,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35116,220347101,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35117,220347101,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35118,220347101,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35119,220347101,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35120,220347101,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35121,220347101,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35122,220347101,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35123,220347101,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35124,220347101,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35125,220347101,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35126,220347101,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35127,220347101,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35128,220347101,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35129,220347101,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35130,220347101,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35131,220347101,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35132,220347101,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35133,220347101,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35134,220347101,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35135,220347101,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35136,220347101,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35137,220347101,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35138,220347101,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35139,220347101,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35140,220347101,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35141,220347101,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35142,220347101,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35143,220347101,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35144,220347101,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35145,220347101,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35146,220347101,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35147,220347101,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35148,220347101,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35149,220347101,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35150,220347101,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35151,220347101,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35152,220347101,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35153,220347101,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35154,220347101,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35155,220347101,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35156,220347101,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35157,220347101,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35158,220347101,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35159,220347101,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35160,220347101,1050,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35161,220347101,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35162,220347101,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35163,220347101,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35164,220347101,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35165,220347101,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35166,220347101,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35167,220363446,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35168,220363446,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35169,220363446,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35170,220363446,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35171,220363446,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35172,220363446,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35173,220363446,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35174,220363446,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35175,220363446,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35176,220363446,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35177,220363446,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35178,220363446,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35179,220363446,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35180,220363446,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35181,220363446,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35182,220363446,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35183,220363446,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35184,220363446,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35185,220363446,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35186,220363446,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35187,220363446,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35188,220363446,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35189,220363446,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35190,220363446,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35191,220363446,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35192,220363446,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35193,220363446,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35194,220363446,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35195,220363446,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35196,220363446,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35197,220363446,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35198,220363446,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35199,220363446,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35200,220363446,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35201,220363446,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35202,220363446,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35203,220363446,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35204,220363446,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35205,220363446,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35206,220363446,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35207,220363446,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35208,220363446,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35209,220363446,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35210,220363446,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35211,220363446,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35212,220363446,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35213,220363446,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35214,220363446,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35215,220363446,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35216,220363446,1050,NULL,NULL,0.1750,0.1750,0.0000,0.0000),(35217,220363446,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35218,220363446,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35219,220363446,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35220,220363446,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35221,220363446,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35222,220363446,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35223,220338036,1042,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35224,220338036,1021,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35225,220338036,904,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35226,220338036,924,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35227,220338036,996,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35228,220338036,1044,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35229,220338036,1031,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35230,220338036,1034,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35231,220338036,1033,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35232,220338036,990,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35233,220338036,1032,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35234,220338036,992,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35235,220338036,1043,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35236,220338036,974,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35237,220338036,1005,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35238,220338036,997,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35239,220338036,1016,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35240,220338036,1014,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35241,220338036,1000,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35242,220338036,1001,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35243,220338036,1002,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35244,220338036,1008,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35245,220338036,1006,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35246,220338036,1007,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35247,220338036,1009,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35248,220338036,1010,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35249,220338036,1059,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35250,220338036,1012,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35251,220338036,1041,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35252,220338036,1013,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35253,220338036,1015,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35254,220338036,1017,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35255,220338036,1018,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35256,220338036,1019,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35257,220338036,1020,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35258,220338036,1022,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35259,220338036,1023,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35260,220338036,1024,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35261,220338036,1025,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35262,220338036,1026,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35263,220338036,1029,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35264,220338036,1028,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35265,220338036,1030,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35266,220338036,1039,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35267,220338036,1045,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35268,220338036,1046,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35269,220338036,1047,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35270,220338036,1048,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35271,220338036,1049,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35272,220338036,1050,NULL,NULL,0.1830,0.1830,0.0000,0.0000),(35273,220338036,1051,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35274,220338036,1053,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35275,220338036,1057,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35276,220338036,1056,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35277,220338036,1055,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000),(35278,220338036,1058,0.0500,0.0500,0.0500,0.0500,0.0000,0.0000);
/*!40000 ALTER TABLE `pay_userrate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_version`
--

DROP TABLE IF EXISTS `pay_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL DEFAULT '0' COMMENT '版本',
  `author` varchar(255) NOT NULL DEFAULT ' ' COMMENT '作者',
  `save_time` varchar(255) NOT NULL DEFAULT '0000-00-00' COMMENT '修改时间,格式YYYY-mm-dd',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='数据库版本表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_version`
--

LOCK TABLES `pay_version` WRITE;
/*!40000 ALTER TABLE `pay_version` DISABLE KEYS */;
INSERT INTO `pay_version` VALUES (1,'5.5','qq2827596170','2018-4-8'),(2,'5.6','qq2827596170','2018/9/02 17:45:33'),(3,'5.7.1','qq2827596170','2018-4-17');
/*!40000 ALTER TABLE `pay_version` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_websiteconfig`
--

DROP TABLE IF EXISTS `pay_websiteconfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_websiteconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `websitename` varchar(300) DEFAULT NULL COMMENT '网站名称',
  `domain` varchar(300) DEFAULT NULL COMMENT '网址',
  `email` varchar(100) DEFAULT NULL,
  `tel` varchar(30) DEFAULT NULL,
  `qq` varchar(30) DEFAULT NULL,
  `directory` varchar(100) DEFAULT NULL COMMENT '后台目录名称',
  `icp` varchar(100) DEFAULT NULL,
  `tongji` varchar(1000) DEFAULT NULL COMMENT '统计',
  `login` varchar(100) DEFAULT NULL COMMENT '登录地址',
  `payingservice` tinyint(1) unsigned DEFAULT '0' COMMENT '商户代付 1 开启 0 关闭',
  `authorized` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '商户认证 1 开启 0 关闭',
  `invitecode` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '邀请码注册',
  `company` varchar(200) DEFAULT NULL COMMENT '公司名称',
  `serverkey` varchar(50) DEFAULT NULL COMMENT '授权服务key',
  `withdraw` tinyint(1) DEFAULT '0' COMMENT '提现通知：0关闭，1开启',
  `login_warning_num` tinyint(3) unsigned NOT NULL DEFAULT '3' COMMENT '前台可以错误登录次数',
  `login_ip` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '登录IP',
  `is_repeat_order` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许重复订单:1是，0否',
  `google_auth` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启谷歌身份验证登录',
  `df_api` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启代付API',
  `logo` varchar(255) NOT NULL DEFAULT ' ' COMMENT '公司logo',
  `random_mchno` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启随机商户号',
  `register_need_activate` tinyint(1) NOT NULL DEFAULT '0' COMMENT '用户注册是否需激活',
  `admin_alone_login` tinyint(1) NOT NULL DEFAULT '0' COMMENT '管理员是否只允许同时一次登录',
  `max_auth_error_times` int(10) NOT NULL DEFAULT '5' COMMENT '验证错误最大次数',
  `auth_error_ban_time` int(10) NOT NULL DEFAULT '10' COMMENT '验证错误超限冻结时间（分钟）',
  `address` varchar(200) DEFAULT NULL COMMENT '公司地址',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_websiteconfig`
--

LOCK TABLES `pay_websiteconfig` WRITE;
/*!40000 ALTER TABLE `pay_websiteconfig` DISABLE KEYS */;
INSERT INTO `pay_websiteconfig` VALUES (1,'元宇宙','47.56.15.193:39218','123456@qq.com','10086','123456','RHwMmDmdyZWe5Gm','粤-2019','','Login',0,1,0,'多多团队','0d6de302cbc615de3b09463acea87662',0,3,'',0,1,0,'',1,0,0,15,1,'');
/*!40000 ALTER TABLE `pay_websiteconfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist`
--

DROP TABLE IF EXISTS `pay_wttklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) NOT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `sheng` varchar(300) NOT NULL,
  `shi` varchar(300) NOT NULL,
  `sqdatetime` datetime DEFAULT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `t` int(4) NOT NULL DEFAULT '1',
  `payapiid` int(11) NOT NULL DEFAULT '0',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1',
  `extends` text COMMENT '扩展数据',
  `out_trade_no` varchar(30) DEFAULT '' COMMENT '下游订单号',
  `df_api_id` int(11) DEFAULT '0' COMMENT '代付API申请ID',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist`
--

LOCK TABLES `pay_wttklist` WRITE;
/*!40000 ALTER TABLE `pay_wttklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_wttklist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-03 13:02:01