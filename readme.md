<img src="https://avatars.githubusercontent.com/u/56885001?s=200&v=4" alt="logo" width="130" height="130" align="right"/>

[![](https://img.shields.io/badge/TgChat-@UnOfficialV2board讨论-blue.svg)](https://t.me/unofficialV2board)

## 本分支支持的后端
 - [修改版V2bX](https://github.com/wyx2685/V2bX)


## 原版迁移步骤

按以下步骤进行面板代码文件迁移：

    git remote set-url origin https://github.com/lucyjack886/v2board.git
    git checkout master  
    ./update.sh  


按以下步骤配置缓存驱动为redis，然后刷新设置缓存，重启队列:

    sed -i 's/^CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env
    php artisan config:clear
    php artisan config:cache
    php artisan horizon:terminate

最后进入后台重新保存主题： 主题配置-选择default主题-主题设置-确定保存

# **V2Board**

- PHP7.3+
- Composer
- MySQL5.5+
- Redis
- Laravel

## Demo
[Demo_user](https://v2bdemo.v-50.me/)
[Demo_admin](https://v2bdemo.v-50.me/admindashboard)
邮箱和密码可随意输入

## Document
[Click](https://v2board.com)

## Sponsors
Thanks to the open source project license provided by [Jetbrains](https://www.jetbrains.com/)

## Community
🔔Telegram Group: [@unofficialV2board](https://t.me/unofficialV2board)  

## How to Feedback
Follow the template in the issue to submit your question correctly, and we will have someone follow up with you.


# v2board直接安装教程 ，并启用webman提升性能

建议使用系统 ubuntu22 以上
第一步首先安装aapanel /宝塔，这一步不会的不建议继续操作了，如果实在想研究的 可自行搜索 安装教程
image

安装完成后登录宝塔，选择安装如下
☑️ Nginx 1.2.2 ☑️ MySQL 5.7 ☑️ PHP 8.1 ☑️ phpmyadmin-5.2

image

等待安装完毕 预计耗时：10-30分钟之间

如下图就是已经安装完成

image

安装Redis、fileinfo
宝塔 面板 > 软件商店 > 找到PHP 8.1 点击Setting > Install extentions > redis,fileinfo 进行安装。 预计耗时：5分钟

image

image

解除被禁止的函数
进行这一步之前，建议要等到 redis,fileinfo 安装完成之后再进行操作。否则可能在安装依赖试提示未禁用 某些函数

宝塔 面板 > 软件商店 > 找到PHP 8.1 点击Setting > Disabled functions 将 putenv 、 proc_open、 pcntl_alarm 、pcntl_signal 从列表中删除。 image

添加站点
宝塔 面板 > 网站 > 添加站点

在 域名 填入你指向服务器的域名 在 Database 选择MySQL 在 PHP Verison 选择PHP-8.1 image

登录到SSH 进行下面的操作
首先进入到网站目录下 cd /www/wwwroot/v2board
删除目录下所有文件以后执行以下命令
 git clone https://github.com/wyx2685/v2board.git ./
克隆完成后执行以下命令
  sh init.sh
image

image

配置站点目录及伪静态
返回到宝塔页面， 选择网站， 点击 网站名，选择网站目录 image

然后选择伪静态，填写以下内容
location /downloads {
}

location / {
try_files $uri $uri/ @backend;
}

location ~ (/config/|/manage/|/webhook|/payment|/order|/theme/) {
try_files $uri $uri/ /index.php$is_args$query_string;
}

location @backend {
proxy_set_header Host $http_host;
proxy_pass http://127.0.0.1:6600;
}

location ~ .*\.(js|css)?$
{
expires 1h;
error_log off;
access_log /dev/null; 
}
配置定时任务
宝塔 面板 >计划任务

任务类型--------Shell脚本 任务名称--------V2B基本任务 执行周期-------- N分钟1分钟 脚本内容-------- php /www/wwwroot/v2board/artisan schedule:run 根据上述信息添加每1分钟执行一次的定时任务。 image

守护任务及启用webman
打开宝塔-点击软件商店-应用搜索 [进程守护管理器] 执行安装

image

php artisan horizon
image

php -c cli-php.ini webman.php start
同样的操作，启动命令换成如上，出现进程ID后证明启动成功 image

如果开启webman后订阅地址显示为127.0.0.1看下方处理方法
请在nginx内设置加入以下内容

proxy_set_header Host            $http_host;
注意
启用webman后做的任何代码修改都需要重启生效


