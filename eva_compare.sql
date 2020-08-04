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

 Date: 03/08/2020 17:31:21
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for eva_compare
-- ----------------------------
DROP TABLE IF EXISTS `eva_compare`;
CREATE TABLE `eva_compare` (
  `compare_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '对拍唯一标识',
  `compare_name` varchar(50) NOT NULL COMMENT '对拍名称',
  `user_id` int(11) NOT NULL COMMENT '关联user表中user_id',
  `first_code_id` int(11) NOT NULL COMMENT '对拍中代码1的id',
  `second_code_id` int(11) NOT NULL COMMENT '对拍中代码2的id',
  `input_data_path` varchar(255) NOT NULL COMMENT '随机生成数据代码\r\n随机生成数据代码的路径',
  `max_input_group` int(11) DEFAULT '1' COMMENT '最大生成数据组数 ',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '修改时间',
  `remarks` text COMMENT '备注',
  PRIMARY KEY (`compare_id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
