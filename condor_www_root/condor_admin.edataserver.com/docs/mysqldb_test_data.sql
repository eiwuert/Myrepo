-- MySQL dump 10.10
--
-- Host: localhost    Database: admin_framework
-- ------------------------------------------------------
-- Server version	4.1.13-standard-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `access_group`
--


/*!40000 ALTER TABLE `access_group` DISABLE KEYS */;
LOCK TABLES `access_group` WRITE;
INSERT INTO `access_group` VALUES ('2006-01-05 13:36:52','2006-01-05 13:36:52','active',6,1,32,'CCS');
UNLOCK TABLES;
/*!40000 ALTER TABLE `access_group` ENABLE KEYS */;

--
-- Dumping data for table `acl`
--


/*!40000 ALTER TABLE `acl` DISABLE KEYS */;
LOCK TABLES `acl` WRITE;
INSERT INTO `acl` VALUES ('2006-01-13 10:32:04','2006-01-13 10:32:04','active',6,32,2,NULL),('0000-00-00 00:00:00','0000-00-00 00:00:00','active',6,32,3,NULL);
UNLOCK TABLES;
/*!40000 ALTER TABLE `acl` ENABLE KEYS */;

--
-- Dumping data for table `agent`
--


/*!40000 ALTER TABLE `agent` DISABLE KEYS */;
LOCK TABLES `agent` WRITE;
INSERT INTO `agent` VALUES ('2005-06-16 18:17:29','2005-06-16 18:17:29','inactive',1,1,'agent','unknown',NULL,NULL,NULL,'**No login','**No login',NULL,NULL),('2005-07-11 14:28:43','2005-07-11 14:28:43','active',1,990,'TSS','The Selling Source',NULL,NULL,NULL,'tss','90ec6ebfa7189c8bd39c5d8c30c90b06',NULL,NULL);
UNLOCK TABLES;
/*!40000 ALTER TABLE `agent` ENABLE KEYS */;

--
-- Dumping data for table `agent_access_group`
--


/*!40000 ALTER TABLE `agent_access_group` DISABLE KEYS */;
LOCK TABLES `agent_access_group` WRITE;
INSERT INTO `agent_access_group` VALUES ('2005-08-23 13:15:56','0000-00-00 00:00:00','active',1,990,6),('2005-08-23 14:24:20','2005-08-23 14:24:20','active',1,990,24),('2005-10-13 10:44:05','2005-10-13 10:44:05','active',3,990,29),('2005-10-24 10:53:51','2005-10-24 10:53:51','active',4,990,30),('2005-10-27 08:41:58','2005-10-27 08:41:58','active',5,990,31),('2006-01-05 13:41:04','2006-01-05 13:41:04','active',6,990,32),('2006-01-10 13:02:47','2006-01-10 13:02:47','active',7,990,36);
UNLOCK TABLES;
/*!40000 ALTER TABLE `agent_access_group` ENABLE KEYS */;

--
-- Dumping data for table `company`
--


