FROM php:8.0-apache

# データベース接続用のドライバ(PDO)をインストール
RUN docker-php-ext-install pdo pdo_mysql

# サーバーのドキュメントルートにファイルをコピーする設定は
# docker-compose.ymlで行うため、ここではドライバのみインストール