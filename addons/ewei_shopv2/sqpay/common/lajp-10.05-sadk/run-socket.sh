#!/bin/sh

# -----------------------------------------------------------
#  LAJP-Java Socket Service 启动脚本 
#		
# 		(2009-10 http://code.google.com/p/lajp/)
#  
# -----------------------------------------------------------

# java服务中需要的jar文件或classpath路径，如业务程序、第三方jar文件log4j等
#export classpath=./lajp-10.05.jar:./test_service:CertificatesKit-XMWS-3.0.0.1.jar:SADK-3.2.1.2.jar:log4j-1.2.14.jar:
clear

lib=./lib
classpath=.

for file in ${lib}/*.jar; 
    do classpath=${classpath}:$file; 
done


# 自动启动类和方法，LAJP服务启动时会自动加载并执行
# export AUTORUN_CLASS=com.foo.AutoRunClass
# export AUTORUN_METHOD=AutoRunMethod

# 设置服务侦听端口
# export SERVICE_PORT=21230

# 字符集设置  GBK|UTF-8
# export CHARSET=UTF-8

# LAJP服务启动指令(前台)
java -classpath $classpath lajpsocket.PhpJava

# LAJP服务启动指令(后台)
# nohup java -classpath $classpath lajpsocket.PhpJava &