/*!40000 ALTER TABLE `company` DISABLE KEYS */;
LOCK TABLES `company` WRITE;
INSERT INTO `company` VALUES ('2006-01-05 13:35:08','0000-00-00 00:00:00','active',6,'Cubis','ccs',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `company` ENABLE KEYS */;

--
-- Dumping data for table `company_section_view`
--


/*!40000 ALTER TABLE `company_section_view` DISABLE KEYS */;
LOCK TABLES `company_section_view` WRITE;
INSERT INTO `company_section_view` VALUES ('2005-10-27 10:17:23','0000-00-00 00:00:00',1,4,94,1),('2005-10-27 10:17:23','0000-00-00 00:00:00',2,4,94,2),('2005-10-27 10:17:44','0000-00-00 00:00:00',3,4,94,3),('2005-10-27 10:17:44','0000-00-00 00:00:00',4,4,94,4),('2005-10-28 08:37:39','0000-00-00 00:00:00',5,3,94,5),('2006-01-17 12:59:26','2006-01-17 12:59:26',6,7,94,6);
UNLOCK TABLES;
/*!40000 ALTER TABLE `company_section_view` ENABLE KEYS */;

--
-- Dumping data for table `module`
--


/*!40000 ALTER TABLE `module` DISABLE KEYS */;
LOCK TABLES `module` WRITE;
INSERT INTO `module` VALUES ('2005-01-17 14:35:49','2005-01-17 14:35:49','active','admin','Admin','admin',NULL),('2005-01-17 14:35:34','2005-01-17 14:35:34','active','funding','Funding','transaction',NULL),('2005-04-11 14:38:32','2005-04-11 14:38:32','active','new_app','New App','new_app',NULL),('2005-01-17 14:36:03','2005-01-17 14:36:03','active','reporting','Reporting','reporting',NULL);
UNLOCK TABLES;
/*!40000 ALTER TABLE `module` ENABLE KEYS */;

--
-- Dumping data for table `section`
--


/*!40000 ALTER TABLE `section` DISABLE KEYS */;
LOCK TABLES `section` WRITE;
INSERT INTO `section` VALUES ('2005-06-16 18:15:51','2005-06-16 18:15:51','active',0,1,'*root','*Root',NULL,1,0,0),('2006-01-05 14:11:51','0000-00-00 00:00:00','active',1,3,'test_sub_module','Test Sub-section',2,6,3,0),('2006-01-06 09:29:08','0000-00-00 00:00:00','active',1,4,'test_sub_sub_module','Test Sub-Sub-section',3,1,4,0),('2006-01-06 09:29:08','0000-00-00 00:00:00','active',1,5,'test_sub_sub_module_2','Test Sub-Sub-section Part 2',3,2,4,0),('0000-00-00 00:00:00','0000-00-00 00:00:00','active',1,7,'ccs','ccs',1,5,1,0),('2006-01-23 15:29:40','0000-00-00 00:00:00','active',1,2,'test_module','Test Section',7,5,2,0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `section` ENABLE KEYS */;

--
-- Dumping data for table `section_views`
--


/*!40000 ALTER TABLE `section_views` DISABLE KEYS */;
LOCK TABLES `section_views` WRITE;
INSERT INTO `section_views` VALUES ('2005-10-27 10:21:16','0000-00-00 00:00:00',1,94,'employment_info'),('2005-10-27 10:21:16','0000-00-00 00:00:00',2,94,'bank_info'),('2005-10-27 10:21:16','0000-00-00 00:00:00',3,94,'card_info'),('2005-10-27 10:21:16','0000-00-00 00:00:00',4,94,'payday_info'),('2005-10-28 08:36:19','0000-00-00 00:00:00',5,94,'card_info'),('2006-01-17 13:10:02','2006-01-17 12:58:43',6,94,'olp_react_div_display');
UNLOCK TABLES;
/*!40000 ALTER TABLE `section_views` ENABLE KEYS */;

--
-- Dumping data for table `session_0`
--


/*!40000 ALTER TABLE `session_0` DISABLE KEYS */;
LOCK TABLES `session_0` WRITE;
INSERT INTO `session_0` VALUES ('031b8c4190817aee9473df2539f3a405','2006-02-13 09:59:06','2006-02-13 09:58:42','2006-02-13 09:59:05','gz','xœåZ_s£8ÿ*>A ÿÉcgïžnvgºïœÆ-`Î†ìf÷òÝO24€	´PrsûRÖ±ü“dI–d+æ¥’\'G²ø‡º¶û[¹öÄµñÌ#’ðYîÚöt½šÏ–öjszbòÀ$Q	MRLdéZžc\Z­rg®µóâ)~âZù/„ûzÄµ–øwåZô™EI><u­õz‚_ó=M”*ÊæG™R®ã¸Ö÷={xbAÀ£ç‡\'‘J=|zÒópÀUbm¨»pƒ,ðábâZDí…LÎ|‡^þYààJP•<<RµøëÑÚœ¸;5¯<³0.-²p­/aÌ%ÓÔ³Fbzeàû=† ÿ×8á\"Rzy#Ï4(-Ûô\'\rxÀ„&_4’{ž*‘ÃÎ<¦[ža/oè1Ûÿ·2¬ßêñ›d;&%ƒÍ?”¿©£JXx±[ÿF¨—ð#¡ðÓà¬\rêy\"kPŒJo£¸OIŽ1+[áôÍoDl_¬ÍW4@•ø»Hƒ‘!}&ÑbÀ¸OZnV*[“¡!ìl¹6FÒ(Eõ—¦8†#Tk\n\0së !Ò#æç\0•d§\\+è¢ -Ä¦B±c¾[aæ0ÅD¡?µ›ÀÈ$§eQ\ZžaO…Û¢Uu\0^ û„¼…¬]z]\rØÎÜ¤5âyÅ­ê7š\0´—›zÒ:—æ‚œÔÀ®5,n3m)³ŸA@|þÌuŽ²¨\ZÍÂÀmkØÏ˜Áùzb¦;×<ÀÈÌÄ‚sf| Ù÷Çƒ]áÁiÜïppúCŸVÐ§ô÷DŒn¢›ãÆé:{’A*P\ruëz—EÒ€ÁÙ‘>Ë\">î?µÆcÚ8{þ†›ŽÔžƒªþ¦¦¸Nü\"¶^%A?ÒW—=1£htøA~nðB¿å‘b¬æ†ñ\0>Ep8. SÑ©b/GÆª­×d–Ÿ3 £Vpìâ¼z\n­/‚€JRxÿûÇ>³ã°—ŸÅÒ¤^ôRõGS(³³„9G(+²dq£Üìsd€ò ¯Ú˜ã…¤„;¯à\ZR½†÷JB˜·…ªä¤\'ûbÛA|øôu\0-ç“öf\0(¿xþèw‰WTÍ*h‡!jÏ!v¼)þ¢Éýò•Œ\'9[\Z½ŽcðxhùG9—¹èEq#‡n¯‚e…‡•YûZþÆ7Èá‡à[ùGÊâg¹\n¨ïK¦iJcËz€ÌÉZ(bÞy2=liYìºb¦®¢ûÓ5\0CiÔxà~ÄÍ‰üÙ¼cOlµvA|Û„*™<p%º$ßýh=“±.ž×ùV¦q+ä~$Å2…\\l©¨hAÐì\\{\Z‹%Û‘x/\"Fö˜fv	1h£iQ¢µÈqšŽœ23]¼½_ffÅb:ú°Ÿýø‡”O÷9ÏŒ¦‡3¸cq±.ÖÚáÜ±5á± ;ïã‡Ü÷{êKTáQy£T\rÒÌmÂ>_aì¸ült¬Ä—nš8à±È~\Zñh\'*µY}cmÜŠb˜¤¶C{§ÿl²5xé6ã¼…ùÕDõÞÈ¹³Û`Y#@Ö–É*_}-ÍíÝÖM®J<»‹&×GŠÞ¦&×{;Îì­’H$ÿû‹IQÍF;´ FíŒZ_ûÞAñ9r6P\ZÜ¥¾‹4ø¿yÔ2R—ô\0#Æ~T‡[€t‘wÓÉ Èþ\r£g­4]t_x\nÃ¡8jí}Í‘ïÍ‘)÷‚¸¦DTóÇ6¤ÏcÖ\0C_A9óynÍÔJ_AÝ‰õß¾\Z$=Xd:ß¶Ï¬Üƒã÷Ù«îÐ«jÓ¬}‚òÑfí™ñÍÚ»hÅýoPù³>*}²c5ÏLç†0|¦<Éõ“àOù®7h½Wø³â…ñç·%>—,êô”èÆýe5c®o)`Þ3-Œ&||>Ký—T%µí\ZÃÃãßÏÞƒõäWCWJõ+ê[<ð.®òGÜÄQ{Î#¿¸^åö#u»>3¬¬g·f{ü#Ø$g>uM‰ßf»+>c‡LÁ.ØV‰ðš\ZGC½À¾ìú¶Ó>ööþý‡òª',0),('0a035f46baa2feea2e6f616aa9cbe110','2006-03-23 10:40:30','2006-03-23 10:40:30','0000-00-00 00:00:00','gz','xœUOÑnƒ0üä/ ¡¥Ã<îÒ÷(cµaŠÃ$´òïMh¶jO¹ÜÙç;¡a\r7ÛÞ*üT5Â´Œìmä™ gTêÔ¨¦ÓMÝï†Â7+ÑEJ—¼qA–ùËù\rzÁ&ýÉ(;ÁòçÁ ´ù}Cp#ùXè´ÒuuFçr»°QþŒžóÞåH‚Z#\\oTš&öce–5T]9æõëðÄ¡?º1¶	èß’ÙËÊm	ñìÓS*qÞ×Nô¾v²I¤ùÕF%å,e±',0),('0b274f40bca769ed5755d8dedf9bfa40','2006-01-30 12:56:45','2006-01-30 12:56:04','2006-01-30 12:56:45','gz','xœ…RÁnƒ0ý”/(¥Ð6«m§i•è=Ê¨‘Å¡êø÷J×\niÝ)Nœ÷üìg„²õ*t\"û–<æäñ‚3m+Õˆ °\\ñ8N6Yš&Ù&ïðgðƒ@ˆÍ€XsVZãdÓ±yB·‡h`šBÇÎ²ñ\\Þ3Za`¹ä¿P)\n–74 °¶>˜g§Ò”S8änL¯C´“XGï;–÷Š\'³:0nF’qöbœò0¢WO58ÙÙ¹Š÷²3Ð„”mpäIŸª¨¤žÑÐß¤V\ZìÏžÂ§?ÀSÎví§ºÖ^ÿ3G—Ì{Ø>ÎqïáÞ™Ö÷H.3YQw“‹T~»]L5ÇU™^þú~ýc_’K‡\Z¢´VM¶õ%D‡¢²T\Z;`î[\'QÊ2¨3c­¾\n%³<8j…¥U<+ø\"•?Yày',0),('0d488db7e9aa785ae8df84e6351d23ee','2006-02-07 07:44:11','2006-02-07 07:44:11','0000-00-00 00:00:00','gz','xœ\0\0\0\0',0),('0fc60b65f18d6899456061bdf514088b','2006-03-01 13:33:28','2006-03-01 13:29:53','2006-03-01 13:33:28','gz','xœÕSÉnÛ0ý_`-–cêh´=	 Ü	FšØ,DQ%)£BªïŒÛP7(‚õEôPo™7#Ek•ïDú[ò?9®8«Ì^ÕÂ+\r,S<“0JnÒMœõ9Ø#Xá¼ô@ˆA6œF7²îXæxŒÿ\nG\'¢š.„*‡\ng)=o8“{¨ýTFÈv»¢ÓzŸªÞˆÆ÷kIž\"ÎîäPUªÞ¹imÁ}žïGgáJ9Ï2ÉSþ„­à!š»$.áÆzÂ$œ=º˜Ž³1}•Î;éÁ÷ËzÅã×YÐ3èfA’röE7ÊÂ€N®zhdg–ð|\';ýß6^™Ú\r<ë«.ö²ZÐà”¾ÉJU`xz>MðŽ“ÙµjÔÞü%Ç&^ö°½ÌñÎÂ#X8ü¾wï\\ç<èó–„ÃÈÂ«#mÊ¶:¥aáuÂB3©…ò |×Àb	ã‹+a~°ì–öÏùrWIÜ¯s#\rî˜0¶Kƒ«ÝmSÙ=C%„¢=.KAü/Ó&T‘ƒEü`w¥Ñ?°1‰\Z#•…Ÿ­\ZR9g€sO)qYÜö©A=~&øæz®È_JÅ”ô©BQ‡ëÉ1š…©Z]?3¿l|;V¦ˆßÁuø6×gåwËëÊ£4”ÊÿONgËÿ>âúó–‹~\0®r&!',0),('003c173f2ef0a2a0f06cd1eea5b49560','2006-03-27 09:04:31','2006-03-27 09:04:31','2006-03-27 09:04:33','gz','xœÕRËnƒ0ü•È_!Åû=»åÀ*q8µMTÔòïÝå’&‡¨ŠZ•‹ÍØ3;;kycµoeü©Nð¥`¥ÙéZz]Kµà<\n£u²\\ó´ËÀÁJç•b,‰²,7ÕAÕ-Kñ/w´#©ñ@ê¢G‹i}Lí ö#Œ”$YÒn5QïNBÃýZ‘\'\'‚@°Í”¥®w‹Ì46‡Å&ËúûÁ\\¸ÔÎ³´oN‹7ÁÔ%iI·7Ö_ÚŽ†£ÑÎs³Õw‰`®uª¹Þ¯ÈU¹×G•)š²§r<õàüD|íAúö\0ß’\nÏŽ¤Ù¾²ô…Br¾x.†0»>`ÒØ,u…ùw½M‚Ý+\"…]’ôoËF„¨>,0™/L¥ðQ(¬1HYxk´…‹\0P`ê)&-‹#¬†YâÍÕ„¨÷³JH…º©NåÌW£c4%sS6U}e<¼m<1â¸æ÷¹ž+?,¯;+¥¡Ðþ?M8ž,ÿ|Ä¿ôß=.ú¾\0^5¹è',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_0` ENABLE KEYS */;

--
-- Dumping data for table `session_1`
--


/*!40000 ALTER TABLE `session_1` DISABLE KEYS */;
LOCK TABLES `session_1` WRITE;
INSERT INTO `session_1` VALUES ('106c9adf94a7194a0e4da2524dabfc45','2006-02-07 09:22:32','2006-02-07 09:22:09','2006-02-07 09:22:32','gz','xœíZÍr£8~•O`À¿ø˜šÝÓÖLUæ®’A¶•\0b%ÈŒgÖï¾-!ì‚·js1Hýu«Õêþ$AüŒÓô€æÿ`Ïö~ÏžxVÈv4F)ˆµ¦žm»+×Ú“åúøBø;áH¤8%²ÇDvYx–Ï¢Çk-¼©gmýÄ•r,ýÑ@µxÖBþ.=ïHœêf×³V«‰|šiéº5¢(ÿ>Æ”ðÇ³¾ïÉÓ	C\Zïž^XÆ}òôýåE}ïœ‡T¤Ö\Z{sï7èN¡¦‰=ãé	wäëÇBŽé,Ò§g,öO=[ë#õ\\ó(€™DIi¹g}‰Ê‰ê=mÄà+c€çoøþ_“”²X¨qf(v8,\rÓô\'iH˜ê>oìîû¢Ôfæ9ÛÐ\\öâŠóùÿ¨Ãê£¿q²%œ˜üãQxðNDJ¢³—Øê€`?¥ïE,ÈÂ“5°ï³¼ÁçQ¶Ê!hJPzHHÙÝïÛ¼Zë¯ÒE\Z<‡ì¬IN†—Î}TzËfQée«nÒ8ªœE8Î¤ùK\\ùýT¶`e)¨½G²¿”9“ß@/NþÎ¨2ÐÙÐµP@E,&=[Q¾`Šeþ©–	´Lt_gÑIì±X¶Ò«:žKø”¼&Y-éU­híÌLVC¾_1Ü²Þp£)‹öµ³EOÚ‰ÖÚœ%ë†\Z±+%\Z7¹‡¶ÔY…Ï0DÝÑT\\J—2¯:ÍÜ€À¶ò3A‚ûŸ;— ej‚àœ À\ZH÷ýa°+œÆù÷ßßþ¤»é®Aú-£›êæ¸q¬ÀåP·ª_²àÀ¿ém|EkOLOšàãoyúrÿÂ­õZ“ÇØ­za¹+Á£½	KsøPúûñõ¢~Õk\\£ñKýG\n_SmœšÖoÙ0ƒº[CÌL†XvØàþ–ïªâ¦«bûÓ-\0MYL›<à4æ;ÒSãØ?ô´sWƒ¶”o›äÃƒÈ¨ ¨`M;ç0VÏu ¤ËÊk/¹1ðÎò} Ÿp×>äÊ¼_Ä„šWãƒÅ(nP²‡ôíTF]BŒôÑ¼k‘ä•ƒÝ¤\n¦iË)ƒé²Úû3-SÑ‡üìgý× hÚþÏF³Ã	…œ±P¬Š±¶òÛ±-á“0\r…­	ŒˆAØÏ¦PoŒÊ+-ÒæAªØ&Ù\'îfKùÒ¡-$;¨øi0NF>û\0àjM=HR³ÿÊ6(¥iOž×µRÒáÿ\\C®`\ZÂ¡€¼R Ü•Â}¹\nÅ\Z÷âùËÑô@aS1V)Ô\'à´CÌQAÜž;ö	N\0‚ý$a¢1A¿\']3±\ZvÎ‡ì@ø8ÁLÀ4ÔÇmÜñÜ¥$wV‘k`ÕþŠà»gëÄ5`›êÃc ‰ð«aC]ú‹&°ƒ.±à¢W³	š„CÓRâž=ÈLJ¸ºKs¹üæg6½Ëêü@Š! ñ–U˜µé#kÃp;bzÿ¤Jká¥ÓŒÓê£‰ê¹‘ó`§REy,Î•¾õ:ŒÊ°ŽÊnŽÏ+>\0±72Ã5ÅÐ…b|Šá¿YU´LlKv€#×ß!ŸÈ:/C{…ð×‚3ý¸9¡˜¥Hþû‹pV]%ÓŠUšNOÏ˜¢h(DíÏR/Á êR ÝèÇh÷l°MÚ­Ùr§™ê”>×å^°	×\\Á±\résŸGRj®6g2·ï•÷žÉ‘¤q&óŒûÿ<óH<ó°¤Ž3›ÕÉ­!u–ŠÔy,à:ß2H™4Ïmð{¼†2~¤¯õa -©¹f:3îXCq=×Ö2——Æ9‰;1çW.°TÓzcï\"|NÕ]èO¿úyÓå»žsB™÷¸…Ódq ¯Ïâà5i-]c¸x<âýÙ1¢¼!qaÄá b¾¹ìzˆm#Nâ¨œóÈ×èÄ—ÚÙ»Ï#«¯[Ã$‘l¢Ág‰*ÿås.³Ý‘GŸ;`‡LÚ.`‹”ùMßP7°Ï³¾é4½Ý\0‡¿ŠRš',0),('1fc894b454b946e7145ff2b17c9f09b6','2006-03-10 13:28:35','2006-03-10 13:28:34','2006-03-10 13:28:35','gz','xœÕSÉnÛ0ýƒ_ ÅRjêh4=	 Ü	FšØ,DQ%)£BªïŒËPR7(‚õEôPo™7#Ek•ïDúSò?;œUæ já•–)†Û(ˆÒ I²>{+œ—ä†³ÂèFÖËñ_áèDTÓ…PåPá,¥ç\'Îäj?•²ÛtJ&ñ©êÝ™h|¿–äÉñ(âìá›ªJÕ‡MnZ[Àæ!Ï‡÷£E¸RÎ³Lò”?c+xˆæ.‰K¸£±ž0[Îž\n]LÇY‡˜n¥ó›½tÇÍ×=ËzÅãß³ gÐÍŠ$åì³n”…½½ê¡‘Y{Àó½ì4ö×xej7ð$W]dµ¢Á)}‘•ªÀðô*|šà\'³oÕ¨}ó‡›xÝÃî2Ç{O`-àðûÞq¼só —-	‡\'\Z‘…W\'Ú”muNÃÂ	ê„…fR%\nåAø®ÕÆWÂ<~cÙíŸóå¾’¸_K#\rî˜0¶Kƒ«ÝmSÙ½@m	E{\\–‚ø_§ÝREQ`ðƒÝ•FKüÀÆp$jŒT¾·jHeÉ\0	æžRâ²¸íSƒzüLðÍd®ÈJÅ”ô¹BQ‡ÉäM‰ÂT­®__7¾+SÄïà:|›ëEùÝòz£ò(\r¥òÿÓ„ÓÙòßø_ýqËE¿_c&',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_1` ENABLE KEYS */;

--
-- Dumping data for table `session_2`
--


/*!40000 ALTER TABLE `session_2` DISABLE KEYS */;
LOCK TABLES `session_2` WRITE;
INSERT INTO `session_2` VALUES ('22e053a2dbf4d497546f2b28031bb471','2006-02-10 12:44:03','2006-02-10 12:44:03','0000-00-00 00:00:00','gz','xœ\0\0\0\0',0),('23f1eb49fa273cfdf74dd53612d975b8','2006-02-17 10:32:41','2006-02-17 10:32:04','2006-02-17 10:32:34','gz','xœÍZÛv›:ý•,¾ÀÜì?æ\\žzÚµÒw-Y(±@Tiiê?#!l‡[ Å±ó,4Ú{†™Ñ0BRR¦J´ü…#7z•‘»ˆœ„?²)–RgÃ\"×\rÞÂóý`³¿§â…\n$VTK‘Uäžæ8+Œ‚Èy )Ñ—z-{±ØŒDŽ«ÿßF~¤™²Ã~ä¬×}Zt;ª¤¬ªægX“’‘çEÎ×½¹§IÂ²Ç›{^Bo¾Þß›ùÞ8aR9-£WÐ.¼ZM½’;.TƒwPÝ«WúKus‡åîæÓ³Ù³Èï_8Ó4o,²Œœ¿Óœ	j¤ƒA9.y“\\Áe\núÎã™4ë„ƒ,qÒXÓ¿8a	åF|9(NˆlˆÃ“¹+¶¬Â^½cÇÜoê°>µãA¨þ~/#¸\'K©hÚðˆ`¢ØE)‹Ä¬“	1zaô»ý-™¢H•9múŸrñí“³ù¬]Oªø.ÁàZGrp/ÄEL…ö•Eôº7\ZëaÙ’ªÜ^» &„à–[fÜ¥8+´á¾žèll€Ö/pªå5f¨ç€” ß\nfLs4ˆÖê©”g´´Ï)­B¥ž¨Gð 0²°²4+Òì¾XíO€—š¾\0%ßC6Á¼î„l/ì³\Z\"¤e¸ÛnÃ]L\nšXgë…^Œƒ¶Ú‘í@ìÚÀÂà¶òÐ‘:›Ä™$(fLÉ·èÚC–m§Yö0øg‚Ÿî²‘—o>xÿ-|=Ònö‚èQ¦vg3Œx}Ü‡’â¹œ |C!è\rÞå1x9:£-ÎžËx+zƒîè…9FoñLZL$ý(+ôJg°¶ª­ñ,éË]tÇQ#ÿ¡M¦íÎ»²ü«Xu(\05WÂËŠPs³^uëà0Ë@\r\nTP¢PLsåÂŸçõÎ êÍ-Á‡É-I9€k¦ÀÞó$ÁÕú÷­Žª{´XÇÈxÁi| Œ+¤þ¤‚ÿQâ{Ã(M¯€QpÊ¨„¿sq\nFææªÖ­¢\0ÞÏÞI-ãÝx|j©Òãß\"7Äø‘N¼hÙ¢¯TY[\"L%—1C¾ƒ}çâyŠ¼Z°.óšÕZÃzhÈE, j˜\'­MI$p!‹\\\'hÉ‡Ê•&ºÎïq,¨S0†½ð0fT·k!÷ò†j×óPX[„©)9jŠøC`ÀM·èL»j¯ÿûü\'Ë¡¼‰§àOÊÐáØˆè«½ B$Ï:šnOùæ‰!»]ÃFh©\\C•\0C—pòÛÊWRÔÍ¸&äYJó¡\\ê³Ä6[ÇØá(2Upå…a®Yƒ<£æm#×æ<cˆ¢·~I7à)‹ã™Šö®ß¨=v|R8kí¡»®2›`úZ ¡øº\réö@†U–üè]¦ZƒÒ)ÅÕø}°Ì„¡\"›éeµãÙöîauûýv4ÆÃùúÜgx³…é1•D0sˆòácœBŸ·	š‘)Ÿwúwí÷¢¾VÃá<æ#“¢®{üÚiŠ,Ö\r?Ru¶kf<*™ß….Ù÷rW\r#&Ç-ó-¯Ù|ó7~Û»D=r]×³õ\'â\\Òo­ÿæZ|\\Ÿ4³GS=Ka­3ØÂ’/rÓøÓ×æè##¾¤Í|Çgsfþ‰õ¨Ñ[*N†zaçÒ|oKû;¾¨êæÎó„¬«ã‡(Ë¥_38}—2±óV8aõg\'oæî_4Íeu2oë´âÇrÚ‹†nèÞBŽ€?w±Z…VÐÌ<|újÞ;ž°—òWõ1Âÿë³¨‡',0),('2b046a8c179901e04f37c5ccaaeed8db','2006-02-13 12:33:54','2006-02-13 12:33:54','0000-00-00 00:00:00','gz','xœ…RÁnƒ0ý”/(ÐÒbŽU·Ó´JôŽ2æÒH	Aq:	uüûJ×\niÝ)/vÞó³Âúì”ï«ì[B‚xBÛFµ•WE¡ ŽÓ|“¥›U^%º/tyé‘ëÀXƒ¨­édÛ‹‚ å[M¥)Q©Ï1\"ç„l°õS˜)y¾h5Õž¢ž~…®ï[,$	ˆÃ	£µVm•öìjŒe9¾Oî…µ\"/\n	\\¸É­É UÑÉ:8KÇÚÔ¼Õ	J/’|´•tŠÞ¶¢¤«°g4ÝL$±3r8²—O=t²·sŒ÷²7Üÿ{ç•miÔY=uÑH=“á-½J­4Ú‘ž=¥O| óf¶çu­½þgŽ]:ï!œãÞáC^þ0pŽzòhî¿$æÌ{vÐ¢',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_2` ENABLE KEYS */;

--
-- Dumping data for table `session_3`
--


/*!40000 ALTER TABLE `session_3` DISABLE KEYS */;
LOCK TABLES `session_3` WRITE;
INSERT INTO `session_3` VALUES ('33f2247fbd6ce42c7853f15ae1d6f7c5','2006-03-17 08:52:19','2006-03-17 08:45:35','2006-03-17 08:52:19','gz','xœÍZ[s›:þ+~Á†Äø1ÓsžÎ´3é»FFJ¬Giiêÿ~Vì˜[ ÇîL§tÑêÛ]í•%MJÁT…¢ß8öã7û‹ØKùË‘bõ6,öýUÁß0Üì¨x¥I…Õ†å6öž8¯¼ŒW±÷˜d‰~Ô{¹7ˆC‰=_ÿ{{ø‰æÊ‘—±·^/ôSèÐUIYod×çX%ã ˆ½ï;zó@Ó”åO7¼	½ùþð`ÖGà”IåmpÅo <µšz/$w\\¨†Ü+û®Þé/,ÕÍ=–»›î½ÍžÅËþ]@fšM¢Øû’LPÃ½\Z”¡ÀoÊ\0Ïßp•þ_Åx.Í>á O8mlÇô7NYJ¹aÙ“D6ØádîË-³Ø·Ø±X6uX¿·ã7A©¿—1¼“•T4kx‰‚àD±WŠ2NÊÔž¼!&¼oØ2súhgÌ…q?½S©ª Mw\\¾{‡øöÙÛ|Õž(¹O1xÚQ¥¼\rqA¨Ð®³ˆßöÆ\0š,[\\6\n´G6åÊp^êsh0,õú•¦`c2\0tn‚3Í¯1C½¸ý·dÆRG»\0k­•ñœVîØ29õBMÁ?Õ€²p¼4/³ì¾Ž_í^€#-¾\0%?B6±½î„ì ì³\ZJ’–áîº\rw1,tâœ­z1ÚisDv„ØµâÖzèHMMSDØSò]{HÔvš¨G‚?qÖ!øé.› äõ5˜~y\n_S:ÐMyIýY@^ÊÕîl&\0JÐ\'ƒ¡¢x.\'¨ODXõot^}Á+£?Ðç/ˆå¼½«îè…5†oñLZL$}”Ýêñù¬\0¶Rž%}ù‹î8jäßÃºdÚ®tÁ•å_­Àm‡Ð‚¥¼²5‹õm·ÚtµÐKŠ™ÎƒÕ{G@9WHÿ÷üEø‰DYv­ÞKTÁŸsÉ´\Z™„lSg¾K>ˆ¡‘uxRÙ<ðÌ·HÁ1Þ7K Ó!<M±@u=mÈ²hÙ¢¯&¯ L¥—1C±ƒ^ýàâeŠ‚š±îgšmIÃš4ä¢G) <¥¤?6ÄPk¢í ËBg\"É‡êr]\'2B•c:£°hFu·ò//ÂP“vÖN‚„©)9jŠ,‡À€›)É,èíž¬×ÿ—ü+ Ž“)ø“2t8>\"XÎrè ®|¢¡‡/ñ3Ù¦·m_ä0m{:Å5F&ëÑ‰¢¯÷‚Ä!yÞ14ð{Úg°¤\rýå\Zš\' ]\"öï¬®¤5ªgsLÈ³´æC)8Ô}f©›½Ž±Ã‘e8ÙÀS†]¸fäe°nû®#@}º\rôGºÏ!3õdíf¨þÐ’íø¤ÆxÖ–LO]e>Áô5CCñuÒïm–üìâk÷ tJaßïvß@*ó™*jÇÙöÖ0=ˆÄ‚ GÚ1ç›sÏ]~ml*ÁÌÊ§OŽup\n}ý&hžL™ü|0¿k.öõC‡ë™ÏLŠ{{ƒc¦Ì‰øcò\\JÕ9®™ñªd~ºäÜË¿m1å˜´Ì]³ùæü¶«DM¹®ÁëÙæÎ/q.éÀwÎÍ>nÎšÕ£E=Kc­3ØÂ	_fªŸ-æè+Ã¾¤Í|×gsfþ‰ý¨Ñ[*žÏ¥ùÞµ,îGÌ|¨š£g¸(R–`Ý\r—EËZ‚÷ßR&vr¬pÊŒ„§_æ-¡Y!õÍü~¿ã)#¸’¿ío	þã]w',0),('355212c1ef9a7936c9cd476600da4def','2006-03-01 09:20:08','2006-03-01 09:20:08','0000-00-00 00:00:00','gz','xœ\0\0\0\0',0),('375f6db739f85a67759fc0532f858803','2006-02-10 13:03:31','2006-02-10 13:03:31','0000-00-00 00:00:00','gz','xœ\0\0\0\0',0),('3d45e50e80099dc22a2c1e3bbea7d986','2006-02-06 14:57:48','2006-02-06 14:57:35','2006-02-06 14:57:47','gz','xœ…RÁŽ£0ý”/ ´¤%«Ù9f$æŽÜà¶Y%€â0êôß7ÚY!m—–í÷òülB58íÇZ|ƒäòJ’§’™î¬ÛÚk‹¬Ô’óM‘‰Ý6ß—·\nÝºš<xˆ\"\"v’©ÎöÐŽ¬$¹•ì¤¬Ša¤Z*µn¦Œd<þ÷’Á[¿¤7’E\Z£|y|Éz¢;ÑÜßBÔD2Ë$û¼`R¡1º=\'U78…ÉgUMýÙÏÃF“g%H!¯a”d÷)#WM—Îù•îí\\»3ýòÉè’¼XyÓróo– m¿\"’½Ø^;œÐÛ§\Zz»µ†ÀhÃüï½×]KOþTÅÌŠ&¬éŒ6ØMpñ®­àa3‡á¨ç·wÿñ±ß¬g(þöñÃá	Ã°üÛÂ1\ZÉ£]]	B@yý…µíšÁÌ›êÀ5õih›°ú\ZšßùèN,Hä‚ û:;}o´‚èßÏÕ	!î÷5MqÒŽübÖÁM³‹ÅR60WÃB+?óqòEP;Ø#º)\ZržóL¤ñãi*²…uê|Œ)¢\0mLÃ',0),('3d5e43581d3454605cc284a5307e1092','2006-01-31 07:56:01','2006-01-31 07:51:33','2006-01-31 07:56:01','gz','xœ…RínÂ0|”\'X)´Ôý‰ö¡IÓö\0QVõÖ4U@ë»Ï)­†*\r~åbëÎ>Û‹ƒ%ßÊäGAgÑˆÊì©–ž4Šœ ŠâU:i–whh¥óÊ#3V‘‚(ŒnTÝŠÜAÌ¿Â”†„¤m‘„wBí±öC˜)Y–´jøË”u9J]µ\nMqdâ•Ó³uú¦Ú±±`EÎ‹\\AgvÀ`>š\nÒ•ÆúÀY€Øºàµú“r~¶V®œ½­EÞÄÿ«°ÔÍD$ñ¨²Ø³7{hTk¦=0Þ¨V³ë÷Æ“	Ygy³‹½ª&2¼gUQ…¦§\'7éÃæ®è¼…õá“.µÓ;slâ©‡ìzŽ‹;´yé]ç€s®uõßuDýË¨ÂÓ¥6ÛCuQâiZl¸š“†OðHxb™_£ºÞ',0),('3e02201f1d2f5567ab62d7bbd9e81dc6','2006-02-10 09:15:48','2006-02-10 09:15:00','2006-02-10 09:15:47','gz','xœ…RÁnƒ0ý”/(ÐÂ0ÇjÛiZ%zGsi¤„ 8„:þ}¥k…´î”;ïùÙasrÊuö-!†3A¼¡m«ºÚ+ƒ¢TÇi±)’<KÊ±B÷…®&/=2#ŒDcM/»A”)ß\Z\n((Í‰Z}NY8Ÿ@È;?‡™R«€6sí9êéWèò¾“ÁA’€Ø1ªPkÕµQeO®Áh_UÓûäVX+ò¢”Á™;a\\›Z5­ó³qhL3Ãk ô\"ÉG[IÇèm+ÊQAú·\n{FÓ/D2Ï¦W\'öú¡‡^véñN†ûï½²M:›‡.Z©2¼¥W©•F;Ñ³‡ôyƒwtÞÌöô¡.µóæØ§ËŠû9îÐ9äå#çh æöKbÎü\0gÿÐš',0),('35f08d3791593040b9ac5f9b40aa82f0','2006-03-24 15:09:25','2006-03-24 15:09:25','2006-03-24 16:21:02','gz','xœÕRËnƒ0ü•È_óJ1Ç~@än9°J\\Nmµü{wyäÑäUQ«r±{fggí h­öL>•àâÃ	¾¬2[ÝH¯k`™œGañUg}ö\0V:¯<cI”•`…©÷ªéXæDˆ…£IMR—\"XBë“`jŸ`¤¤é’vñT|B½;\n÷EžœÁÖ;XäPUºÙ.rÓÚë<î§Â•vžeCsZ$¸	æ.IKº±þÒv4MvžÛF¸ïHsóPŸºáÃŠ\\Ux}\0Y›²­*ÇSÎŸAÄ×¤ïöð-©ðìHšÍ+Ë^($çËçJa\'×{B\Z[‚¥®0ÿ~°I°»bEÄ¢°ËR’þmÙˆ5¤€fó¥©¾‚1\n…5F)o­¶p\0\nÌ=%¤eq$Sƒõ8K¼Ïˆz?«„ThÚúˆPÎ<ž£)Y˜ª­›+ãámãéˆL?À5¿Ïõ©òÃòº³òX\ZJíÿÓ„“ÙòÏGüÛAÿÝã¢ïM ¹å',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_3` ENABLE KEYS */;

--
-- Dumping data for table `session_4`
--


/*!40000 ALTER TABLE `session_4` DISABLE KEYS */;
LOCK TABLES `session_4` WRITE;
INSERT INTO `session_4` VALUES ('471afb00b64c93b0fe06f71528247301','2006-03-24 15:11:10','2006-03-24 15:11:10','2006-03-24 15:15:16','gz','xœUOÑnƒ0üä/hRÅ<îÒ÷(caŠÍ$ÔòïKh¶jO¹ÜÙç;¦a^6Û<*¼3ªÂ´Œ>Xñ3AïQ©ú¬kui›~7¿)Z\'”6Ú¼Ñ\"ËüåÂ=ã9ýÎ(;ÁúÏƒAhò{Ap#)tZéºSFoåva…ÿŒžóÁåHŒZ#\\oTš&ÆÊ,k¨º\ZsÌë×áÉ³@tóØ$ Kf/Ë·%ÊÿØõS*qÞ×Ÿè}gìxc¡ùÕF%å	e»',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_4` ENABLE KEYS */;

--
-- Dumping data for table `session_5`
--


/*!40000 ALTER TABLE `session_5` DISABLE KEYS */;
LOCK TABLES `session_5` WRITE;
INSERT INTO `session_5` VALUES ('51946aa333cfb6b8f8f875311ffe84cf','2006-02-08 07:23:56','2006-02-08 07:21:07','2006-02-08 07:23:56','gz','xœ…RÛnƒ0ý”/(—B1U·§i•è;Ê2\"%Åé$ÔñïK(Õ6¤uO±lŸãsìŠ‹•nlòO1\\	â\r0eZÙ7Njd•„8NË,NvE^M5Ú´\r9îÐ#Š€(€	£Þ¬\"È€…!TK¥‘ïsXÞ0Þbï–t\n¬,7!Ú.Ã—¬#ºÝú{4$	°S‡QJÉ¾js±£S]ÏýÉ÷`%É±ŠCWoÅÉÝeàj¨3Ö­tg·Úé‰“‹öœºèeÏªIBú7‹×ŒzX‘äÀzgtöPÃÀG³Öàã#µ÷ÿ:8izšy¶U´\\­hü™ž¹’\nÍÏÂ… Ü_fy“·ÙÅ?{Òµ‡òçÏh-úãO¯ÑHõ¯_2}NþÐó',0),('5a37ca26433c31bbe90a3a2dafef8953','2006-03-22 13:26:13','2006-03-22 13:26:13','0000-00-00 00:00:00','none','',0),('5c14861458cae2cd0e5c36513eb852f7','2006-01-27 13:45:29','2006-01-27 10:51:03','2006-01-27 13:45:28','gz','xœ…SÁnÛ0ýC_Å‰ËÇlk/Ã\n¸wƒU˜D˜d¢ÀÈüï“de\r,õE4É÷øHJ„r°ÊMñ7|%˜îÎªmœ2È*%8Ï÷y¹ßÛjªÑ^Ñ6äÀ¡G”±Lv¦‡vd‰`\'ÙçÁT)Ò¨cô¶ç^08cë’;¬,WÁÚ¦âÉëˆîDs~A‰õZ°÷f5j­ÚsVwƒ•˜½×uÌ_ÖŠ«@âæ[ñÆúÞeàjèÒY÷O·‘É¼×	L?€\\v\0ºd?¬š”ÈÿÏâ5£é$…`ßM¯,Fôæ©†Æn©ÁÛo0\Zßÿ¯Þ©®¥È³}ªâzAã×ôZiì\"¼x\n—’p¿™Ãð¡æÚ»/æ8ïÿ±‡òqŽoOh-úåOù[Äh$‡æó–ðxz! ºbcºã #“O–`Í	‘ÒÊ	`ð:oóÈ ïµ’¶ä½‹>)K.5÷\r®sV™‚\Zæ˜ŸÛ«’¿\rÄKÉyªßæmtùý«|Ãù*|¼àÛ}ÆÌÇâÓté´:ÂHþõ¬ÄmúˆA',0),('5ec6c42c91948953b415a20b4778878d','2006-02-20 12:06:23','2006-02-20 12:06:08','2006-02-20 12:06:22','gz','xœÍZÛr£8ý•_`0`?f/O»3U™w•,äX	 V™ñdüïÛÂv¸¦ N^‚…Z§»Õ7µ”‚©#\náÈ^eä.\"\'á,CŠ¥ÔÙ²Èuý…k³==PñB’\n+ª)É*rOsœ­ŒüÈÙ“”èG½–}ƒXlF\"ÇÕÿ×‘ƒi¦ìð2r6›…~\n,ºURV•ó3¬™’‘çEÎ·½{ IÂ²Ç»^Bï¾=<˜ùÞ8aR9[…Ñ+È^%¦^ÉªÆ·_¾«VúKuwåáîŸ{g{bÑ²{à™¦ym‘0rþLs&¨¡ö{yÈñ‘×y€ç¯ø˜‚ü_rÅx&Í:A/8©-Ûô7NXB¹!{É	‘5rØ™ûbÇJìÕ;zÌ—u6×zü*èž\nAaóO\'Á;y”Š¦5+qL{¡(åq‘˜•`2Á\"F/Œ~·¿%S©cNëö·¼z‡øîÉÙ~Ñ¦\'U|Ÿ`0­‹9˜â\"¦BÛÊ\"z=‰õ°lP•f¯MÂ0Ë3fˆRœZñ5‚¥žïëlt€Ö.pªé5f ç\0• ÿÌ¨æ¢ ­Ä¦RžÑ£Ý§´t•j¢Á?ŒƒÀÈÂÒÒ¬HÏ°§Êaµ=\05û„|Ù8ó¦\Z°½ Kkˆ†âÖíŠ»™\0%4±ÆÖ	½m¥¹ ÛØ…Á]i¡e63IPÌ™’oÑµ…„M£	;8øcíƒo²‘—o:øå[øj¤Ýä‚èQ¦³©\0F¼.Ü3GŠ§2‚\nð\r~§ó†çåPèŒv8{F,Ûó†÷úíÞs\rÞá‰¤áHz+KôRŽç`c(Sã,áË]´ûQ-þž·ÐÓf¦ó>YüÕ¬Z€š+áÇ’¡z²^µË «ªÊXt\0žÑþFtÏ¿6”q…ôÏŸTð¦ƒù¬,XJQ È~Ç>æ˜QöQÚøß!/Äx×L,ó$ÁU¹¢ÆË¢¡‹®|³±Œ0•ÜF\rùê,ô‹ç1jð*Â*W×SnMz¨3äû×\\@èïs·ßVD_ÚÕzE®½Lò¾œSG×N\ZÇ‚Ê!Y?è„‡1#º]¹·g¡¯\0™‡…å€ÀA}&Xö€7GþIÐ›õF§ý/-øO–CŽŠÇàŠÐÁpð`:Ë ûQXZP¢PLs§Ì™tÓY’úg>LIšŒ1ÁzL ð®³iÏµS#êå7¥é­9êª½ ¸Jžµ4\rÜŽò¬\'†Äx{‰Î¬|†í†¡[ÄÇu©OR>VÍ¸=r–Ò¼/MºvÅ,±ÍÖ!z¸ôdxò‚ \r×¬AžQ\nóv‘kÓ¥QxÑ‡ë@Ò\rxÊâx¢ºµY0vÃŸËÖux˜´lÕ]W™P}EP|Ó„t; ƒ2J~tR®Aé˜ä?¼&ì=¡ÀP‘MTu´ìmgÿ j¿ïiKc<˜®Ï=C‰Óc*‰`æåÃ;ÇÚ9…¾o4#c:?ïôïšGê®šñ|ó‘AQ×=ËÊhŠ,Ö\r?Rµ¶k&¼*™Þ„nÙ÷rW5%&Ç\rõ…ŸY}Ó7~›Y¢\Zù\\×ÙúÎ#/qniÀkk¿¹&Ög†ÍìÁ¬ÎRXë¶°Ì¹9Sêçsð•‡!ß’fºë³)#ÿÈzÔÈ-\'}mÔ¹$?Ù’Å~EÀ.UÝ?Áyž0‚u5pù%—×g)ã;ÿb…V}†puÊ0oÿ i.Ë›ysY§¿”Ó.h4pw\r1þÜÅjXB3óüéC ù>ð„Åø(•#üP¶¨',0),('5f91ddb16b2cfb6cafbf524e3d8c6f26','2006-01-30 13:47:41','2006-01-30 12:07:48','2006-01-30 13:47:41','gz','xœ…Rínƒ0|•*O0J¡­ùYíC“¦UÚD¸%!(I;¡ŽwŸCa«ÖþÊÅÑïì8ÊVùV¦ßœDw *³WµôJ“ÈDQ¼J“ù\"‰³îì‘¬t=1cK¹Ñ\rÖ­ÈÄ|Ë]@Aixªè+ Òp®@àžj?”™²^/J†Þþ0e]ŽRgFÁWæ žùyö„_ŸªvcmlX)çE†Â‰0˜á‚‚t¥±>p v¹Îx©þ€ÎÏ6èÊÙËFd‚øN@º™ˆ¤ îu£,õìÅU\r¶fêñ[Í©_¯LÈÈ:ÉU{¬&2¼G¬TE¦§§WéÃæ.è¼…Íá]{/oÌ±‰§Ö—sÜZÚ‘µÄKï:üæZçIÿýŽ¨?Ùæ^IjSªßi`QH§<õß/8úÑŒÜä',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_5` ENABLE KEYS */;

--
-- Dumping data for table `session_6`
--


/*!40000 ALTER TABLE `session_6` DISABLE KEYS */;
LOCK TABLES `session_6` WRITE;
INSERT INTO `session_6` VALUES ('66447badf93d95fdf55304d889f788e8','2006-02-10 12:44:24','2006-02-10 12:44:03','2006-02-10 12:44:24','gz','xœ…RÁnƒ0ý”/(ÐÂ0ÇjÛiZ%zGsi¤„ 8„:þ}¥k…´î”;ïùÙasrÊuö-!†3A¼¡m«ºÚ+ƒ¢TÇi‘­6I^”c…î]M^zdF9ˆÆš^vƒ(	R¾5PPšµúœ\" ²p>-v~3¥(VmæÚsÔÓ¯Ðå}\'ƒ%‚$±?bT¡Öªk£Êž\\ƒÑ¾ª¦÷É­°VäE)!ƒ3wÂ ¹6´j:Zçg\râÐ˜f†×:AéE’¶’ŽÑÛV”£‚ôoöŒ¦_ˆd žM¯NìõC½ìÒã÷ÿÞ{e;št6]´R/dxK¯R+v¢géóïè¼™íéC]jçÿÌ±O—=÷sÜ9< sÈËGÎÑ@Íí—ÄœùcÐ˜',0),('67efececb8d1d34aed7fd048f26f0874','2006-02-02 15:01:00','2006-02-02 14:56:29','2006-02-02 15:01:00','gz','xœ…RÁnÂ0ý”/Xè(Ä=¢m§iHãeÅ@¤¦‰’€T±þûœ’TiìÇÎ{öËsÀúäuìdù­€Ã%\0ÖØƒneÔY¥ób%æ|)xÕ¢?£—!ªˆ„	±V[ãTÛ±*@A·:¤(1å‚Ô»!¬Lç\n˜:`sš Bˆ-rïîÑY#†8f®¸V¥Ñõg[*Î¶ã‹ù­e£SNA	Ò@Á|”—Ð2‰9ažíkSçpdNL¯Š¨×*gïkVõ\ZŠ¿YH\Z7!)½§=èç‡38ÕÙéoTgHñ‡‹Ú¶aàY<œâ š	\rùó¦\ZÝ àåCxöîNž¬O_úÚ{ùÏ?ºbªAÜÿãÆã½G²½ïûB\"šÛ~ðá¤ATõ¥±»SóëÈu‚:Žþçw‰ o0éMõÌI(^¤v?&æë#',0),('6a1ed0ac3dfe785b7f1798c9ea5e77c8','2006-01-26 15:40:35','2006-01-26 14:01:58','2006-01-26 15:40:35','gz','xœí[Ír£8~•O`À¿ä˜šÝÓÖLUæNÉ ÇJ\0±xÂÌøÝ·[€Â@ðÖî\\¢’¾îV«ÿ$Kê¥‚%™»üMÓù%sæb‘›°\Z÷Ì1M{m›Û\\Þ©8PáÊ„$g¨)+Çðx“(3î¥3wŒÛØÄµŠ/.óUc¬ðïÚ1È’¢ÛvŒÍf†­E^ô&R–åã#‚DIÇ²ãûžÞ=Ò `ÑÓÝ#O…Gï¾?>ªñÖ8`21î‰³t~/Ð°J6q-Wî¹HNt‡^Ñ,qp¥?ˆLîˆÜßýõ`Ü™cëWšiWY:Æ—0f‚ªÙóV\Zb’ñ*\rÐþF²øÿ\Z\'ŒGR­³h¥â‰•e`›þ$(WÓ—­Ó=OV¦ÃÎ<¤[–c¯®È1ßÿ·<lÞÊñ› ;*…Í?¥ßd&\ZžµÄTâ%ì@Ýûip’ñ<ž‚6x‚‚\"b/.Áê&YL«Zh¿ùæòí³qÿP&þC@@ÁÎœÄ d.>¨13ç×QñÝ²6ËTÓPKr¶L)£’(EñW&Ø8~Ž=DI\n\0í !ÎGÌŽY‚þ2% ³8`jÉòˆfÅn…ù)byUÇzfÅ\\\Z¥á	öX[ÔªÀK$_\0“×Õ‘Þ4B¶µÐIÍõ¼šàÖÍ‚›ŒÚ+”M=ë]psF.:\Z`7\n:·¹†väY™Ï p}öÄy‰Ž\Z²¬+ÍRCX|$¾ÆnFÉPDÀpë’è™ëH°N$ÀHöÃÑ`Öh°Z÷ß;¬áÐí\Zº­AÅèÇºÞn›-pÕÔmš¬2²{°\\®Ž»ÇÙÁMÏ\'–Ú_UâYÝî¡+§2ß8ÂåÈø…Îðn\ntÕ(–«\rûä¯wLL€}…Ð}D\"ûºíÐîüâ\rWíhw\nê2°[vÿ™o!ºNÒ¼ºß2gmÛ¯Ðáƒø\\ß…\'˜E’\n0\ZWl×à6‚C´\0ªÊÑüuW£™k~A€rZAÖÇv«! ´>\"ÜÒø¿?ö³Ì9>\0{‰ëÓ˜C”<ˆ\\êçQçÉÌ<_\nxYež+ŒÉéOÄ	 ,(RÄ.êxžRÁ]Ôp5¤ZÃ{qC·…¤ô¨û|Ûƒ}húEòsÕê˜”Ÿ,†óè÷±³ÚEÐ]KúœƒAì/ªÜ.^Üé8°%ÑË4\n.@ñ?‰ÿ]¬—ñã•ª»V5\ZÖzé+þ¯(ß(ÎÝ€\'¾/¨”n[þP¥\0dWLë@‚>³.·`¢<²*óÓ±.å°%ÃåQ›š\ZêJÐ•F­÷#\Z¨Dà#D$ò²êõÔñM>4d\ZSq`’÷	¾‡ÙôœJû¨}wäk‘Æ5“û‘Kgr±ú)£ å„vÍnÝcX,èÎÍ3ö=†™}Ý°É~1}ŒÍ°ÄÌËÅ”ñ£¯Ãœÿ!ß¹ƒL&‡¸SQ±)×ÚáØ)hÀTly®¦ñÚEúÃUŽ‰E;^«¬Í›kÓ†”ã„=òûá}ygðÊmÆi‹«‰ú½‘uc·ÈÀª</Ï	ª^}­4åÝ>UŽÚ±º‰*ÇG²ž¶*Ç{SNkþVGÝˆ\'.þ÷\'¼ŽôÈA\'Mÿë)ØôÉÏÄaøHqPŸ<è&â çe@GK]‘ôh«=ÊÀ#É¢(§º{€r3ø7–ž×¤ÒvÑ}¦)Ç¢¨ûµ÷%E¾?5EºØìšäQÃS>O‘$ßD\nôÿUøDWáãÞ;Y‹EnÃ½ÓZÝ;ÝˆÅ»~%4JH¸Ìe0½=;‘rÆ~ÈuU—\níûƒÒVh‡Ï†Šg}DøîŽ6<3]4{¬yù¶öó¦ÀGã‚F½^Q\\¹7©ÇŠÚd\Z¾ùTz‚©·ÐŸþôó]ï.m`Üc—J“F>>Ÿ%þs*“Æræáñ„ïgoDˆx7p!Ä€¿&¾e³ø&¾?¼ŽVúíy›<¡&OXxÏ£7¥¿1NïVg†ÕèÎ¤Žë¡›Ä§±Ê)±cv»òÒöHYÌ’l™p¯­p4Öìó®o{íã`/À‹¥øeSQ³r1êaxÌ#èš«¿æ©ä[*ÞòÉ!u™	ª#½gÞKH\"„Üó€ù$“¿ó_åü1¤@k',0),('6d4797a84c486d759bbfe726895ef8bd','2006-01-30 13:17:33','2006-01-30 12:59:07','2006-01-30 13:17:33','gz','xœ…RÍnƒ0~”\'(PhkŽÕ¶Ó´JôŽ2êÒH„ 8tBï>‡ÒµBZwŠcçû±Â²³ÊõEú-!„A¸\0Q›J5…S\ZE¦ ãuš$I¼Ì†ímAN:dÄÚ#V J£[Ùô\"#ˆùV’<ÓT(ÔaÌ€Hý¹!+lÜ”fÈf³ðQ2iOYG¿D×÷ô–¢Äþ„AŽu­š*ÈMgKöy>¾îÂµ\"\'2	)\\¸¢[“ž« “±Îc– Ž¥.§ð¦ã™^%¹`+é¼oE6(ˆÿfaÏ¨ÛI\nâE·Êâˆ^>õÐÊÞÌ=p¼“½æþ?Z§LC#OòÔE%ë\roéMÖªF3ÂÓ§ðiƒpÞÌ¶ûTWíÕ?slãy›Ç9î,ÑZäå×¨\'‡úþKÂñd#²têŒ…6‡®¾2ñ4-¶¬F…á¯xVøÅ4?g±àt',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_6` ENABLE KEYS */;

--
-- Dumping data for table `session_7`
--


/*!40000 ALTER TABLE `session_7` DISABLE KEYS */;
LOCK TABLES `session_7` WRITE;
INSERT INTO `session_7` VALUES ('710f17a809d23a0a81a4ce1572e8601b','2006-02-14 11:09:24','2006-02-14 11:09:24','0000-00-00 00:00:00','gz','xœ\0\0\0\0',0),('73095344ddafe7ff48eb420edb0a9632','2006-03-20 14:52:31','2006-03-20 12:47:27','2006-03-20 14:52:31','gz','xœÕU[o›0þ+‘AH€$æ1Úö4µRún¹pšxÂ˜Ù&\ZÊøï;Ç8¥mVMU«=aŽý]Îã ì¬ò½ÈKžðƒãÉ”³ÚlU#¼ÒÀ\nÅ“$-—Ë$K‹avV8/=\"@œ•F·²éYáøßJG+¢ŠBU!ÂYNÏ%gra„¬VSZeQ<F½;çIžŸÍ8{ØÁdu­šídc:[Âäa³	çggáZ9Ï\nÉs~ÀTp1;fI\\ÂíŒõ„I9{*u—Gbú*Ÿ¬¥ÛM¾¯Y1(>=ƒn¯HrÎ¾èVYèô¦‡VöæÚ®ïe¯1ÿ»Ö+Ó¸À“Ýt±•õ\rvé›¬U\r&Àó›ðØÁ8vfÝ=ªQ{ñ—:¶óëV—u¼·ðÖ6Ç=×;ú<%Ix¢Yzµ¡MÕÕ§jXØCÓ°ÐFU¢P„ï[¸\ZÂùÅ–0?XqGóç|µ®%Î×9‘gL[¥™òÃÒ¦°{†J	Es\\U‚ø_¦M)\"ƒE~0»Êh‰ØX‰\Z#•…Ÿ\nU9×\0	Ž9åÄeqÚc‚züLðdvŒÈ_JÅ*éS„JdÑ1š¥©;Ý<3>ÙøjŒÄ¿ƒëäm®ÏÊïV¯7*ÒP)ÿ?u8?Zþ÷t¡?o¸†ØäxÍÐýÿƒ‹xmŒWþ’ÿÎÈ3ì',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_7` ENABLE KEYS */;

--
-- Dumping data for table `session_8`
--


/*!40000 ALTER TABLE `session_8` DISABLE KEYS */;
LOCK TABLES `session_8` WRITE;
INSERT INTO `session_8` VALUES ('82776824aa20a89dcce1902b228bcb2d','2006-03-17 08:42:14','2006-03-17 08:42:14','0000-00-00 00:00:00','none','',0),('864799817e5e20187f02004671c67da7','2006-03-01 09:21:03','2006-03-01 09:21:03','0000-00-00 00:00:00','gz','xœ\0\0\0\0',0),('8755425458181c0f23ae0ad96aa06624','2006-03-17 08:43:34','2006-03-17 08:42:59','2006-03-17 08:43:34','gz','xœÕS[o›0þ+È¿ \\Bóm}ªZ‰¾[.œ&žlLmS\ruü÷(í²jª65RÄáØßå\\°PuF¸žå?8é³¥ñ†©¢aN( … qœ%9þ/ób(Á<aÖq±ñJ*­ZÞô¤°4Å·ÊúÈSMLÔ!CIîŸ—”ð4nJ#d·Ûøh;‰OYg_ˆÆû\r÷ž,MJîŽ• ¥hQ©;SAtW–á~²Ka)8Íé3–‚A2Wé¹˜=jã<&£ä¡RÕÎ:žé+·.Ús{Œ®÷¤MÏ‚žAµ+’œ’/ª:;ë¡å½^{Àø–÷\në¿iÐ\r<Û³.\\®hpJW\\\n	:Àó³ði‚\'pœÌ¾»£öÅúØ¦ë\Zv§}¼5ð\0Æ\0,Å3Û[jÙ’8<Ñ¯œx¦tÝI˜W§®™.¼z,†Ìõ-¬¶/=9búþ)n<Úºz/9.ÖRA‹ËÅ´©ÁøMÁB½>m_¡2úÅÅ[´™ÏðÐ~°¬Z+Ž_ÖØŽ\Z#•ÇN„v,Å#Á\\Sî¹®ùT \Z¿¼¹3üû‰B¡éÔKÆ÷8ÞNŽÑ«´ìTóÊxú¶ñÝ˜™Zü®ã÷¹^”?¬_ïT¥¡î3M8Ÿ-ÿýˆÿu£ÿßrùßOE#:',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_8` ENABLE KEYS */;

--
-- Dumping data for table `session_9`
--


/*!40000 ALTER TABLE `session_9` DISABLE KEYS */;
LOCK TABLES `session_9` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_9` ENABLE KEYS */;

--
-- Dumping data for table `session_a`
--


/*!40000 ALTER TABLE `session_a` DISABLE KEYS */;
LOCK TABLES `session_a` WRITE;
INSERT INTO `session_a` VALUES ('abb567a612a3b129f6bc2a8daac945bd','2006-02-15 12:23:43','2006-02-15 12:16:14','2006-02-15 12:23:43','gz','xœ…RÛnƒ0ý”/àRèj«mOÓ*Ñw”1—FJŠÓI¨ãßçPºVHëžrbçÛ!lNNù¡.¾%$p&HbÚ¶ª«½2(JI²Šã¬ˆó¼+t_èjòÒ#3Ö±ÑXÓËn%AÆ·†\nJs¢VŸSDÎ\'²ÅÎÏa¦l6q@ù\\{Žzúº¼ïd°D¦ öGŒ*ÔZumTÙ“k0ÚWÕô>½ÖŠ¼(%pæN¤×&ƒVMGë|à¬@\ZÓÌðZ\'(½HòÑVÒ1zÛŠrTý­ÂžÑô‘Ä³é•Ã‰½zè¡—ƒ]z`¼“ƒáþß{¯lG“NþÐE+õB†·ô*µÒh\'zñ>oðŽÎ›Ùž>Ô¥öúŸ9öÙ²‡ÍýwèòòÇ‘€s4Gsû%	g~\07Ð†',0),('ad2aa3029d5fe076f69582bfe5365ce4','2006-03-22 13:26:47','2006-03-22 13:26:32','2006-03-22 13:26:46','gz','xœåZ_s£8ÿ*>A ÿÉcgïžnvgºïœÆ-`Î†ìf÷òÝO24€	´PrsûRÖ±ü“dI–d+æ¥’\'G²ø‡º¶û[¹öÄµñÌ#’ðYîÚöl:YLí…³9=1y`’¨„&)&H²t-O„1ŽÖF¹3×Úyñ?q­üÂ}=âZKü»r-úÌ¢$žºÖz=Á¯yŽž&Jeó#ŠL)×q\\ëûž=<± àÑóÃ“H¥Ç¾?=éùÎ8à*±6Ô]¸¿Aøp\n1q-¢öB&g¾C/ÿ,pp¥?¨J©Ú?üõhmNÜšWžY—Y¸Ö—0æ’iêY#1=Š2ðýCÿkœp)½Î¼‘‹g\Z”–mú“<`B“/\ZÉ=O•ÈagÓ-Ï°—7ô˜íÿ[ÖoõøM²“’ÁæŸNÊ…ßÔQ%,¼X‰­ÿ#ÔKø‘PøipÖõ<‘‚5(F¥·ÇQ\\‚\'Œ$Ç˜•­púæ7\"¶/Öæ+\Z JüÇ€‚]$‰ÁÈˆ>“h1`Ü\'-7«\n•­ÉÐv¶\\#	i”¢úKSœ?Ãª5€¹uÐésŽs€J²¿S®tQâS¡ˆØ1ß­0s˜b\"ŽÐŸÚM`d’Ó²(\rÏ°§ÂmÑª:\0/}	BÞBÖ.½®…lgnÒ\Zñ¼ŠâVõŠM€ÚËÍ=iKsAÎj`×\Z·™…¶”Y‡Ï  >æ‰ºFGYTfaàÀ¶5ìgLŽà|=1Ók`dfbÁ9³\0>ìûãÁ®ðà4î¿w88ý¡O+èSú{\"F7ÑÍqãt=É ¨†ºu½Ë\"iÀàl‚HŸe÷ŸZã1mœ=ÃÀMGjÏAUSSÜ\'~[H¯’ é«Ëž˜ÀÑ4:ü ?7x¡ßòH1	VsÃx\0Ÿ\"8©èT±—#cÕÖk2ËÏÐQ+8vq^=ŒÖA@%)¼ÿý‡¿cŸÙñØKˆÏbiR/z©ú£)”ÙYÂˆ#”Y²8ÈQnöˆ92@y×mÌñBRÂWp\r)„^Ã{%!ÌÛBUrÒ“}±í >|ú:€–óI{3\0”_<ô»Ä‚+ªf4Ãµç;ÞÑä~ùJÆ“Ž€-^Ç1x<´ü£œ¿Ë\\ô\"¸‘C·WÁ²ÂÃÊ¬}-ÿ\rãäðÇCð­ü#eñ³\\Ô÷%SŠ4¥±e=Àæd-1oˆ<™¶´¿,v]±SWÑ€ýé\Z€¡4j<p?bæD~„ìFÞ±§¶Z» ¾mÂ‡•ÆL¸]’ï~´žÉÀXÏk|+Ó¸r?’b™B.¶¿TÔ´ hv®Æ=Å’íH¼#{L3»„´ÑŒ´(ÑZä8MGN™™.ÞÞ/3³b1}ØÏ~ü¿CÊ§ûœgFÓÃ™Ü±¸XkípîØšðXŒÆ…÷ñCîû=õ%ªðÆ¨¼ÎÑªiæ6aŸ¯0v\\~6:Vâ‹K7MðXd?ˆNx´•ÎÚ¬¾±6nE1LRÛ¡½Ó6Ù\Z¼t›qÞÂüj¢zoäÜÙm\n°¬ kËd•¯¾–æönë&W%žÝE“ë#EoS“ë½göÖFI$‚ÿýÅ¤¨f£Z£vF­ÀÇ¯}ï ø¹\n(\rîRßE\Züß¼j©Kz€c?ªÃ-À@ºÈ»édPäÿ†ŠÑ³ŠVš.º/<…áPµ¿ö¾æÈ÷ÇæÈ”{A\\S\"ªy‚cÒç1k€¡¯ œù¼·æ\nj¥¯ îÄúoß\r’,2ŒoÛgVîÁñûìUwèUµiÖ¾?Aùh³vˆÌxŒfí]´âþ·\r¨üY•>Ù±šg¦sÃ‰>SžäúIð§¿€|×´Þ+üYñÂøó[†ŸÎKuzJtãþ²š1×·0ï™F“F>>Ÿ¥þKª’ÚvááñˆïgïÁzò«¡+%‚úõ-xWù#nâ¨=ç‘ßÜ‰¯rû‘º]ŸVÖ³[³=H~l’3ŸÆº¦Äï³Ý•GŸ±¿C¦`l«DxM£¡^`_v}Ûi{{ÿþN–',0),('ae9fe01a0509de7abc87f62b1dea27aa','2006-02-10 09:02:40','2006-02-10 09:01:31','2006-02-10 09:02:40','gz','xœå[_s£8ÿ*>A !ècgïžnvgºïœÆ-`Î†´ì^¾ûÉÆ$)`-”Ü\\_J\ròO’%Y’]AüŒÓ4GË°g{¿…gÏ<+dO4F)ˆuO=ÛžoÜåÜvÖ÷ÇGÂ„#‘â”HŠ™$Yy–Ï¢Ç¹u/¼…gíüd.å\\ú\r¢\Zñ¬•ü½ö,üDâTÏ=k³™É\'W£ëÑTˆr¢âûK¦„ç8žõsOîIÒøéî‘eÜ\'w?Õ÷Î8¤\"µî±·ô~ƒ,ðà”bÊ¹Ø3žžøŽ|ýXâÈ™þÀ\"½{Àb÷×ƒu¤ÞÜ<ðL¢¤2ÉÒ³¾E	åDQ/ZyHpÎª<ÀóœG ÿ÷$¥,j·•‹\'V¦eú‡4$L‘/[É}_TÈae²--°WWôX¬ÿ¥›K=þàdG8\'°øÇ£ðàÈEJ¢³•Øê70‚ý”ŠX…¤<PòŠ|Ìƒ4OÔ œ¦•_\ZáüâbÛgëþ»´?‘!û:’€!ÆÂ¥Á€m•ØrXÔ¨lE&íû>ËÀ6·TÙ\"ŠpœIíWæòû…ÁJQ\0¨G’^bºò âäïŒ*ýœµ¤¥xÀTÄb’ëÅŠ\n)?”#øMy	ŒÌ4-‰³è{,½V\ZUà¥dŸƒ×•Go\Z¡ÛqMZC¾_SÜºYq“	P@ûÚØŒÐ³nÐZš3²h€Ý(XÜÚQf=Ãô‰¦â=º´eÝh–l[±@Þ”<ð¹óžY˜XpN,€¤ûáx°k<8­ëïÎpèó\ZúÜ€þ‘ˆÑOtsÜ8ÛQé¼>\'	ÔCÝ¦Ùe%iH`k‚@_$Ÿ÷ŸFã1-œí^0pÕ‘ºsP×ßÜwÁ‰ŸÙ²«4Fúzà²g&pi\n^ð¯\r^Òoi,«¹b¼#€Ï%8l¨¨Lq-cÝÕk\nË×¨¨æ}œW}F°0Ä•ÞÿñÍß±Oì\0ì§( 	ƒ4i½ÔýÑÊì\"_YUE‘+Ž²•›=Â•`\Zê¡‹9žI*¸n\r×B¨9üÁw[(JŽêã€m{ˆ\n Õí|ÖÝ\0åMÀƒ>±àU»\nÚÀaÈ‡ÒsŒo‹¿Òä^AÓI[ÀÇ/Ó¼Ü”ü“ì¿+-z™@\\É¡»«`UãamÖ¾’ÿŠñ²ùËMðRþ‰²ø…VN„@milU°‚š¬ƒ\"Ü–ÈSèa‹‡Ëb750p5\rØ_®ÊâÖ\r÷3hNDà%d8öó\nØzí\"ñm><ˆ,!ü@ë“|£õBBúx^wäk™Æµû™ËreûKÄ=@K‚vçj]cÐXÂÉ%{´—ifŸ#m´ -K´9NÛ–Se¦·ËÌ¢œLEò6Œÿ÷HùTŸóÄÁdz8q!×c*.6å\\;ùíÔšðINÆ…­Ûø\r‚úuxcTÞhô‹Qš¹mØ§Œå_.+ñå¹›Ær[$¯H¥F4Þ±ZgmÑÜX›¶¢\'©íÑÞ>›ì^9Í8-¡>š¨Ÿ97vš!X5P´e\n†ªG_+s{·s“«Ïn¢Éõ™¢·­ÉõÑŽƒ³¸´Q³É?ÎêÙhÄ¤]€I+ðékß(>\'®ÂFJƒû”Á7‘ÿ7Ï‚:FêŠ`ÄØêq\n0’.t7í\nåð3VŒ^Ô´ÒvÐ}æ)ŠÆâ¨û±÷{Ž‚`jŽL¹Ä5Áâ†+8¶!}ž²ûÊqÝ&Ü†#¨µ:‚ºë¿~:4Jz°,t0½mŸX¹Ç²WÝ£WÕ¥Yûñå³ÍÚ12ã)šµ7ÑŠûß6 ôµ>Ì´#\r×L]ÃŽ>§êFð—ß€üÐ´Á+üEyÁøë[†\\Þœç$îu•èÊùe=cnn)È¼g^\ZMòú,ž3‘6¶k\'¼?{Ö£†Þ)1d8¨©oiðÀ›8ÊŸp\'í9O|àFx­í7‘ÔÝúÌ0³úº3Û£ä÷2‚Í4óY¢jÊó¿st;ò2ö÷Èì’m‘2¿­q4Ö\rìóªo{­ã`7Àáç_~ÀU',0),('a16b5988aa36c9ad2e854f10a7300239','2006-03-24 15:19:15','2006-03-24 15:19:15','2006-03-24 15:43:26','gz','xœÕRËnƒ0ü•È_óJ1Ç~@än9°J\\Nmµü{wyäÑäUQ«r±{fggí h­öL>•àâÃ	¾¬2[ÝH¯k`™œGaa¼Êúì¬t^y Æ’(+Á\nSïUÓ±Ì‰ÿ\nG;’š¤.D°„Ö\'ÁÔ\Z?ÁHIÓ%íâ©ø„zw\Zï7Š<9‚­w°È¡ªt³]ä¦µ,Öy>ÜN…+í<Ë†æ´HpÌ]’–t;cý¥íh<šì<·pß;‘\næ:ç¡>uÃ‡¹ªðú\0²6e[\rTŽ§œ?ƒˆ¯=Hßíá[RáÙ‘4›W–½PHÎ—Ï•ÂN®÷„4¶K]aþý`“`wÅŠˆEa—¥$ýÛ²!jHÌæKS+|c\nkŒRÞZmá\"\0˜{JHËâH¦ëq–x3žõ~V	©Ð´õ¡œy<9FS²0U[7WÆÃÛÆÓ™\"~€k~ŸëSå‡åugå±4”Úÿ§	\'³åŸø·ƒþ»ÇEßB¢¹ã',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_a` ENABLE KEYS */;

--
-- Dumping data for table `session_b`
--


/*!40000 ALTER TABLE `session_b` DISABLE KEYS */;
LOCK TABLES `session_b` WRITE;
INSERT INTO `session_b` VALUES ('b029ff3715a49fc483fa89c9e3dcd516','2006-02-10 07:26:56','2006-02-10 07:26:56','0000-00-00 00:00:00','gz','xœ\0\0\0\0',0),('b980c77ff48b0802e4024e28e70cf56e','2006-01-30 13:04:46','2006-01-30 12:59:57','2006-01-30 13:04:46','gz','xœ…RÍnƒ0~”\'(P(5ÇjÛiZ%zGui$BP:¡ŽwŸCéZUZwŠcçû±Âª·Ê\reú-!„3A¸\0Ñ˜Zµ¥S\ZE® ã,M’$Kò±@{B[’“‘yÄ\nDet\'ÛAä1ß*ò‘gš¥ÚO©?3²ÆÖÍi†¬×%³öœuôKtyßJo‰ Š@ìŽØ4ª­ƒÂô¶Â`WÓûè&Ü(r\"—Â™;á º6é¹J:\Zë<f	âPéj¯:žéU’6’ŽÁûFä£‚øoöŒº{ IA¼èNYœÐË§:9˜Goå ¹ÿÎ)ÓÒÄ“<uQËæ†·ô&Õ ™àéSø¼Á;8ofÓª‹öêŸ9vñcëû9n-ÐZäå#×h ‡úöKÂéd#²rê„¥6û¾¹0ñ4-v¬F¥á¯xRøÅ4?wXàz',0),('bb8a1afbe9ec757fcde5b30fdaa13043','2006-03-23 08:47:47','2006-03-23 08:47:32','2006-03-23 08:47:47','gz','xœÕSao›0ý+È¿ Bó1Z÷ij%úÝrášx²1³M6Ôñßw¤D´Íª©ê´(ÇÙïÝ»»‡‡²u*t\"û%yÌ=Wœi»WµÊ\0Ëã4‰ügy_€;‚>È\0„XäŠ³ÒšFÖË=Oð­ôÕt T5d8Ëèù‰3¹‡:Li„l·+Š6Sñ)üÑx¿–¤Éóõš³»Dh­ê}TØÖ•ÝÅp=ÖÊ–KžñGlƒõ©Kâþ`] LÊÙCiÊ)<Õ!¦kéC´“þ}Ý±¼W<y5ƒi$gŸM£èô¢†Fvv©ã[Ùìÿ¦	ÊÖ~àÙ\\T±—zAƒ[ú\"µÒ`xv>mðŽ›Ùµ÷j¬}õ‡96É²‡íùo<€s€Ëï{ÏñÌw>€™]O\"Ë Ž Œ­Z=2á44XÍ‹^<*øAy\"QDè\ZXØ09;öþËoÈ>T;-Ñas+\rºLXW#Ë ¹û¡qJûg¨”PääªÄÿ2mJ9Œz°¿Ê\Z‰ŸØ8‰5F*ß[5ÌežœzÊˆË¡ß§Íø¡àÍÍ)#žUB(Ô­yÊÐ°ãÍ¤E‰ÒêÖÔÏ„\'/ßŽ™iÄï :~›ê¹ò»Íë•ÇÒP©ð?m8;Iþûô ÿ¹è÷\'Ú\'',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_b` ENABLE KEYS */;

--
-- Dumping data for table `session_c`
--


/*!40000 ALTER TABLE `session_c` DISABLE KEYS */;
LOCK TABLES `session_c` WRITE;
INSERT INTO `session_c` VALUES ('c5215a4f826931bd7a2cbc9269d8e50b','2006-02-14 15:05:48','2006-02-14 15:05:47','0000-00-00 00:00:00','gz','xœ…RÁnƒ0ý”/(ÐÂ0ÇjÛiZ%zGsi¤„ 8„:þ}¥k…´î”;ïùÙasrÊuö-!†3A¼¡m«ºÚ+ƒ¢TÇiQlŠ4ÏÊ±B÷…®&/=2#ŒDcM/»A”)ß\Z\n((Í‰Z}NY8Ÿ@È;?‡™R«€6sí9êéWèò¾“ÁA’€Ø1ªPkÕµQeO®Áh_UÓûäVX+ò¢”Á™;a\\›Z5­ó³qhL3Ãk ô\"ÉG[IÇèm+ÊQAú·\n{FÓ/D2Ï¦W\'öú¡‡^véñN†ûï½²M:›‡.Z©2¼¥W©•F;Ñ³‡ôyƒwtÞÌöô¡.µóæØ§ËŠû9îÐ9äå#çh æöKbÎü\0{Ð¢',0),('c851f1b58298ca82ff7664f5c3a8d9fa','2006-02-07 15:38:32','2006-02-07 07:44:58','2006-02-07 15:38:31','gz','xœÍY[w›8þ+9üsuÀéî>í¶ç¤ï:²Pb¥€XI¤uSÿ÷	a\'Ü]Û/!ƒFßÌhnŒ$%•`j¢_8q“™¸«ÄÉø#+b9u6,q]?ö½u¼\n6‡{*ž©@RaE5‡aY\'áy‰‹½³‘I8$\'úQïeß –\ZJâ¸úïmâàGZ(Kö\'ŽWú)´è–ª¤l6ª×X%ÏKœ¯;zsO³Œ7÷¼„Þ|½¿7ë½pÆ¤r68‰’Ð¼FM½’;.TKî ~×ìô\'–êæËÝÍßwÎæÀx™æek“(qþÈK&¨áFe(ñž·e€ç/xŸƒþŸKÅx!Í>á¨8kmÇôÎXF¹aFÙ	‘-v8™»jËjìõ;v,ý¶ñk;~ô\nAáð™À;¹—Šæ-/qAL{¦(çi•™`1Á\"EÏŒ~·ÿÛ5šƒœ/µÉpYfŒ`m°“›E‘ß8”û	©¬uþÁ\nìÓ€˜×>¾ýç*©qqpûZ†¢Ê·Tœqè†î­»Ò?wµ^‡–Ñ¬<*T«1S©}IÛAã¿z‡øöÉÙ|Öñ\"UzâHçdøbq‘jp²J^æ˜4Yv¸êXÕqƒ	áÄÒ–™ØA9.*í--_¯4\0 ufœkþ£±KÐ+fÎótzÀÚ¨Bå¼¨êåu|75ÿ0Q\r”•å¥`Ü#ì¡É2:f\0GZ|J¾‡l2PÜ\rØ^8d5DHÇp·ý†»˜54±Î6½šmµ9![Bll`› ™¬³ÉöY†RöÈ”|‹®=$ê:M4 Áï8ëü|—%ˆ<?{ËÁûoáJº)‚Ñ%dÏBíÎf xC2¸Gö/å\rà‚ÁàNÁË¡{Ñ…mqñ\r±âw¢7è^Xcxð/¤ÅŒ@ÒGY£×z|¼± ®çgI_P9§äßãÚdÚ­tÞ•å_­ÀºGh(2¾¯jëu¿ÚRè½ÑdFi:#{ÁkG@WHÿû“\nþ¿\"üDy~¯%ÚÃï\\2“PÝÔÕÇ\r_OïÄÐÄ:<+†ê<ðÄ·HÁ1Þ5K ÓIy–ašzÚ’eÕ±ÅPMŽ­ Le—1C¹ƒ^}çâÛ3x\rã©éÛ–´, Ic.z’ÊãXJúmCŒµ&Ú²*u&’|¬.·Ñu\"KSAå”Î(„šQÝî…ÜË‹0Ö¤G„ØJ@˜š“£æ8€?æ\0ÜÌrAïödƒþï[ðŸ¬„:žÎÁŸ•¡ÃéáÁrV@‡\0uà‰B)-9|‰ŸÉ6ƒm{p”Ã´íÙ×˜˜¬\'\'Š¡Þ‡äEÏÐÀhŸÁ2)$ýkè¬(×Ð<é±[[àJZ£Ö(nùÖ|,‡º/Ã,³â)v8±Œ\'xòÂ°×ìA¾¡Öm×–‚WÇµþH7à9KÓ…z²n34lÉv|Vc¼hK¦§®²˜aú†¡¥xÜ…t Ã:K~tñ­÷ tNa›ÞïŒvß@ªŠ…*jÏÙÖ°æÎàöÆÃåæÜK—ß:6S*‰`ææçÃ\'Ç:8…¾$´ s&?ïÌïºŸ‹CýÐñé#“¢î{üÆiª\"Õœ>URõŽk¼*YÞ….9÷r×-#f§óE×l¾å¿Ý*ÑP®kðz¶¹óÌKœK:ð­õßR³O›3Ã†fõdQÏÒXë¶²ÂW¥™‡êç\Zsò•‡aßÎÒf¹ë³%3ÿÌ~Ôè-\'c#Âsi®;ž±ïå¯ú^ÿ?!¨š',0),('c88fae1d25575d52bb46238a0cb4132b','2006-02-15 08:09:42','2006-02-15 08:09:42','0000-00-00 00:00:00','gz','xœ\0\0\0\0',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_c` ENABLE KEYS */;

--
-- Dumping data for table `session_d`
--


/*!40000 ALTER TABLE `session_d` DISABLE KEYS */;
LOCK TABLES `session_d` WRITE;
INSERT INTO `session_d` VALUES ('d4b1c808423b1a8b283c0e8893533cc1','2006-02-06 15:04:28','2006-02-06 15:03:11','2006-02-06 15:04:27','gz','xœ…SÛŽ›0ýä/À0<¦ÝíKÕ•Øw4kœÄª‘m\"¡”¯m`7BjêÆsÎœ¹Ør6áÆ¦øƒ@án¦@¤¾ˆ®qBqR	 4/³âHZM577n\ZëÐq(â\0„iÕc7’ÊÂÈ™õy0ÕòÒˆ6z€Â}‚Þ¹Å)Ë4Xû%ùâuÖ®Ds|‡A“…,ò~åIÍ¥Ý%©õ`OÞë:Æg_‰¥°ŽTÜ})ÞÈÖ*Wc¯Ú¸OÝŠ-æš\'0½ uÉ	í5ùy\"Õ$ ÿ7‹×ÌU¿!)€|W½0<¢wO5ô8ê­o¿á¨|ý¿z\'tg#Ïþ©ŠÊ\rÓ+J!¹Žðâ)œ1»ûÉœ†1ç>ü§óük(ûøfø™Ãýð§Éú-\"v´Ž«¯-¡ñöB9qãÒí g¦°nhÚæ<t­}#5FTùXÐ¾Ï½Ã¾—‚aèÜ6Áªþ,ŒuK•ßð&VÂø(q~ó‰ö[aÜNJÝ >¸‰.?è\"Íw”¦áø³?.Àù˜|š®ZŠGë¿Q\n÷é/Î¡ª',0),('d4bca7f4b8ab54032d2eea61f7c21e53','2006-03-01 08:57:37','2006-03-01 08:57:37','0000-00-00 00:00:00','gz','xœ\0\0\0\0',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_d` ENABLE KEYS */;

--
-- Dumping data for table `session_e`
--


/*!40000 ALTER TABLE `session_e` DISABLE KEYS */;
LOCK TABLES `session_e` WRITE;
INSERT INTO `session_e` VALUES ('eb2b327bd96b5b14a6f1a641cc5fa502','2006-03-17 15:19:38','2006-03-17 15:18:01','2006-03-17 15:19:38','gz','xœ…RAnƒ0ü\nò$¤,GÔöÔ&¹[lW6F^\'Jù{mB”©éÉ«]ÏìŒ=„õÉJ×óìG@‚xL™ƒl¹“\ZY!!Ž—I–®óå¢*´g´œœp‹\0Y«îDÛ³‚ †ºU š\\6cX\ZÎ`â€­›Ú’ç‹P­¦åS×Ýˆ®÷[4$	°Ý£\n•’í!ªÌÉÖíªj¼ŸÜ+IŽ2¸x+¾Hn.§£±.`–À¾j]OåmO`zä¢RÐ1ú(Y1HHÿf¹› É€½êNZÑË§\Z:Ñ›¹_oE¯½ÿMç¤iiäY=UqjFãé](©ÐŒðì)¼®i÷?Sžöòº{ýÏ;véÜCþøŽ[‹_h-úÏ?£žê{JâñôBDíä¹6ÍIÝtˆF_€Ò!w}‡³è¥#nöß¬Ø„Ô‘kJ%|ªîò;Ÿ,nlƒ6ÄÄz—„¶7ûéþ`ýI',0),('ecbd8f65cbd626d065a0cd8284e54f32','2006-03-01 14:10:58','2006-03-01 14:10:08','2006-03-01 14:10:57','gz','xœÕSËnÛ0ü_`=¬TÔÑhz*@¹Œ´±Yˆ¢JRn…TÿÞ]=bCIÜ RÔÑKÍìììÈAÙYå{‘þ’<äŽ‡Îj³WðJËÃ$Œ¶›,Êò¡\0{+œ—±!Èg¥Ñ­lz–;ã¿ÒÑ‰¨æ¡ª±ÂYJÏOœÉ=4~.#$Ë6tÚÎÍçªwODÓû$MŽGgw\n¨kÕìƒÂt¶„à®(Æ÷£SãZ9ÏrÉSþˆ£à!Z¦$.áÆzÂ$œ=”ºœKbº–Î;éÁ×ËÅã×YP3èvE’röY·ÊÂˆN.jheoÖ\Zð|+{óß´^™Æ<Û‹*ö²^Ñà–¾ÈZÕ`Fxz>oðŽ›Ùu÷jê}õÛx=Cvîã­…°pùÃà8Þ¹ÞyÐ§”„ã…ÈÒ«#mª®ž˜ÐM-vsÂ`\n~PH”áûV1ŒÏ®„¹ÿÆòJ óÕ®–˜°Ó(-¦L[¥È`¸‡qp*»g¨„P”äªÄÿ2mB9Zƒ\rF=8_e´ÄOl²Gb‰ÊÂ÷N¾œ\\@‚e¦”¸,æ}PO\n¾¹]*òçY\'„BÓé§\n™ngÅ(J”¦îtóLxü²ðlªÌ¿ƒêðmªOßÍ¯7vžZC¥üÿ´át‘ü÷+þh£ÿ]¸è÷^©\'',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_e` ENABLE KEYS */;

--
-- Dumping data for table `session_f`
--


/*!40000 ALTER TABLE `session_f` DISABLE KEYS */;
LOCK TABLES `session_f` WRITE;
INSERT INTO `session_f` VALUES ('f6324659e5d1724ac87346f199285d18','2006-01-27 12:30:55','2006-01-27 08:16:57','2006-01-27 12:30:55','gz','xœÕUo›0ý*‘?A©æÏh?4ij¥}\0Ë…kâcf›n(ã»ïlLAi›USµjåræ½{ïî0ÊÎH×óô—`	;Z–\\0Rë½l¸“\nH!Y’Ð-ÍršÓbøæ·N8ðˆ\0É)µjEÓ“Â2ŠÿJë#O¸¬B†‘Ôÿn{h\\L#$Ï3mbñßéCs˜¨FD#¼*Ì\\2òWŸÅ{ÙØ)7¬¥u¤,eG´€ÁåäÎ3p{ÐÆyÌš‘ÛR•1\\²Ö­vÂV_w¤$£/³ Pí	IÊÈÕJ½>«¡½>Õ€ñµèº¾jÔÞ#òlÎªØ‹ú„§óIÔ²àéYxœÜŽSØu7r¬ý¡-=õ/ûxmàŒú0X†g¶·Ô¼IøE!¢tò¸ÒUW?vCT·ÒAØ¿ ÔSø„ë[8Y>º8âúæŽW~ï¬«vµ°–ÌFZÜ,®MÆ/Ì;Á¶OÛ\'¨µGmg-ÏÓ®}F„Æ` ÝUZ	|±Ææ¬1RøÞÉÐ•¹H0yJ=—‘Í>\ZTãëOn¦Œø¹¨„Ph:õ˜ñ­N6Q1Šâ¥®;Õ<NŸž™Øâ7P¼Nõ\\ùÍúõÊÊci¨¤ûŸ&œN’ÿ~ÄÿºÑï·\\Cr¼füý¿Y¼6Æ+	?	õÏÿ£²1P',0),('f10d53cf199b94c5f4be3e5aeea45a7c','2006-03-24 15:15:47','2006-03-24 15:15:47','2006-03-24 15:17:46','gz','xœUOÑnƒ0üä/hRJ‹yÜ\'¤ïQÆ,\Z	Â›J¨ãß—ÐtÕžr¹³ÏwLý½¬¶ùq¨ðÁ¨ã<ø`ÅOG¥ê£®µªÛn3ï-‹Jç¼qFèçéÛ…:ÆcúõœQv*‚õ_;ƒÐä÷‚à\nRè´Ò¶‡ŒNåva…ÿŒžóÁåHŒZ#\\oT\ZG†ÊÌKì©º\Z³Ïë÷áÑ³@·wóØ$ _%³—åÛåìú)•8Ë§Oô¶1¶¼²Ðôn£’òweµ',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE `session_f` ENABLE KEYS */;

--
-- Dumping data for table `system`
--


/*!40000 ALTER TABLE `system` DISABLE KEYS */;
LOCK TABLES `system` WRITE;
INSERT INTO `system` VALUES ('2005-08-23 10:36:11','0000-00-00 00:00:00','active',1,'ccsadmin','ccsadmin');
UNLOCK TABLES;
/*!40000 ALTER TABLE `system` ENABLE KEYS */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

