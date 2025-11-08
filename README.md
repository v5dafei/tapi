### 程序安装说明
 1. 关闭supervisorctl守护进展    任意目录         sudo supervisorctl stop all
 2. 删表重建结构                 网站根目录       php artisan migrate:fresh
 3. 填充数据库                   网站根目录       php artisan db:seed
 4. 清空redis                    redis目录        flushall
 5. 启动supervisorctl守护进程     任意目录        sudo supervisorctl start all


### 后端脚本

# 配置文件 - env.ini 和 .env 保持一致，swoole.env 要设置
- 复制配置文件：cp -r /home/wwwroot/api/doc/yaconf/default/* /home/wwwroot/yaconf/
- 文件env.ini：和 .env 保持一致就行
- 文件swoole.ini：对应HOST根据服务器IP来，聊天室和客服默认IP加端口访问，要使用用域名访问，配置nginx指向，然后跳转：wsServerUrl

# laravels - 服务脚本
生成执行脚本：php /home/wwwroot/api/artisan laravels publish
启动服务脚本：php /home/wwwroot/api/bin/laravels start
直接清理脚本：ps -ef | grep -Ei 'laravels' | grep -v grep | awk '{print $2}' | xargs kill -9
安全清理脚本：ps -ef | grep -Ei 'laravels' | grep -v grep | awk '{print $2}' | xargs kill -15
 
# WIN客服启动脚本
启动脚本：php /home/wwwroot/api/rpc/public/services/KefuServer.php
安全停止服务：ps -ef | grep  -Ei  'KefuServer|kefu_reload_master' | grep -v grep | awk '{print $2}' | xargs kill -15
直接停止服务：ps -ef | grep  -Ei  'KefuServer|kefu_reload_master' | grep -v grep | awk '{print $2}' | xargs kill -9

# WIN聊天室启动脚本
启动脚本：php /home/wwwroot/api/rpc/public/services/ChatServer.php
安全停止服务：ps -ef | grep  -Ei  'ChatServer|chat_reload_master' | grep -v grep | awk '{print $2}' | xargs kill -15
直接停止服务：ps -ef | grep  -Ei  'ChatServer|chat_reload_master' | grep -v grep | awk '{print $2}' | xargs kill -9

# WIN多任务启动脚本
启动脚本：php /home/wwwroot/api/rpc/public/services/TaskCenter.php
直接清理脚本：ps -ef | grep -Ei 'TaskCenter|task_center_master' | grep -v grep | awk '{print $2}' | xargs kill -9
安全清理脚本：ps -ef | grep -Ei 'TaskCenter|task_center_master' | grep -v grep | awk '{print $2}' | xargs kill -15


#重置库时需备份的表inf_video