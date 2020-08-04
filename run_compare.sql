/*
 Navicat MySQL Data Transfer

 Source Server         : ll
 Source Server Type    : MySQL
 Source Server Version : 50642
 Source Host           : 47.107.83.200:3306
 Source Schema         : code_evaluation

 Target Server Type    : MySQL
 Target Server Version : 50642
 File Encoding         : 65001

 Date: 03/08/2020 17:31:04
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for run_compare
-- ----------------------------
DROP TABLE IF EXISTS `run_compare`;
CREATE TABLE `run_compare` (
  `compare_id` int(11) NOT NULL COMMENT '对拍id，为什么要和对拍分个表？因为我在多进程读数据库时会有排它锁，防止因为排它锁导致对拍表的正常读取数据',
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `compare_data` text COMMENT '对拍结果',
  `version` int(11) NOT NULL DEFAULT '0' COMMENT '版本，0为没有对拍过，1为对拍过',
  PRIMARY KEY (`compare_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
