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

 Date: 03/08/2020 18:07:31
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for eva_code
-- ----------------------------
DROP TABLE IF EXISTS `eva_code`;
CREATE TABLE `eva_code` (
  `code_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '代码唯一标识id',
  `code_name` varchar(50) CHARACTER SET utf8 NOT NULL COMMENT '代码名称',
  `user_id` int(11) NOT NULL COMMENT '关联user表中user_id',
  `code_text` text CHARACTER SET utf8 COMMENT '代码',
  `path` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT '存储路径',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更改时间',
  PRIMARY KEY (`code_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS = 1;
