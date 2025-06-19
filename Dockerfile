# 最新のPHP公式イメージを使用
FROM php:8.3-apache

# 作業ディレクトリを設定
WORKDIR /var/www/html

# PHPファイルをコンテナにコピー
COPY index.php .
COPY dashboard.php .

# Apacheの設定を調整（必要に応じて）
RUN a2enmod rewrite

# ポート80を公開
EXPOSE 80

# Apacheをフォアグラウンドで実行
CMD ["apache2-foreground"]