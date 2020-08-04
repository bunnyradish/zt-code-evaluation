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

 Date: 03/08/2020 18:07:23
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for eva_user
-- ----------------------------
DROP TABLE IF EXISTS `eva_user`;
CREATE TABLE `eva_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户唯一标识Id',
  `user_account` varchar(20) CHARACTER SET utf8 NOT NULL COMMENT '用户账号',
  `user_password` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT '用户密码',
  `user_nick` varchar(100) CHARACTER SET utf8 NOT NULL COMMENT '用户昵称',
  `user_status` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT 'common' COMMENT '用户权限',
  `salt` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT '盐',
  `user_portrait` varchar(255) CHARACTER SET utf8 DEFAULT NULL COMMENT '用户头像存储路径',
  PRIMARY KEY (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS = 1;
